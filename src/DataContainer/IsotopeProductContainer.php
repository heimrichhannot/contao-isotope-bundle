<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\DataContainer;

use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\DC_Table;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Image\ResizeConfiguration;
use Contao\StringUtil;
use Contao\Validator;
use DC_ProductData;
use Haste\Util\Format;
use Isotope\Model\ProductPrice;
use Isotope\Model\ProductType;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class IsotopeProductContainer
{
    /**
     * @var ImageFactoryInterface
     */
    private $imageFactory;

    /**
     * IsotopeProductContainer constructor.
     */
    public function __construct(ImageFactoryInterface $imageFactory)
    {
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param array                   $row   Current list element data
     * @param string                  $label
     * @param DC_ProductData|DC_Table $dc
     * @param array                   $args  Backend list arguments
     *
     * @return mixed
     */
    public function onLabelCallback($row, $label, $dc, $args)
    {
        foreach ($GLOBALS['TL_DCA'][$dc->table]['list']['label']['fields'] as $index => $field) {
            switch ($field) {
                // Add an image
                case 'images':
                    $images = StringUtil::deserialize($row['images']);
                    $args[$index] = '&nbsp;';

                    if (\is_array($images) && !empty($images)) {
                        foreach ($images as $image) {
                            $strImage = 'isotope/'.strtolower(substr($image['src'], 0, 1)).'/'.$image['src'];

                            try {
                                $this->createImageWithModalForList($strImage, $row['name'], $image['alt']);
                                break;
                            } catch (FileNotFoundException $e) {
                                continue;
                            }
                        }
                    }
                    break;
                case 'uploadedFiles':
                    $uploadedFiles = StringUtil::deserialize($row['uploadedFiles']);
                    if (!\is_array($uploadedFiles) || empty($uploadedFiles)) {
                        $args[$index] = '-';
                        break;
                    }
                    $image = $uploadedFiles[0];
                    if (!Validator::isUuid($image)) {
                        $args[$index] = '-';
                        break;
                    }
                    $image = FilesModel::findByUuid($image);
                    if (!$image) {
                        $args[$index] = '-';
                        break;
                    }
                    try {
                        $args[$index] = $this->createImageWithModalForList($image->path, $row['name'], $image->alt ?: '');
                        break;
                    } catch (FileNotFoundException $e) {
                        $args[$index] = '-';
                        break;
                    }
                    break;
                case 'name':
                    /** @var ProductType $productType */
                    if (0 == $row['pid'] && null !== ($productType = ProductType::findByPk($row['type'])) && $productType->hasVariants()) {
                        // Add a variants link
                        $args[$index] = sprintf('<a href="%s" title="%s">%s</a>', ampersand(Environment::get('request')).'&amp;id='.$row['id'], StringUtil::specialchars($GLOBALS['TL_LANG'][$dc->table]['showVariants']), $args[$index]);
                    }
                    break;
                case 'price':
                    /** @var ProductPrice $price */
                    $price = ProductPrice::findPrimaryByProductId($row['id']);

                    if ($price) {
                        /* @var \Isotope\Model\TaxClass $objTax */
                        try {
                            $objTax = $price->getRelated('tax_class');
                        } catch (\Exception $e) {
                            break;
                        }
                        $strTax = (null === $objTax ? '' : ' ('.$objTax->getName().')');

                        $args[$index] = $price->getValueForTier(1).$strTax;
                    }
                    break;

                case 'variantFields':
                    $attributes = [];
                    foreach ($GLOBALS['TL_DCA'][$dc->table]['list']['label']['variantFields'] as $variantField) {
                        $attributes[] = '<strong>'.Format::dcaLabel($dc->table, $variantField).':</strong>&nbsp;'.Format::dcaValue($dc->table, $variantField, $row[$variantField]);
                    }

                    $args[$index] = ($args[$index] ? $args[$index].'<br>' : '').implode(', ', $attributes);
                    break;
            }
        }

        return $args;
    }

    protected function createImageWithModalForList(string $imagePath, string $name, string $alt)
    {
        if (!is_file(TL_ROOT.'/'.$imagePath)) {
            throw new FileNotFoundException('Image file does not exist!');
        }

        $size = @getimagesize(TL_ROOT.'/'.$imagePath);

        $resizeImage = $this->imageFactory->create($imagePath, [50, 50, ResizeConfiguration::MODE_PROPORTIONAL]);

        return sprintf(
            '<a href="%s" onclick="Backend.openModalImage({\'width\':%s,\'title\':\'%s\',\'url\':\'%s\'});return false"><img src="%s" alt="%s" align="left"></a>',
            TL_FILES_URL.$imagePath,
            $size[0],
            str_replace("'", "\\'", $name),
            TL_FILES_URL.$imagePath,
            TL_ASSETS_URL.str_replace(TL_ROOT, '', $resizeImage->getPath()),
            $alt
        );
    }
}
