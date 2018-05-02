<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Action;

use Contao\System;
use HeimrichHannot\IsotopeBundle\Manager\AjaxManager;
use Isotope\Frontend\ProductAction\CartAction;
use Isotope\Interfaces\IsotopeProduct;

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

    public function getBlockedDates(IsotopeProduct $product)
    {
        return System::getContainer()->get('huh.isotope.manager')->getBlockedDates($product->id);
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
}
