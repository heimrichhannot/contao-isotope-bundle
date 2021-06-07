<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\ConfigElementType;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\ReaderBundle\ConfigElementType\ConfigElementType;
use HeimrichHannot\ReaderBundle\Item\ItemInterface;
use HeimrichHannot\ReaderBundle\Model\ReaderConfigElementModel;

class IsotopeImageReaderConfigElementType implements ConfigElementType
{
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function addToItemData(ItemInterface $item, ReaderConfigElementModel $readerConfigElementModel)
    {
        $templateData['isotopeImages'] = [];
        $product = $this->framework->getAdapter(ProductModel::class)->findByPk($item->id);
        if (null === $product) {
            $item->setFormattedValue('isotopeImages', $templateData['isotopeImages']);

            return;
        }

        $data = [];
        $data['name'] = $product->name;
        $data['images'] = $product->images;
        $data['src'] = $product->src;
        $data['uploadedFiles'] = $product->uploadedFiles;
        $data['size'] = $product->size;

        System::getContainer()->get('huh.isotope.manager')->addImageToTemplateData($data, $readerConfigElementModel->imgSize, $templateData, 'isotopeImages');
        $item->setFormattedValue('isotopeImages', $templateData['isotopeImages']);
    }
}
