<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use HeimrichHannot\IsotopeBundle\Attribute\BookingAttributes;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/contao", defaults={
 *     "_scope" = "backend",
 *     "_token_check" = true
 * })
 */
class BackendController extends AbstractController
{
    const ROUTE = '/contao/isotope_bundle';

    /**
     * @Route("/isotope_bundle/bookinglist", name="huh.isotope.backend.bookinglist")
     * @Template("@HeimrichHannotContaoIsotope/backend/bookinglist.html.twig")
     *
     * @param Request                  $request
     * @param ContaoFrameworkInterface $contaoFramework
     * @param BookingAttributes        $bookingAttributes
     * @param TranslatorInterface      $translator
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
}
