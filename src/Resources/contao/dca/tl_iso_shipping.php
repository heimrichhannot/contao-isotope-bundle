<?php

$dca = &$GLOBALS['TL_DCA']['tl_iso_shipping'];

$dca['palettes']['flat']  = str_replace('product_types_condition', 'product_types_condition,skipProducts', $dca['palettes']['flat']);

$dca['fields']['skipProducts'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_iso_shipping']['skipProducts'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => ['HeimrichHannot\IsotopeBundle\Backend\Callbacks', 'getProductsByType'],
    'eval'             => ['multiple' => true, 'size' => 8, 'chosen' => true, 'tl_class' => 'clr w50 w50h'],
    'sql'              => "blob NULL",
];