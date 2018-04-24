<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Module;
use Contao\PageModel;
use Contao\System;

class CartLink extends Module
{
    protected $strTemplate = 'mod_iso_cart_link';

    /**
     * @var PageModel
     */
    protected $target = null;

    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    public function generate()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: CART LINK ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        $this->framework = System::getContainer()->get('contao.framework');

        $this->target = $this->framework->getAdapter(PageModel::class)->findByPk($this->jumpTo);

        if (null === $this->target) {
            return '';
        }

        return parent::generate();
    }

    protected function compile()
    {
        global $objPage;

        $this->Template->href = $this->target->getFrontendUrl();

        if ($objPage->id == $this->jumpTo) {
            $this->Template->active = true;
        }
    }
}
