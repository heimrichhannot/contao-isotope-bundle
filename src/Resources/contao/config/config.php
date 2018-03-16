<?php

/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['isotopeBundle']['iso_cart_link'] = 'HeimrichHannot\IsotopeBundle\Module\CartLink';

/**
 * CSS
 */
if (System::getContainer()->get('huh.utils.container')->isBackend())
{
    $GLOBALS['TL_CSS'][] = 'bundles/heimrichhannotcontaoisotope/css/backend.css|static';
}