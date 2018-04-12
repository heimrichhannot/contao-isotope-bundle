<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\System;
use Isotope\Isotope;
use Isotope\Model\Product;
use Isotope\Model\ProductType;

class IsotopeManager
{
    /**
     * @param            $product
     * @param            $quantity
     * @param null       $objCartItem
     * @param bool|false $includeError
     * @param bool|false $skipSets     override the normal handling of this property -> used for backend handling since this
     *                                 uses the order item's setQuantity property
     *
     * @return array|bool
     */
    public function validateQuantity(Product $product, int $quantity, $objCartItem = null, $includeError = false, $intSetQuantity = null)
    {
        $blnSkipStockValidation = $this->getOverridableStockProperty('skipStockValidation', $product);

        // no quantity at all
        if (null === $quantity) {
            return true;
        } elseif ('' == $quantity) {
            $quantity = 1;
        }

        $intQuantityTotal = $this->getTotalStockQuantity($quantity, $product, $objCartItem, $intSetQuantity);

        $utilsContainer = System::getContainer()->get('huh.utils.container');
        // stock
        if (!$blnSkipStockValidation && '' != $product->stock && null !== $product->stock) {
            if ($product->stock <= 0) {
                $strErrorMessage = sprintf($GLOBALS['TL_LANG']['MSC']['stockEmpty'], $product->name);

                if ($utilsContainer->isFrontend()) {
                    $_SESSION['ISO_ERROR'][] = $strErrorMessage;
                } else {
                    \Message::addError($strErrorMessage);
                }

                if ($includeError) {
                    return [false, $strErrorMessage];
                }

                return false;
            } elseif ($intQuantityTotal > $product->stock) {
                $strErrorMessage = sprintf($GLOBALS['TL_LANG']['MSC']['stockExceeded'], $product->name, $product->stock);

                if ($utilsContainer->isFrontend()) {
                    $_SESSION['ISO_ERROR'][] = $strErrorMessage;
                } else {
                    \Message::addError($strErrorMessage);
                }

                if ($includeError) {
                    return [false, $strErrorMessage];
                }

                return false;
            }
        }

        // maxOrderSize
        if ('' != $product->maxOrderSize && null !== $product->maxOrderSize) {
            if ($intQuantityTotal > $product->maxOrderSize) {
                $strErrorMessage = sprintf($GLOBALS['TL_LANG']['MSC']['maxOrderSizeExceeded'], $product->name, $product->maxOrderSize);

                if ($utilsContainer->isFrontend()) {
                    $_SESSION['ISO_ERROR'][] = $strErrorMessage;
                } else {
                    \Message::addError($strErrorMessage);
                }

                if ($includeError) {
                    return [false, $strErrorMessage];
                }

                return false;
            }
        }

        if ($includeError) {
            return [true, null];
        }

        return true;
    }

    /**
     * priorities (first is the most important):
     * product, product type, global shop config.
     *
     *
     * @param string $property
     * @param        $product
     *
     * @return mixed
     */
    public function getOverridableStockProperty(string $property, $product)
    {
        // at first check for product and product type
        if ($product->overrideStockShopConfig) {
            return $product->{$property};
        }
        if (null !== ($objProductType = System::getContainer()->get('contao.framework')->getAdapter(ProductType::class)->findByPk($product->type)) && $objProductType->overrideStockShopConfig) {
            return $objProductType->{$property};
        }

        // nothing returned?
        $objConfig = Isotope::getConfig();

        // defaultly return the value defined in the global config
        return $objConfig->{$property};
    }

    /**
     * watch out: also in backend the current set quantity is used.
     *
     * @param int     $quantity
     * @param Product $product
     * @param null    $objCartItem
     * @param null    $intSetQuantity
     * @param null    $config
     *
     * @return int|null
     */
    public function getTotalStockQuantity(int $quantity, Product $product, $objCartItem = null, $intSetQuantity = null, $config = null)
    {
        $intFinalSetQuantity = 1;

        if ($intSetQuantity) {
            $intFinalSetQuantity = $intSetQuantity;
        } elseif (!$this->getOverridableShopConfigProperty('skipSets', $config) && $product->setQuantity) {
            $intFinalSetQuantity = $product->setQuantity;
        }

        $quantity *= $intFinalSetQuantity;

        if (null !== $objCartItem) {
            $quantity += $objCartItem->quantity * $intFinalSetQuantity;
        }

        return $quantity;
    }

    /**
     * @param string $property
     * @param null   $config
     *
     * @return mixed
     */
    public function getOverridableShopConfigProperty(string $property, $config = null)
    {
        if (!$config) {
            $config = Isotope::getConfig();
        }

        return $config->{$property};
    }
}
