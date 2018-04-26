<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Item;

use Contao\System;
use HeimrichHannot\ReaderBundle\Item\DefaultItem;
use Isotope\Frontend\ProductCollectionAction\AddToCartAction;
use Isotope\Model\Product\Standard;

class IsotopeReaderItem extends DefaultItem
{
    public function getAddToCartAction()
    {
        $action = new AddToCartAction();

        $product = Standard::findPublishedByPk($this->_raw['id']);

        return $action->generate($product);
    }

    public function getBookingCalendar()
    {
        $daysLocked = [];

        if (null !== ($bookings = (System::getContainer()->get('huh.isotope.model.product_collection_item')->findByItem($this->_raw['id'])))) {
            $daysLocked = $this->getDaysLocked($bookings);
        }

        return json_encode($daysLocked);
    }

    protected function getDaysLocked($bookings)
    {
        $locked = [];

        foreach ($bookings as $booking) {
            $locked = array_merge($locked, range($booking->bookingStart, $booking->bookingStop, 86400));
        }

        return array_unique($locked);
    }
}
