<?php

define('ISO_PRODUCT_CREATOR_SINGLE_IMAGE_PRODUCT', 'HeimrichHannot\IsotopeBundle\Product\SingleImageProductEditor');
define('ISO_PRODUCT_CREATOR_MULTI_IMAGE_PRODUCT', 'HeimrichHannot\IsotopeBundle\Product\MultiImageProductEditor');

$GLOBALS['ISO_HOOKS']['generateProduct'][]                                       =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'generateProductHook'];
$GLOBALS['ISO_HOOKS']['addProductToCollection']['validateStockCollectionAdd']    =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'validateStockCollectionAdd'];
$GLOBALS['ISO_HOOKS']['preCheckout']['validateStockCheckout']                    =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'validateStockPreCheckout'];
$GLOBALS['ISO_HOOKS']['postCheckout']['validateStockCheckout']                   =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'validateStockPostCheckout'];
$GLOBALS['ISO_HOOKS']['postCheckout']['sendOrderNotification']                   =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'sendOrderNotification'];
$GLOBALS['ISO_HOOKS']['postCheckout']['setSetQuantity']                          =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'setSetQuantity'];
$GLOBALS['ISO_HOOKS']['updateItemInCollection']['validateStockCollectionUpdate'] =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'validateStockCollectionUpdate'];
$GLOBALS['ISO_HOOKS']['buttons'][]                                               =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'addDownloadSingleProductButton'];
$GLOBALS['ISO_HOOKS']['buttons'][]                                               =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'defaultButtons'];
$GLOBALS['ISO_HOOKS']['preOrderStatusUpdate']['updateStock']                     =
    ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'updateStock'];

$GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'][]             = ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'hookReplaceDynamicScriptTags'];
$GLOBALS['TL_HOOKS']['postDownload']['downloadCounter']        = ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'updateDownloadCounter'];
$GLOBALS['ISO_HOOKS']['generateProduct']['updateTemplateData'] = ['HeimrichHannot\IsotopeBundle\Backend\IsotopePlus', 'updateTemplateData'];
//$GLOBALS['TL_HOOKS']['parseItems']['addPdfViewerToTemplate']   = ['HeimrichHannot\IsotopeBundle\Helper\ProductHelper', 'addPdfViewerToTemplate'];


/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['isotopeBundle'] = [
    'iso_cart_link'               => 'HeimrichHannot\IsotopeBundle\Module\CartLink',
    'iso_product_ranking'         => 'HeimrichHannot\IsotopeBundle\Module\ProductRanking',
    'iso_orderdetails_plus'       => 'HeimrichHannot\IsotopeBundle\Module\OrderDetailsPlus',
    'iso_productlistplus'         => 'HeimrichHannot\IsotopeBundle\Module\ProductListPlus',
    'iso_product_frontend_editor' => 'HeimrichHannot\IsotopeBundle\Module\ProductFrontendEditor',
    'iso_direct_checkout'         => 'HeimrichHannot\IsotopeBundle\Module\DirectCheckout',
    'iso_stockreport'             => 'HeimrichHannot\IsotopeBundle\Module\ModuleStockReport',
    'iso_orderreport'             => 'HeimrichHannot\IsotopeBundle\Module\ModuleOrderReport',
    'iso_productlistslick'        => 'HeimrichHannot\IsotopeBundle\Module\ProductListSlick',
];


/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_iso_product']                                 = 'HeimrichHannot\IsotopeBundle\Model\ProductModel';
$GLOBALS['TL_MODELS'][\Isotope\Model\ProductCollectionItem::getTable()] = 'HeimrichHannot\IsotopeBundle\Model\ProductCollectionItemModel';

/**
 * Product actions
 */
\Isotope\Frontend\ProductAction\Registry::add(new \HeimrichHannot\IsotopeBundle\Action\BookingPlanAction());


/**
 * CSS
 */
if (System::getContainer()->get('huh.utils.container')->isBackend()) {
    $GLOBALS['TL_CSS'][] = 'bundles/heimrichhannotcontaoisotope/css/backend.css|static';
}

/**
 * JS
 */
if (System::getContainer()->get('huh.utils.container')->isFrontend()) {
    $GLOBALS['TL_JAVASCRIPT']['tablesorter'] = 'assets/components/tablesorter/js/tablesorter.min.js|static';
}


if (\Contao\System::getContainer()->get('huh.utils.container')->isFrontend()
    && !class_exists(\HeimrichHannot\EncoreBundle\DependencyInjection\EncoreExtension::class)) {
    $GLOBALS['TL_JAVASCRIPT']['contao-watchlist-bundle'] = 'bundles/heimrichhannotcontaowatchlist/js/contao.isotope-bundle.min.js|static';
}

$GLOBALS['TL_HOOKS']['getPageLayout'][] = ['huh.isotope.ajax_manager', 'ajaxActions'];

/**
 * ajax actions
 */
$GLOBALS['AJAX'][\HeimrichHannot\IsotopeBundle\Manager\AjaxManager::ISOTOPE_AJAX_GROUP] = [
    'actions' => [
        \HeimrichHannot\IsotopeBundle\Manager\AjaxManager::ISOTOPE_AJAX_BOOKING_PLAN_UPDATE => [
            'arguments' => [
                \HeimrichHannot\IsotopeBundle\Manager\AjaxManager::ISOTOPE_AJAX_VARIABLE_PRODUCT_ID,
                \HeimrichHannot\IsotopeBundle\Manager\AjaxManager::ISOTOPE_AJAX_VARIABLE_QUANTITY,
            ],
            'options'   => [],
        ],
    ],
];
