<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\EventListener;

use Contao\System;
use HeimrichHannot\IsotopeBundle\Attribute\BookingAttributes;
use HeimrichHannot\IsotopeBundle\Manager\IsotopeManager;
use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;
use Isotope\Message;
use Isotope\Model\ProductCollection\Order;

class IsotopeHookListener
{
    /**
     * @var IsotopeManager
     */
    private $isotopeManager;
    /**
     * @var ProductDataManager
     */
    private $productDataManager;

    /**
     * @var BookingAttributes
     */
    private $bookingAttributes;

    public function __construct(ProductDataManager $productDataManager, IsotopeManager $isotopeManager, BookingAttributes $bookingAttributes)
    {
        $this->isotopeManager = $isotopeManager;
        $this->productDataManager = $productDataManager;
        $this->bookingAttributes = $bookingAttributes;
    }

    public function validateStockCollectionAdd(&$objItem, $intQuantity, &$collection)
    {
        System::getContainer()->get('huh.isotope.attribute.booking')->validateCart($objItem, $intQuantity, $collection);
    }

    /**
     * ['ISO_HOOKS']['preCheckout'].
     *
     * @param Order $order
     * @param $checkout
     *
     * @return bool
     */
    public function validateStockPreCheckout(&$order, $checkout)
    {
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            if ($this->isotopeManager->getOverridableStockProperty('skipStockValidation', $product)) {
                continue;
            }
            if (false === $this->bookingAttributes->validateCart($item, $item->quantity)) {
                Message::addError($item->getErrors()[0]);

                return false;
            }
        }

        return $this->validateStockCheckout($order);
    }

    /**
     * ['ISO_HOOKS']['postCheckout'].
     *
     * @param Order $order
     *
     * @return bool
     */
    public function validateStockPostCheckout($order)
    {
        return $this->validateStockCheckout($order, true);
    }

    /**
     * @param Order $order
     * @param bool  $isPostCheckout
     *
     * @return bool
     */
    public function validateStockCheckout($order, bool $isPostCheckout = false)
    {
        $orderItems = $order->getItems();
        $arrOrders = [];

        foreach ($orderItems as $item) {
            $product = $item->getProduct();
            $productData = $this->productDataManager->getProductData($product);
            if (!empty($productData->stock)) {
                // override the quantity!
                if (!$this->isotopeManager->validateQuantity($product, $item->quantity)) {
                    return false;
                }

                if ($isPostCheckout) {
                    $arrOrders[] = $item;
                }
            }
        }

        // save new stock
        if ($isPostCheckout) {
            foreach ($arrOrders as $item) {
                $product = $item->getProduct();
                $productData = $this->productDataManager->getProductData($product);

                if ($this->isotopeManager->getOverridableStockProperty('skipStockEdit', $product)) {
                    continue;
                }

                $quantity = $this->isotopeManager->getTotalCartQuantity($item->quantity, $productData);

                $productData->stock -= $quantity;

                if ($productData->stock <= 0
                    && !$this->isotopeManager->getOverridableStockProperty('skipExemptionFromShippingWhenStockEmpty', $product)) {
                    $product->shipping_exempt = true;
                }

                $productData->save();
            }
        }

        return true;
    }
}
