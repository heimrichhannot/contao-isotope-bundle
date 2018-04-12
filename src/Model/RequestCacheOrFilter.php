<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Isotope\Model\Product;
use Isotope\Model\RequestCache;

/**
 * Isotope\Model\RequestCache represents an Isotope request cache model.
 *
 * @copyright  Isotope eCommerce Workgroup 2009-2012
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 */
class RequestCacheOrFilter extends RequestCache
{
    /**
     * Generate query string for native filters.
     *
     * @param array $arrFilters
     *
     * @return array
     */
    public static function buildSqlFilters(array $arrFilters)
    {
        $strWhere = '';
        $arrWhere = [];
        $arrValues = [];
        $arrGroups = [];

        // Initiate native SQL filtering
        /** @var \Isotope\RequestCache\Filter $objFilter */
        foreach ($arrFilters as $k => $objFilter) {
            if ($objFilter->hasGroup() && false !== $arrGroups[$objFilter->getGroup()]) {
                if ($objFilter->isDynamicAttribute()) {
                    $arrGroups[$objFilter->getGroup()] = false;
                } else {
                    $arrGroups[$objFilter->getGroup()][] = $k;
                }
            } elseif (!$objFilter->hasGroup() && !$objFilter->isDynamicAttribute()) {
                $arrWhere[] = $objFilter->sqlWhere();
                $arrValues[] = $objFilter->sqlValue();
                unset($arrFilters[$k]);
            }
        }

        if (!empty($arrGroups)) {
            foreach ($arrGroups as $arrGroup) {
                $arrGroupWhere = [];

                // Skip dynamic attributes
                if (false === $arrGroup) {
                    continue;
                }

                foreach ($arrGroup as $k) {
                    $objFilter = $arrFilters[$k];

                    $arrGroupWhere[] = $objFilter->sqlWhere();
                    $arrValues[] = $objFilter->sqlValue();
                    unset($arrFilters[$k]);
                }

                $arrWhere[] = '('.implode(' OR ', $arrGroupWhere).')';
            }
        }

        if (!empty($arrWhere)) {
            $time = time();
            $t = Product::getTable();

            $strTemp = '';
            $arrTemp = $arrWhere;
            if (in_array('tl_iso_product.shipping_exempt = ?', $arrTemp, true)) {
                $strTemp = 'tl_iso_product.shipping_exempt = ? AND ';
                unset($arrTemp[array_search('tl_iso_product.shipping_exempt = ?', $arrTemp, true)]);
            }
            $strTemp .= '('.implode(' OR ', $arrTemp).')';

            $strWhere = '
                (
                ('.$strTemp.")
                    OR $t.id IN (SELECT $t.pid FROM tl_iso_product AS $t WHERE $t.language='' AND ".implode(' AND ', $arrWhere).(BE_USER_LOGGED_IN === true ? '' : " AND $t.published='1' AND ($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time)").")
                    OR $t.pid IN (SELECT $t.id FROM tl_iso_product AS $t WHERE $t.language='' AND ".implode(' AND ', $arrWhere).(BE_USER_LOGGED_IN === true ? '' : " AND $t.published='1' AND ($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time)").')
                )
            ';

            $arrValues = array_merge($arrValues, $arrValues, $arrValues);
        }

        return [$arrFilters, $strWhere, $arrValues];
    }
}
