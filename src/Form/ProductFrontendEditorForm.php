<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Form;

use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\FormHybrid\Form;
use HeimrichHannot\IsotopeBundle\Model\ProductModel;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;

class ProductFrontendEditorForm extends Form
{
    protected $strMethod = FORMHYBRID_METHOD_POST;
    protected $strTable = 'tl_iso_product';
    protected $strTemplate = 'iso_product_creator';
    /**
     * @var Request|object
     */
    protected $request;

    public function __construct($objModule = null, $instanceId = 0)
    {
        parent::__construct($objModule, $instanceId);
    }

    public function modifyDC(&$arrDca = null)
    {
        // limit upload to one image for editing existing product
        if (null !== ($product = System::getContainer()->get('contao.framework')->getAdapter(ProductModel::class)->findByPk(System::getContainer()->get('huh.request')->getGet('id'))) && 0 != $product->tstamp && !$product->createMultiImageProduct) {
            $arrDca['fields']['uploadedFiles']['eval']['maxFiles'] = 1;
        }

        if (System::getContainer()->get('huh.request')->hasPost('licence')) {
            $licence = array_unique(StringUtil::deserialize(System::getContainer()->get('huh.request')->getPost('licence'), true));

            System::getContainer()->get('huh.request')->setPost('licence', reset($licence));
        }

        // HOOK: send insert ID and user data
        if (isset($GLOBALS['TL_HOOKS']['modifyDCProductEditor']) && \is_array($GLOBALS['TL_HOOKS']['modifyDCProductEditor'])) {
            foreach ($GLOBALS['TL_HOOKS']['modifyDCProductEditor'] as $callback) {
                $this->import($callback[0]);
                $this->{$callback[0]}->{$callback[1]}($arrDca, $this->objModule);
            }
        }
    }

    public function onSubmitCallback(\DataContainer $dc)
    {
        $submission = $this->getSubmission();

        if (!\is_array($submission->uploadedFiles)) {
            $submission->uploadedFiles = json_decode($submission->uploadedFiles);
        }

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
