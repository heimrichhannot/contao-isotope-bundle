<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\Environment;
use Contao\StringUtil;
use Contao\System;
use Haste\Generator\RowClass;
use Haste\Haste;
use Haste\Http\Response\HtmlResponse;
use HeimrichHannot\IsotopeBundle\Model\RequestCacheOrFilter;
use Isotope\Isotope;
use Isotope\Model\Product;
use Isotope\Model\ProductCache;
use Isotope\Model\RequestCache;
use Isotope\Module\ProductList;
use Isotope\RequestCache\Sort;
use Model\Collection;

/**
 * Class ProductListPlus.
 */
class ProductListPlus extends ProductList
{
    /**
     * Template.
     *
     * @var string
     */
    protected $template = 'mod_iso_productlist';

    /**
     * Cache products. Can be disable in a child class, e.g. a "random products list".
     *
     * @var bool
     */
    protected $cacheProducts = true;
    /**
     * @var ContaoFramework|object
     */
    protected $framework;
    protected $request;
    protected $tokenManager;
    protected $token;

    public function __construct(\ModuleModel $objModule, string $strColumn = 'main')
    {
        parent::__construct($objModule, $strColumn);
        $container = System::getContainer();
        $this->framework = $container->get('contao.framework');
        $this->request = $container->get('huh.request');
        $this->tokenManager = $container->get('security.csrf.token_manager');
        $this->token = $container->getParameter('contao.csrf_token_name');
    }

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: PRODUCT LIST PLUS ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Compile product list.
     *
     * This function is specially designed so you can keep it in your child classes and only override findProducts().
     * You will automatically gain product caching (see class property), grid classes, pagination and more.
     */
    protected function compile()
    {
        // return message if no filter is set
        if ($this->iso_emptyFilter && !$this->request->hasGet('isorc') && !$this->request->hasGet('keywords')) {
            $this->Template->message = $this->replaceInsertTags($this->iso_noFilter);
            $this->Template->type = 'noFilter';
            $this->Template->products = [];

            return;
        }

        global $objPage;
        $intPage = ('article' == $this->iso_category_scope ? $GLOBALS['ISO_CONFIG']['current_article']['pid'] : $objPage->id);
        $products = null;
        $cacheIds = null;

        $cacheKey = $this->getCacheKey();
        /** @var ProductCache $cache Try to load the products from cache */
        if ($this->cacheProducts && null !== ($cache = $this->framework->getAdapter(ProductCache::class)->findByUniqid($cacheKey))) {
            $cacheIds = $cache->getProductIds();

            // Use the cache if keywords match. Otherwise we will use the product IDs as a "limit" for findProducts()
            if ($cache->keywords == $this->request->getGet('keywords')) {
                $cacheIds = $this->generatePagination($cacheIds);

                $products = $this->framework->getAdapter(Product::class)->findAvailableByIds($cacheIds, [
                    'order' => $this->framework->createInstance(Database::class)->findInSet(Product::getTable().'.id', $cacheIds),
                ]);

                $products = (null === $products) ? [] : $products->getModels();

                // Cache is wrong, drop everything and run findProducts()
                if (count($products) != count($cacheIds)) {
                    $cacheIds = null;
                    $products = null;
                }
            }
        }

        if (!is_array($products)) {
            // Display "loading products" message and add cache flag
            if ($this->cacheProducts) {
                $productCacheAdapter = $this->framework->getAdapter(ProductCache::class);
                $cacheMessage = (bool) $this->iso_productcache[$intPage][(int) $this->request->getGet('isorc')];

                if ($cacheMessage && !$this->request->hasGet('buildCache')) {
                    // Do not index or cache the page
                    $objPage->noSearch = 1;
                    $objPage->cache = 0;

                    $this->Template = new \Isotope\Template('mod_iso_productlist_caching');
                    $this->Template->message = $GLOBALS['TL_LANG']['MSC']['productcacheLoading'];

                    return;
                }

                // Start measuring how long it takes to load the products
                $start = microtime(true);

                // Load products
                $products = $this->findProducts($cacheIds);

                // Decide if we should show the "caching products" message the next time
                $end = microtime(true) - $start;
                $this->cacheProducts = $end > 1 ? true : false;

                $arrCacheMessage = $this->iso_productcache;
                if ($cacheMessage != $this->cacheProducts) {
                    $arrCacheMessage[$intPage][(int) $this->request->getGet('isorc')] = $this->cacheProducts;
                    $this->framework->createInstance(Database::class)->prepare('UPDATE tl_module SET iso_productcache=? WHERE id=?')->execute(serialize($arrCacheMessage), $this->id);
                }

                // Do not write cache if table is locked. That's the case if another process is already writing cache
                if ($productCacheAdapter->isWritable()) {
                    $this->framework->createInstance(Database::class)->lockTables([ProductCache::getTable() => 'WRITE', 'tl_iso_product' => 'READ']);

                    $arrIds = [];
                    foreach ($products as $product) {
                        $arrIds[] = $product->id;
                    }

                    // Delete existing cache if necessary
                    $productCacheAdapter->deleteByUniqidOrExpired($cacheKey);

                    /** @var ProductCache $cache */
                    $cache = $productCacheAdapter->createForUniqid($cacheKey);
                    $cache->expires = $this->getProductCacheExpiration();
                    $cache->setProductIds($arrIds);
                    $cache->save();

                    $this->framework->createInstance(Database::class)->getInstance()->unlockTables();
                }
            } else {
                $products = $this->findProducts();
            }

            if (!empty($products)) {
                $products = $this->generatePagination($products);
            }
        }

        // No products found
        if (!is_array($products) || empty($products)) {
            $this->compileEmptyMessage();

            return;
        }

        $buffer = [];
        $defaultProductOptions = $this->getDefaultProductOptions();

        // unset Isotope::defaultButtons because of performance reasons
        unset($GLOBALS['ISO_HOOKS']['buttons'][0]);

        /** @var \Isotope\Model\Product\Standard $product */
        foreach ($products as $product) {
            $arrConfig = [
                'module' => $this,
                'template' => ($this->iso_list_layout ?: $product->getRelated('type')->list_template),
                'gallery' => ($this->iso_gallery ?: $product->getRelated('type')->list_gallery),
                'buttons' => StringUtil::deserialize($this->iso_buttons, true),
                'useQuantity' => $this->iso_use_quantity,
                'jumpTo' => $this->findJumpToPage($product),
                'requestToken' => $this->tokenManager->getToken($this->token)->getValue(),
            ];

            if (Environment::get('isAjaxRequest') && $this->request->getPost('AJAX_MODULE') == $this->id && $this->request->getPost('AJAX_PRODUCT') == $product->getProductId()) {
                $arrCheck = System::getContainer()->get('huh.isotope.manager')->validateQuantity($product, $this->request->getPost('quantity_requested'), Isotope::getCart()->getItemForProduct($product), true);
                if (isset($arrCheck[0])) {
                    // remove synchronous error messages in case of ajax
                    unset($_SESSION['ISO_ERROR']);
                    $objResponse = new HtmlResponse($arrCheck[1], 400);
                } else {
                    $objResponse = new HtmlResponse($product->generate($arrConfig));
                }

                $objResponse->send();
            }

            $product->mergeRow($defaultProductOptions);

            // Must be done after setting options to generate the variant config into the URL
            if ($this->iso_jump_first && '' == \Haste\Input\Input::getAutoItem('product', false, true)) {
                $this->framework->getAdapter(Controller::class)->redirect($product->generateUrl($arrConfig['jumpTo']));
            }

            $arrCSS = StringUtil::deserialize($product->cssID, true);
            $buffer[] = [
                'cssID' => ('' != $arrCSS[0]) ? ' id="'.$arrCSS[0].'"' : '',
                'class' => trim('product '.($product->isNew() ? 'new ' : '').$arrCSS[1]),
                'html' => $product->generate($arrConfig),
                'product' => $product,
            ];
        }

        // HOOK: to add any product field or attribute to mod_iso_productlist template
        if (isset($GLOBALS['ISO_HOOKS']['generateProductList']) && is_array($GLOBALS['ISO_HOOKS']['generateProductList'])) {
            foreach ($GLOBALS['ISO_HOOKS']['generateProductList'] as $callback) {
                $objCallback = System::importStatic($callback[0]);
                $buffer = $objCallback->$callback[1]($buffer, $products, $this->Template, $this);
            }
        }

        RowClass::withKey('class')->addCount('product_')->addEvenOdd('product_')->addFirstLast('product_')->addGridRows($this->iso_cols)->addGridCols($this->iso_cols)->applyTo($buffer);

        $this->Template->products = $buffer;
    }

    /**
     * Find all products we need to list.
     *
     * @param   array|null
     *
     * @return array
     */
    protected function findProducts($cacheIds = null)
    {
        $columns = [];
        $categories = $this->findCategories();

        list($filters, $sortings, $where, $values) = $this->getFiltersAndSorting();

        if (!is_array($values)) {
            $values = [];
        }

        $columns[] = 'c.page_id IN ('.implode(',', $categories).')';

        if (!empty($cacheIds) && is_array($cacheIds)) {
            $columns[] = Product::getTable().'.id IN ('.implode(',', $cacheIds).')';
        }

        $newProductLimit = $this->framework->getAdapter(Isotope::class)->getConfig()->getNewProductLimit();
        // Apply new/old product filter
        if ('show_new' == $this->iso_newFilter) {
            $columns[] = Product::getTable().'.dateAdded>='.$newProductLimit;
        } elseif ('show_old' == $this->iso_newFilter) {
            $columns[] = Product::getTable().'.dateAdded<'.$newProductLimit;
        }

        if ('' != $this->iso_list_where) {
            $columns[] = Haste::getInstance()->call('replaceInsertTags', $this->iso_list_where);
        }

        if ('' != $where) {
            $columns[] = $where;
        }

        if ($this->iso_producttype_filter) {
            $arrProductTypes = StringUtil::deserialize($this->iso_producttype_filter, true);

            if (!empty($arrProductTypes)) {
                $columns[] = 'tl_iso_product.type IN ('.implode(',', $arrProductTypes).')';
            }
        }

        if ($this->iso_price_filter) {
            $columns[] = '(SELECT tl_iso_product_pricetier.price FROM tl_iso_product_price LEFT JOIN tl_iso_product_pricetier ON tl_iso_product_pricetier.pid = tl_iso_product_price.id WHERE tl_iso_product.id = tl_iso_product_price.pid) '.('paid' == $this->iso_price_filter ? '> 0' : '= 0');
        }

        /** @var Collection $products */
        $products = $this->framework->getAdapter(Product::class)->findAvailableBy($columns, $values, [
            'order' => 'c.sorting',
            'filters' => $filters,
            'sorting' => $sortings,
        ]);

        return (null === $products) ? [] : $products->getModels();
    }

    /**
     * Get filter & sorting configuration.
     *
     * @param bool
     *
     * @return array
     */
    protected function getFiltersAndSorting($nativeSql = true)
    {
        /** @var RequestCache $requestCache */
        $requestCache = $this->framework->getAdapter(Isotope::class)->getRequestCache();
        $arrFilters = $requestCache->getFiltersForModules($this->iso_filterModules);
        $arrSorting = $requestCache->getSortingsForModules($this->iso_filterModules);

        if (empty($arrSorting) && '' != $this->iso_listingSortField) {
            $arrSorting[$this->iso_listingSortField] = ('DESC' == $this->iso_listingSortDirection ? Sort::descending() : Sort::ascending());
        }

        if ($nativeSql) {
            $requestCacheOrFilter = new RequestCacheOrFilter();
            list($arrFilters, $strWhere, $arrValues) = $requestCacheOrFilter->buildSqlFilters($arrFilters);

            return [$arrFilters, $arrSorting, $strWhere, $arrValues];
        }

        return [$arrFilters, $arrSorting];
    }
}
