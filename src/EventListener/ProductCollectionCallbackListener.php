<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\DataContainer;
use Contao\Model;
use Contao\Model\Collection;
use Contao\StringUtil;
use HeimrichHannot\IsotopeBundle\Manager\IsotopeManager;
use HeimrichHannot\IsotopeBundle\Manager\ProductDataManager;
use Isotope\Model\ProductCollection\Order;
use Isotope\Model\ProductCollectionItem;

class ProductCollectionCallbackListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;
    /**
     * @var IsotopeManager
     */
    private $isotopeManager;
    /**
     * @var ProductDataManager
     */
    private $productDataManager;

    public function __construct(ContaoFrameworkInterface $framework, IsotopeManager $isotopeManager, ProductDataManager $productDataManager)
    {
        $this->framework = $framework;
        $this->isotopeManager = $isotopeManager;
        $this->productDataManager = $productDataManager;
    }

    /**
     * increase stock after deleting an order.
     */
    public function increaseStock(DataContainer $dc)
    {
        $order = $this->framework->getAdapter(Order::class)->findByPk($dc->id);
        if (!$order) {
            return;
        }
        /** @var ProductCollectionItem[]|Collection|Model $order */
        $items = $this->framework->getAdapter(ProductCollectionItem::class)->findByPid($order->id);
        if (!$items) {
            return;
        }
        $config = $order->getRelated('config_id');
        // if the order had already been set to a stock increasing state,
        // the stock doesn't need to be increased again
        if (\in_array($order->order_status, StringUtil::deserialize($config->stockIncreaseOrderStates, true), true)) {
            return;
        }

        foreach ($items as $item) {
            $productData = $this->productDataManager->getProductData($item->product_id);
            $totalStockQuantity = $this->isotopeManager->getTotalCartQuantity($item->quantity, $productData, null, null, $config);

            if ($totalStockQuantity) {
                $productData->stock += $totalStockQuantity;
                $productData->save();
            }
        }
    }
}
