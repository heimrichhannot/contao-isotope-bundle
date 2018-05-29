<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_iso_product'];

/**
 * Labels in Backend
 */
$arrDca['list']['label']['fields']         =
	['images', 'uploadedFiles', 'name', 'sku', 'price', 'stock', 'initialStock', 'jumpTo']; // added stock and initialstock to product overview
$arrDca['list']['label']['label_callback'] = ['HeimrichHannot\IsotopeBundle\Backend\Backend', 'getProductCreatorLabel'];

//$arrDca['palettes']['default'] = str_replace('type', 'type,name,', $arrDca['palettes']['default']);

$arrDca['config']['onload_callback'][] = ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'updateRelevance'];

/**
 * Fields
 */
$arrDca['fields']['shipping_exempt']['attributes']['fe_filter'] = true;

$arrDca['fields']['initialStock'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['initialStock'],
	'inputType'  => 'text',
	'eval'       => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'digit'],
	'attributes' => ['legend' => 'inventory_legend'],
	'sql'        => "varchar(255) NOT NULL default ''",
];

$arrDca['fields']['stock'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['stock'],
	'inputType'  => 'text',
	'eval'       => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'digit'],
	'attributes' => ['legend' => 'inventory_legend', 'fe_sorting' => true],
	'sql'        => "varchar(255) NOT NULL default ''",
];

$arrDca['fields']['setQuantity'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['setQuantity'],
	'inputType'  => 'text',
	'eval'       => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'digit'],
	'attributes' => ['legend' => 'inventory_legend', 'fe_sorting' => true],
	'sql'        => "varchar(255) NOT NULL default ''",
];

$arrDca['fields']['releaseDate'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['releaseDate'],
	'exclude'    => true,
	'inputType'  => 'text',
	'default'    => time(),
	'eval'       => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
	'attributes' => ['legend' => 'publish_legend', 'fe_sorting' => true],
	'sql'        => "varchar(10) NOT NULL default ''",
];

$arrDca['fields']['maxOrderSize'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['maxOrderSize'],
	'inputType'  => 'text',
	'eval'       => ['tl_class' => 'w50', 'rgxp' => 'digit'],
	'attributes' => ['legend' => 'inventory_legend'],
	'sql'        => "varchar(255) NOT NULL default ''",
];

$arrDca['fields']['overrideStockShopConfig'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['overrideStockShopConfig'],
	'exclude'    => true,
	'inputType'  => 'checkbox',
	'eval'       => ['tl_class' => 'w50'],
	'attributes' => ['legend' => 'shipping_legend'],
	'sql'        => "char(1) NOT NULL default ''",
];

$arrDca['fields']['jumpTo'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['jumpTo'],
	'exclude'    => true,
	'inputType'  => 'pageTree',
	'foreignKey' => 'tl_page.title',
	'eval'       => ['fieldType' => 'radio'],
	'sql'        => "int(10) unsigned NOT NULL default '0'",
	'attributes' => ['legend' => 'general_legend'],
	'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
];

$arrDca['fields']['addedBy'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['addedBy'],
	'inputType'  => 'select',
	'exclude'    => true,
	'search'     => true,
	'default'    => FE_USER_LOGGED_IN ? \Contao\FrontendUser::getInstance()->id : \Contao\BackendUser::getInstance()->id,
	'foreignKey' => 'tl_member.username',
	'eval'       => ['doNotCopy' => true, 'mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
	'relation'   => ['type' => 'hasOne', 'load' => 'eager'],
	'attributes' => ['fe_sorting' => true, 'fe_search' => true],
	'sql'        => "int(10) unsigned NOT NULL default '0'",
];

$arrDca['fields']['uploadedFiles'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['uploadedFiles'],
	'exclude'    => true,
	'inputType'  => 'multifileupload',
	'eval'       => [
		'tl_class'           => 'clr',
		'extensions'         => \Config::get('validImageTypes'),
		'filesOnly'          => true,
		'fieldType'          => 'checkbox',
		'maxImageWidth'      => \Config::get('gdMaxImgWidth'),
		'maxImageHeight'     => \Config::get('gdMaxImgHeight'),
		'skipPrepareForSave' => true,
		'uploadFolder'       => ['HeimrichHannot\IsotopeBundle\Backend\Callbacks', 'getUploadFolder'],
		'addRemoveLinks'     => true,
		'maxFiles'           => 15,
		'multipleFiles'      => true,
		'maxUploadSize'      => \Config::get('maxFileSize'),
		'mandatory'          => true,
	],
	'attributes' => ['legend' => 'media_legend'],
	
	'sql' => "blob NULL",
];

$arrDca['fields']['uploadedDownloadFiles'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['uploadedDownloadFiles'],
	'exclude'    => true,
	'inputType'  => 'multifileupload',
	'eval'       => [
		'tl_class'           => 'clr',
		'extensions'         => \Config::get('uploadTypes'),
		'filesOnly'          => true,
		'fieldType'          => 'checkbox',
		'skipPrepareForSave' => true,
		'uploadFolder'       => ['HeimrichHannot\IsotopeBundle\Backend\Callbacks', 'getUploadFolder'],
		'addRemoveLinks'     => true,
		'maxFiles'           => 15,
		'multipleFiles'      => true,
		'maxUploadSize'      => \Config::get('maxFileSize'),
	],
	'attributes' => ['legend' => 'media_legend'],
	
	'sql' => "blob NULL",
];

$arrDca['fields']['tag'] = [
	'label'            => &$GLOBALS['TL_LANG']['tl_iso_product']['tag'],
	'exclude'          => true,
	'search'           => true,
	'sorting'          => true,
	'inputType'        => 'tagsinput',
	'options_callback' => ['HeimrichHannot\IsotopeBundle\Helper\ProductHelper', 'getTags'],
	'eval'             => [
		'tl_class'       => 'long clr autoheight',
		'multiple'       => true,
		'freeInput'      => true,
		'trimValue'      => true,
		'decodeEntities' => true,
		'helpwizard'     => true,
		'highlight'      => true,
	],
	'attributes'       => ['legend' => 'general_legend', 'multilingual' => true, 'fixed' => true, 'fe_sorting' => true, 'fe_search' => true],
	'sql'              => "blob NULL",
];

$arrDca['fields']['licence'] = [
	'label'            => &$GLOBALS['TL_LANG']['tl_iso_product']['licence'],
	'exclude'          => true,
	'search'           => true,
	'sorting'          => true,
	'inputType'        => 'select',
	'reference'        => &$GLOBALS['TL_LANG']['tl_iso_product']['licence'],
	'options_callback' => ['\HeimrichHannot\IsotopeBundle\Helper\ProductHelper', 'getLicenceTitle'],
	'eval'             => ['mandatory' => true, 'tl_class' => 'clr w50', 'includeBlankOption' => true],
	'attributes'       => ['legend' => 'general_legend', 'fe_sorting' => true, 'fe_search' => true],
	'sql'              => "varchar(255) NOT NULL default ''",
];

$arrDca['fields']['createMultiImageProduct'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['createMultiImageProduct'],
	'exclude'    => true,
	'inputType'  => 'checkbox',
	'eval'       => ['tl_class' => 'w50'],
	'attributes' => ['legend' => 'shipping_legend'],
	'sql'        => "char(1) NOT NULL default ''",
];

$arrDca['fields']['downloadCount'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_iso_product']['downloadCount'],
	'inputType' => 'text',
	'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit'],
	'sql'       => "int(10) unsigned NOT NULL",
];

$arrDca['fields']['relevance'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_iso_product']['relevance'],
	'inputType' => 'text',
	'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit'],
	'sql'       => "int(10) unsigned NOT NULL",
];

$arrDca['fields']['isPdfProduct'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['isPdfProduct'],
	'exclude'    => true,
	'inputType'  => 'checkbox',
	'eval'       => ['tl_class' => 'w50'],
	'attributes' => ['legend' => 'shipping_legend'],
	'sql'        => "char(1) NOT NULL default ''",
];

$arrDca['fields']['copyright'] = [
	'label'            => &$GLOBALS['TL_LANG']['tl_iso_product']['copyright'],
	'exclude'          => true,
	'search'           => true,
	'sorting'          => true,
	'inputType'        => 'tagsinput',
	'options_callback' => ['\HeimrichHannot\IsotopeBundle\Helper\ProductHelper', 'getCopyrights'],
	'eval'             => [
		'maxlength'      => 255,
		'decodeEntities' => true,
		'tl_class'       => 'long clr',
		'helpwizard'     => true,
		'freeInput'      => true,
		'multiple'       => true,
	],
	'sql'              => "blob NULL",
	'attributes'       => ['legend' => 'general_legend'],
];

$arrDca['fields']['bookingStart'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingStart'],
	'inputType'  => 'text',
	'eval'       => ['tl_class' => 'w50', 'rgxp' => 'date', 'datepicker' => true],
	'attributes' => ['legend' => 'inventory_legend'],
	'sql'        => "varchar(16) NOT NULL default ''",
];

$arrDca['fields']['bookingStop'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingStop'],
	'inputType'  => 'text',
	'eval'       => ['tl_class' => 'w50', 'rgxp' => 'date', 'datepicker' => true],
	'attributes' => ['legend' => 'inventory_legend'],
	'sql'        => "varchar(16) NOT NULL default ''",
];

$arrDca['fields']['bookingBlock'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingBlock'],
	'inputType'  => 'text',
	'eval'       => ['tl_class' => 'w50'],
	'attributes' => ['legend' => 'inventory_legend'],
	'sql'        => "varchar(8) NOT NULL default ''",
];

$arrDca['fields']['bookingReservedDates'] = [
	'label'        => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingReservedDates'],
	'inputType'    => 'fieldpalette',
	'foreignKey'   => 'tl_fieldpalette.id',
	'relation'     => ['type' => 'hasMany', 'load' => 'eager'],
	'eval'         => ['tl_class' => 'long'],
	'attributes'   => ['legend' => 'inventory_legend'],
	'sql'          => "blob NULL",
	'fieldpalette' => [
		'config'   => [
			'hidePublished' => true,
		],
		'list'     => [
			'label' => [
				'fields' => ['start', 'stop'],
				'format' => '%s - %s',
			
			],
		],
		'palettes' => [
			'default' => '{block_legend},start,stop,count;',
		],
		'fields'   => [
			'start' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_iso_product']['start'],
				'exclude'   => true,
				'inputType' => 'text',
				'eval'      => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard', 'mandatory' => true],
				'sql'       => "varchar(10) NOT NULL default ''",
			],
			'stop'  => [
				'label'     => &$GLOBALS['TL_LANG']['tl_iso_product']['stop'],
				'exclude'   => true,
				'inputType' => 'text',
				'eval'      => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard', 'mandatory' => true],
				'sql'       => "varchar(10) NOT NULL default ''",
			],
			'count' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_iso_product']['count'],
				'exclude'   => true,
				'inputType' => 'text',
				'eval'      => ['tl_class' => 'w50'],
				'sql'       => "varchar(10) NOT NULL default '1'",
			]
		],
	],
];


// arrays are always copied by value (not by reference) in php
$arrDca['fields']['skipStockValidation']                                   = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipStockValidation'];
$arrDca['fields']['skipStockValidation']['attributes']                     = ['legend' => 'shipping_legend'];
$arrDca['fields']['skipStockEdit']                                         = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipStockEdit'];
$arrDca['fields']['skipStockEdit']['attributes']                           = ['legend' => 'shipping_legend'];
$arrDca['fields']['skipExemptionFromShippingWhenStockEmpty']               =
	$GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipExemptionFromShippingWhenStockEmpty'];
$arrDca['fields']['skipExemptionFromShippingWhenStockEmpty']['attributes'] = ['legend' => 'shipping_legend'];

if (\Contao\System::getContainer()->get('huh.utils.container')->isFrontend()) {
	$arrDca['fields']['type']['options_callback'] = ['\HeimrichHannot\IsotopeBundle\Helper\ProductHelper', 'getEditableCategories'];
}