<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\Module;

class ModuleStockReport extends Module
{
    protected $strTemplate = 'mod_stockReport';

    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['iso_stockreport'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $arrProducts = [];
        $objProducts = \Database::getInstance()->prepare('SELECT p.*, t.name as type FROM tl_iso_product p INNER JOIN tl_iso_producttype t ON t.id = p.type WHERE p.published=1 AND p.shipping_exempt="" AND p.initialStock!="" AND stock IS NOT NULL ORDER BY category ASC')->execute();

        \System::loadLanguageFile('tl_reports');

        if ($objProducts->numRows < 1) {
            return false;
        }

        while ($objProducts->next()) {
            $category = 'category_'.$objProducts->type;
            if (!isset($arrProducts[$category])) {
                $arrProducts[$category]['type'] = 'category';
                $arrProducts[$category]['title'] = $objProducts->type;
            }

            $arrProducts[$objProducts->id] = $objProducts->row();
            $arrProducts[$objProducts->id]['stockPercent'] = '-';

            if ($objProducts->initialStock > 0 && '' !== $objProducts->initialStock) {
                $percent = floor($objProducts->stock * 100 / $objProducts->initialStock);

                $arrProducts[$objProducts->id]['stockPercent'] = $percent;

                switch ($percent) {
                    default:
                        $strClass = 'progress-bar-success';
                        break;
                    case $percent < 25:
                        $strClass = 'progress-bar-danger';
                        break;
                    case $percent < 50:
                        $strClass = 'progress-bar-warning';
                        break;
                    case $percent < 75:
                        $strClass = 'progress-bar-info';
                        break;
                }
                $arrProducts[$objProducts->id]['stockClass'] = $strClass;
            }
        }

        $this->Template->items = $arrProducts;
        $this->Template->id = 'stockReport';
    }
}