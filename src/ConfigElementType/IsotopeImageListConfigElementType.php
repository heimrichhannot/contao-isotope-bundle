<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\ConfigElementType;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\ListBundle\ConfigElementType\ConfigElementType;
use HeimrichHannot\ListBundle\Item\ItemInterface;
use HeimrichHannot\ListBundle\Model\ListConfigElementModel;

class IsotopeImageListConfigElementType implements ConfigElementType
{
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function addToItemData(ItemInterface $item, ListConfigElementModel $listConfigElement)
    {
        $product = $this->framework->getAdapter(ProductModel::class)->findById($item->getFormattedValue('id'));

        $data = [];
        $data['name'] = $product->name;
        $data['images'] = $product->images;
        $data['src'] = $product->src;
        $data['uploadedFiles'] = $product->uploadedFiles;
        $data['size'] = $product->size;

        $templateData['isotopeImages'] = [];
        System::getContainer()->get('huh.isotope.manager')->addImageToTemplateData($data, $listConfigElement->imgSize, $templateData, 'isotopeImages');
        if (0 < $templateData['isotopeImages']['picture']['img']['width'] && 0 < $templateData['isotopeImages']['picture']['img']['height']) {
            $item->setFormattedValue('isotopeImages', $templateData['isotopeImages']);
        }
    }
}
