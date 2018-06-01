<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Attribute;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Model\Collection;
use Contao\StringUtil;
use HeimrichHannot\IsotopeBundle\Manager\IsotopeManager;
use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;
use HeimrichHannot\IsotopeBundle\Model\ProductCollectionItemModel;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Model\ProductCollectionItem;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class BookingAttributes.
 */
class BookingAttributes
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;
    /**
     * @var ProductDataManager
     */
    private $productDataManager;
    /**
     * @var ModelUtil
     */
    private $modelUtil;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var IsotopeManager
     */
    private $isotopeManager;

    public function __construct(ContaoFrameworkInterface $framework, ProductDataManager $productDataManager, ModelUtil $modelUtil, TranslatorInterface $translator, IsotopeManager $isotopeManager)
    {
        $this->framework = $framework;
        $this->productDataManager = $productDataManager;
        $this->modelUtil = $modelUtil;
        $this->translator = $translator;
        $this->isotopeManager = $isotopeManager;
    }

    /**
     * Validated cart for items with booking option.
     *
     * Will add an error to the item, if booking for selected dates is not possible. Will return true otherwise.
     *
     * @param ProductCollectionItemModel|ProductCollectionItem $item
     * @param int                                              $quantity
     *
     * @return bool
     */
    public function validateCart(&$item, $quantity)
    {
        $product = $item->getProduct();
        if (!is_a($item, ProductCollectionItemModel::class) || !$item->hasBooking()) {
            return true;
        }
        $blockedDates = $this->getBlockedDatesWithoutSelf($product, $quantity, $item);
        $productDates = $this->getRange($item->bookingStart, $item->bookingStop, $product->bookingBlock);

        if (count(array_diff($blockedDates, $productDates)) == count($blockedDates)) {
            return true;
        }

        $item->addError($this->translator->trans('huh.isotope.collection.booking.error.overbooked', ['%product%' => $product->getName()]));

        return false;
    }

    /**
     * @param IsotopeProduct             $product
     * @param int                        $quantity
     * @param ProductCollectionItemModel $collection
     *
     * @return array
     */
    public function getBlockedDatesWithoutSelf($product, $quantity, $item)
    {
        if (!$collectionItems = ProductCollectionItemModel::findBy(['product_id=?', 'id!=?'], [$product->id, $item->id])) {
            return [];
        }

        return $this->getBlockedDatesByItems($collectionItems, $product, $quantity);
    }

    /**
     * @param IsotopeProduct $product
     * @param int            $quantity
     *
     * @return array
     */
    public function getBlockedDates($product, int $quantity = 1)
    {
        /** @var ProductCollectionItemModel|Collection|null $collectionItems */
        if (null === ($collectionItems = $this->framework->getAdapter(ProductCollectionItemModel::class)->findByItem($product->id))) {
            return [];
        }

        return $this->getBlockedDatesByItems($collectionItems, $product, $quantity);
    }

    /**
     * @param Collection     $collectionItems
     * @param IsotopeProduct $product
     * @param $quantity
     *
     * @return array
     */
    public function getBlockedDatesByItems($collectionItems, $product, int $quantity)
    {
        $stock = $this->productDataManager->getProductData($product)->stock - $quantity;

        if ($this->isotopeManager->getOverridableStockProperty('skipStockValidation', $product)) {
            return [];
        }

        $bookings = $this->getBookings($product, $collectionItems);
        $reservedDates = $this->getReservedDates($product);

        if (!empty($reservedDates)) {
            $bookings = $this->mergeBookedWithReserved($bookings, $reservedDates);
        }

        return $this->getLockedDates($bookings, $stock, $quantity);
    }

    /**
     * calculate the bookingRange of a product
     * if the product has a bookingBlock it as to be added to the bookingStop and subtracted from the bookingStart
     * bookingBlock means that the product will be blocked for a certain amount of days after it's booking.
     *
     * @param int    $start
     * @param int    $stop
     * @param string $blocking
     *
     * @return array
     */
    public function getRange(int $start, int $stop, string $blocking = '')
    {
        $bookingStart = '' != $blocking ? $start - ($blocking * 86400) : $start;
        $bookingStop = '' != $blocking ? $stop + ($blocking * 86400) : $stop;

        return range($bookingStart, $bookingStop, 86400);
    }

    /**
     * get the booking dates for a product from collectionItems.
     *
     * @param            $product
     * @param Collection $collectionItems
     *
     * @return array
     */
    protected function getBookings($product, Collection $collectionItems)
    {
        $bookings = [];

        foreach ($collectionItems as $booking) {
            if (!$booking->bookingStart || !$booking->bookingStop) {
                continue;
            }

            $range =
                $this->getRange($booking->bookingStart, $booking->bookingStop, $product->bookingBlock ? $product->bookingBlock : '');
            $bookings[$booking->id] = $range;
        }

        return $bookings;
    }

    /**
     * get reserved dates from product.
     *
     * @param $product
     *
     * @return array
     */
    protected function getReservedDates($product)
    {
        if (!$product->bookingReservedDates) {
            return [];
        }

        if (empty($reserved = StringUtil::deserialize($product->bookingReservedDates))) {
            return [];
        }

        $reservedDates = [];
        foreach ($reserved as $pk) {
            if (null === ($blockedDates = $this->modelUtil->findModelInstanceByPk('tl_fieldpalette', $pk))) {
                continue;
            }

            $range = $this->getRange($blockedDates->start, $blockedDates->stop, $product->bookingBlock ? $product->bookingBlock : '');

            for ($i = 0; $i < $blockedDates->count; ++$i) {
                $reservedDates[] = $range;
            }
        }

        return $reservedDates;
    }

    /**
     * merge reserved dates into booking array.
     *
     * @param array $bookings
     * @param array $reservedDates
     *
     * @return array
     */
    protected function mergeBookedWithReserved(array $bookings, array $reservedDates)
    {
        foreach ($reservedDates as $range) {
            $bookings[] = $range;
        }

        return $bookings;
    }

    /**
     * get the final locked days for this product.
     *
     * @param array $bookings
     * @param int   $stock
     * @param int   $quantity
     *
     * @return array
     */
    protected function getLockedDates(array $bookings, int $stock, int $quantity)
    {
        $counts = [];

        foreach ($bookings as $dates) {
            foreach ($dates as $date) {
                $count = 0;

                foreach ($bookings as $compareDates) {
                    foreach ($compareDates as $compareDate) {
                        if ($compareDate != $date) {
                            continue;
                        }

                        ++$count;

                        $counts[$date] = $count;
                    }
                }
            }
        }

        $locked = [];

        foreach ($counts as $date => $bookingCount) {
            if ($date < strtotime('today midnight') || ($stock + $quantity) - $bookingCount > $quantity) {
                continue;
            }

            $locked[] = $date;
        }

        return $locked;
    }
}
