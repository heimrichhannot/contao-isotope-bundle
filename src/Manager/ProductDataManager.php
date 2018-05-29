<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\Controller;
use HeimrichHannot\IsotopeBundle\Model\ProductDataModel;
use Isotope\Model\Product;

class ProductDataManager
{
    /**
     * @var array
     */
    protected $productDataFields;

    /**
     * Returns all product data fields.
     *
     * @param bool $useCache
     *
     * @return array
     */
    public function getProductDataFields(bool $useCache = true)
    {
        if (!$this->productDataFields || false === $useCache) {
            $table = ProductDataModel::getTable();
            Controller::loadDataContainer($table);
            $fields = $GLOBALS['TL_DCA'][$table]['fields'];
            $metaFields = [];
            foreach ($fields as $key => $field) {
                if (true !== $field['eval']['skipProductPalette']) {
                    unset($field['sql']);
                    $metaFields[$key] = $field;
                }
            }
            $this->productDataFields = $metaFields;
        }

        return $this->productDataFields;
    }

    /**
     * Returns the product data for a product.
     * If no product data is available, a new instance will be returned.
     *
     * @param Product|int $product
     *
     * @return ProductDataModel
     */
    public function getProductDataByProduct($product)
    {
        $pid = is_int($product) ?: $product->id;

        $productData = ProductDataModel::findOneBy('pid', $pid);
        if (!$productData) {
            $productData = new ProductDataModel();
        }

        return $productData;
    }
}
