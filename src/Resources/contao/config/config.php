<?php

if (System::getContainer()->get('huh.utils.container')->isBackend())
{
    $GLOBALS['TL_CSS'][] = 'bundles/heimrichhannotcontaoisotope/css/backend.css|static';
}