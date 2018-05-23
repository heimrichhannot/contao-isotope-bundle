<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\System;
use HeimrichHannot\IsotopeBundle\Model\ProductCollectionItemModel;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Message;
use Isotope\Model\Product;
use Isotope\Model\ProductCollection;
use Isotope\Model\ProductCollectionItem;
use Model\Registry;

class ProductCollectionManager extends ProductCollection
{
    /**
     * Name of the current table.
     *
     * @var string
     */
    protected static $strTable = 'tl_iso_product_collection';

    /**
     * Interface to validate product collection.
     *
     * @var string
     */
    protected static $strInterface = '\Isotope\Interfaces\IsotopeProductCollection';

    /**
     * List of types (classes) for this model.
     *
     * @var array
     */
    protected static $arrModelTypes = [];

    /**
     * Constructor.
     *
     * @param \Database\Result $objResult
     */
    public function __construct(\Database\Result $objResult = null)
    {
        $this->arrData['type'] = 'cart';

        parent::__construct($objResult);
    }

    /**
     * Add a product to the collection.
     *
     * @param IsotopeProduct $objProduct
     * @param int            $intQuantity
     * @param array          $arrConfig
     *
     * @return ProductCollectionItem|false
     */
    public function addProduct(IsotopeProduct $objProduct, $intQuantity, array $arrConfig = [])
    {
        // !HOOK: additional functionality when adding product to collection
        if (isset($GLOBALS['ISO_HOOKS']['addProductToCollection'])
            && is_array($GLOBALS['ISO_HOOKS']['addProductToCollection'])
        ) {
            foreach ($GLOBALS['ISO_HOOKS']['addProductToCollection'] as $callback) {
                $intQuantity = \System::importStatic($callback[0])->{$callback[1]}($objProduct, $intQuantity, $this);
            }
        }

        if (0 == $intQuantity) {
            return false;
        }

        $time = time();
        $this->tstamp = $time;

        // Make sure collection is in DB before adding product
        if (!Registry::getInstance()->isRegistered($this)) {
            $this->save();
        }

        // Remove uploaded files from session so they are not added to the next product (see #646)
        unset($_SESSION['FILES']);

        $intMinimumQuantity = $objProduct->getMinimumQuantity();

        if ($intQuantity < $intMinimumQuantity) {
            Message::addInfo(sprintf(
                $GLOBALS['TL_LANG']['ERR']['productMinimumQuantity'],
                $objProduct->getName(),
                $intMinimumQuantity
            ));
            $intQuantity = $intMinimumQuantity;
        }

        $objItem = new ProductCollectionItemModel();
        $objItem->pid = $this->getCart();
        $objItem->jumpTo = (int) $arrConfig['jumpTo']->id;

        $this->setProductForItem($objProduct, $objItem, $intQuantity);
        $objItem->save();

        // Add the new item to our cache
        $this->arrItems[$objItem->id] = $objItem;

        // !HOOK: additional functionality when adding product to collection
        if (isset($GLOBALS['ISO_HOOKS']['postAddProductToCollection'])
            && is_array($GLOBALS['ISO_HOOKS']['postAddProductToCollection'])
        ) {
            foreach ($GLOBALS['ISO_HOOKS']['postAddProductToCollection'] as $callback) {
                \System::importStatic($callback[0])->{$callback[1]}($objItem, $intQuantity, $this);
            }
        }

        return $objItem;
    }

    protected function getCart()
    {
        if (null === ($cart = Isotope::getCart())) {
            return null;
        }

        return $cart->id;
    }

    /**
     * @param IsotopeProduct        $product
     * @param ProductCollectionItem $item
     * @param int                   $quantity
     */
    private function setProductForItem(IsotopeProduct $product, ProductCollectionItem $item, $quantity)
    {
        $item->tstamp = time();
        $item->type = array_search(get_class($product), Product::getModelTypes(), true);
        $item->product_id = $product->getId();
        $item->sku = $product->getSku();
        $item->name = $product->getName();
        $item->configuration = $product->getOptions();
        $item->quantity = (int) $quantity;

        $item->price = (float) ($product->getPrice($this) ? $product->getPrice($this)->getAmount((int) $quantity) : 0);
        $item->tax_free_price = (float) ($product->getPrice($this) ? $product->getPrice($this)->getNetAmount((int) $quantity) : 0);

        list($bookingStart, $bookingStop) = System::getContainer()->get('huh.isotope.manager')->splitUpBookingDates(System::getContainer()->get('huh.request')->getPost('edit_booking_plan'));

        if ($bookingStart && $bookingStop) {
            $item->bookingStart = $bookingStart;
            $item->bookingStop = $bookingStop;
        }
    }
}
