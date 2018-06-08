<?php

$GLOBALS['TL_DCA']['tl_iso_product_data'] = [
    'config'   => [
        'dataContainer'     => 'Table',
        'enableVersioning'  => false,
        'onsubmit_callback' => [
            ['huh.utils.dca', 'setDateAdded'],
        ],
        'oncopy_callback'   => [
            ['huh.utils.dca', 'setDateAddedOnCopy'],
        ],
        'sql'               => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list'     => [
        'label'             => [
            'fields' => ['id'],
            'format' => '%s'
        ],
        'sorting'           => [
            'mode'        => 0,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'global_operations' => [
        ],
        'operations'        => [
        ]
    ],
    'palettes' => [
        '__selector__' => [],
        'default'      => ''
    ],
    'fields'   => [
        'id'                      => [
            'sql'  => "int(10) unsigned NOT NULL auto_increment",
            'eval' => ['skipProductPalette' => true]
        ],
        'pid'                     => [
            'sql'  => "int(10) unsigned NOT NULL default '0'",
            'eval' => ['skipProductPalette' => true]
        ],
        'tstamp'                  => [
            'label' => &$GLOBALS['TL_LANG']['tl_iso_product_data']['tstamp'],
            'sql'   => "int(10) unsigned NOT NULL default '0'",
            'eval'  => ['skipProductPalette' => true]
        ],
        'dateAdded'               => [
            'label'   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
            'sorting' => true,
            'flag'    => 6,
            'eval'    => ['rgxp' => 'datim', 'doNotCopy' => true, 'skipProductPalette' => true],
            'sql'     => "int(10) unsigned NOT NULL default '0'",
        ],
        'initialStock'            => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['initialStock'],
            'inputType'  => 'text',
            'eval'       => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'digit'],
            'attributes' => ['legend' => 'inventory_legend'],
            'sql'        => "varchar(255) NOT NULL default ''",
        ],
        'stock'                   => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['stock'],
            'inputType'  => 'text',
            'eval'       => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'digit'],
            'attributes' => ['legend' => 'inventory_legend', 'fe_sorting' => true],
            'sql'        => "varchar(255) NOT NULL default ''",
        ],
        'setQuantity'             => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['setQuantity'],
            'inputType'  => 'text',
            'eval'       => ['mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'digit'],
            'attributes' => ['legend' => 'inventory_legend', 'fe_sorting' => true],
            'sql'        => "varchar(255) NOT NULL default ''",
        ],
        'releaseDate'             => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['releaseDate'],
            'exclude'    => true,
            'inputType'  => 'text',
            'default'    => time(),
            'eval'       => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'attributes' => ['legend' => 'publish_legend', 'fe_sorting' => true],
            'sql'        => "varchar(10) NOT NULL default ''",
        ],
        'maxOrderSize'            => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['maxOrderSize'],
            'inputType'  => 'text',
            'eval'       => ['tl_class' => 'w50', 'rgxp' => 'digit'],
            'attributes' => ['legend' => 'inventory_legend'],
            'sql'        => "varchar(255) NOT NULL default ''",
        ],
        'overrideStockShopConfig' => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['overrideStockShopConfig'],
            'exclude'    => true,
            'inputType'  => 'checkbox',
            'eval'       => ['tl_class' => 'w50'],
            'attributes' => ['legend' => 'shipping_legend'],
            'sql'        => "char(1) NOT NULL default ''",
        ],
        'jumpTo' > [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['jumpTo'],
            'exclude'    => true,
            'inputType'  => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval'       => ['fieldType' => 'radio'],
            'sql'        => "int(10) unsigned NOT NULL default '0'",
            'attributes' => ['legend' => 'general_legend'],
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'addedBy'                 => [
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
        ],
        'uploadedFiles'           => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['uploadedFiles'],
            'exclude'    => true,
            'inputType'  => 'multifileupload',
            'eval'       => [
                'tl_class'           => 'clr',
                'extensions'         => \Contao\Config::get('validImageTypes'),
                'filesOnly'          => true,
                'fieldType'          => 'checkbox',
                'maxImageWidth'      => \Contao\Config::get('gdMaxImgWidth'),
                'maxImageHeight'     => \Contao\Config::get('gdMaxImgHeight'),
                'skipPrepareForSave' => true,
                'uploadFolder'       => ['HeimrichHannot\IsotopeBundle\Backend\Callbacks', 'getUploadFolder'],
                'addRemoveLinks'     => true,
                'maxFiles'           => 15,
                'multipleFiles'      => true,
                'maxUploadSize'      => \Contao\Config::get('maxFileSize'),
                'mandatory'          => true,
            ],
            'attributes' => ['legend' => 'media_legend'],

            'sql' => "blob NULL",
        ],
        'uploadedDownloadFiles'   => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['uploadedDownloadFiles'],
            'exclude'    => true,
            'inputType'  => 'multifileupload',
            'eval'       => [
                'tl_class'           => 'clr',
                'extensions'         => \Contao\Config::get('uploadTypes'),
                'filesOnly'          => true,
                'fieldType'          => 'checkbox',
                'skipPrepareForSave' => true,
                'uploadFolder'       => ['HeimrichHannot\IsotopeBundle\Backend\Callbacks', 'getUploadFolder'],
                'addRemoveLinks'     => true,
                'maxFiles'           => 15,
                'multipleFiles'      => true,
                'maxUploadSize'      => \Contao\Config::get('maxFileSize'),
            ],
            'attributes' => ['legend' => 'media_legend'],

            'sql' => "blob NULL",
        ],
        'tag'                     => [
            'label'            => &$GLOBALS['TL_LANG']['tl_iso_product']['tag'],
            'exclude'          => true,
            'search'           => true,
            'sorting'          => true,
            'inputType'        => 'tagsinput',
            'options_callback' => ['huh.isotope.helper.product', 'getTags'],
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
        ],
        'licence'                 => [
            'label'            => &$GLOBALS['TL_LANG']['tl_iso_product']['licence'],
            'exclude'          => true,
            'search'           => true,
            'sorting'          => true,
            'inputType'        => 'select',
            'reference'        => &$GLOBALS['TL_LANG']['tl_iso_product']['licence'],
            'options_callback' => ['huh.isotope.helper.product', 'getLicenceTitle'],
            'eval'             => ['mandatory' => true, 'tl_class' => 'clr w50', 'includeBlankOption' => true],
            'attributes'       => ['legend' => 'general_legend', 'fe_sorting' => true, 'fe_search' => true],
            'sql'              => "varchar(255) NOT NULL default ''",
        ],
        'createMultiImageProduct' => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['createMultiImageProduct'],
            'exclude'    => true,
            'inputType'  => 'checkbox',
            'eval'       => ['tl_class' => 'w50'],
            'attributes' => ['legend' => 'shipping_legend'],
            'sql'        => "char(1) NOT NULL default ''",
        ],
        'downloadCount'           => [
            'label'     => &$GLOBALS['TL_LANG']['tl_iso_product']['downloadCount'],
            'inputType' => 'text',
            'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit'],
            'sql'       => "int(10) unsigned NOT NULL",
        ],
        'relevance'               => [
            'label'     => &$GLOBALS['TL_LANG']['tl_iso_product']['relevance'],
            'inputType' => 'text',
            'eval'      => ['tl_class' => 'w50', 'rgxp' => 'digit'],
            'sql'       => "int(10) unsigned NOT NULL",
        ],
        'isPdfProduct'            => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['isPdfProduct'],
            'exclude'    => true,
            'inputType'  => 'checkbox',
            'eval'       => ['tl_class' => 'w50'],
            'attributes' => ['legend' => 'shipping_legend'],
            'sql'        => "char(1) NOT NULL default ''",
        ],
        'copyright'               => [
            'label'            => &$GLOBALS['TL_LANG']['tl_iso_product']['copyright'],
            'exclude'          => true,
            'search'           => true,
            'sorting'          => true,
            'inputType'        => 'tagsinput',
            'options_callback' => ['huh.isotope.helper.product', 'getCopyrights'],
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
        ],
        'bookingStart'            => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingStart'],
            'inputType'  => 'text',
            'eval'       => ['tl_class' => 'w50', 'rgxp' => 'date', 'datepicker' => true],
            'attributes' => ['legend' => 'inventory_legend'],
            'sql'        => "varchar(16) NOT NULL default ''",
        ],
        'bookingStop'             => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingStop'],
            'inputType'  => 'text',
            'eval'       => ['tl_class' => 'w50', 'rgxp' => 'date', 'datepicker' => true],
            'attributes' => ['legend' => 'inventory_legend'],
            'sql'        => "varchar(16) NOT NULL default ''",
        ],
        'bookingBlock'            => [
            'label'      => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingBlock'],
            'inputType'  => 'text',
            'eval'       => ['tl_class' => 'w50'],
            'attributes' => ['legend' => 'inventory_legend'],
            'sql'        => "varchar(8) NOT NULL default ''",
        ],
        'bookingReservedDates' => [
            'label'        => &$GLOBALS['TL_LANG']['tl_iso_product']['bookingReservedDates'],
            'inputType'    => 'fieldpalette',
            'foreignKey'   => 'tl_fieldpalette.id',
            'relation'     => ['type' => 'hasMany', 'load' => 'eager'],
            'eval'         => ['tl_class' => 'clr'],
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

            ]
        ],
        "bookingOverview"      => [
            'inputType' => 'huh_be_explanation',
            'eval'      => [
                'text_callback' => ['huh.isotope.listener.callback.product', 'getBookingOverview'],
            ],
            'attributes'   => ['legend' => 'inventory_legend'],
            'text_callback' => ['huh.isotope.listener.callback.product', 'getBookingOverview'],
        ],
    ]
];

Controller::loadDataContainer('tl_iso_config');
$arrDca = &$GLOBALS['TL_DCA']['tl_iso_product_data'];
$arrDca['fields']['skipStockValidation']                                   = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipStockValidation'];
$arrDca['fields']['skipStockValidation']['attributes']                     = ['legend' => 'shipping_legend'];
$arrDca['fields']['skipStockEdit']                                         = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipStockEdit'];
$arrDca['fields']['skipStockEdit']['attributes']                           = ['legend' => 'shipping_legend'];
$arrDca['fields']['skipExemptionFromShippingWhenStockEmpty']               = $GLOBALS['TL_DCA']['tl_iso_config']['fields']['skipExemptionFromShippingWhenStockEmpty'];
$arrDca['fields']['skipExemptionFromShippingWhenStockEmpty']['attributes'] = ['legend' => 'shipping_legend'];