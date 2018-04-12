<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\Date;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\StringUtil;
use Contao\System;
use Haste\Generator\RowClass;
use HeimrichHannot\Request\Request;
use Isotope\Isotope;
use Isotope\Model\Address;
use Isotope\Model\ProductCollection\Order;
use Isotope\Module\OrderHistory;

/**
 * Class OrderHistory.
 *
 * Adds a switch to display all orders (not only the ones of the currently logged in user)
 *
 * @copyright  Isotope eCommerce Workgroup 2009-2012
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 */
class OrderHistoryPlus extends OrderHistory
{
    protected $template = 'mod_iso_orderhistoryplus';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: ORDER HISTORY PLUS ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        $this->iso_config_ids = StringUtil::deserialize($this->iso_config_ids);

        if (FE_USER_LOGGED_IN !== true || !is_array($this->iso_config_ids)
            || !count($this->iso_config_ids)) { // Can't use empty() because its an object property (using __get)
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile()
    {
        $arrOrders = [];
        $arrColumns = [];
        $container = System::getContainer();
        $framework = $container->get('contao.framework');

        if (Request::getGet('order_status')) {
            $arrColumns[] = 'order_status > 0 AND order_status = '.Request::getGet('order_status');
        } else {
            $arrColumns[] = 'order_status > 0';
        }

        if (Request::getGet('config_id')) {
            $arrColumns[] = 'config_id = '.Request::getGet('config_id');
        } else {
            $arrColumns[] = 'config_id IN ('.implode(',', array_map('intval', $this->iso_config_ids)).')';
        }

        if (!$this->iso_show_all_orders) {
            $arrColumns[] = 'member='.$container->get('contao.framework')->createInstance(FrontendUser::class)->id;
        }

        // auto_item = member
        if (isset($_GET['items'])) {
            $arrColumns[] = 'member='.Request::getGet('auto_item');
        }

        $objOrders = $framework->getAdapter(Order::class)->findBy($arrColumns, [], ['order' => 'locked DESC']);

        // No orders found, just display an "empty" message
        if (null === $objOrders) {
            $this->Template = new \Isotope\Template('mod_message');
            $this->Template->type = 'empty';
            $this->Template->message = $GLOBALS['TL_LANG']['ERR']['emptyOrderHistory'];

            return;
        }

        /** @var Order $objOrder */
        foreach ($objOrders as $objOrder) {
            Isotope::setConfig($objOrder->getRelated('config_id'));
            $dateFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->dateFormat : $GLOBALS['TL_CONFIG']['dateFormat'];
            $timeFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->timeFormat : $GLOBALS['TL_CONFIG']['timeFormat'];
            $dateTimeFormat = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->datimFormat : $GLOBALS['TL_CONFIG']['datimFormat'];

            $arrOrders[] = [
                'collection' => $objOrder,
                'raw' => $objOrder->row(),
                'date' => Date::parse($dateFormat, $objOrder->locked),
                'time' => Date::parse($timeFormat, $objOrder->locked),
                'datime' => Date::parse($dateTimeFormat, $objOrder->locked),
                'grandTotal' => $framework->getAdapter(Isotope::class)->formatPriceWithCurrency($objOrder->getTotal()),
                'status' => $objOrder->getStatusLabel(),
                'link' => ($this->jumpTo ? ($container->get('huh.utils.url')->addQueryString('uid='.$objOrder->uniqid, $this->jumpTo)) : ''),
                'class' => $objOrder->getStatusAlias(),
            ];
            // add member name
            if (!($intId = $objOrder->row()['member'])) {
                $arrOrders[count($arrOrders) - 1]['memberName'] = $GLOBALS['TL_LANG']['tl_module']['guestOrder'];
            } else {
                if (null !== ($objMember = $framework->getAdapter(MemberModel::class)->findByPk($intId))) {
                    if ('' != $objMember->firstname && '' != $objMember->lastname) {
                        $arrOrders[count($arrOrders) - 1]['memberName'] = $objMember->firstname.' '.$objMember->lastname;
                    } else {
                        if (null !== ($objAddress = $framework->getAdapter(Address::class)->findForMember($objOrder->row()['member']))) {
                            $arrOrders[count($arrOrders) - 1]['memberName'] = $objAddress->firstname.' '.$objAddress->lastname;
                        } else {
                            $arrOrders[count($arrOrders) - 1]['memberName'] = $GLOBALS['TL_LANG']['tl_module']['notExistingAnyMore'];
                        }
                    }
                } else {
                    $arrOrders[count($arrOrders) - 1]['memberName'] = $GLOBALS['TL_LANG']['tl_module']['notExistingAnyMore'];
                }
            }
        }

        RowClass::withKey('class')->addFirstLast()->addEvenOdd()->applyTo($arrOrders);

        $this->Template->orders = $arrOrders;
    }
}
