<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Contao\Database;
use Contao\Model;
use Contao\System;

class ProductModel extends Model
{
    protected static $strTable = 'tl_iso_product';

    public function getCopyrights()
    {
        if (null !== ($copyrights = System::getContainer()->get('contao.framework')->createInstance(Database::class)->prepare("SELECT * FROM tl_iso_product WHERE copyright IS NOT NULL AND copyright != ''")->execute())) {
            return array_unique($copyrights->fetchEach('copyright'));
        }

        return [];
    }
}
