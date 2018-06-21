<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Attribute;

use Isotope\Model\Product;

/**
 * Class StockAttribute.
 *
 * Contains all stock related functions.
 */
class StockAttribute
{
    /**
     * @param Product $product
     * @param int     $quantityTotal
     * @param bool    $includeError
     *
     * @return array
     */
    public function validate(Product $product, int $quantityTotal, bool $includeError = false)
    {
        if ('' != $product->stock && null !== $product->stock) {
            if ($product->stock <= 0) {
                $strErrorMessage = sprintf($GLOBALS['TL_LANG']['MSC']['stockEmpty'], $product->name);

                return [false, $strErrorMessage];
            } elseif ($quantityTotal > $product->stock) {
                $strErrorMessage = sprintf($GLOBALS['TL_LANG']['MSC']['stockExceeded'], $product->name, $product->stock);

                return [false, $strErrorMessage];
            }
        }

        return [true, null];
    }
}
