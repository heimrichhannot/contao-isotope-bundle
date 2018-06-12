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
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Model\ProductCollection;
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
     * Returns a list of orders for given products for requested day.
     *
     * @param ProductModel $product
     * @param int          $day
     * @param int          $month
     * @param int          $year
     *
     * @return Collection|array|ProductCollection[]|null
     */
    public function getOrdersWithBookingsByDay(ProductModel $product, int $day, int $month, int $year)
    {
        $orders = [];
        $start = mktime(0, 0, 0, $month, $day, $year);
        $end = mktime(23, 59, 59, $month, $day, $year);
        $items = $this->getBookedItemsInTimeRange($product, $start, $end, true);
        if (!$items) {
            return $orders;
        }
        foreach ($items as $item) {
            $orders[$item->pid]['items'][] = $item;
            $orders[$item->pid]['order'] = ProductCollection::findOneBy(['id =?', 'type=?'], [$item->pid, 'order']);
        }

        return $orders;
    }

    /**
     * Return a list with number of bookings per day.
     *
     * Includes reservations an blocked days.
     *
     * @param ProductModel $product
     * @param int          $month
     * @param int          $year
     *
     * @return array
     */
    public function getBookingCountsByMonth(ProductModel $product, int $month, int $year)
    {
        $firstDay = mktime(0, 0, 0, $month, 1, $year);
        $monthDays = date('t', mktime(0, 0, 0, $month, 1, $year));
        $lastDay = mktime(23, 59, 59, $month, $monthDays, $year);

        $bookingList = [];
        $bookingList['booked'] = $bookingList['blocked'] = $bookingList['reserved'] = array_fill(1, $monthDays, 0);
        $items = $this->getBookedItemsInTimeRange($product, $firstDay, $lastDay);
        if (!$items) {
            return $bookingList;
        }
        foreach ($items as $item) {
            $range = $this->getRange($item->bookingStart, $item->bookingStop, $product->bookingBlock ?: 0);
            $startDay = date('j', $item->bookingStart);
            $endDay = date('j', $item->bookingStop);
            foreach ($range as $tstamp) {
                if ($year == date('Y', $tstamp) && ($month == date('n', $tstamp))) {
                    $selectedDay = date('j', $tstamp);
                    if ($selectedDay < $startDay || $selectedDay > $endDay) {
                        ++$bookingList['blocked'][$selectedDay];
                        continue;
                    }
                    ++$bookingList['booked'][$selectedDay];
                }
            }
        }
        $reservedDates = $this->getReservedDates($product);
        foreach ($reservedDates as $reserved) {
            foreach ($reserved as $tstamp) {
                if ($year == date('Y', $tstamp) && ($month == date('n', $tstamp))) {
                    ++$bookingList['reserved'][date('j', $tstamp)];
                }
            }
        }

        return $bookingList;
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
     * Split up booking date string to two seperate timestamps.
     *
     * @param string $booking
     *
     * @return array
     */
    public function splitUpBookingDates(string $booking)
    {
        $bookingDates = explode('bis', $booking);

        return [strtotime(trim($bookingDates[0])), strtotime(trim($bookingDates[1]))];
    }

    /**
     * @param ProductModel $product
     * @param int          $startDate
     * @param int          $endDate
     * @param bool         $ignoreBlocking
     *
     * @return Collection|ProductCollectionItemModel[]|null
     */
    protected function getBookedItemsInTimeRange(ProductModel $product, int $startDate, int $endDate, bool $ignoreBlocking = false)
    {
        $searchRange = 0;
        if ($product->bookingBlock && !$ignoreBlocking) {
            //search block range * 2 to get also overlapping block dates and add 1 day to get the booking date
            $searchRange = (86400 * $product->bookingBlock * 2) + 1;
        }
        $firstDayWithBlocking = $startDate - $searchRange;
        $lastDayWithBlocking = $endDate + $searchRange;

        return ProductCollectionItemModel::findBy([
            'product_id = ?',
            "((bookingStart <= $lastDayWithBlocking AND bookingStop >= $startDate) ".
            "OR (bookingStart <= $endDate AND bookingStop >= $firstDayWithBlocking) ".
            "OR (bookingStart <= $startDate AND bookingStop >= $endDate))",
        ], [
            (int) $product->id,
        ]);
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
