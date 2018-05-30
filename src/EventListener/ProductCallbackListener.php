<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\DataContainer;
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

    /**
     * @param $value
     * @param DataContainer $dc
     *
     * @return mixed
     */
    public function saveMetaFields($value, DataContainer $dc)
    {
        return $value;
    }
}
