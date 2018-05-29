<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\DataContainer;
use DC_ProductData;
use HeimrichHannot\IsotopeBundle\Model\ProductDataModel;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Container\ContainerUtil;
use Isotope\Model\Product;

class ProductCallbackListener
{
    /**
     * @var array
     */
    protected $metaFields;
    /**
     * @var ContainerUtil
     */
    private $containerUtil;
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;
    /**
     * @var Request
     */
    private $request;

    public function __construct(ContaoFrameworkInterface $framework, Request $request, ContainerUtil $containerUtil)
    {
        $this->containerUtil = $containerUtil;
        $this->framework = $framework;
        $this->request = $request;
    }

    /**
     * @param DataContainer $dc
     */
    public function updateRelevance(DataContainer $dc)
    {
        if ($this->containerUtil->isBackend()) {
            return;
        }

        /** @var Product $product */
        if (null !== ($product = $this->framework->getAdapter(Product::class)->findBy('sku', $this->request->getGet('auto_item')))) {
            ++$product->relevance;
            $product->save();
        }
    }

    public function addMetaFields(DC_ProductData $dc)
    {
        $GLOBALS['TL_DCA'][Product::getTable()]['fields'] = array_merge(
            $GLOBALS['TL_DCA'][Product::getTable()]['fields'],
            $this->getMetaFields()
        );
    }

    public function saveMetaFields()
    {
    }

    protected function getMetaFields(bool $useCache = true)
    {
        if (!$this->metaFields) {
            $table = ProductDataModel::getTable();
            Controller::loadDataContainer($table);
            $fields = $GLOBALS['TL_DCA'][$table]['fields'];
            $metaFields = [];
            foreach ($fields as $key => $field) {
                if ('inventory_legend' === $field['attributes']['legend']) {
                    unset($field['sql']);
                    $metaFields[$key] = $field;
                }
            }
            $this->metaFields = $metaFields;
        }

        return $this->metaFields;
    }
}
