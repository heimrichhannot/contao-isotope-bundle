<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\Controller;
use Contao\Database;
use Contao\StringUtil;
use Contao\System;
use Haste\Generator\RowClass;
use Haste\Haste;
use Haste\Http\Response\HtmlResponse;
use Isotope\Isotope;
use Isotope\Model\Product;
use Isotope\Model\ProductCache;
use Isotope\Module\ProductList;
use Isotope\RequestCache\Sort;

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
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: PRODUCT LIST PLUS ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

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
        $container = System::getContainer();
        $request = $container->get('huh.request');
        // return message if no filter is set
        if ($this->iso_emptyFilter && !$request->getGet('isorc') && !$request->getGet('keywords')) {
            $this->Template->message = $this->replaceInsertTags($this->iso_noFilter);
            $this->Template->type = 'noFilter';
            $this->Template->products = [];

            return;
        }

        global $objPage;
        $intPage = ('article' == $this->iso_category_scope ? $GLOBALS['ISO_CONFIG']['current_article']['pid'] : $objPage->id);
        $arrProducts = null;
        $arrCacheIds = null;

        $framework = $container->get('contao.framework');
        // Try to load the products from cache
        if ($this->cacheProducts && null !== ($objCache = $framework->getAdapter(ProductCache::class)->findForPageAndModule($intPage, $this->id))) {
            $arrCacheIds = $objCache->getProductIds();

            // Use the cache if keywords match. Otherwise we will use the product IDs as a "limit" for findProducts()
            if ($objCache->keywords == $request->getGet('keywords')) {
                $arrCacheIds = $this->generatePagination($arrCacheIds);

                $objProducts = $framework->getAdapter(Product::class)->findAvailableByIds($arrCacheIds, [
                    'order' => $framework->createInstance(Database::class)->findInSet(Product::getTable().'.id', $arrCacheIds),
                ]);

                $arrProducts = (null === $objProducts) ? [] : $objProducts->getModels();

                // Cache is wrong, drop everything and run findProducts()
                if (count($arrProducts) != count($arrCacheIds)) {
                    $arrCacheIds = null;
                    $arrProducts = null;
                }
            }
        }

        if (!is_array($arrProducts)) {
            // Display "loading products" message and add cache flag
            if ($this->cacheProducts) {
                $blnCacheMessage = (bool) $this->iso_productcache[$intPage][(int) $request->getGet('isorc')];

                if ($blnCacheMessage && !$request->getGet('buildCache')) {
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
                $arrProducts = $this->findProducts($arrCacheIds);

                // Decide if we should show the "caching products" message the next time
                $end = microtime(true) - $start;
                $this->cacheProducts = $end > 1 ? true : false;

                $arrCacheMessage = $this->iso_productcache;
                if ($blnCacheMessage != $this->cacheProducts) {
                    $arrCacheMessage[$intPage][(int) $request->getGet('isorc')] = $this->cacheProducts;
                    $framework->createInstance(Database::class)->prepare('UPDATE tl_module SET iso_productcache=? WHERE id=?')->execute(serialize($arrCacheMessage), $this->id);
                }

                // Do not write cache if table is locked. That's the case if another process is already writing cache
                if ($framework->getAdapter(ProductCache::class)->isWritable()) {
                    $framework->createInstance(Database::class)->lockTables([ProductCache::getTable() => 'WRITE', 'tl_iso_product' => 'READ']);

                    $arrIds = [];
                    foreach ($arrProducts as $objProduct) {
                        $arrIds[] = $objProduct->id;
                    }

                    // Delete existing cache if necessary
                    $framework->getAdapter(ProductCache::class)->deleteForPageAndModuleOrExpired($intPage, $this->id);

                    $objCache = $framework->getAdapter(ProductCache::class)->createForPageAndModule($intPage, $this->id);
                    $objCache->expires = $this->getProductCacheExpiration();
                    $objCache->setProductIds($arrIds);
                    $objCache->save();

                    $framework->createInstance(Database::class)->getInstance()->unlockTables();
                }
            } else {
                $arrProducts = $this->findProducts();
            }

            if (!empty($arrProducts)) {
                $arrProducts = $this->generatePagination($arrProducts);
            }
        }

        // No products found
        if (!is_array($arrProducts) || empty($arrProducts)) {
            $this->compileEmptyMessage();

            return;
        }

        $arrBuffer = [];
        $arrDefaultOptions = $this->getDefaultProductOptions();

        /** @var \Isotope\Model\Product\Standard $objProduct */
        foreach ($arrProducts as $objProduct) {
            $arrConfig = [
                'module' => $this,
                'template' => ($this->iso_list_layout ?: $objProduct->getRelated('type')->list_template),
                'gallery' => ($this->iso_gallery ?: $objProduct->getRelated('type')->list_gallery),
                'buttons' => StringUtil::deserialize($this->iso_buttons, true),
                'useQuantity' => $this->iso_use_quantity,
                'jumpTo' => $this->findJumpToPage($objProduct),
            ];

            if (\Environment::get('isAjaxRequest') && $request->post('AJAX_MODULE') == $this->id && $request->post('AJAX_PRODUCT') == $objProduct->getProductId()) {
                $arrCheck = $container->get('huh.isotope.manager')->validateQuantity($objProduct, $request->post('quantity_requested'), Isotope::getCart()->getItemForProduct($objProduct), true);
                if (isset($arrCheck[0])) {
                    // remove synchronous error messages in case of ajax
                    unset($_SESSION['ISO_ERROR']);
                    $objResponse = new HtmlResponse($arrCheck[1], 400);
                } else {
                    $objResponse = new HtmlResponse($objProduct->generate($arrConfig));
                }

                $objResponse->send();
            }

            $objProduct->mergeRow($arrDefaultOptions);

            // Must be done after setting options to generate the variant config into the URL
            if ($this->iso_jump_first && '' == \Haste\Input\Input::getAutoItem('product', false, true)) {
                $framework->getAdapter(Controller::class)->redirect($objProduct->generateUrl($arrConfig['jumpTo']));
            }

            $arrCSS = StringUtil::deserialize($objProduct->cssID, true);

            $arrBuffer[] = [
                'cssID' => ('' != $arrCSS[0]) ? ' id="'.$arrCSS[0].'"' : '',
                'class' => trim('product '.($objProduct->isNew() ? 'new ' : '').$arrCSS[1]),
                'html' => $objProduct->generate($arrConfig),
                'product' => $objProduct,
            ];
        }

        // HOOK: to add any product field or attribute to mod_iso_productlist template
        if (isset($GLOBALS['ISO_HOOKS']['generateProductList']) && is_array($GLOBALS['ISO_HOOKS']['generateProductList'])) {
            foreach ($GLOBALS['ISO_HOOKS']['generateProductList'] as $callback) {
                $objCallback = System::importStatic($callback[0]);
                $arrBuffer = $objCallback->$callback[1]($arrBuffer, $arrProducts, $this->Template, $this);
            }
        }

        RowClass::withKey('class')->addCount('product_')->addEvenOdd('product_')->addFirstLast('product_')->addGridRows($this->iso_cols)->addGridCols($this->iso_cols)->applyTo($arrBuffer);

        $this->Template->products = $arrBuffer;
    }

    /**
     * Find all products we need to list.
     *
     * @param   array|null
     *
     * @return array
     */
    protected function findProducts($arrCacheIds = null)
    {
        $arrColumns = [];
        $arrCategories = $this->findCategories();

        list($arrFilters, $arrSorting, $strWhere, $arrValues) = $this->getFiltersAndSorting();

        if (!is_array($arrValues)) {
            $arrValues = [];
        }

        $arrColumns[] = 'c.page_id IN ('.implode(',', $arrCategories).')';

        if (!empty($arrCacheIds) && is_array($arrCacheIds)) {
            $arrColumns[] = Product::getTable().'.id IN ('.implode(',', $arrCacheIds).')';
        }

        // Apply new/old product filter
        if ('show_new' == $this->iso_newFilter) {
            $arrColumns[] = Product::getTable().'.dateAdded>='.Isotope::getConfig()->getNewProductLimit();
        } elseif ('show_old' == $this->iso_newFilter) {
            $arrColumns[] = Product::getTable().'.dateAdded<'.Isotope::getConfig()->getNewProductLimit();
        }

        if ('' != $this->iso_list_where) {
            $arrColumns[] = Haste::getInstance()->call('replaceInsertTags', $this->iso_list_where);
        }

        if ('' != $strWhere) {
            $arrColumns[] = $strWhere;
        }

        if ($this->iso_producttype_filter) {
            $arrProductTypes = StringUtil::deserialize($this->iso_producttype_filter, true);

            if (!empty($arrProductTypes)) {
                $arrColumns[] = 'tl_iso_product.type IN ('.implode(',', $arrProductTypes).')';
            }
        }

        if ($this->iso_price_filter) {
            $arrColumns[] = '(SELECT tl_iso_product_pricetier.price FROM tl_iso_product_price LEFT JOIN tl_iso_product_pricetier ON tl_iso_product_pricetier.pid = tl_iso_product_price.id WHERE tl_iso_product.id = tl_iso_product_price.pid) '.('paid' == $this->iso_price_filter ? '> 0' : '= 0');
        }

        $objProducts = System::getContainer()->get('contao.framework')->getAdapter(Product::class)->findAvailableBy($arrColumns, $arrValues, [
            'order' => 'c.sorting',
            'filters' => $arrFilters,
            'sorting' => $arrSorting,
        ]);

        return (null === $objProducts) ? [] : $objProducts->getModels();
    }

    /**
     * Get filter & sorting configuration.
     *
     * @param bool
     *
     * @return array
     */
    protected function getFiltersAndSorting($blnNativeSQL = true)
    {
        $arrFilters = Isotope::getRequestCache()->getFiltersForModules($this->iso_filterModules);
        $arrSorting = Isotope::getRequestCache()->getSortingsForModules($this->iso_filterModules);

        if (empty($arrSorting) && '' != $this->iso_listingSortField) {
            $arrSorting[$this->iso_listingSortField] = ('DESC' == $this->iso_listingSortDirection ? Sort::descending() : Sort::ascending());
        }

        if ($blnNativeSQL) {
            list($arrFilters, $strWhere, $arrValues) = System::getContainer()->get('huh.isotope.model.requestCacheOrFilter')->buildSqlFilters($arrFilters);

            return [$arrFilters, $arrSorting, $strWhere, $arrValues];
        }

        return [$arrFilters, $arrSorting];
    }
}
