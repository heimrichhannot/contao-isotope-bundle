<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Contao\Model;
use Contao\System;

class ProductCollectionItemModel extends Model
{
    protected static $strTable = 'tl_iso_product_collection_item';

    public function findByItem($id, array $options = [])
    {
        return System::getContainer()->get('huh.utils.model')->findModelInstancesBy(
            static::$strTable,
            [static::$strTable.'.product_id=?'],
            [$id],
            $options
        );
    }
}
