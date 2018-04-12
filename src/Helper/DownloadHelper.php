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
        $container = System::getContainer();
        $framework = $container->get('contao.framework');

        $arrDownloads = [];        // array for downloadfiles from db
        $arrFiles = [];            // contains queryresults from db
        $strTable = 'tl_iso_download';    // name of download table

        global $objPage;

        $arrOptions = ['order' => 'sorting ASC'];

        $arrFiles = $framework->getAdapter(\Isotope\Model\Download::class)->findBy('pid', $objTemplate->id, $arrOptions);
        if (null === $arrFiles) {
            return $arrDownloads;
        }

        while ($arrFiles->next()) {
            $objModel = $framework->getAdapter(\FilesModel::class)->findByUuid($arrFiles->singleSRC);
            if (null === $objModel) {
                if (!\Validator::isUuid($arrFiles->singleSRC)) {
                    $objTemplate->text = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
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

                $objDownload = new \stdClass();
                $objDownload->id = $objModel->id;
                $objDownload->uuid = $objModel->uuid;
                $objDownload->name = $objFile->basename;
                $objDownload->formedname = preg_replace(['/_/', '/.\w+$/'], [' ', ''], $objFile->basename);
                $objDownload->title = specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename));
                $objDownload->link = $arrMeta['title'];
                $objDownload->filesize = \System::getReadableSize($objFile->filesize, 1);
                $objDownload->icon = TL_ASSETS_URL.'assets/contao/images/'.$objFile->icon;
                $objDownload->href = $strHref;
                $objDownload->mime = $objFile->mime;
                $objDownload->extension = $objFile->extension;
                $objDownload->path = $objFile->dirname;
                $objDownload->class = 'isotope-download isotope-download-file';

                // add thumbnail
                $thumbnails = [];
                foreach (StringUtil::deserialize($arrFiles->download_thumbnail, true) as $thumbnail) {
                    $thumbnails[] = $framework->getAdapter(FilesModel::class)->findByUuid($thumbnail);
                }
                $objDownload->thumbnail = $thumbnails;

                // get width and height of download
                if (in_array($objFile->extension, ['jpg', 'jpeg', 'tiff', 'png'], true)) {
                    $size = getimagesize($objFile->path);
                    $objDownload->size = sprintf($GLOBALS['TL_LANG']['MSC']['downloadSize'], $size[0], $size[1], $objDownload->filesize);
                }

                if ('pdf' == $objFile->extension) {
                    $objDownload->size = sprintf($GLOBALS['TL_LANG']['MSC']['downloadSizePdf'], $objDownload->name, $objDownload->filesize);
                }

                $objDownload->downloadTitle = $arrFiles->title;

                $objT = new \FrontendTemplate('isotope_download_from_attribute');
                $objT->setData((array) $objDownload);
                $objDownload->output = $objT->parse();

                $arrDownloads[] = $objDownload;
            }
        }
        $objTemplate->downloads = $arrDownloads;
    }
}
