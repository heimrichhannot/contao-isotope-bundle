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
        $container = System::getContainer();
        $this->framework = $container->get('contao.framework');
        $this->productDataManager = $container->get('huh.isotope.manager.productdata');
    }

    /**
     * @return array
     *
     * @todo
     */
    public function getCopyrights()
    {
        /** @var Database\Result $copyrights */
        $copyrights = $this->framework->get('contao.framework')->createInstance(Database::class)->prepare("SELECT * FROM tl_iso_product_data WHERE copyright IS NOT NULL AND copyright != ''")->execute();

        if (null !== ($copyrights)) {
            return array_unique($copyrights->fetchEach('copyright'));
        }

        return [];
    }

    public function getStock(int $id)
    {
        return $this->productDataManager->getProductDataByProduct($id)->stock;
    }
}
