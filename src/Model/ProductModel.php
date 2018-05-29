<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Contao\Database;
use Contao\Model;
use Contao\System;

class ProductModel extends Model
{
    protected static $strTable = 'tl_iso_product';
    protected $productDataManager;

    public function __construct(Database\Result $objResult = null)
    {
        parent::__construct($objResult);
        $this->productDataManager = System::getContainer()->get('huh.isotope.manager.productdata');
    }

    public function getCopyrights()
    {
        if (null !== ($copyrights = System::getContainer()->get('contao.framework')->createInstance(Database::class)->prepare("SELECT * FROM tl_iso_product WHERE copyright IS NOT NULL AND copyright != ''")->execute())) {
            return array_unique($copyrights->fetchEach('copyright'));
        }

        return [];
    }

    public function getStock(int $id)
    {
        return $this->productDataManager->getProductDataByProduct($id)->stock;
    }
}
