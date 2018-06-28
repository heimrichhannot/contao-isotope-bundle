<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Action;

use Contao\Controller;
use Contao\Input;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Manager\AjaxManager;
use Isotope\Frontend\ProductAction\CartAction;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Message;

class BookingPlanAction extends CartAction
{
    protected $actionManager;

    public function __construct()
    {
        $this->actionManager = System::getContainer()->get('huh.ajax.action');
    }

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
        return System::getContainer()->get('huh.isotope.attribute.booking')->getBlockedDates($product);
    }

    public function generate(IsotopeProduct $product, array $config = [])
    {
        foreach ([1533765600, 1533852000, 1533938400, 1534024800, 1534111200, 1534197600, 1534284000, 1534370400, 1534456800, 1534543200, 1534629600, 1534716000, 1534802400, 1534888800, 1534975200, 1535061600, 1535148000, 1535234400, 1535320800, 1535407200, 1535493600, 1535580000, 1535666400, 1535752800, 1535839200, 1535925600, 1536012000, 1536098400, 1536184800, 1536271200, 1536357600, 1536444000, 1536530400, 1536616800, 1536703200, 1536789600, 1536876000, 1536962400, 1530136800] as $day) {
            echo '<pre>';
            var_dump(date('d.m.Y', $day));
            echo '</pre>';
        }

        $url = $this->actionManager->generateUrl(AjaxManager::ISOTOPE_AJAX_GROUP, AjaxManager::ISOTOPE_AJAX_BOOKING_PLAN_UPDATE);

        return sprintf(
                    '<div class="bookingPlan_container" data-update="%s" data-product-id="%s">
                        <label for="%s">%s</label>
                    <input type="text" name="%s" id="bookingPlan" class="submit %s %s"  data-blocked="%s" required></div>',
            $url,
            $product->id,
            $this->getName(),
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
        if (empty($_POST[$this->getName()])) {
            Message::addError(System::getContainer()->get('translator')->trans('huh.isotope.collection.booking.error.emptySelection'));

            return false;
        }

        // do not update cart item that is already in cart but add a new one with the set booking dates
        $success = $this->handleAddToCart($product, $config);

        if ($success) {
            if (!$config['module']->iso_addProductJumpTo) {
                Controller::reload();
            }
            System::getContainer()->get('huh.utils.url')->redirect($config['module']->iso_addProductJumpTo);
        } else {
            return false;
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

        if ($module->iso_use_quantity && Input::post('quantity_requested') > 0) {
            $quantity = (int) Input::post('quantity_requested');
        }

        // Do not add parent of variant product to the cart
        if (($product->hasVariants() && !$product->isVariant())
            || !$item = Isotope::getCart()->addProduct($product, $quantity, $config)
        ) {
            return false;
        }

        if ($item->hasErrors()) {
            Message::addError($item->getErrors()[0]);

            return false;
        }

        Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['addedToCart']);

        return true;
    }
}
