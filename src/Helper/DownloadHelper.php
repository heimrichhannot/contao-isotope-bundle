<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Helper;

use Contao\Controller;
use Contao\File;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\Request\Request;

class DownloadHelper
{
    public function addDownloadsFromProductDownloadsToTemplate($objTemplate)
    {
        $objTemplate->downloads = $this->getDownloadsFromProductDownloads($objTemplate->id);
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getDownloadsFromProductDownloads($id)
    {
        $container = System::getContainer();
        $framework = $container->get('contao.framework');

        $downloads = [];        // array for downloadfiles from db

        global $objPage;

        $downloadFiles = $framework->getAdapter(\Isotope\Model\Download::class)->findBy('pid', $id, ['order' => 'sorting ASC']);
        if (null === $downloadFiles) {
            return $downloads;
        }

        foreach ($downloadFiles as $downloadFile) {
            $objModel = $framework->getAdapter(\FilesModel::class)->findByUuid($downloadFile->singleSRC);
            if (null === $objModel) {
                if (!\Validator::isUuid($downloadFile->singleSRC)) {
                    return ['<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>'];
                }
            } elseif (is_file(TL_ROOT.'/'.$objModel->path)) {
                $objFile = new File($objModel->path);

                $file = Request::getGet('file', true);
                // Send the file to the browser and do not send a 404 header (see #4632)
                if ('' != $file && $file == $objFile->path) {
                    $framework->getAdapter(Controller::class)->sendFileToBrowser($file);
                }

                $arrMeta = $framework->getAdapter(Frontend::class)->getMetaData($objModel->meta, $objPage->language);
                if (empty($arrMeta)) {
                    if (null !== $objPage->rootFallbackLanguage) {
                        $arrMeta = $framework->getAdapter(Frontend::class)->getMetaData($objModel->meta, $objPage->rootFallbackLanguage);
                    }
                }

                $strHref = \Environment::get('request');
                // Remove an existing file parameter (see #5683)
                if (preg_match('/(&(amp;)?|\?)file=/', $strHref)) {
                    $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
                }
                $strHref .= ((\Config::get('disableAlias') || false !== strpos($strHref, '?')) ? '&amp;' : '?').'file='.\System::urlEncode($objFile->path);

                $download['id'] = $objModel->id;
                $download['uuid'] = $objModel->uuid;
                $download['name'] = $objModel->basename;
                $download['formedname'] = preg_replace(['/_/', '/.\w+$/'], [' ', ''], $objFile->basename);
                $download['title'] = specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename));
                $download['link'] = $arrMeta['title'];
                $download['filesize'] = \System::getReadableSize($objFile->filesize, 1);
                $download['icon'] = TL_ASSETS_URL.'assets/contao/images/'.$objFile->icon;
                $download['href'] = $strHref;
                $download['mime'] = $objFile->mime;
                $download['extension'] = $objFile->extension;
                $download['path'] = $objFile->dirname;
                $download['class'] = 'isotope-download isotope-download-file';

                // add thumbnail
                $thumbnails = [];
                foreach (StringUtil::deserialize($downloadFile->download_thumbnail, true) as $thumbnail) {
                    $thumbnails[] = $framework->getAdapter(FilesModel::class)->findByUuid($thumbnail);
                }
                $download['thumbnail'] = $thumbnails;

                // get width and height of download
                if (in_array($objFile->extension, ['jpg', 'jpeg', 'tiff', 'png'], true)) {
                    $size = getimagesize($objFile->path);
                    $download['size'] = sprintf($GLOBALS['TL_LANG']['MSC']['downloadSize'], $size[0], $size[1], $download['filesize']);
                }

                if ('pdf' == $objFile->extension) {
                    $download['size'] = sprintf($GLOBALS['TL_LANG']['MSC']['downloadSizePdf'], $download['name'], $download['filesize']);
                }

                $download['downloadTitle'] = $downloadFile->title;

                $objT = new \FrontendTemplate('isotope_download_from_attribute');
                $objT->setData((array) $download);
                $download['output'] = $objT->parse();

                $downloads[] = $download;
            }
        }

        return $downloads;
    }
}
