<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Backend;

use Contao\FilesModel;
use Contao\Image\ResizeConfiguration;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Haste\Util\Format;
use Isotope\Model\Product;
use Isotope\Model\ProductPrice;
use Isotope\Model\ProductType;

class Backend
{
    public function getProductCreatorLabel($row, $label, $dc, $args)
    {
        $container = System::getContainer();
        $framework = $container->get('contao.framework');
        $product = $framework->getAdapter(Product::class)->findByPk($row['id']);

        foreach ($GLOBALS['TL_DCA'][$dc->table]['list']['label']['fields'] as $i => $field) {
            switch ($field) {
                // Add an image
                case 'images':
                    $arrImages = StringUtil::deserialize($product->images);
                    $args[$i] = '&nbsp;';

                    if (is_array($arrImages) && !empty($arrImages)) {
                        foreach ($arrImages as $image) {
                            $strImage = 'isotope/'.strtolower(substr($image['src'], 0, 1)).'/'.$image['src'];

                            if (!is_file(TL_ROOT.'/'.$strImage)) {
                                continue;
                            }

                            $size = @getimagesize(TL_ROOT.'/'.$strImage);

                            $resizeImage = $container->get('contao.image.image_factory')->create($strImage, [50, 50, ResizeConfiguration::MODE_PROPORTIONAL]);

                            $args[$i] = sprintf('<a href="%s" onclick="Backend.openModalImage({\'width\':%s,\'title\':\'%s\',\'url\':\'%s\'});return false"><img src="%s" alt="%s" align="left"></a>', TL_FILES_URL.$strImage, $size[0], str_replace("'", "\\'", $product->name), TL_FILES_URL.$strImage, TL_ASSETS_URL.str_replace(TL_ROOT, '', $resizeImage->getPath()), $image['alt']);
                            break;
                        }
                    }
                    break;
                case 'uploadedFiles':
                    if (is_array($uploadedFiles = unserialize($product->uploadedFiles))) {
                        $product->uploadedFiles = $uploadedFiles[0];
                    }
                    if (Validator::isUuid($product->uploadedFiles)) {
                        $image = $framework->getAdapter(FilesModel::class)->findByUuid($product->uploadedFiles);
                        $size = @getimagesize(TL_ROOT.'/'.$image->path);

                        if (!file_exists(TL_ROOT.'/'.$image->path)) {
                            $args[$i] = '-';
                            break;
                        }
                        $resizeImage = $container->get('contao.image.image_factory')->create($image->path, [50, 50, ResizeConfiguration::MODE_PROPORTIONAL]);

                        $args[$i] = sprintf('<a href="%s" onclick="Backend.openModalImage({\'width\':%s,\'title\':\'%s\',\'url\':\'%s\'});return false"><img src="%s" alt="%s" align="left"></a>', TL_FILES_URL.$image->path, $size[0], str_replace("'", "\\'", $product->name), TL_FILES_URL.$image->path, TL_ASSETS_URL.str_replace(TL_ROOT, '', $resizeImage->getPath()), $image->alt);
                    } else {
                        $args[$i] = '-';
                    }
                    break;
                case 'name':
                    $args[$i] = $product->name;
                    /** @var \Isotope\Model\ProductType $objProductType */
                    if (0 == $row['pid'] && null !== ($objProductType = $framework->getAdapter(ProductType::class)->findByPk($row['type'])) && $objProductType->hasVariants()) {
                        // Add a variants link
                        $args[$i] = sprintf('<a href="%s" title="%s">%s</a>', ampersand(\Environment::get('request')).'&amp;id='.$row['id'], StringUtil::specialchars($GLOBALS['TL_LANG'][$dc->table]['showVariants']), $args[$i]);
                    }
                    break;
                case 'price':
                    /** @var ProductPrice $objPrice */
                    $objPrice = $framework->getAdapter(ProductPrice::class)->findPrimaryByProductId($row['id']);

                    if (null !== $objPrice) {
                        /** @var \Isotope\Model\TaxClass $objTax */
                        $objTax = $objPrice->getRelated('tax_class');
                        $strTax = (null === $objTax ? '' : ' ('.$objTax->getName().')');

                        $args[$i] = $objPrice->getValueForTier(1).$strTax;
                    }
                    break;

                case 'variantFields':
                    $attributes = [];
                    foreach ($GLOBALS['TL_DCA'][$dc->table]['list']['label']['variantFields'] as $variantField) {
                        $attributes[] = '<strong>'.$framework->getAdapter(Format::class)->dcaLabel($dc->table, $variantField).':</strong>&nbsp;'.$framework->getAdapter(Format::class)->dcaValue($dc->table, $variantField, $product->$variantField);
                    }

                    $args[$i] = ($args[$i] ? $args[$i].'<br>' : '').implode(', ', $attributes);
                    break;
                case 'stock':
                    $args[$i] = $product->stock;
                    break;
                case 'initialStock':
                    $args[$i] = $product->initialStock;
                    break;
            }
        }

        return $args;
    }
}
