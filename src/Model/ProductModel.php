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

/**
 * Class ProductModel.
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
class ProductModel extends Model
{
    protected static $strTable = 'tl_iso_product';
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

    public function __construct(Database\Result $objResult = null)
    {
        parent::__construct($objResult);
        $container = System::getContainer();
        $this->framework = $container->get('contao.framework');
        $this->productDataManager = $container->get('huh.isotope.manager.productdata');
    }

    public function __set($strKey, $varValue)
    {
        if (in_array($strKey, $this->getProductDataManager()->getProductDataFields(), true)) {
            $this->getProductData()->$strKey = $varValue;
            $this->productDataChanged = true;
        }
        parent::__set($strKey, $varValue);
    }

    public function __get($strKey)
    {
        if (in_array($strKey, $this->getProductDataManager()->getProductDataFields(), true)) {
            return $this->getProductData()->$strKey;
        }

        return parent::__get($strKey);
    }

    public function __isset($strKey)
    {
        if (in_array($strKey, $this->getProductDataManager()->getProductDataFields(), true)) {
            return true;
        }

        return parent::__isset($strKey);
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
        return $this->getProductDataManager()->getProductData($id)->stock;
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

    public function save()
    {
        if ($this->productDataChanged) {
            $this->getProductData()->save();
            $this->productDataChanged = false;
        }

        return parent::save();
    }
}
