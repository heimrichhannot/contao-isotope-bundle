<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Form;

use Contao\System;
use HeimrichHannot\FormHybrid\Form;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\Request\Request;

class ProductFrontendEditorForm extends Form
{
    protected $strMethod = FORMHYBRID_METHOD_POST;
    protected $strTable = 'tl_iso_product';
    protected $strTemplate = 'iso_product_creator';

    public function __construct($objModule = null, $instanceId = 0)
    {
        parent::__construct($objModule, $instanceId);
    }

    public function modifyDC(&$arrDca = null)
    {
        // limit upload to one image for editing existing product
        if (null !== ($product = System::getContainer()->get('contao.framework')->getAdapter(ProductModel::class)->findByPk(Request::getGet('id'))) && 0 != $product->tstamp && !$product->createMultiImageProduct) {
            $arrDca['fields']['uploadedFiles']['eval']['maxFiles'] = 1;
        }

        // HOOK: send insert ID and user data
        if (isset($GLOBALS['TL_HOOKS']['modifyDCProductEditor']) && is_array($GLOBALS['TL_HOOKS']['modifyDCProductEditor'])) {
            foreach ($GLOBALS['TL_HOOKS']['modifyDCProductEditor'] as $callback) {
                $this->import($callback[0]);
                $this->{$callback[0]}->{$callback[1]}($this->dca, $this->objModule);
            }
        }
    }

    public function onSubmitCallback(\DataContainer $dc)
    {
        $submission = $this->getSubmission();

        if (empty($submission->uploadedFiles)) {
            return;
        }

        $strClass = ISO_PRODUCT_CREATOR_SINGLE_IMAGE_PRODUCT;

        if ($submission->createMultiImageProduct) {
            $strClass = ISO_PRODUCT_CREATOR_MULTI_IMAGE_PRODUCT;
        }

        if (!class_exists($strClass)) {
            return;
        }

        $product = new $strClass($this->objModule, $submission, $dc);

        $product->generateProduct();
    }

    protected function compile()
    {
    }
}
