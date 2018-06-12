<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Helper;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FilesModel;
use Contao\StringUtil;
use HeimrichHannot\IsotopeBundle\Model\ProductDataModel;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use Isotope\Model\ProductType;

class ProductHelper
{
    // licence
    const ISO_LICENCE_FREE = 'free';
    const ISO_LICENCE_COPYRIGHT = 'copyright';
    const ISO_LICENCE_LOCKED = 'locked';
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function prepareExifDataForSave($strExifTag, $arrExifData)
    {
        switch ($strExifTag) {
            case \PHPExif\Exif::CREATION_DATE:
                $strValue = $this->prepareDateTimes($arrExifData);
                break;
            case \PHPExif\Exif::KEYWORDS:
                $strValue = $this->prepareKeywords($arrExifData);
                break;
            default:
                $strValue = null;
        }

        return $strValue;
    }

    public function getFileName($file, $size)
    {
        return str_replace('.'.$file->extension, $this->getFileSizeName($file, $size), ltrim($file->name, '_'));
    }

    public function getFilePath($file, $name)
    {
        return str_replace($file->name, $name, $file->path);
    }

    /**
     * @param $file object
     * @param $size array
     *
     * @return string
     */
    public function getFileSizeName($file, $size)
    {
        $suffix = '';
        if ($GLOBALS['TL_LANG']['MSC']['originalSize'] != $size['name']) {
            $suffix = '_'.$size['size'][0];
        }

        return $suffix.'.'.$file->extension;
    }

    /**
     * return all product groups that are defined as editable in module.
     *
     * @param $module object
     *
     * @return array
     */
    public function getEditableCategories($module)
    {
        $categories = [];

        if (!$module->iso_editableCategories) {
            return $categories;
        }

        foreach (StringUtil::deserialize($module->iso_editableCategories, true) as $cat) {
            $categories[$cat] = $this->framework->getAdapter(ProductType::class)->findByPk($cat)->name;
        }

        asort($categories);

        return $categories;
    }

    public function getLicenceTitle()
    {
        return [
            static::ISO_LICENCE_FREE,
            static::ISO_LICENCE_COPYRIGHT,
            static::ISO_LICENCE_LOCKED,
        ];
    }

    /**
     * @return array
     */
    public function getTags()
    {
        $options = [];
        if (null === ($tags = $this->framework->getAdapter(ProductDataModel::class)->findAll())) {
            return $options;
        }

        if (null === ($tags = $tags->fetchEach('tag'))) {
            return $options;
        }

        foreach ($tags as $tag) {
            $options = array_merge($options, StringUtil::deserialize($tag, true));
        }

        return $options;
    }

    public function getCopyrights()
    {
        return $this->framework->getAdapter(ProductModel::class)->getCopyrights();
    }

    /**
     * @param FilesModel $file
     *
     * @return mixed|string
     */
    public function getFileNameFromFile(FilesModel $file)
    {
        if ('mp3' == $file->extension) {
            $title = str_replace(['_', '.'], [' ', ' '], $file->name);
        } else {
            $title = str_replace(['_', '.'.$file->extension], [' ', ''], $file->name);
        }

        $title = $this->ucfirstOnSign('-', $title);
        $title = $this->ucfirstOnSign(' ', $title);

        return $title;
    }

    protected function prepareDateTimes($arrExifData)
    {
        $objCreationDate = $arrExifData[\PHPExif\Exif::CREATION_DATE];

        if (null === $objCreationDate) {
            return null;
        }

        return $objCreationDate->getTimestamp();
    }

    protected function prepareKeywords($arrExifData)
    {
        if (is_array($arrExifData[\PHPExif\Exif::KEYWORDS])) {
            $strKeywords = implode(', ', $arrExifData[\PHPExif\Exif::KEYWORDS]);
        }

        if (empty($strKeywords)) {
            return null;
        }

        return '<p>'.$strKeywords.'</p>';
    }

    /**
     * @param $sign
     * @param $value
     *
     * @return string
     */
    protected function ucfirstOnSign($sign, $value)
    {
        $split = explode($sign, $value);
        $result = [];
        foreach ($split as $part) {
            $result[] = ucfirst($part);
        }

        return implode($sign, $result);
    }
}
