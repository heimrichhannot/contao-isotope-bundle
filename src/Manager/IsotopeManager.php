<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FilesModel;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\IsotopeBundle\Attribute\MaxOrderSizeAttribute;
use HeimrichHannot\IsotopeBundle\Attribute\StockAttribute;
use HeimrichHannot\IsotopeBundle\Model\ProductDataModel;
use HeimrichHannot\UtilsBundle\Container\ContainerUtil;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Model\Config;
use Isotope\Model\Product;
use Isotope\Model\ProductCollectionItem;
use Isotope\Model\ProductType;

class IsotopeManager
{
    /**
     * @var ProductDataManager
     */
    private $productDataManager;
    /**
     * @var StockAttribute
     */
    private $stockAttribute;
    /**
     * @var MaxOrderSizeAttribute
     */
    private $maxOrderSizeAttribute;
    /**
     * @var ContainerUtil
     */
    private $containerUtil;
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * IsotopeManager constructor.
     *
     * @param ProductDataManager       $productDataManager
     * @param StockAttribute           $stockAttribute
     * @param MaxOrderSizeAttribute    $maxOrderSizeAttribute
     * @param ContainerUtil            $containerUtil
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ProductDataManager $productDataManager, StockAttribute $stockAttribute, MaxOrderSizeAttribute $maxOrderSizeAttribute, ContainerUtil $containerUtil, ContaoFrameworkInterface $framework)
    {
        $this->productDataManager = $productDataManager;
        $this->stockAttribute = $stockAttribute;
        $this->maxOrderSizeAttribute = $maxOrderSizeAttribute;
        $this->containerUtil = $containerUtil;
        $this->framework = $framework;
    }

    /**
     * @param Product               $product
     * @param                       $quantity
     * @param ProductCollectionItem $cartItem
     * @param bool                  $includeError
     * @param int                   $setQuantity
     *
     * @return array|bool
     */
    public function validateQuantity(Product $product, $quantity, ProductCollectionItem $cartItem = null, bool $includeError = false, int $setQuantity = null)
    {
        // no quantity at all
        if (null === $quantity) {
            return true;
        } elseif (empty($quantity)) {
            $quantity = 1;
        }
        $productData = $this->productDataManager->getProductData($product->id);
        $quantityTotal = $this->getTotalCartQuantity($quantity, $productData, $cartItem, $setQuantity);

        // Stock
        if (!$this->getOverridableStockProperty('skipStockValidation', $product)) {
            $validateStock = $this->stockAttribute->validate($product, $quantityTotal, $includeError);
            if (true !== $validateStock[0]) {
                return $this->validateQuantityErrorResult($validateStock[1], $includeError);
            }
        }

        // maxOrderSize
        $validateMaxOrderSize = $this->maxOrderSizeAttribute->validate($product, $quantityTotal);
        if (true !== $validateMaxOrderSize[0]) {
            return $this->validateQuantityErrorResult($validateMaxOrderSize[1], $includeError);
        }

        if ($includeError) {
            return [true, null];
        }

        return true;
    }

    /**
     * Returns the config value.
     *
     * Checks if global value is overwritten by product or product type
     *
     * priorities (first is the most important):
     * product, product type, global shop config.
     *
     *
     * @param string $property
     * @param        $product
     *
     * @return mixed
     */
    public function getOverridableStockProperty(string $property, $product)
    {
        // at first check for product and product type
        if ($product->overrideStockShopConfig) {
            return $product->{$property};
        }
        /** @var ProductType|null $objProductType */
        if (null !== ($objProductType = $this->framework->getAdapter(ProductType::class)->findByPk($product->type))
            && $objProductType->overrideStockShopConfig) {
            return $objProductType->{$property};
        }

        // nothing returned?
        $objConfig = Isotope::getConfig();

        // defaultly return the value defined in the global config
        return $objConfig->{$property};
    }

    /**
     * watch out: also in backend the current set quantity is used.
     *
     * @param int            $quantity
     * @param IsotopeProduct $product
     * @param null           $objCartItem
     * @param null           $intSetQuantity
     * @param null           $config
     *
     * @return int|null
     */
    public function getTotalStockQuantity(int $quantity, IsotopeProduct $product, $objCartItem = null, $intSetQuantity = null, $config = null)
    {
        $intFinalSetQuantity = 1;

        if ($intSetQuantity) {
            $intFinalSetQuantity = $intSetQuantity;
        } elseif (!$this->getOverridableShopConfigProperty('skipSets', $config) && $product->setQuantity) {
            $intFinalSetQuantity = $product->setQuantity;
        }

        $quantity *= $intFinalSetQuantity;

        if (null !== $objCartItem) {
            $quantity += $objCartItem->quantity * $intFinalSetQuantity;
        }

        return $quantity;
    }

    /**
     * @param string $property
     * @param null   $config
     *
     * @return mixed
     */
    public function getOverridableShopConfigProperty(string $property, $config = null)
    {
        if (!$config) {
            $config = Isotope::getConfig();
        }

        return $config->{$property};
    }

    /**
     * Returns the total quanitity of the product type added to cart and already in cart, taking set size into account.
     *
     * watch out: also in backend the current set quantity is used.
     *
     * @param int              $quantity
     * @param ProductDataModel $product
     * @param null             $cartItem
     * @param null             $setQuantity
     * @param Config           $config
     *
     * @return int|null
     */
    public function getTotalCartQuantity(int $quantity, ProductDataModel $product, $cartItem = null, $setQuantity = null, Config $config = null)
    {
        $intFinalSetQuantity = 1;

        if ($setQuantity) {
            $intFinalSetQuantity = $setQuantity;
        } elseif (!$this->getOverridableShopConfigProperty('skipSets', $config) && $product->setQuantity) {
            $intFinalSetQuantity = $product->setQuantity;
        }

        $quantity *= $intFinalSetQuantity;

        // Add to already existing quantity (if product is already in cart)
        if ($cartItem) {
            $quantity += $cartItem->quantity * $intFinalSetQuantity;
        }

        return $quantity;
    }

    /**
     * adds the image to the template data.
     *
     * @param array  $itemData
     * @param string $imgSize
     * @param array  $templateData
     * @param string $imageKey
     */
    public function addImageToTemplateData(array $itemData, string $imgSize, array &$templateData, string $imageKey = 'image')
    {
        $image = null;
        $imageFile = null;
        $itemData['imageTitle'] = $itemData['name'];

        if (null !== $itemData['images']) {
            $imageField = 'images';
            $arrImages = StringUtil::deserialize($itemData['images']);

            if (!is_array($arrImages) || empty($arrImages)) {
                return;
            }

            foreach ($arrImages as $image) {
                $strImage = 'isotope/'.strtolower(substr($image['src'], 0, 1)).'/'.$image['src'];

                if (!is_file(TL_ROOT.'/'.$strImage)) {
                    continue;
                }
                $image = System::getContainer()->get('contao.image.image_factory')->create($strImage);
                if (null === $image
                    || !file_exists(System::getContainer()->get('huh.utils.container')->getProjectDir().'/'.$image->getPath())) {
                    return;
                }
                $itemData[$imageField] = $image->getPath();
            }
        } elseif (null !== $itemData['uploadedFiles']) {
            $imageField = 'uploadedFiles';
            $uploadedFiles = StringUtil::deserialize($itemData['uploadedFiles'], true);

            if (null === $uploadedFiles) {
                return;
            }

            if (\Validator::isUuid($uploadedFiles[0])) {
                $imageFile = System::getContainer()->get('contao.framework')->getAdapter(FilesModel::class)->findByUuid($uploadedFiles[0]);
                if (null === $imageFile
                    || !file_exists(TL_ROOT.\DIRECTORY_SEPARATOR.$imageFile->path)) {
                    return;
                }
                $itemData[$imageField] = $imageFile->path;
            }
        } else {
            return;
        }

        // Override the default image size
        if ('' !== $imgSize) {
            $size = StringUtil::deserialize($imgSize, true);

            if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2])) {
                $itemData['size'] = $imgSize;
            }
        }

        $templateData[$imageKey] = [];

        System::getContainer()->get('huh.utils.image')->addToTemplateData($imageField, 'published', $templateData[$imageKey], $itemData, null, null, null, $imageFile);
    }

    /**
     * get the isotope image by product.
     *
     * @param        $template
     * @param        $product
     * @param string $size
     *
     * @return string
     */
    public function getIsotopeImage($product, string $size = '')
    {
        $data['name'] = $product->name;
        $data['images'] = $product->images;
        $data['src'] = $product->src;
        $data['uploadedFiles'] = $product->uploadedFiles;
        $data['size'] = $product->size;

        $img = [];
        $this->addImageToTemplateData($data, $size, $img);

        $img['image']['picture']['image'] = $img['image']['images'];

        return System::getContainer()->get('twig')->render('HeimrichHannotBegBundle:image:iso_gallery_standard.html.twig', $img['image']['picture']);
    }

    /**
     * Formats the return message of validateQuantity if an error occurred.
     *
     * @param string $errorMessage
     * @param bool   $includeError
     *
     * @return array|bool
     */
    protected function validateQuantityErrorResult(string $errorMessage, bool $includeError)
    {
        if ($this->containerUtil->isFrontend()) {
            $_SESSION['ISO_ERROR'][] = $errorMessage;
        } else {
            Message::addError($errorMessage);
        }

        if ($includeError) {
            return [false, $errorMessage];
        }

        return false;
    }
}
