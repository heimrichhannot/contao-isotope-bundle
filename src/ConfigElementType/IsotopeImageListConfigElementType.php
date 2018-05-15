<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\ConfigElementType;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
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
        $image = null;
        $imageFile = null;
        $imageArray = $item->getRaw();
        $imageArray['imageTitle'] = $imageArray['name'];

        if (null !== $item->getRawValue('images')) {
            $imageField = 'images';
            $arrImages = StringUtil::deserialize($item->getRawValue('images'));

            if (!is_array($arrImages) || empty($arrImages)) {
                return;
            }

            foreach ($arrImages as $image) {
                $strImage = 'isotope/'.strtolower(substr($image['src'], 0, 1)).'/'.$image['src'];

                if (!is_file(TL_ROOT.'/'.$strImage)) {
                    continue;
                }
                $image = System::getContainer()->get('contao.image.image_factory')->create($strImage);
                if (null === $image
                    || !file_exists(System::getContainer()->get('huh.utils.container')->getProjectDir().'/'.$image->getPath())) {
                    return;
                }
                $imageArray[$imageField] = $image->getPath();
            }
        } elseif (null !== $item->getRawValue('uploadedFiles')) {
            $imageField = 'uploadedFiles';
            $uploadedFiles = $item->getRawValue('uploadedFiles');
            if (is_array($upload = unserialize($uploadedFiles))) {
                $uploadedFiles = $upload[0];
            }
            if (\Validator::isUuid($uploadedFiles)) {
                $imageFile = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByUuid($uploadedFiles);
                if (null === $imageFile
                    || !file_exists(System::getContainer()->get('huh.utils.container')->getProjectDir().'/'.$imageFile->path)) {
                    return;
                }
                $imageArray[$imageField] = $imageFile->path;
            }
        } else {
            return;
        }

        // Override the default image size
        if ('' !== $listConfigElement->imgSize) {
            $size = StringUtil::deserialize($listConfigElement->imgSize);

            if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2])) {
                $imageArray['size'] = $listConfigElement->imgSize;
            }
        }

        $templateData['isotopeImages'] = [];

        System::getContainer()->get('huh.utils.image')->addToTemplateData($imageField, 'published', $templateData['isotopeImages'], $imageArray, null, null, null, $imageFile);

        $item->setFormattedValue('isotopeImages', $templateData['isotopeImages']);
    }
}
