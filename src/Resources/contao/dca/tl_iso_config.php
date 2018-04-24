<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_iso_config'];

/**
 * Palettes
 */
$arrDca['palettes']['default'] = str_replace('{analytics_legend}', '{stock_legend},skipSets,skipStockValidation,skipStockEdit,skipExemptionFromShippingWhenStockEmpty,stockIncreaseOrderStates;{analytics_legend}', $arrDca['palettes']['default']);

$arrDca['fields']['skipStockValidation'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_iso_config']['skipStockValidation'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$arrDca['fields']['skipStockEdit'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_iso_config']['skipStockEdit'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$arrDca['fields']['skipExemptionFromShippingWhenStockEmpty'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_iso_config']['skipExemptionFromShippingWhenStockEmpty'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$arrDca['fields']['skipSets'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_iso_config']['skipSets'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$arrDca['fields']['stockIncreaseOrderStates'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_iso_config']['stockIncreaseOrderStates'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => ['tl_iso_config_isotope_plus', 'getOrderStates'],
    'eval'             => ['chosen' => true, 'multiple' => true, 'tl_class' => 'w50'],
    'sql'              => "blob NULL",
];

class tl_iso_config_isotope_plus
{

    public static function getOrderStates()
    {
        $arrOptions = [];

        if (($objOrderStatus = \Isotope\Model\OrderStatus::findAll()) !== null) {
            while ($objOrderStatus->next()) {
                $arrOptions[$objOrderStatus->id] = $objOrderStatus->name;
            }
        }

        return $arrOptions;
    }

}