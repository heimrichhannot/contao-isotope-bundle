<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\EventListener;

use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;
use Isotope\Model\Product;

class HookListener
{
    /**
     * @var ProductDataManager
     */
    private $productDataManager;

    /**
     * HookListener constructor.
     *
     * @param ProductDataManager $productDataManager
     */
    public function __construct(ProductDataManager $productDataManager)
    {
        $this->productDataManager = $productDataManager;
    }

    public function addMetaFields(string $current)
    {
        $table = Product::getTable();
        if ($table !== $current) {
            return;
        }
        $GLOBALS['TL_DCA'][$table]['fields'] = array_merge(
            $GLOBALS['TL_DCA'][$table]['fields'],
            $this->productDataManager->getProductDataFields(true)
        );
    }
}
