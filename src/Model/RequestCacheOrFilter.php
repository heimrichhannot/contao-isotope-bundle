<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Model;

use Isotope\Model\Product;

/**
 * Isotope\Model\RequestCache represents an Isotope request cache model.
 *
 * @copyright  Isotope eCommerce Workgroup 2009-2012
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 */
class RequestCacheOrFilter
{
    /**
     * Generate query string for native filters.
     *
     * @param array $filters
     *
     * @return array
     */
    public function buildSqlFilters(array $filters)
    {
        $where = '';
        $arrWhere = [];
        $values = [];
        $groups = [];

        // Initiate native SQL filtering
        /** @var \Isotope\RequestCache\Filter $objFilter */
        foreach ($filters as $k => $objFilter) {
            if ($objFilter->hasGroup() && false !== $groups[$objFilter->getGroup()]) {
                if ($objFilter->isDynamicAttribute()) {
                    $groups[$objFilter->getGroup()] = false;
                } else {
                    $groups[$objFilter->getGroup()][] = $k;
                }
            } elseif (!$objFilter->hasGroup() && !$objFilter->isDynamicAttribute()) {
                $arrWhere[] = $objFilter->sqlWhere();
                $values[] = $objFilter->sqlValue();
                unset($filters[$k]);
            }
        }

        if (!empty($groups)) {
            foreach ($groups as $arrGroup) {
                $arrGroupWhere = [];

                // Skip dynamic attributes
                if (false === $arrGroup) {
                    continue;
                }

                foreach ($arrGroup as $k) {
                    $objFilter = $filters[$k];

                    $arrGroupWhere[] = $objFilter->sqlWhere();
                    $values[] = $objFilter->sqlValue();
                    unset($filters[$k]);
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

            $where = '
                (
                ('.$strTemp.")
                    OR $t.id IN (SELECT $t.pid FROM tl_iso_product AS $t WHERE $t.language='' AND ".implode(' AND ', $arrWhere).(BE_USER_LOGGED_IN === true ? '' : " AND $t.published='1' AND ($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time)").")
                    OR $t.pid IN (SELECT $t.id FROM tl_iso_product AS $t WHERE $t.language='' AND ".implode(' AND ', $arrWhere).(BE_USER_LOGGED_IN === true ? '' : " AND $t.published='1' AND ($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time)").')
                )
            ';

            $values = array_merge($values, $values, $values);
        }

        return [$filters, $where, $values];
    }
}
