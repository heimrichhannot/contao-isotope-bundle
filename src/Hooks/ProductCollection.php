<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Hooks;

use Contao\System;

class ProductCollection
{
    public function applyBookingPlanToCollectionItem($product, $quantity, $collection)
    {
        $booking = System::getContainer()->get('huh.request')->getPost('edit_booking_plan');

        if (!$booking) {
            return;
        }

        $bookingDates = explode('bis', $booking);
        $bookingStart = strtotime(trim($bookingDates[0]));
        $bookingStop = strtotime(trim($bookingDates[1]));

        $product->bookingStart = $bookingStart;
        $product->bookingStop = $bookingStop;

        $product->save();
    }
}
