<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Contao\Model;

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
}
