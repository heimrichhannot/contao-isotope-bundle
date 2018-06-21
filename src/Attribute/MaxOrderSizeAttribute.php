<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Attribute;

use Isotope\Model\Product;

class MaxOrderSizeAttribute
{
    /**
     * @param Product $product
     * @param int     $quantityTotal
     *
     * @return array
     */
    public function validate(Product $product, int $quantityTotal)
    {
        if (!empty($product->maxOrderSize)) {
            if ($quantityTotal > $product->maxOrderSize) {
                $strErrorMessage = sprintf($GLOBALS['TL_LANG']['MSC']['maxOrderSizeExceeded'], $product->name, $product->maxOrderSize);

                return [false, $strErrorMessage];
            }
        }

        return [true, null];
    }
}
