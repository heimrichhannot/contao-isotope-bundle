<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Action;

use Contao\Controller;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Manager\AjaxManager;
use Isotope\Frontend\ProductAction\CartAction;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Message;

class BookingPlanAction extends CartAction
{
    public function getName()
    {
        return 'edit_booking_plan';
    }

    public function getLabel(IsotopeProduct $product = null)
    {
        return $GLOBALS['TL_LANG']['MSC']['buttonLabel']['edit_booking_plan'];
    }

    public function getBlockedDates($product)
    {
        return System::getContainer()->get('huh.isotope.manager')->getBlockedDates($product);
    }

    public function generate(IsotopeProduct $product, array $config = [])
    {
        return sprintf(
                    '<div class="bookingPlan_container" data-update="%s" data-product-id="%s">
                    <label for="bookingPlan">%s</label>
            <input type="text" name="%s" id="bookingPlan" class="submit %s %s"  data-blocked="%s"></div>',
            System::getContainer()->get('huh.ajax.action')->generateUrl(AjaxManager::ISOTOPE_AJAX_GROUP, AjaxManager::ISOTOPE_AJAX_BOOKING_PLAN_UPDATE),
            $product->id,
            $this->getLabel(),
            $this->getName(),
            $this->getName(),
            $this->getClasses($product),
            json_encode($this->getBlockedDates($product))
        ).'<input type="submit" name="submit" class="submit btn btn-primary" value="zum Warenkorb hinzufügen">';
    }

    /**
     * {@inheritdoc}
     */
    public function handleSubmit(IsotopeProduct $product, array $config = [])
    {
        if (!isset($_POST[$this->getName()])) {
            return false;
        }

        // do not update cart item that is already in cart but add a new one with the set booking dates
        $success = $this->handleAddToCart($product, $config);

        if ($success) {
            if (!$config['module']->iso_addProductJumpTo) {
                Controller::reload();
            }

            System::getContainer()->get('huh.utils.url')->jumpTo($config['module']->iso_addProductJumpTo);
        }
    }

    /**
     * @param IsotopeProduct $product
     * @param array          $config
     *
     * @return bool
     */
    private function handleAddToCart(IsotopeProduct $product, array $config = [])
    {
        $module = $config['module'];
        $quantity = 1;

        if ($module->iso_use_quantity && \Input::post('quantity_requested') > 0) {
            $quantity = (int) \Input::post('quantity_requested');
        }

        // Do not add parent of variant product to the cart
        if (($product->hasVariants() && !$product->isVariant())
            || !System::getContainer()->get('huh.isotope.product_collection_manager')->addProduct($product, $quantity, $config)
        ) {
            return false;
        }

        Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['addedToCart']);

        return true;
    }
}
