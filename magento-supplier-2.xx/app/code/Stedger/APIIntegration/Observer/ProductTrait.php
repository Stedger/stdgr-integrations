<?php

namespace Stedger\APIIntegration\Observer;

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

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface        $storeManager,
        \Magento\Catalog\Model\ProductFactory             $productFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Stedger\APIIntegration\Model\Api                 $api,
        \Stedger\APIIntegration\Helper\Data               $helper,
        \Magento\Store\Model\App\Emulation                $emulation,
        \Magento\Framework\App\ResourceConnection         $resource
    )
    {
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->stockState = $stockState;
        $this->api = $api;
        $this->helper = $helper;
        $this->emulation = $emulation;
        $this->resource = $resource;
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

            $ids = $this->connection->fetchCol(
                'SELECT parent_id FROM ' . $this->connection->getTableName('catalog_product_relation') . ' WHERE `child_id` = "' . $childId . '"');

            foreach ($ids as $id) {
                $product = $this->productFactory->create()->load($id);

                $this->sendProduct($product, false, $deleted ? $childId : false);
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
                    $apiProduct['tags'][] = $childProduct->getResource()->getAttribute(strtolower($productAttribute['label']))->getFrontend()->getValue($childProduct);
                }
            }

            foreach ($childProduct->getMediaGalleryImages() as $image) {
                $apiProduct["images"][] = [
                    'foreignId' => $image->getId(),
                    'sourceUrl' => $image->getUrl()
                ];
            }
        }

        $this->api->request('POST', 'connected_products/batch_upsert', [$apiProduct], null, $storeId);

        $this->emulation->stopEnvironmentEmulation();
    }
}