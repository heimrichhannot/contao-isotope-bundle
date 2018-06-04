<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Database;
use Contao\Model\Collection;
use HeimrichHannot\IsotopeBundle\Model\ProductDataModel;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use Isotope\Interfaces\IsotopeProduct;

class ProductDataManager
{
    /**
     * @var array
     */
    protected $productDataFields;

    /**
     * @var ProductDataModel[]
     */
    protected $productDataModelCache = [];
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

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
//                    unset($field['sql']);
                    $field['save_callback'][] = ['huh.isotope.listener.callback.product', 'saveMetaFields'];
                    $field['load_callback'][] = ['huh.isotope.listener.callback.product', 'getMetaFieldValue'];
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
     * @param IsotopeProduct|ProductModel|int $product The product model or id
     *
     * @return ProductDataModel
     */
    public function getProductData($product)
    {
        $pid = is_numeric($product) ? (int) $product : (int) $product->id;

        if (array_key_exists($pid, $this->productDataModelCache)) {
            return $this->productDataModelCache[$pid];
        }

        $productData = ProductDataModel::findOneBy('pid', $pid);
        if (!$productData) {
            $productData = new ProductDataModel();
            $productData->pid = $pid;
            $productData->dateAdded = $productData->tstamp = time();
            $productData->syncWithProduct();
        } else {
            $this->productDataModelCache[$productData->pid] = $productData;
        }

        return $productData;
    }

    /**
     * Returns all product models bypassing findAll method from isotope TypeAgent.
     *
     * @return Collection|ProductModel[]
     */
    public function getAllProducts()
    {
        $table = ProductModel::getTable();
        /** @var Database $db */
        $db = $this->framework->createInstance(Database::class);
        $result = $db->query("SELECT * FROM $table");

        return $this->framework->getAdapter(Collection::class)->createFromDbResult($result, $table);
    }
}
