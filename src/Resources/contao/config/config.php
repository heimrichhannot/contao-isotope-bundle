<?php

if (System::getContainer()->get('huh.utils.container')->isBackend())
{
    $GLOBALS['TL_CSS'][] = 'system/modules/isotope/assets/css/backend.css|static';
}