<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use HeimrichHannot\IsotopeBundle\Attribute\BookingAttributes;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/contao/isotope_bundle", defaults={
 *     "_scope" = "backend",
 *     "_token_check" = true
 * })
 */
class BackendController extends AbstractController
{
    const ROUTE = '/contao/isotope_bundle';

    /**
     * @Route("/bookinglist", name="huh.isotope.backend.bookinglist")
     *
     * @return array
     */
    public function bookingListAction(Request $request, ContaoFrameworkInterface $contaoFramework, BookingAttributes $bookingAttributes, TranslatorInterface $translator)
    {
        if (!$contaoFramework->isInitialized()) {
            $contaoFramework->initialize();
        }
        $id = $request->get('id');
        if (!is_numeric($id) | !$product = ProductModel::findById($id)) {
            return ['error' => $translator->trans('Invalid id')];
        }
        $day = is_numeric($request->get('day')) ? (int) $request->get('day') : date('d');
        $month = is_numeric($request->get('month')) ? (int) $request->get('month') : date('n');
        $year = is_numeric($request->get('year')) ? (int) $request->get('year') : date('Y');
        $orders = $bookingAttributes->getOrdersWithBookingsByDay($product, $day, $month, $year);
        $date = mktime(0, 0, 0, $month, $day, $year);

        return [
            'product' => $product,
            'orders' => $orders,
            'tstamp' => $date,
        ];
    }

    /**
     * @Route("/bookingoverview", name="huh.isotope.backend.bookingoverview")
     *
     * @return array
     */
    public function bookingOverviewAction(Request $request, ContaoFrameworkInterface $contaoFramework, BookingAttributes $bookingAttributes, TranslatorInterface $translator)
    {
        $id = $request->get('id');
        if (!is_numeric($id) | !$product = ProductModel::findById($id)) {
            return ['error' => $translator->trans('Invalid id')];
        }
        $bookings = $bookingAttributes->getBookingCountsByMonth($product, date('n'), date('Y'));
        $year = is_numeric($request->get('year')) ? (int) $request->get('year') : date('Y');
        $month = is_numeric($request->get('month')) ? (int) $request->get('month') : date('n');
        $date = mktime(0, 0, 0, $month, 1, $year);

        return [
            'bookings' => $bookings,
            'product' => $product,
            'time' => $date,
        ];
    }
}
