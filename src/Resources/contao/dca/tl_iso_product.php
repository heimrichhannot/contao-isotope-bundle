<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_iso_product'];

/**
 * Labels in Backend
 */
$arrDca['list']['label']['fields']         = ['images', 'uploadedFiles', 'name', 'sku', 'price', 'stock', 'initialStock', 'jumpTo']; // added stock and initialstock to product overview
$arrDca['list']['label']['label_callback'] = ['HeimrichHannot\IsotopeBundle\Backend\Backend', 'getProductCreatorLabel'];

//$arrDca['palettes']['default'] = str_replace('type', 'type,name,', $arrDca['palettes']['default']);

$arrDca['config']['onload_callback'][] = ['huh.isotope.listener.callback.product', 'updateRelevance'];
//$arrDca['config']['onload_callback'][] = ['huh.isotope.listener.callback.product', 'addMetaFields'];
//$arrDca['config']['oncreate_callback'][] = ['huh.isotope.listener.callback.product', 'saveMetaFields'];


// arrays are always copied by value (not by reference) in php
$arrDca['fields']['skipStockValidation']                                   = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipStockValidation'];
$arrDca['fields']['skipStockValidation']['attributes']                     = ['legend' => 'shipping_legend'];
$arrDca['fields']['skipStockEdit']                                         = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipStockEdit'];
$arrDca['fields']['skipStockEdit']['attributes']                           = ['legend' => 'shipping_legend'];
$arrDca['fields']['skipExemptionFromShippingWhenStockEmpty']               = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipExemptionFromShippingWhenStockEmpty'];
$arrDca['fields']['skipExemptionFromShippingWhenStockEmpty']['attributes'] = ['legend' => 'shipping_legend'];

if (\Contao\System::getContainer()->get('huh.utils.container')->isFrontend()) {
    $arrDca['fields']['type']['options_callback'] = ['huh.isotope.helper.product', 'getEditableCategories'];
}