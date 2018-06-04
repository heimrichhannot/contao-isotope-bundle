<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Message;
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
     * @param int                   $quantity
     * @param ProductCollectionItem $cartItem
     * @param bool                  $includeError
     * @param int                   $setQuantity
     *
     * @return array|bool
     */
    public function validateQuantity(Product $product, int $quantity, ProductCollectionItem $cartItem = null, bool $includeError = false, int $setQuantity = null)
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
            $validateStock = $this->stockAttribute->validate($productData, $quantityTotal, $includeError);
            if (true !== $validateStock) {
                return $this->validateQuantityErrorResult($validateStock[1], $includeError);
            }
        }

        // maxOrderSize
        $validateMaxOrderSize = $this->maxOrderSizeAttribute->validate($productData, $quantityTotal);
        if (true !== $validateMaxOrderSize) {
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
