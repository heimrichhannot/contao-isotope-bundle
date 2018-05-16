<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\ConfigElementType\Delete;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\File;
use Contao\FilesModel;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\ReaderBundle\Backend\ReaderConfigElement;
use HeimrichHannot\ReaderBundle\ConfigElementType\Delete\DefaultDelete;
use HeimrichHannot\ReaderBundle\Item\ItemInterface;
use HeimrichHannot\ReaderBundle\Model\ReaderConfigElementModel;
use Isotope\Backend\Product\Permission;
use Isotope\Model\Download;

class IsotopeProductDelete extends DefaultDelete
{
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;

        parent::__construct($framework);
    }

    public function delete(ItemInterface $item, ReaderConfigElementModel $readerConfigElement)
    {
        if (in_array($item->getRawValue('id'), $this->framework->getAdapter(Permission::class)->getUndeletableIds(), true)) {
            return;
        }

        if ($readerConfigElement->addMemberGroups && !$this->checkPermission($readerConfigElement->memberGroups)) {
            return;
        }

        $readerConfig = System::getContainer()->get('huh.reader.reader-config-registry')->findByPk($readerConfigElement->pid);
        if (null === $readerConfig) {
            return;
        }

        $redirectParams = StringUtil::deserialize($readerConfigElement->redirectParams, true);

        $values = [];
        $columns = [];

        foreach ($redirectParams as $redirectParam) {
            if (ReaderConfigElement::REDIRECTION_PARAM_TYPE_FIELD_VALUE !== $redirectParam['parameterType'] || !System::getContainer()->get('huh.request')->hasGet($redirectParam['name'])) {
                continue;
            }
            $columns[] = $redirectParam['name'];
            $values[] = System::getContainer()->get('huh.request')->getGet($redirectParam['name']);
        }
        /** @var ProductModel $model */
        $product = System::getContainer()->get('huh.utils.model')->findOneModelInstanceBy($readerConfig->dataContainer, $columns, $values);
        if (null === $product) {
            return;
        }

        $isoDownloads = $this->framework->getAdapter(Download::class)->findBy('pid', $product->id);
        if (null !== $isoDownloads) {
            $this->deleteDownloads($isoDownloads);
        } else {
            $this->deleteProductImages($item);
        }

        // delete
        $product->delete();

        $this->redirectAfterDelete($readerConfigElement->deleteJumpTo);
    }

    /**
     * delete all downloads and files.
     *
     * @param \Contao\Model\Collection $downloads
     */
    protected function deleteDownloads(Collection $downloads)
    {
        /** @var Download $download */
        foreach ($downloads as $download) {
            /** @var FilesModel $filesModel */
            $filesModel = $this->framework->getAdapter(FilesModel::class)->findByUuid($download->singleSRC);
            if (null !== $filesModel) {
                $filesModel->delete();
            }
            $this->deleteFiles($download->getFiles());
            $download->delete();
        }
    }

    /**
     * delete images from tl_files.
     *
     * @param ItemInterface $item
     */
    protected function deleteProductImages(ItemInterface $item)
    {
        if (null !== $item->getRawValue('images')) {
            $arrImages = StringUtil::deserialize($item->getRawValue('images'));

            if (!is_array($arrImages) || empty($arrImages)) {
                return;
            }

            foreach ($arrImages as $image) {
                $strImage = 'isotope/'.strtolower(substr($image['src'], 0, 1)).'/'.$image['src'];

                if (!is_file(TL_ROOT.'/'.$strImage)) {
                    continue;
                }
                unlink(System::getContainer()->get('huh.utils.container')->getProjectDir().'/'.$strImage);
            }
        } elseif (null !== $item->getRawValue('uploadedFiles')) {
            $uploadedFiles = $item->getRawValue('uploadedFiles');
            if (is_array($upload = unserialize($uploadedFiles))) {
                $uploadedFiles = $upload[0];
            }
            if (\Validator::isUuid($uploadedFiles)) {
                /** @var FilesModel $imageFile */
                $imageFile = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByUuid($uploadedFiles);
                $file = new File($imageFile->path);
                $file->delete();
                $imageFile->delete();
            }
        }
    }

    /**
     * deletes all given files.
     *
     * @param array $files
     */
    protected function deleteFiles(array $files)
    {
        /** @var File $file */
        foreach ($files as $file) {
            $file->delete();
        }
    }
}
