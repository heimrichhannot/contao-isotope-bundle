<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_iso_download'];

/**
 * Palettes
 */
$arrDca['palettes']['default'] = '{title_legend},title,download_thumbnail;' . $arrDca['palettes']['default'];

/**
 * Fields
 */
$arrDca['fields']['title'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_iso_download']['title'],
    'exclude'   => true,
    'search'    => true,
    'sorting'   => true,
    'flag'      => 1,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$arrDca['fields']['download_thumbnail'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_iso_download']['download_thumbnail'],
    'exclude'    => true,
    'search'     => true,
    'sorting'    => true,
    'flag'       => 1,
    'inputType'  => 'multifileupload',
    'eval'       => [
        'tl_class'           => 'clr',
        'extensions'         => \Config::get('validImageTypes'),
        'filesOnly'          => true,
        'fieldType'          => 'radio',
        'maxImageWidth'      => \Config::get('gdMaxImgWidth'),
        'maxImageHeight'     => \Config::get('gdMaxImgHeight'),
        'skipPrepareForSave' => true,
        'uploadFolder'       => ['HeimrichHannot\IsotopeBundle\Backend\Callbacks', 'getUploadFolder'],
        'addRemoveLinks'     => true,
        'multipleFiles'      => false,
        'maxUploadSize'      => \Config::get('maxFileSize'),
    ],
    'attributes' => ['legend' => 'media_legend'],

    'sql' => "blob NULL",
];

//$arrDca['fields']['singleSRC']['eval']['path'] = '/isotope';

