<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\Module;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Form\DirectCheckoutForm;
use Isotope\Module\Checkout;

/**
 * Class DirectCheckout.
 *
 * Copyright (c) 2015 Heimrich & Hannot GmbH
 *
 * @author  Dennis Patzer <d.patzer@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */
class DirectCheckout extends Checkout
{
    protected $strTemplate = 'mod_iso_direct_checkout';

    public function generate()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: DIRECT CHECKOUT ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return Module::generate();
    }

    protected function compile()
    {
        $this->formHybridDataContainer = 'tl_iso_product_collection';
        $objForm = new DirectCheckoutForm($this);
        $this->Template->checkoutForm = $objForm->generate();
    }
}
