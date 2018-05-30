<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Attribute;

use HeimrichHannot\IsotopeBundle\Model\ProductDataModel;

class MaxOrderSizeAttribute
{
    public function validate(ProductDataModel $product, int $quantityTotal)
    {
        if (!empty($product->maxOrderSize)) {
            if ($quantityTotal > $product->maxOrderSize) {
                $strErrorMessage = sprintf($GLOBALS['TL_LANG']['MSC']['maxOrderSizeExceeded'], $product->name, $product->maxOrderSize);

                return [false, $strErrorMessage];
            }
        }

        return true;
    }
}
