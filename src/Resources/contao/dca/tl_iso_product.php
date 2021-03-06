<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_iso_product'];


/**
 * Labels in Backend
 */
$arrDca['list']['label']['fields']         = ['images', 'uploadedFiles', 'name', 'sku', 'price', 'stock', 'initialStock', 'jumpTo']; // added stock and initialstock to product overview
$arrDca['list']['label']['label_callback'] = [
    \HeimrichHannot\IsotopeBundle\DataContainer\IsotopeProductContainer::class, 'onLabelCallback'
];

//$arrDca['palettes']['default'] = str_replace('type', 'type,name,', $arrDca['palettes']['default']);

$arrDca['config']['onload_callback'][] = ['huh.isotope.listener.callback.product', 'updateRelevance'];

if (\Contao\System::getContainer()->get('huh.utils.container')->isFrontend()) {
    $arrDca['fields']['type']['options_callback'] = ['huh.isotope.helper.product', 'getEditableCategories'];
}