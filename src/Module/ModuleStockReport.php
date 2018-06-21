<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\BackendTemplate;
use Contao\Database;
use Contao\Module;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use Patchwork\Utf8;

class ModuleStockReport extends Module
{
    protected $strTemplate = 'mod_stockReport';

    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['iso_stockreport'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $arrProducts = [];
        $result = Database::getInstance()->prepare('SELECT p.*, t.name as type FROM tl_iso_product p INNER JOIN tl_iso_producttype t ON t.id = p.type WHERE p.published=1 AND p.shipping_exempt="" AND p.initialStock!="" AND stock IS NOT NULL')->execute();

        System::loadLanguageFile('tl_reports');

        if ($result->numRows < 1) {
            return false;
        }

        while ($result->next()) {
            $product = ProductModel::findByIdOrAlias($result->id);
            $category = 'category_'.$product->type;
            if (!isset($arrProducts[$category])) {
                $arrProducts[$category]['type'] = 'category';
                $arrProducts[$category]['title'] = $result->type;
            }

            $arrProducts[$product->id] = $product->row();
            $arrProducts[$product->id]['stockPercent'] = '-';
            $arrProducts[$product->id]['stock'] = $product->stock;
            $arrProducts[$product->id]['initialStock'] = $product->initialStock;

            if ($product->initialStock > 0 && '' !== $product->initialStock) {
                $percent = floor($product->stock * 100 / $product->initialStock);

                $arrProducts[$product->id]['stockPercent'] = $percent;

                switch ($percent) {
                    default:
                        $strClass = 'badge-success';
                        break;
                    case $percent < 25:
                        $strClass = 'badge-danger';
                        break;
                    case $percent < 50:
                        $strClass = 'badge-warning';
                        break;
                    case $percent < 75:
                        $strClass = 'badge-info';
                        break;
                }
                $arrProducts[$product->id]['stockClass'] = $strClass;
            }
        }

        $this->Template->items = $arrProducts;
        $this->Template->id = 'stockReport';
    }
}
