<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\ListItem;

use Contao\MemberModel;
use Contao\System;
use HeimrichHannot\ListBundle\Item\DefaultItem;
use Isotope\Isotope;
use Isotope\Model\Config;
use Isotope\Model\OrderStatus;
use Isotope\Model\ProductCollection\Order;

class IsotopeCollectionItem extends DefaultItem
{
    public function getOrderStatus()
    {
        $orderStatus = System::getContainer()->get('contao.framework')->getAdapter(OrderStatus::class)->findById($this->_raw['order_status']);

        if (null === $orderStatus) {
            return null;
        }

        return $orderStatus->name;
    }

    public function getCustomer()
    {
        if ('0' === $this->_raw['member']) {
            $config = System::getContainer()->get('contao.framework')->getAdapter(Config::class)->findById($this->_raw['config_id']);

            if (null === $config) {
                return $GLOBALS['TL_LANG']['tl_module']['guestOrder'];
            }

            return $config->name;
        }

        $customer = System::getContainer()->get('contao.framework')->getAdapter(MemberModel::class)->findByPk($this->_raw['member']);

        if (null === $customer) {
            return $GLOBALS['TL_LANG']['tl_module']['notExistingAnyMore'];
        }

        return $customer->firstname.' '.$customer->lastname;
    }

    public function getCustomerLink()
    {
        if ('0' === $this->_raw['member']) {
            return null;
        }

        $jumpTo = System::getContainer()->get('huh.utils.url')->getJumpToPageObject(131);

        return $jumpTo->getFrontendUrl('/'.$this->_raw['member']);
    }

    public function getGrandTotal()
    {
        $total = 0;
        $framework = System::getContainer()->get('contao.framework');
        $order = $framework->getAdapter(Order::class)->findById($this->_raw['id']);

        if (null !== $order) {
            $total = $order->getTotal();
        }

        return $framework->getAdapter(Isotope::class)->formatPriceWithCurrency($total);
    }

    public function getDetailsLink()
    {
        return System::getContainer()->get('huh.utils.url')->addQueryString('uid='.$this->_raw['uniqid'], $this->_jumpToDetails);
    }
}
