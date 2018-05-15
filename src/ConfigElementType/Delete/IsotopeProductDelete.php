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
