<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Contao\Model;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;

/**
 * Class ProductDataModel.
 *
 * @property int $id
 * @property int $pid
 * @property int $tstamp
 * @property int $dateAdded
 * @property string $initialStock
 * @property string $stock
 * @property string $setQuantity
 * @property string $releaseDate
 */
class ProductDataModel extends Model
{
    protected static $strTable = 'tl_iso_product_data';

    /**
     * @var ProductModel
     */
    protected $productModel;
    /**
     * @var ProductDataManager
     */
    protected $productDataManager;
    protected $productDataChanged = false;

    public function __set($strKey, $varValue)
    {
        if (array_key_exists($strKey, $this->getProductDataManager()->getProductDataFields())) {
            $this->getProductModel()->$strKey = $varValue;
            $this->productDataChanged = true;
        }
        parent::__set($strKey, $varValue);
    }

    /**
     * Returns the product model for the current product data instance.
     *
     * @return ProductModel
     */
    public function getProductModel(bool $useCache = true)
    {
        if (!$this->productModel || false === $useCache) {
            $this->productModel = ProductModel::findByPk($this->pid);
        }

        return $this->productModel;
    }

    /**
     * Returns the ProductDataManager.
     *
     * @return \HeimrichHannot\IsotopeBundle\Manager\ProductDataManager|object
     */
    public function getProductDataManager()
    {
        if (!$this->productDataManager) {
            $this->productDataManager = System::getContainer()->get('huh.isotope.manager.productdata');
        }

        return $this->productDataManager;
    }

    public function save()
    {
        if ($this->productDataChanged) {
            try {
                $this->getProductModel()->save();
                $this->productDataChanged = false;
            } catch (\Exception $e) {
            }
        }

        return parent::save();
    }
}
