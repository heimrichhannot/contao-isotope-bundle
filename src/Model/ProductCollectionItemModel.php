<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Contao\System;
use Isotope\Model\ProductCollectionItem;

/**
 * Class ProductCollectionItemModel.
 *
 * @property int $bookingStart
 * @property int $bookingStop
 */
class ProductCollectionItemModel extends ProductCollectionItem
{
    protected static $strTable = 'tl_iso_product_collection_item';
    protected $productCache;

    public function findByItem($id, array $options = [])
    {
        return System::getContainer()->get('huh.utils.model')->findModelInstancesBy(
            static::$strTable,
            [static::$strTable.'.product_id=?'],
            [$id],
            $options
        );
    }

    /**
     * Get product price. Automatically falls back to the collection item table if product is not found.
     *
     * @return string
     */
    public function getPrice()
    {
        $price = parent::getPrice();

        if (!$this->hasBooking()) {
            return $price;
        }

        return $price * $this->getBookingRange();
    }

    /**
     * Get tax free product price. Automatically falls back to the collection item table if product is not found.
     *
     * @return string
     */
    public function getTaxFreePrice()
    {
        $price = parent::getTaxFreePrice();

        if (!$this->hasBooking()) {
            return $price;
        }

        return $price * $this->getBookingRange();
    }

    /**
     * @return int
     */
    public function getBookingRange()
    {
        return ceil(($this->getBookingStop() - $this->getBookingStart()) / 86400) + 1;
    }

    /**
     * @return bool
     */
    public function hasBooking()
    {
        if (!$this->getBookingStart() && !$this->getBookingStop()) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getBookingStart()
    {
        return $this->bookingStart;
    }

    public function setBookingStart(int $bookingStart)
    {
        $this->bookingStart = $bookingStart;
    }

    /**
     * @return int
     */
    public function getBookingStop()
    {
        return $this->bookingStop;
    }

    public function setBookingStop(int $bookingStop)
    {
        $this->bookingStop = $bookingStop;
    }

    public function getProduct($noCache = false)
    {
        if (!$this->productCache || $noCache) {
            $this->productCache = ProductModel::findByPk($this->product_id);
        }

        return $this->productCache;
    }
}
