<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\Date;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\System;
use Isotope\Isotope;
use Isotope\Model\ProductCollection\Order;
use Isotope\Module\OrderDetails;

/**
 * Class OrderDetailsPlus.
 *
 * Adds a switch to display all orders (not only the ones of the currently logged in user)
 *
 * @copyright  Isotope eCommerce Workgroup 2009-2012
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 */
class OrderDetailsPlus extends OrderDetails
{
    public function generate($blnBackend = false)
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend() && !$blnBackend) {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: ORDER DETAILS PLUS ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        if ($blnBackend) {
            $this->backend = true;
            $this->jumpTo = 0;
        }

        return parent::generate();
    }

    protected function compile()
    {
        $container = System::getContainer();
        $framework = $container->get('contao.framework');
        // Also check owner (see #126)
        if (null === ($objOrder = $framework->getAdapter(Order::class)->findOneBy('uniqid', (string) \Input::get('uid')))
            || (FE_USER_LOGGED_IN === true
                && $objOrder->member > 0
                && $framework->createInstance(FrontendUser::class)->id != $objOrder->member
                && !$this->iso_show_all_orders)) {
            $this->Template = new \Isotope\Template('mod_message');
            $this->Template->type = 'error';
            $this->Template->message = $GLOBALS['TL_LANG']['ERR']['orderNotFound'];

            return;
        }

        // Order belongs to a member but not logged in
        if (!$this->iso_show_all_orders || $container->get('huh.utils.container')->isFrontend() && $this->iso_loginRequired && $objOrder->member > 0 && FE_USER_LOGGED_IN !== true) {
            global $objPage;

            $objHandler = new $GLOBALS['TL_PTY']['error_403']();
            $objHandler->generate($objPage->id);
            exit;
        }

        $framework->getAdapter(Isotope::class)->setConfig($objOrder->getRelated('config_id'));

        $objTemplate = new \Isotope\Template($this->iso_collectionTpl);
        $objTemplate->linkProducts = true;

        $objOrder->addToTemplate($objTemplate, [
            'gallery' => $this->iso_gallery,
            'sorting' => $objOrder->getItemsSortingCallable($this->iso_orderCollectionBy),
        ]);
        $dateFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->dateFormat : $GLOBALS['TL_CONFIG']['dateFormat'];
        $timeFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->timeFormat : $GLOBALS['TL_CONFIG']['timeFormat'];
        $dateTimeFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->datimFormat : $GLOBALS['TL_CONFIG']['datimFormat'];

        $this->Template->collection = $objOrder;
        $this->Template->products = $objTemplate->parse();
        $this->Template->info = StringUtil::deserialize($objOrder->checkout_info, true);
        $this->Template->date = Date::parse($dateFormat, $objOrder->locked);
        $this->Template->time = Date::parse($timeFormat, $objOrder->locked);
        $this->Template->datim = Date::parse($dateTimeFormat, $objOrder->locked);
        $this->Template->orderDetailsHeadline = sprintf($GLOBALS['TL_LANG']['MSC']['orderDetailsHeadline'], $objOrder->document_number, $this->Template->datim);
        $this->Template->orderStatus = sprintf($GLOBALS['TL_LANG']['MSC']['orderStatusHeadline'], $objOrder->getStatusLabel());
        $this->Template->orderStatusKey = $objOrder->getStatusAlias();
    }
}
