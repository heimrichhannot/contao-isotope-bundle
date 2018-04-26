<?php

$dca = &$GLOBALS['TL_DCA']['tl_iso_product_collection_item'];

/**
 * Fields
 */
$dca['fields']['setQuantity'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_iso_product_collection']['setQuantity'],
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit'],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$dca['fields']['bookingStart'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_iso_product_collection_item']['bookingStart'],
	'inputType' => 'text',
	'eval'      => ['tl_class' => 'w50', 'rgxp' => 'date'],
	'sql'       => "varchar(16) NOT NULL default ''",
];

$dca['fields']['bookingStop'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_iso_product_collection_item']['bookingStop'],
	'inputType' => 'text',
	'eval'      => ['tl_class' => 'w50', 'rgxp' => 'date'],
	'sql'       => "varchar(16) NOT NULL default ''",
];
