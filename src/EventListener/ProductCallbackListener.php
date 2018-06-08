<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\DataContainer;
use Contao\Date;
use HeimrichHannot\IsotopeBundle\Attribute\BookingAttributes;
use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Container\ContainerUtil;
use Isotope\Model\Product;

class ProductCallbackListener
{
    /**
     * @var ContainerUtil
     */
    private $containerUtil;
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ProductDataManager
     */
    private $productDataManager;
    /**
     * @var BookingAttributes
     */
    private $bookingAttributes;
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(ContaoFrameworkInterface $framework, Request $request, ContainerUtil $containerUtil, ProductDataManager $productDataManager, BookingAttributes $bookingAttributes, \Twig_Environment $twig)
    {
        $this->containerUtil = $containerUtil;
        $this->framework = $framework;
        $this->request = $request;
        $this->productDataManager = $productDataManager;
        $this->bookingAttributes = $bookingAttributes;
        $this->twig = $twig;
    }

    /**
     * @param DataContainer $dc
     */
    public function updateRelevance(DataContainer $dc)
    {
        if ($this->containerUtil->isBackend()) {
            return;
        }

        /** @var Product $product */
        if (null !== ($product = $this->framework->getAdapter(Product::class)->findBy('sku', $this->request->getGet('auto_item')))) {
            ++$product->relevance;
            $product->save();
        }
    }

    /**
     * Save Product data fields to product data table
     * Contao: onsave_callbak.
     *
     * @param $value
     * @param DataContainer $dc
     *
     * @return mixed
     */
    public function saveMetaFields($value, DataContainer $dc)
    {
        if (!$dc->table === ProductModel::getTable()) {
            return $value;
        }
        if (!array_key_exists($field = $dc->field, $this->productDataManager->getProductDataFields())) {
            return $value;
        }
        $productData = $this->productDataManager->getProductData($dc->id);
        $productData->$field = $value;
        $productData->tstamp = time();
        $productData->save();

        return $value;
    }

    /**
     * Generates an overview of bookings for product.
     *
     * @param array $attributes
     *
     * @return string An Html-Code
     */
    public function getBookingOverview(array $attributes)
    {
        $product = ProductModel::findById($attributes['dataContainer']->id);
        $bookings = $this->bookingAttributes->getBookingCountsByMonth($product, date('n'), date('Y'));

        return $this->twig->render('@HeimrichHannotContaoIsotope/attribute/bookingoverview.html.twig', [
            'time' => time(),
            'bookings' => $bookings,
            'product' => $product->id,
        ]);
    }
}
