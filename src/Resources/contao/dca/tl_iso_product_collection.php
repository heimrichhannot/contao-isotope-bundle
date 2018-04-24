<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_iso_product_collection'];

/**
 * Callbacks
 */
$arrDca['config']['ondelete_callback'][] = ['HeimrichHannot\IsotopeBundle\Backend\Backend', 'increaseStock'];