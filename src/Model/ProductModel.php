<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Contao\Database;
use Contao\StringUtil;
use Contao\System;
use Isotope\Model\Product\Standard;
use Isotope\Model\ProductType;

/**
 * Class ProductModel.
 *
 *
 * @property int    $id
 * @property int    $pid
 * @property int    $gid
 * @property int    $tstamp
 * @property string $language
 * @property int    $dateAdded
 * @property int    $type
 * @property array  $pages
 * @property array  $orderPages
 * @property array  $inherit
 * @property bool   $fallback
 * @property string $alias
 * @property string $sku
 * @property string $name
 * @property string $teaser
 * @property string $description
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property bool   $shipping_exempt
 * @property array  $images
 * @property bool   $protected
 * @property array  $groups
 * @property bool   $guests
 * @property array  $cssID
 * @property bool   $published
 * @property string $start
 * @property string $stop
 *
 * From Product data:
 * @property string $initialStock
 * @property string $stock
 * @property string $setQuantity
 * @property string $releaseDate
 */
class ProductModel extends Standard
{
    protected static $strTable = 'tl_iso_product';

    /**
     * @var \HeimrichHannot\IsotopeBundle\Manager\ProductDataManager|object
     */
    protected $productDataManager;
    /**
     * @var ProductDataModel
     */
    protected $productData;
    /**
     * Flag for saving product data.
     *
     * @var bool
     */
    protected $productDataChanged = false;
    /**
     * @var bool
     */
    protected $blankProduct = false;
    /**
     * @var BlankProductModel
     */
    protected $blankProductModel;

    public function __construct(Database\Result $result = null)
    {
        if (null === $result) {
            $this->blankProductModel = new BlankProductModel();
            $this->blankProduct = true;
            $this->arrRelations = $this->blankProductModel->arrRelations;
            $this->productData = new ProductDataModel();
        } else {
            parent::__construct($result);
        }

        $this->productDataManager = System::getContainer()->get('huh.isotope.manager.productdata');
    }

    public function __set($key, $value)
    {
        if (array_key_exists($key, $this->getProductDataManager()->getProductDataFields()) && null !== $this->getProductData()) {
            $this->getProductData()->$key = $value;
            $this->productDataChanged = true;
        }
        if ($this->blankProduct) {
            $this->blankProductModel->$key = $value;
        } else {
            parent::__set($key, $value);
        }
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->getProductDataManager()->getProductDataFields()) && null !== $this->getProductData()) {
            return $this->getProductData()->$key;
        }

        return parent::__get($key);
    }

    public function __isset($key)
    {
        if (array_key_exists($key, $this->getProductDataManager()->getProductDataFields())) {
            return true;
        }

        return parent::__isset($key);
    }

    /**
     * @return array
     *
     * @todo
     */
    public function getCopyrights()
    {
        if (null === ($products = System::getContainer()->get('contao.framework')->getAdapter(ProductDataModel::class)->findBy(['copyright IS NOT NULL'], null))) {
            return [];
        }

        $result = [];

        foreach ($products as $product) {
            if (!$product->copyright || '' == $product->copyright) {
                continue;
            }

            $options = StringUtil::deserialize($product->copyright, true);

            foreach ($options as $option) {
                $result[] = $option;
            }
        }

        // do not use array_unique -> wrong copyright will be displayed as set copyright for this product
        return $result;
    }

    public function getStock(int $id)
    {
        return $this->getProductDataManager()->getProductData($id)->stock;
    }

    public function getInitialStock(int $id)
    {
        return $this->getProductDataManager()->getProductData($id)->initialStock;
    }

    /**
     * Return the product data for the current product.
     *
     * @return ProductDataModel
     */
    public function getProductData(bool $useCache = true)
    {
        if (!$this->productData || !$useCache) {
            $this->productData = $this->getProductDataManager()->getProductData($this);
        }

        return $this->productData;
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

    /**
     * Updates the model data with product data model data.
     * Attention: Only updates model instance data and do not save. You need to call save() yourself!
     *
     * @return $this
     */
    public function syncWithProductData()
    {
        $productData = $this->getProductData();
        $this->mergeRow($productData->row());
        $this->tstamp = time();

        return $this;
    }

    /**
     * @param array $data
     *
     * @return Standard
     */
    public function setRow(array $data)
    {
        try {
            // set random type if creating new product to avoid error
            if ('0' === $data['type']) {
                $data['type'] = ProductType::findAll()->current()->id;
            }

            return parent::setRow($data);
        } catch (\UnderflowException $exception) {
            return $this;
        }
    }

    public function save()
    {
        if ($this->blankProduct) {
            $blankModel = $this->blankProductModel->save();
            $this->arrData['id'] = $blankModel->id;

            if ($this->productDataChanged) {
                $this->getProductData()->pid = $blankModel->id;
                $this->getProductData()->save();
                $this->productDataChanged = false;
            }

            return $blankModel;
        }
        if ($this->productDataChanged) {
            $this->getProductData()->save();
            $this->productDataChanged = false;
        }

        return parent::save();
    }
}
