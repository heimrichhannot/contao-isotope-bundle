<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Backend;

use Contao\BackendUser;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\System;
use Isotope\Frontend\ProductAction\Registry;

class Callbacks
{
    protected static $strProductTable = 'tl_iso_product';

    /**
     * option callback.
     *
     * @return array
     */
    public function getProductTableFieldsAsOptions()
    {
        $arrOptions = [];

        \Controller::loadDataContainer(static::$strProductTable);

        $arrFields = $GLOBALS['TL_DCA'][static::$strProductTable]['fields'];

        if (!is_array($arrFields) || empty($arrFields)) {
            return $arrOptions;
        }

        foreach ($arrFields as $strField => $arrData) {
            $arrOptions[static::$strProductTable.'.'.$strField] = $strField;
        }

        asort($arrOptions);

        return $arrOptions;
    }

    /**
     * option callback.
     *
     * @return array
     */
    public function getDefaultValueFields()
    {
        return System::getContainer()->get('huh.utils.dca')->getFields(static::$strProductTable);
    }

    /**
     * upload path callback.
     *
     * @return string
     */
    public function getUploadFolder(DataContainer $dc)
    {
        if (System::getContainer()->get('huh.utils.container')->isFrontend()) {
            $folder = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByUuid($dc->objModule->iso_uploadFolder)->path;

            if (FE_USER_LOGGED_IN) {
                if (null === ($user = System::getContainer()->get('contao.framework')->createInstance(FrontendUser::class))) {
                    return $folder;
                }

                return $folder.'/'.$user->username;
            }

            return $folder;
        }
        $folder = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByUuid(\Config::get('iso_productFolderFallback'))->path;

        if (null === ($user = System::getContainer()->get('contao.framework')->createInstance(BackendUser::class))) {
            return $folder;
        }

        return $folder.'/'.$user->username;
    }

    public function getButtons()
    {
        $arrOptions['downloadSingleProduct'] = $GLOBALS['TL_LANG']['MSC']['buttonLabel']['downloadSingleProduct'];

        foreach (Registry::all() as $action) {
            $arrOptions[$action->getName()] = $action->getLabel();
        }

        return $arrOptions;
    }
}
