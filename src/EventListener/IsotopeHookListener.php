<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\EventListener;

use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Attribute\BookingAttributes;
use HeimrichHannot\IsotopeBundle\Manager\IsotopeManager;
use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;
use HeimrichHannot\IsotopeBundle\Model\ProductCollectionItemModel;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use Isotope\Message;
use Isotope\Model\ProductCollection;
use Isotope\Model\ProductCollection\Order;
use Isotope\Model\ProductCollectionItem;
use Isotope\Model\Shipping;

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
    /**
     * @var Request
     */
    private $request;

    public function __construct(ProductDataManager $productDataManager, IsotopeManager $isotopeManager, BookingAttributes $bookingAttributes, Request $request)
    {
        $this->isotopeManager = $isotopeManager;
        $this->productDataManager = $productDataManager;
        $this->bookingAttributes = $bookingAttributes;
        $this->request = $request;
    }

    /**
     * Add booking information to Cart Item.
     *
     * ['ISO_HOOKS']['postAddProductToCollection']
     *
     * @param ProductCollectionItem|ProductCollectionItemModel $item
     */
    public function addBookingInformationToItem(ProductCollectionItem &$item, int $quantity, ProductCollection $collection)
    {
        if (!$this->request->hasPost('edit_booking_plan')) {
            return;
        }
        list($bookingStart, $bookingStop) = $this->bookingAttributes->splitUpBookingDates($this->request->getPost('edit_booking_plan'));

        if ($bookingStart && $bookingStop) {
            $item->bookingStart = $bookingStart;
            $item->bookingStop = $bookingStop;
            $item->tstamp = time();
            $item->save();
        }
    }

    public function validateStockCollectionAdd(&$objItem, $intQuantity, &$collection)
    {
        System::getContainer()->get('huh.isotope.attribute.booking')->validateCart($objItem, $intQuantity, $collection);
    }

    /**
     * ['ISO_HOOKS']['preCheckout'].
     *
     * @param Order $order
     * @param       $checkout
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

    /**
     * @param $order
     * @param $module
     *
     * @return void|null
     */
    public function modifyShippingPrice($order, $module)
    {
        $shippingMethodId = $module->getModel()->iso_shipping_modules;

        if (null === ($method = System::getContainer()->get('contao.framework')->getAdapter(Shipping::class)->findByPk($shippingMethodId))) {
            return null;
        }

        if ('group' != $method->type) {
            return;
        }

        $groupMethodIds = StringUtil::deserialize($method->group_methods, true);
        if (null === ($groupMethods =
                System::getContainer()->get('contao.framework')->getAdapter(Shipping::class)->findMultipleByIds($groupMethodIds))) {
            return;
        }

        if (null === ($shippingMethod = $this->getCurrentShippingMethod($groupMethods, $order))) {
            return;
        }

        $order->setShippingMethod($shippingMethod);
    }

    /**
     * @return mixed
     */
    protected function getCurrentShippingMethod(Collection $groupMethods, Order $order)
    {
        $quantity = $this->getQuantityBySkipProducts($groupMethods, $order);

        foreach ($groupMethods as $method) {
            if (!$this->isCurrentShippingMethod($quantity, $method)) {
                continue;
            }

            return $method;
        }

        return null;
    }

    /**
     * @return int|null
     */
    protected function getQuantityBySkipProducts(Collection $methods, Order $order)
    {
        $currentQuantity = $this->getItemQuantity($order);
        $skipItems = $this->getSkipItems($methods, $currentQuantity);

        if (null === $skipItems) {
            return null;
        }

        $items = $order->getItems();
        foreach ($items as $item) {
            if (!\in_array($item->product_id, $skipItems, true)) {
                continue;
            }

            $currentQuantity -= $item->quantity;
        }

        return $currentQuantity;
    }

    /**
     * @return array|null
     */
    protected function getSkipItems(Collection $methods, int $quantity)
    {
        foreach ($methods as $method) {
            if (!$this->isCurrentShippingMethod($quantity, $method)) {
                continue;
            }

            $skipItems = StringUtil::deserialize($method->skipProducts, true);
            if (!empty($skipItems)) {
                return $skipItems;
            }
        }

        return null;
    }

    /**
     * check for suitable method boundaries.
     *
     * @return bool
     */
    protected function isCurrentShippingMethod(int $quantity, Shipping $method)
    {
        if ($quantity >= $method->minimum_quantity && $quantity <= $method->maximum_quantity) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    protected function getItemQuantity(Order $order)
    {
        $quantity = 0;
        $items = $order->getItems();

        foreach ($items as $item) {
            $quantity += $item->quantity;
        }

        return $quantity;
    }
}
