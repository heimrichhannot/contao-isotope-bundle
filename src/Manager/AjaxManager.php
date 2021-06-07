<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\System;
use HeimrichHannot\AjaxBundle\Response\ResponseData;
use HeimrichHannot\AjaxBundle\Response\ResponseError;
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
        if (null === ($product = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_iso_product', $productId))) {
            return new ResponseError();
        }

        $blocked = System::getContainer()->get('huh.isotope.attribute.booking')->getBlockedDates($product, $quantity);

        $response = new ResponseSuccess();
        $response->setResult(new ResponseData('', ['blocked' => $blocked]));

        return $response;
    }
}
