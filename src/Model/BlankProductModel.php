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

class BlankProductModel extends Model
{
    protected static $strTable = 'tl_iso_product';

    /**
     * @return array
     *
     * @todo
     */
    public function getCopyrights()
    {
        /** @var \Contao\Database\Result $copyrights */
        $copyrights = System::getContainer()->get('contao.framework')->createInstance(Database::class)->prepare("SELECT * FROM tl_iso_product_data WHERE copyright IS NOT NULL AND copyright != ''")->execute();

        if (null !== ($copyrights)) {
            return array_unique($copyrights->fetchEach('copyright'));
        }

        return [];
    }
}
