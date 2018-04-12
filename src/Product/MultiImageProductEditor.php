<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Product;

use Contao\Dbafs;
use Contao\FilesModel;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use Isotope\Backend\Product\Category;
use Isotope\Backend\Product\Price;

class MultiImageProductEditor extends ProductEditor
{
    /**
     * @return bool
     */
    protected function createImageProduct()
    {
        if ($this->checkFiles($this->submission->uploadedFiles)) {
            $this->prepareProductImages($this->submission->uploadedFiles);

            $product = $this->create();

            $this->afterCreate($product);

            $this->createDownloadItemsFromProductImage($product);

            $this->createDownloadItemsFromUploadedDownloadFiles($product);

            return true;
        }

        return false;
    }

    /**
     * @param $files array
     *
     * @return bool
     */
    protected function checkFiles($files)
    {
        $filesLegit = true;
        foreach ($files as $file) {
            $filesLegit = $this->checkFile($file);
        }

        return $filesLegit;
    }

    // TODO store exif data for multiple files
    protected function getExifData()
    {
    }

    /**
     * @param $uuids array
     */
    protected function prepareProductImages($uuids)
    {
        foreach ($uuids as $key => $upload) {
            $file = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByUuid($upload);

            $this->moveFile($file, $this->getUploadFolder($this->dc));

            if ('pdf' != strtolower($file->extension)) {
                continue;
            }

            $this->productData['uploadedFiles'][$key] = $this->preparePdfPreview($file);
            $this->productData['isPdfProduct'] = true;
        }

        unset($GLOBALS['TL_DCA']['tl_iso_product']['config']['onsubmit_callback']['multifileupload_moveFiles']);
    }

    protected function preparePdfPreview($file)
    {
        // copy original pdf to user folder to keep it as download element
        $uploadFolder = $this->getUploadFolder($this->dc);
        $this->moveFile($file, $uploadFolder);

        $this->productData['downloadPdf'][] = $file;

        $completePath = $uploadFolder.'/'.$this->getPreviewFromPdf($file, $uploadFolder);

        // replace $this->file with the preview image of the pdf
        if (file_exists($completePath)) {
            return System::getContainer()->get('contao.framework')->getAdapter(Dbafs::class)->addResource(urldecode($completePath))->uuid;
        }
    }

    /**
     * @param $product ProductModel
     *
     * @return bool
     */
    protected function createDownloadItemsFromProductImage($product)
    {
        if (!$this->module->iso_useUploadsAsDownload) {
            return false;
        }

        if ($this->productData['isPdfProduct'] && !empty($this->productData['downloadPdf'])) {
            foreach ($this->productData['downloadPdf'] as $pdf) {
                $size = ['name' => sprintf($GLOBALS['TL_LANG']['MSC']['downloadPdfItem'], $pdf->name)];
                $this->createDownloadItem($product, $pdf, $size);
            }

            return true;
        }

        foreach ($this->submission->uploadedFiles as $key => $value) {
            $size = $this->getOriginalImageSize($key);
            $this->createDownloadItem($product, $this->file, $size);

            if (!$this->module->iso_addImageSizes) {
                continue;
            }

            $this->createDownloadItemsForSizes($product, $key);
        }

        return true;
    }

    /**
     * save price and category for product.
     *
     * @param $product
     */
    protected function afterCreate($product)
    {
        // set intId to save category and price on correct id
        $this->dc->intId = $product->id;

        // add product categories to isotope category table
        Category::save(deserialize($this->module->orderPages, true), $this->dc);

        // add price to product and isotope price table
        Price::save(['value' => '0.00', 'unit' => 0], $this->dc);

        // clear product cache
        \Isotope\Backend::truncateProductCache();
    }
}
