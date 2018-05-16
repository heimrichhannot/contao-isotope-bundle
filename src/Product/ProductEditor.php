<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Product;

use Contao\Controller;
use Contao\Dbafs;
use Contao\File;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Ghostscript\Transcoder;
use HeimrichHannot\Haste\Dca\General;
use HeimrichHannot\Haste\Util\FormSubmission;
use HeimrichHannot\HastePlus\Files;
use HeimrichHannot\IsotopeBundle\Backend\Callbacks;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\MultiFileUpload\FormMultiFileUpload;
use Isotope\Model\Download;

abstract class ProductEditor
{
    protected static $convertFileType = 'png';

    protected $productData = [];
    protected $exifData = [];
    protected $file;

    protected static $strTable = 'tl_iso_product';

    public function __construct($module, $submission, $dc)
    {
        $this->module = $module;
        $this->submission = $submission;
        $this->imageCount = count($submission->uploadedFiles);
        $this->originalName = $submission->name;
        $this->dc = $dc;
    }

    /**
     * @return bool
     */
    public function generateProduct()
    {
        if (empty($this->submission->uploadedFiles)) {
            return false;
        }

        // common data
        $this->prepareBasicData();

        $this->prepareDataFromModule();

        $this->prepareDataFromExifData();

        $this->prepareDataFromForm();

        $this->modifyData();

        $this->prepareTagData();

        // image data
        $this->createImageProduct();

        // delete submission since for all products an new model was created
        $this->submission->delete();

        return true;
    }

    /**
     * create download element for each set size of isotope product image.
     *
     * @param $id   int
     * @param $file object
     * @param $size array
     */
    public function createDownloadItem($product, $file, $size, $uploadFolder = null)
    {
        $container = System::getContainer();
        $framework = $container->get('contao.framework');
        if (!empty($size['size'])) {
            $name = $container->get('huh.isotope.helper.product')->getFileName($file, $size);
            $path = $container->get('huh.isotope.helper.product')->getFilePath($file, $name);
        } else {
            $path = $file->path;
        }

        if (!file_exists($path) && !empty($size['size'])) {
            $image = $container->get('contao.image.image_factory')->create($file->path, [$size['size'][0], $size['size'][1], $size['size'][2]], System::getContainer()->get('huh.utils.container')->getProjectDir().'/'.$path);
            if (null !== $image) {
                $path = $image->getPath();
            }
        }

        if (null === ($downloadFile = $framework->getAdapter(FilesModel::class)->findByPath($path))) {
            $path = str_replace(System::getContainer()->get('huh.utils.container')->getProjectDir().'/', '', urldecode($path));
            $downloadFile = $framework->getAdapter(Dbafs::class)->addResource($path);
        }

        $this->saveCopyrightForFile($downloadFile, $product);

        // create Isotope download
        $download = new Download();

        if ('pdf' == $file->extension) {
            $download->download_thumbnail = serialize([$this->getPDFThumbnail($file, $uploadFolder)]);
        } elseif (strpos(\Config::get('validImageTypes'), $file->extension)) {
            $download->download_thumbnail = serialize([$file->uuid]);
        }

        $download->pid = $product->id;
        $download->tstamp = time();
        $download->title = $size['name'];
        $download->singleSRC = $downloadFile->uuid;
        $download->published = 1;

        $download->save();
    }

    /**
     * @param $file    \FilesModel
     * @param $product ProductModel
     */
    public function saveCopyrightForFile($file, $product)
    {
        $file->licence = $product->licence;
        $file->addedBy = $product->addedBy;
        $file->copyright = $product->copyright;

        $file->save();
    }

    /**
     * move file to destination.
     *
     * @param $file   \Contao\FilesModel
     * @param $folder string
     */
    public function moveFile(FilesModel $file, $folder)
    {
        // create new File to enable moving the pdf to user folder
        $moveFile = new File($file->path);
//        $moveFile->close();
        $strTarget = $folder.'/'.$file->name;
        $strTarget = System::getContainer()->get('contao.framework')->getAdapter(Files::class)->getUniqueFileNameWithinTarget($strTarget, FormMultiFileUpload::UNIQID_PREFIX);

        // move file to upload folder
        $moveFile->renameTo($strTarget);
    }

    /**
     * @param $dc \DataContainer
     *
     * @return string
     */
    public function getUploadFolder($dc)
    {
        $uploadFolder = System::getContainer()->get('contao.framework')->getAdapter(Callbacks::class)->getUploadFolder($dc);

        if ($this->module->iso_useFieldDependendUploadFolder) {
            $uploadFolder .= '/'.$this->productData[$this->module->iso_fieldForUploadFolder];
        }

        return $uploadFolder;
    }

    /**
     * convert pdf to png and return only first page/image
     * delete the other png files.
     *
     * @param $uploadFolder string
     *
     * @return string name of preview file
     */
    public function getPreviewFromPdf($file, $uploadFolder)
    {
        $destinationFileName = 'preview-'.str_replace('.pdf', '', $file->name).'.'.static::$convertFileType;

        // ghostscript
        /** @var Transcoder $transcoder */
        $transcoder = System::getContainer()->get('contao.framework')->getAdapter(Transcoder::class)->create();

        $transcoder->toImage(TL_ROOT.DIRECTORY_SEPARATOR.$file->path, TL_ROOT.DIRECTORY_SEPARATOR.$uploadFolder.'/'.$destinationFileName);

        $search = str_replace('.'.static::$convertFileType, '', $destinationFileName);
        $files = preg_grep('~^'.$search.'.*\.'.static::$convertFileType.'$~', scandir(TL_ROOT.DIRECTORY_SEPARATOR.$uploadFolder));

        return reset($files);
    }

    /**
     * @return bool
     */
    protected function create()
    {
        if (empty($this->productData)) {
            return false;
        }

        $product = clone $this->submission;
        $product->mergeRow($this->productData);

        return $product->save();
    }

    /**
     * set basic values for product.
     */
    protected function prepareBasicData()
    {
        $this->productData['dateAdded'] = $this->submission->dateAdded ? $this->submission->dateAdded : time();

        $this->productData['tstamp'] = time();

        $this->productData['alias'] = $this->submission->alias ? $this->submission->alias : General::generateAlias('', $this->submission->id, 'tl_iso_product', $this->submission->name);

        $this->productData['sku'] = $this->productData['alias'];

        $this->productData['addedBy'] = \Contao\Config::get('iso_creatorFallbackMember');

        // add user reference to product
        if (FE_USER_LOGGED_IN) {
            $objUser = \FrontendUser::getInstance();
            $this->productData['addedBy'] = $objUser->id;
        }
    }

    /**
     * set productData from module configuration.
     */
    protected function prepareDataFromModule()
    {
        $pages = StringUtil::deserialize($this->module->orderPages, true);

        if (null !== $this->submission->orderPages) {
            foreach (StringUtil::deserialize($this->submission->orderPages, true) as $page) {
                $pages[] = $page;
            }
        }

        $this->productData['orderPages'] = serialize($pages);

        $this->setDataFromDefaultValues();
    }

    /**
     * map exif data according to module settings.
     */
    protected function prepareDataFromExifData()
    {
        $mappings = StringUtil::deserialize($this->module->iso_exifMapping, true);

        if (empty($mappings)) {
            return;
        }

        foreach ($mappings as $mapping) {
            $arrTableFields = explode('.', $mapping['tableField']);

            if (!empty($arrTableFields) && '' != ($strTableField = array_pop($arrTableFields))) {
                switch ($mapping['exifTag']) {
                    case \PHPExif\Exif::CREATION_DATE:
                        $strValue = System::getContainer()->get('huh.isotope.helper.product')->prepareExifDataForSave(\PHPExif\Exif::CREATION_DATE, $this->exifData);
                        break;
                    case \PHPExif\Exif::KEYWORDS:
                        $strValue = System::getContainer()->get('huh.isotope.helper.product')->prepareExifDataForSave(\PHPExif\Exif::KEYWORDS, $this->exifData);
                        break;
                    case 'custom':
                        $strValue = $this->exifData[$mapping['customTag']];
                        break;

                    default:
                        $strValue = $this->exifData[$mapping['exifTag']];
                        break;
                }

                // Hook : handle exif tags
                if (isset($GLOBALS['TL_HOOKS']['creatorProduct']['handleExifTags'])
                    && is_array($GLOBALS['TL_HOOKS']['creatorProduct']['handleExifTags'])) {
                    foreach ($GLOBALS['TL_HOOKS']['creatorProduct']['handleExifTags'] as $arrCallback) {
                        $objClass = System::getContainer()->get('contao.framework')->getAdapter(Controller::class)->importStatic($arrCallback[0]);
                        $strValue = $objClass->{$arrCallback[1]}($mapping['exifTag'], $mapping, $strValue);
                    }
                }

                if ($strValue) {
                    $this->productData[$strTableField] = $strValue;
                }
            }
        }
    }

    /**
     * set productData values from submission.
     */
    protected function prepareDataFromForm()
    {
        foreach (StringUtil::deserialize($this->module->formHybridEditable, true) as $value) {
            if ($this->productData[$value]) {
                continue;
            }
            $this->productData[$value] = $this->submission->{$value};
        }
    }

    /**
     * join fields from submission into tag field (has to be set in module).
     */
    protected function prepareTagData()
    {
        if (!$this->module->iso_useFieldsForTags) {
            return;
        }

        $data = $this->productData;
        $tags = [];

        foreach (StringUtil::deserialize($this->module->iso_tagFields, true) as $tagValueField) {
            //			if ($tagValueField == 'type') {
            //				$data[$tagValueField] = ProductType::findByPk($this->submission->type)->name;
            //			}

            if ('' == $data[$tagValueField]) {
                continue;
            }

            $tags[] = FormSubmission::prepareSpecialValueForPrint($data[$tagValueField], $GLOBALS['TL_DCA']['tl_iso_product']['fields'][$tagValueField], 'tl_iso_product', $this->dc);
        }

        // add tags from form-field
        $tags = array_merge(StringUtil::deserialize($this->submission->{$this->module->iso_tagField}, true), $tags);

        // Hook : modify the product data
        if (isset($GLOBALS['TL_HOOKS']['creatorProduct']['modifyTagData']) && is_array($GLOBALS['TL_HOOKS']['creatorProduct']['modifyTagData'])) {
            foreach ($GLOBALS['TL_HOOKS']['creatorProduct']['modifyTagData'] as $arrCallback) {
                $objClass = \Controller::importStatic($arrCallback[0]);
                $tags = $objClass->{$arrCallback[1]}($tags, $this);
            }
        }

        // add tag-array to field
        $this->productData[$this->module->iso_tagField] = serialize($tags);
    }

    /**
     * hook to manipulate values before image product is created.
     *
     * $this->module object
     * $this->productData array
     * $this-submission object
     */
    protected function modifyData()
    {
        // Hook : modify the product data
        if (isset($GLOBALS['TL_HOOKS']['editProduct_modifyData']) && is_array($GLOBALS['TL_HOOKS']['editProduct_modifyData'])) {
            foreach ($GLOBALS['TL_HOOKS']['editProduct_modifyData'] as $arrCallback) {
                $objClass = \Controller::importStatic($arrCallback[0]);
                list($this->module, $this->productData, $this->submission) = $objClass->{$arrCallback[1]}($this->module, $this->productData, $this->submission);
            }
        }
    }

    /**
     * global objFile is set when file exists.
     *
     * @param $uuid
     *
     * @return bool
     */
    protected function checkFile($uuid)
    {
        if (!\Validator::isUuid($uuid) || null === ($file = \Contao\FilesModel::findByUuid($uuid)) || !file_exists($file->path)) {
            return false;
        }

        $this->file = $file;

        return true;
    }

    /**
     * set productData that was set default in module configuration.
     */
    protected function setDataFromDefaultValues()
    {
        if (!$this->module->formHybridAddDefaultValues) {
            return;
        }

        $dcaFields = System::getContainer()->get('huh.utils.dca')->getFields(static::$strTable);
        $defaultValues = StringUtil::deserialize($this->module->formHybridDefaultValues, true);

        foreach ($defaultValues as $value) {
            if (!in_array($value['field'], $dcaFields, true)) {
                continue;
            }

            $this->productData[$value['field']] = $value['value'];
        }
    }

    /**
     * delete all download items for a product before adding new ones.
     *
     * @param $id
     */
    protected function cleanDownloadItems($id)
    {
        if (null !== ($productDownloads = Download::findBy('pid', $id))) {
            // clean downloads before adding new ones
            while ($productDownloads->next()) {
                $productDownloads->delete();
            }
        }
    }

    /**
     * @param $index int
     *
     * @return array
     */
    protected function getOriginalImageSize($index = null)
    {
        $suffix = '';

        if (!$this->exifData['width'] && !$this->exifData['height']) {
            $orginalSize = getimagesize($this->file->path);

            $this->exifData['width'] = $orginalSize[0];
            $this->exifData['height'] = $orginalSize[1];
        }

        if ($index) {
            $suffix = ' '.($index + 1);
        }

        // add original image to download items
        return [
            'size' => [
                $this->exifData['width'],
                $this->exifData['height'],
                'center-center',
            ],
            'name' => $GLOBALS['TL_LANG']['MSC']['originalSize'].$suffix,
        ];
    }

    protected function getPDFThumbnail($file, $uploadFolder = null)
    {
        if (null === $uploadFolder) {
            $uploadFolder = $this->getUploadFolder($this->dc);
        }

        $completePath = $uploadFolder.'/'.$this->getPreviewFromPdf($file, $uploadFolder);
        if (file_exists($completePath)) {
            $completePath = System::getContainer()->get('contao.framework')->getAdapter(Dbafs::class)->addResource(urldecode($completePath));
        }

        if ($completePath->uuid) {
            return $completePath->uuid;
        }

        return System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByPath($completePath)->uuid;
    }

    /**
     * @param $product ProductModel
     */
    protected function createDownloadItemsFromUploadedDownloadFiles($product)
    {
        $downloadUploads = StringUtil::deserialize($product->uploadedDownloadFiles, true);

        if (empty($downloadUploads)) {
            return;
        }

        foreach ($downloadUploads as $downloadUpload) {
            $file = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByUuid($downloadUpload);

            $this->moveFile($file, $this->getUploadFolder($this->dc));

            $size = [
                'name' => sprintf($GLOBALS['TL_LANG']['MSC']['downloadItem'], System::getContainer()->get('huh.isotope.helper.product')->getFileNameFromFile($file)),
            ];

            $this->createDownloadItem($product, $file, $size);
        }
    }

    /**
     * @param $id int
     */
    protected function createDownloadItemsForSizes($id, $index = null)
    {
        $suffix = '';

        if ($index) {
            $suffix = ' '.($index + 1);
        }

        foreach (StringUtil::deserialize($this->module->iso_imageSizes, true) as $size) {
            $size['name'] = $size['name'].$suffix;
            $this->createDownloadItem($id, $this->file, $size);
        }
    }

    abstract protected function createImageProduct();

    abstract protected function getExifData();

    abstract protected function prepareProductImages($uuid);

    abstract protected function createDownloadItemsFromProductImage($product);

    abstract protected function afterCreate($product);
}
