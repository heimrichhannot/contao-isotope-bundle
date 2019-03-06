<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Module;

use Contao\Controller;
use Contao\StringUtil;
use Isotope\Isotope;
use Isotope\Module\Checkout;

/**
 * Class ModuleIsotopeCheckoutPlus
 * Front end module Isotope "checkout".
 * adds dependance to order condition form.
 *
 * @property array $iso_payment_modules
 * @property array $iso_shipping_modules
 * @property bool  $iso_forward_review
 * @property array $iso_checkout_skippable
 */
class CheckoutPlus extends Checkout
{
    const ISO_CHECKOUT_ORDERCONDITIONS = 'orderconditions';

    /**
     * remove the conditions form fields from template.
     */
    public function removeCoditionsForm()
    {
        $fields = $this->Template->fields;

        foreach ($fields as $key => $orderPart) {
            if (!strpos($orderPart['class'], static::ISO_CHECKOUT_ORDERCONDITIONS)) {
                continue;
            }

            unset($fields[$key]);
        }

        $this->Template->fields = $fields;
    }

    /**
     * Generate module.
     */
    protected function compile()
    {
        $this->showConditions();

        parent::compile();
    }

    /**
     * check if conditions should be displayed.
     */
    protected function showConditions()
    {
        if (!$this->iso_order_conditions) {
            return;
        }

        if ($this->productTypeDependantOrderConditions || $this->productDependantOrderConditions) {
            $this->showDependantConditions();
        }

        if ($this->iso_order_conditions_text) {
            $this->addOrderConditionsText();
        }
    }

    /**
     * check for field/type dependant order conditions.
     */
    protected function showDependantConditions()
    {
        $dependantTypes = StringUtil::deserialize($this->dependantTypes, true);
        $dependantProducts = StringUtil::deserialize($this->dependantProducts, true);

        if (empty($dependantTypes) && empty($dependantProducts)) {
            $this->resetOrderConditions();
        }

        $products = Isotope::getCart()->getItems();

        if ($this->productTypeDependantOrderConditions && !empty($dependantTypes)) {
            $showConditionsForm = $this->checkFormCondition($products, 'type', $dependantTypes);
        }

        if ($this->productDependantOrderConditions && !empty($dependantProducts)) {
            $showConditionsForm = $this->checkFormCondition($products, 'id', $dependantProducts);
        }

        if (!$showConditionsForm) {
            $this->resetOrderConditions();
        }
    }

    /**
     * set order conditions to null.
     */
    protected function resetOrderConditions()
    {
        $this->iso_order_conditions = null;
    }

    /**
     * check if cart products fit the conditions to display the conditional form.
     *
     * @param array  $products
     * @param string $attribute
     * @param array  $dependencies
     *
     * @return bool
     */
    protected function checkFormCondition(array $products, string $attribute, array $dependencies)
    {
        foreach ($products as $product) {
            if (in_array($product->getProduct()->{$attribute}, $dependencies, true)) {
                return true;
            }
        }

        return false;
    }

    protected function addOrderConditionsText()
    {
        $fields = $this->Template->fields;

        if (empty($fields))
        {
            return;
        }

        foreach ($fields as $key => &$orderPart) {
            if (!strpos($orderPart['class'], static::ISO_CHECKOUT_ORDERCONDITIONS)) {
                continue;
            }

            $orderPart['html'] = '<div class="form-group order_conditions_text">'.$this->iso_order_conditions_text.'</div>'.$orderPart['html'];
        }

        $this->Template->fields = $fields;
    }

    protected function generateSteps(array $arrSteps)
    {
        Controller::loadLanguageFile('defaults');
        return parent::generateSteps($arrSteps);
    }


}
