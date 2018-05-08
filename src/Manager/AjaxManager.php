<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\System;
use HeimrichHannot\AjaxBundle\Response\ResponseData;
use HeimrichHannot\AjaxBundle\Response\ResponseSuccess;

class AjaxManager
{
    const ISOTOPE_AJAX_GROUP = 'isotope_ajax';
    const ISOTOPE_AJAX_BOOKING_PLAN_UPDATE = 'updateBookingPlan';

    const ISOTOPE_AJAX_VARIABLE_PRODUCT_ID = 'productId';
    const ISOTOPE_AJAX_VARIABLE_QUANTITY = 'quantity';

    public function ajaxActions()
    {
        System::getContainer()->get('huh.ajax')->runActiveAction(static::ISOTOPE_AJAX_GROUP, static::ISOTOPE_AJAX_BOOKING_PLAN_UPDATE, $this);
    }

    public function updateBookingPlan(int $productId, int $quantity)
    {
        $blocked = System::getContainer()->get('huh.isotope.manager')->getBlockedDates($productId, $quantity);

        $response = new ResponseSuccess();
        $response->setResult(new ResponseData('', ['blocked' => $blocked]));

        return $response;
    }
}
