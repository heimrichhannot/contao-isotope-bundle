<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Backend;

use Contao\Controller;
use Contao\Model;
use Contao\StringUtil;
use Contao\System;
use Haste\Generator\RowClass;
use Haste\Haste;
use HeimrichHannot\HastePlus\Files;
use HeimrichHannot\Request\Request;
use Isotope\Frontend;
use Isotope\Interfaces\IsotopeAttribute;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Model\Download;
use Isotope\Model\Gallery;
use Isotope\Model\Gallery\Standard;
use Isotope\Model\Product;
use Isotope\Model\ProductCollection;
use Isotope\Model\ProductCollection\Order;
use Isotope\Model\ProductCollectionItem;
use Isotope\Model\ProductType;
use Isotope\Template;
use NotificationCenter\Model\Notification;

class IsotopePlus extends \Isotope\Isotope
{
    /**
     * Remove isotope js, as we dont want to use mootools.
     *
     * @param $strBuffer
     *
     * @return mixed
     */
    public function hookReplaceDynamicScriptTags($strBuffer)
    {
        if (!\is_array($GLOBALS['TL_JAVASCRIPT'])) {
            return $strBuffer;
        }

        $arrJs = $GLOBALS['TL_JAVASCRIPT'];

        foreach ($arrJs as $key => $strValue) {
            $arrData = StringUtil::trimsplit('|', $strValue);

            if (System::getContainer()->get('huh.utils.string')->endsWith($arrData[0], 'system/modules/isotope/assets/js/isotope.min.js')) {
                unset($GLOBALS['TL_JAVASCRIPT'][$key]);
                break;
            }
        }

        return $strBuffer;
    }

    public function generateProductHook(&$objTemplate)
    {
        System::getContainer()->get('huh.isotope.helper.download')->addDownloadsFromProductDownloadsToTemplate($objTemplate);
    }

    public function validateStockPreCheckout($objOrder)
    {
        return $this->validateStockCheckout($objOrder);
    }

    public function validateStockPostCheckout($objOrder)
    {
        return $this->validateStockCheckout($objOrder, true);
    }

    /**
     * @param bool $isPostCheckout
     *
     * @return bool
     */
    public function validateStockCheckout(Order $order, $isPostCheckout = false)
    {
        $items = $order->getItems();
        $orders = [];

        foreach ($items as $item) {
            $product = $item->getProduct();

            if ('' != $product->stock && null !== $product->stock) {
                // override the quantity!
                if (!System::getContainer()->get('huh.isotope.manager')->validateQuantity($product, $item->quantity)) {
                    return false;
                }

                if ($isPostCheckout) {
                    $orders[] = $item;
                }
            }
        }

        // save new stock
        if ($isPostCheckout) {
            foreach ($orders as $item) {
                $product = $item->getProduct();

                if ($this->getOverridableStockProperty('skipStockEdit', $product)) {
                    continue;
                }

                $intQuantity = $this->getTotalStockQuantity($item->quantity, $product);

                $product->stock -= $intQuantity;

                if ($product->stock <= 0
                    && !$this->getOverridableStockProperty('skipExemptionFromShippingWhenStockEmpty', $product)) {
                    $product->shipping_exempt = true;
                }

                $product->save();
            }
        }

        return true;
    }

    /**
     * @param $objProduct
     * @param $intQuantity
     *
     * @return int
     */
    public function validateStockCollectionAdd($objProduct, $intQuantity, ProductCollection $objProductCollection)
    {
        if (!System::getContainer()->get('huh.isotope.manager')->validateQuantity($objProduct, $intQuantity, $objProductCollection->getItemForProduct($objProduct))) {
            return 0;
        }

        unset($_SESSION['ISO_ERROR']);

        return $intQuantity;
    }

    public function validateStockCollectionUpdate($objItem, $arrSet)
    {
        $objProduct = System::getContainer()->get('contao.framework')->getAdapter(Product::class)->findPublishedByPk($objItem->product_id);

        if (!System::getContainer()->get('huh.isotope.manager')->validateQuantity($objProduct, $arrSet['quantity'])) {
            \Controller::reload();
        }

        return $arrSet;
    }

    // watch out: also in backend the current set quantity is used
    public function getTotalStockQuantity(int $quantity, Product $product, $objCartItem = null, $intSetQuantity = null, $config = null)
    {
        $intFinalSetQuantity = 1;

        if ($intSetQuantity) {
            $intFinalSetQuantity = $intSetQuantity;
        } elseif (!$this->getOverridableShopConfigProperty('skipSets', $config) && $product->setQuantity) {
            $intFinalSetQuantity = $product->setQuantity;
        }

        $quantity *= $intFinalSetQuantity;

        if (null !== $objCartItem) {
            $quantity += $objCartItem->quantity * $intFinalSetQuantity;
        }

        return $quantity;
    }

    public function setSetQuantity(Order $order)
    {
        if ($this->getOverridableShopConfigProperty('skipSets')) {
            return;
        }

        $arrItems = $order->getItems();

        foreach ($arrItems as $objItem) {
            $objProduct = $objItem->getProduct();

            if ($objProduct->setQuantity) {
                $objItem->setQuantity = $objProduct->setQuantity;
                $objItem->save();
            }
        }
    }

    /**
     * @return array
     */
    public function addDownloadSingleProductButton(array $buttons)
    {
        $buttons['downloadSingleProduct'] = [
            'label' => $GLOBALS['TL_LANG']['MSC']['buttonLabel']['downloadSingleProduct'],
            'callback' => ['\HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'downloadSingleProduct'],
        ];

        return $buttons;
    }

    /**
     * Currently only works for products containing one single download.
     *
     * @param array $arrConfig
     */
    public function downloadSingleProduct(IsotopeProduct $product)
    {
        $framework = System::getContainer()->get('contao.framework');

        if (null !== ($objDownload = $framework->getAdapter(Download::class)->findByPid($product->getProductId()))
            && $strPath = $framework->getAdapter(Files::class)->getPathFromUuid($objDownload->singleSRC)) {
            // TODO count downloads
            // start downloading the file (protected folders also supported)
            System::getContainer()->get('huh.utils.url')->addQueryString('file='.$strPath);
            $framework->getAdapter(Controller::class)->redirect(System::getContainer()->get('huh.utils.url')->addQueryString('file='.$strPath));
        }
    }

    public function sendOrderNotification(Order $order, array $tokens)
    {
        $arrItems = $order->getItems();

        // only send one one notification per product type and order
        $arrProductTypes = [];
        foreach ($arrItems as $objItem) {
            $arrProductTypes[] = $objItem->getProduct()->type;
        }

        foreach (array_unique($arrProductTypes) as $intProductType) {
            if (null !== ($objProductType = ProductType::findByPk($intProductType))) {
                if ($objProductType->sendOrderNotification
                    && null !== ($objNotification = Notification::findByPk($objProductType->orderNotification))) {
                    if ($objProductType->removeOtherProducts) {
                        $objNotification->send($this->getCleanTokens($intProductType, $order, $objNotification, $tokens), $GLOBALS['TL_LANGUAGE']);
                    } else {
                        $objNotification->send($tokens, $GLOBALS['TL_LANGUAGE']);
                    }
                }
            }
        }
    }

    // copy of code in Order->getNotificationTokens
    public function getCleanTokens(int $productType, Order $order, Notification $notification, array $tokens = [])
    {
        $objTemplate = new Template($notification->iso_collectionTpl);
        $objTemplate->isNotification = true;

        // FIX - call to custom function since addToTemplate isn't static
        $this->addToTemplate($productType, $order, $objTemplate, [
            'gallery' => $notification->iso_gallery,
            'sorting' => $order->getItemsSortingCallable($notification->iso_orderCollectionBy),
        ]);

        $tokens['cart_html'] = Haste::getInstance()->call('replaceInsertTags', [$objTemplate->parse(), false]);
        $objTemplate->textOnly = true;
        $tokens['cart_text'] = strip_tags(Haste::getInstance()->call('replaceInsertTags', [$objTemplate->parse(), true]));

        return $tokens;
    }

    // copy of code in ProductCollection->addToTemplate
    public function addToTemplate(int $productType, Order $order, \Template $template, array $config = [])
    {
        $arrGalleries = [];
        // FIX - call to custom function since addItemsToTemplate isn't static
        $arrItems = $this->addItemsToTemplate($productType, $order, $template, $config['sorting']);

        $template->id = $order->id;
        $template->collection = $order;
        $template->config = ($order->getRelated('config_id') || Isotope::getConfig());
        $template->surcharges = Frontend::formatSurcharges($order->getSurcharges());
        $template->subtotal = Isotope::formatPriceWithCurrency($order->getSubtotal());
        $template->total = Isotope::formatPriceWithCurrency($order->getTotal());
        $template->tax_free_subtotal = Isotope::formatPriceWithCurrency($order->getTaxFreeSubtotal());
        $template->tax_free_total = Isotope::formatPriceWithCurrency($order->getTaxFreeTotal());

        $template->hasAttribute = function ($strAttribute, ProductCollectionItem $objItem) {
            if (!$objItem->hasProduct()) {
                return false;
            }

            $objProduct = $objItem->getProduct();

            return \in_array($strAttribute, $objProduct->getAttributes(), true)
                   || \in_array($strAttribute, $objProduct->getVariantAttributes(), true);
        };

        $template->generateAttribute = function (
            $strAttribute,
            ProductCollectionItem $objItem,
            array $arrOptions = []
        ) {
            if (!$objItem->hasProduct()) {
                return '';
            }

            $objAttribute = $GLOBALS['TL_DCA']['tl_iso_product']['attributes'][$strAttribute];

            if (!($objAttribute instanceof IsotopeAttribute)) {
                throw new \InvalidArgumentException($strAttribute.' is not a valid attribute');
            }

            return $objAttribute->generate($objItem->getProduct(), $arrOptions);
        };

        $template->getGallery = function (
            $strAttribute,
            ProductCollectionItem $objItem
        ) use (
            $config,
            &$arrGalleries
        ) {
            if (!$objItem->hasProduct()) {
                return new Standard();
            }

            $strCacheKey = 'product'.$objItem->product_id.'_'.$strAttribute;
            $config['jumpTo'] = $objItem->getRelated('jumpTo');

            if (!isset($arrGalleries[$strCacheKey])) {
                $arrGalleries[$strCacheKey] = Gallery::createForProductAttribute($objItem->getProduct(), $strAttribute, $config);
            }

            return $arrGalleries[$strCacheKey];
        };

        // !HOOK: allow overriding of the template
        if (isset($GLOBALS['ISO_HOOKS']['addCollectionToTemplate'])
            && \is_array($GLOBALS['ISO_HOOKS']['addCollectionToTemplate'])) {
            foreach ($GLOBALS['ISO_HOOKS']['addCollectionToTemplate'] as $callback) {
                $objCallback = \System::importStatic($callback[0]);
                $objCallback->$callback[1]($template, $arrItems, $order);
            }
        }
    }

    // priorities (first is the most important):
    // product, product type, global shop config
    public function getOverridableStockProperty(string $property, $product)
    {
        // at first check for product and product type
        if ($product->overrideStockShopConfig) {
            return $product->{$property};
        }
        if (null !== ($objProductType = ProductType::findByPk($product->type)) && $objProductType->overrideStockShopConfig) {
            return $objProductType->{$property};
        }

        // nothing returned?
        $objConfig = Isotope::getConfig();

        // defaultly return the value defined in the global config
        return $objConfig->{$property};
    }

    public function getOverridableShopConfigProperty(string $property, $config = null)
    {
        if (!$config) {
            $config = Isotope::getConfig();
        }

        return $config->{$property};
    }

    public function updateStock(Order $order, $newsStatus)
    {
        // atm only for backend
        if (System::getContainer()->get('huh.utils.container')->isFrontend()) {
            return false;
        }

        // the order's config is used!
        $objConfig = Isotope::getConfig();

        $arrStockIncreaseOrderStates = StringUtil::deserialize($objConfig->stockIncreaseOrderStates, true);

        // e.g. new -> cancelled => increase the stock based on the order item's setQuantity-values (no validation required, of course)
        if (!\in_array($order->order_status, $arrStockIncreaseOrderStates, true) && \in_array($newsStatus->id, $arrStockIncreaseOrderStates, true)) {
            foreach ($order->getItems() as $objItem) {
                if (null !== ($objProduct = $objItem->getProduct())) {
                    $intTotalQuantity = $this->getTotalStockQuantity($objItem->quantity, $objProduct, null, $objItem->setQuantity);

                    if ($intTotalQuantity) {
                        $objProduct->stock += $intTotalQuantity;
                        $objProduct->save();
                    }
                }
            }
        } // e.g. cancelled -> new => decrease the stock after validation
        elseif (\in_array($order->order_status, $arrStockIncreaseOrderStates, true) && !\in_array($newsStatus->id, $arrStockIncreaseOrderStates, true)) {
            foreach ($order->getItems() as $objItem) {
                if (null !== ($objProduct = $objItem->getProduct())) {
                    $blnSkipValidation = $this->getOverridableStockProperty('skipStockValidation', $objProduct);

                    // watch out: also in backend the current set quantity is used for validation!
                    if (!$blnSkipValidation && !System::getContainer()->get('huh.isotope.manager')->validateQuantity($objProduct, $objItem->quantity)) {
                        // if the validation breaks for only one product collection item -> cancel the order status transition
                        return true;
                    }
                }
            }

            foreach ($order->getItems() as $objItem) {
                if (null !== ($objProduct = $objItem->getProduct())) {
                    $intTotalQuantity = $this->getTotalStockQuantity($objItem->quantity, $objProduct);

                    if ($intTotalQuantity) {
                        $objProduct->stock -= $intTotalQuantity;

                        if ($objProduct->stock <= 0
                            && !$this->getOverridableStockProperty('skipExemptionFromShippingWhenStockEmpty', $objProduct)) {
                            $objProduct->shipping_exempt = true;
                        }

                        $objProduct->save();
                    }
                }
            }
        }

        // don't cancel
        return false;
    }

    public function updateTemplateData($template, $product)
    {
        $module = $template->config['module'];

        if ($product->uploadedFiles) {
            // main image
            if (\is_array($uploadedFiles = unserialize($product->uploadedFiles))) {
                $product->uploadedFiles = $uploadedFiles[0];
            }

            $img = \FilesModel::findByUuid($product->uploadedFiles);
            $image = [];

            if (null === $img) {
                return;
            }

            // Override the default image size
            if ('' != $module->imgSize) {
                $size = StringUtil::deserialize($module->imgSize);

                if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2])) {
                    $image['size'] = $module->imgSize;
                }
            }

            $image['singleSRC'] = $img->path;
            Controller::addImageToTemplate($template, $image);
        }
    }

    public function getUserGroup(\DataContainer $dc)
    {
        $mediathekGroups = \MemberGroupModel::findBy('useForIsoProducts', 1);
        $userGroups = \FrontendUser::getInstance()->groups;

        $return = [];
        foreach ($userGroups as $group) {
            if (\array_key_exists($group, $mediathekGroups)) {
                $return[] = $group;
            }
        }

        return serialize($return);
    }

    public function updateRelevance(\DataContainer $dc)
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            return;
        }

        if (null !== ($product = System::getContainer()->get('contao.framework')->getAdapter(Product::class)->findBy('sku', Request::getGet('auto_item')))) {
            ++$product->relevance;
            $product->save();
        }
    }

    public function updateDownloadCounter($filePath)
    {
        $framework = System::getContainer()->get('contao.framework');
        if (null === ($file = $framework->getAdapter(\Contao\FilesModel::class)->findByPath($filePath))) {
            return;
        }

        if (null === ($download = $framework->getAdapter(Download::class)->findBy('singleSRC', $file->uuid))) {
            return;
        }

        /** @var Model $product */
        if (null !== ($product = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk(Product::getTable(), $download->pid))) {
            ++$product->downloadCount;
            $product->save();
        }
    }

    /**
     * Callback for isoButton Hook.
     *
     * @param array          $arrButtons
     * @param IsotopeProduct $objProduct
     *
     * @return array
     */
    public static function defaultButtons($arrButtons, IsotopeProduct $objProduct = null)
    {
        $actions = [
            new Frontend\ProductAction\UpdateAction(),
            new Frontend\ProductAction\CartAction(),
        ];

        /** @var Frontend\ProductAction\ProductActionInterface $action */
        foreach ($actions as $action) {
            $arrButtons[$action->getName()] = [
                'label' => $action->getLabel($objProduct),
                'callback' => [\get_class($action), 'handleSubmit'],
                'class' => ($objProduct instanceof IsotopeProduct && \is_callable([$action, 'getClasses']) ? $action->getClasses($objProduct) : ''),
            ];
        }

        return $arrButtons;
    }

    public function getProductLabels()
    {
        if (null === ($products = System::getContainer()->get('huh.utils.model')->findModelInstancesBy('tl_iso_product', ['tl_iso_product.sku!=""'], []))) {
            return [];
        }

        return $products->fetchEach('name');
    }

    // copy of code in ProductCollection->generateItem
    protected function generateItem(ProductCollectionItem $item)
    {
        $blnHasProduct = $item->hasProduct();
        $objProduct = $item->getProduct();

        // Set the active product for insert tags replacement
        if ($blnHasProduct) {
            Product::setActive($objProduct);
        }

        $arrCSS = ($blnHasProduct ? StringUtil::deserialize($objProduct->cssID, true) : []);

        $arrItem = [
            'id' => $item->id,
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'options' => Isotope::formatOptions($item->getOptions()),
            'configuration' => $item->getConfiguration(),
            'quantity' => $item->quantity,
            'price' => Isotope::formatPriceWithCurrency($item->getPrice()),
            'tax_free_price' => Isotope::formatPriceWithCurrency($item->getTaxFreePrice()),
            'total' => Isotope::formatPriceWithCurrency($item->getTotalPrice()),
            'tax_free_total' => Isotope::formatPriceWithCurrency($item->getTaxFreeTotalPrice()),
            'tax_id' => $item->tax_id,
            'href' => false,
            'hasProduct' => $blnHasProduct,
            'product' => $objProduct,
            'item' => $item,
            'raw' => $item->row(),
            'rowClass' => trim('product '.(($blnHasProduct && $objProduct->isNew()) ? 'new ' : '').$arrCSS[1]),
        ];

        if (null !== $item->getRelated('jumpTo') && $blnHasProduct && $objProduct->isAvailableInFrontend()) {
            $arrItem['href'] = $objProduct->generateUrl($item->getRelated('jumpTo'));
        }

        Product::unsetActive();

        return $arrItem;
    }

    // copy of code in ProductCollection->addItemsToTemplate
    protected function addItemsToTemplate(int $productType, Order $order, \Template $template, $varCallable = null)
    {
        $taxIds = [];
        $arrItems = [];

        foreach ($order->getItems($varCallable) as $objItem) {
            // FIX - check for product type id
            if ($objItem->getProduct()->type != $productType) {
                continue;
            }
            // ENDFIX

            $item = $this->generateItem($objItem);

            $taxIds[] = $item['tax_id'];
            $arrItems[] = $item;
        }

        RowClass::withKey('rowClass')->addCount('row_')->addFirstLast('row_')->addEvenOdd('row_')->applyTo($arrItems);

        $template->items = $arrItems;
        $template->total_tax_ids = \count(array_unique($taxIds));

        return $arrItems;
    }
}
