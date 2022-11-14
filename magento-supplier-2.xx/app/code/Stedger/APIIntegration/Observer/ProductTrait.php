<?php

namespace Stedger\APIIntegration\Observer;

use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Stedger\APIIntegration\Helper\Data;
use Stedger\APIIntegration\Model\Api;

trait ProductTrait
{
    private $storeManager;
    private $productFactory;
    private $stockState;
    private $api;
    private $helper;
    private $emulation;
    private $resource;
    private $connection;
    public $logger;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        StockStateInterface $stockState,
        Api $api,
        Data $helper,
        Emulation $emulation,
        ResourceConnection $resource,
        LoggerInterface $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->stockState = $stockState;
        $this->api = $api;
        $this->helper = $helper;
        $this->emulation = $emulation;
        $this->resource = $resource;
        $this->logger = $logger;
        $this->connection = $resource->getConnection();
    }

    private function updateProduct($product, $deleted = false)
    {
        if ($product->getTypeId() == 'configurable') {
            $product = $this->productFactory->create()->load($product->getId());
            $this->sendProduct($product, $deleted);
        } elseif ($product->getTypeId() == 'simple') {

            $childId = $product->getId();

            $product = $this->productFactory->create()->load($childId);

            $this->sendProduct($product, false, $deleted ? $childId : false, true);

            $catalogProductRelation = $this->connection->getTableName('catalog_product_relation');

            $isTableExist = $this->connection->isTableExists($catalogProductRelation);

            if($isTableExist) {
                $ids = $this->connection->fetchCol(
                    'SELECT parent_id FROM ' . $this->connection->getTableName('catalog_product_relation') . ' WHERE `child_id` = "' . $childId . '"');

                foreach ($ids as $id) {
                    $product = $this->productFactory->create()->load($id);

                    $this->sendProduct($product, false, $deleted ? $childId : false);
                }
            }
        }
    }

    private function sendProduct($product, $deleted = false, $deletedChildId = false, $simple = false)
    {
        $productStores = $product->getStoreIds();
        $storeId = isset($productStores[0]) ? $productStores[0] : 1;
        $store = $this->storeManager->getStore($storeId);

        $this->emulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $customerGroup = $this->helper->getConfig('stedgerintegration/product_settings/customer_group', $storeId);

        if ($customerGroup) {
            $product->setCustomerGroupId($customerGroup);
        }

        $localeCode = 'dk';
        $countryCode = 'DK';

        $apiProduct = [
            "foreignId" => $product->getId() . '' . 1000,
            "title" => $product->getName(),
            "vendor" => $product->getAttributeText('manufacturer') ? $product->getAttributeText('manufacturer') : '',
            "description" => $product->getDescription() ?? $product->getName(),
            "locale" => $localeCode,
        ];

        $allowCategories = $this->helper->getConfig('stedgerintegration/product_settings/categories', $storeId);

        if ($allowCategories) {
            $allowCategories = explode(',', str_replace(' ', '', $allowCategories));
            $categories = $product->getCategoryIds();

            $in = false;
            foreach ($categories as $category) {
                if (in_array($category, $allowCategories)) {
                    $in = true;
                    break;
                }
            }

            if ($in === false) return false;
        }

        $allowDropshipCategories = $this->helper->getConfig('stedgerintegration/product_settings/allow_dropship_categories', $storeId);

        if (count($productStores) > 1) {
            foreach ($productStores as $productStoreId) {
                if ($storeId == $productStoreId) continue;
                $apiProduct['infoLayers'][] = [
                    'locale' => $localeCode,
                    'info' => [
                        'title' => $product->getResource()->getAttributeRawValue($product->getId(), 'name', $productStoreId),
                        'description' => $product->getResource()->getAttributeRawValue($product->getId(), 'description', $productStoreId),
                    ],
                ];
            }
        }

        if ($simple === true) {
            $childProducts = [$product];
        } else {
            $childProducts = $product->getTypeInstance()->getUsedProducts($product);
        }

        foreach ($childProducts as $childProduct) {

            $childProduct = $this->productFactory->create()->load($childProduct->getId());
            $stockQty = $this->stockState->getStockQty($childProduct->getId(), $childProduct->getStore()->getWebsiteId());

            $variant = [
                "foreignId" => $childProduct->getId(),
                "identifiers" => [
                    "sku" => $childProduct->getSku(),
                    "barcode" => $childProduct->getBarcode() ? $childProduct->getBarcode() : ''
                ],
                "inventory" => (int)$stockQty,
                "isAvailable" => $deleted === false && $product->getStatus() == 1 && $childProduct->getStatus() == 1 && $deletedChildId != $childProduct->getId(),
                "zones" => [
                    [
                        "code" => $countryCode,
                        "currency" => strtolower($store->getCurrentCurrencyCode()),
                        "tradePrice" => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue() * 100,
                        "recommendedRetailPrice" => $childProduct->getMsrp() * 100,
                    ]
                ],
                "weight" => [
                    "net" => $childProduct->getWeight() * 453.59237,
                ]
            ];

            if ($allowDropshipCategories) {
                $allowDropshipCategories = explode(',', str_replace(' ', '', $allowDropshipCategories));
                $categories = $product->getCategoryIds();

                $in = false;
                foreach ($categories as $category) {
                    if (in_array($category, $allowDropshipCategories)) {
                        $in = true;
                        break;
                    }
                }

                if ($in === true) {
                    $variant['acceptsDropshipOrders'] = true;
                }
            } else {
                $variant['acceptsDropshipOrders'] = true;
            }

            $apiProduct["variants"][] = $variant;

            if ($simple === false) {
                $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                foreach ($productAttributeOptions as $productAttribute) {
                    $attribute = $childProduct->getResource()->getAttribute(strtolower($productAttribute['label']));
                    if($attribute) {
                        $apiProduct['tags'][] = $attribute->getFrontend()->getValue($childProduct);
                    }

                }
            }

            foreach ($childProduct->getMediaGalleryImages() as $image) {
                $apiProduct["images"][] = [
                    'foreignId' => $image->getId(),
                    'sourceUrl' => $image->getUrl()
                ];
            }
        }

        $this->api->request('POST', 'connected_products/batch_upsert', [
            'options' => ['ignoreUnknown' => false],
            'products' => [$apiProduct],
        ], null, $storeId);

        $this->emulation->stopEnvironmentEmulation();
    }
}
