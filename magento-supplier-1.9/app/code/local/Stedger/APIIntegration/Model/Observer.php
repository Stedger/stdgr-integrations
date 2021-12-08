<?php

class Stedger_APIIntegration_Model_Observer
{
    public function catalogProductSaveAfter(Varien_Event_Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $this->updateProduct($product);
        } catch (\Exception $e) {
            $errors = $observer->getError();
            if ($observer->getError()) {
                $errors->message = 'ERROR: ' . $e->getMessage();
            }
            Mage::log('Error "product save after": ' . $e->getMessage(), null, 'stedgerintegration.log', true);
        }
    }

    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $this->updateProduct($product, true);
        } catch (\Exception $e) {
            Mage::log('Error "product delete after": ' . $e->getMessage(), null, 'stedgerintegration.log', true);
        }
    }

    private function updateProduct($product, $deleted = false)
    {
        if ($product->getTypeId() == 'configurable') {
            $product = Mage::getModel('catalog/product')->load($product->getId());
            $this->sendProduct($product, $deleted);
        } elseif ($product->getTypeId() == 'simple') {

            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

            $childId = $product->getId();

            $product = Mage::getModel('catalog/product')->load($childId);

            $this->sendProduct($product, false, $deleted ? $childId : false, true);

            $ids = $connection->fetchCol(
                'SELECT parent_id FROM ' . $connection->getTableName('catalog_product_relation') . ' WHERE `child_id` = "' . $childId . '"');

            foreach ($ids as $id) {
                $product = Mage::getModel('catalog/product')->load($id);

                $this->sendProduct($product, false, $deleted ? $childId : false);
            }

        }
    }

    private function sendProduct($product, $deleted = false, $deletedChildId = false, $simple = false)
    {
        $productStores = $product->getStoreIds();
        $storeId = isset($productStores[0]) ? $productStores[0] : 1;
        $store = Mage::app()->getStore($storeId);

        $storeLocale = Mage::getStoreConfig('general/locale/code', $storeId);

        $countryCode = substr($storeLocale, 3);
        $localeCode = substr($storeLocale, 0, 2);

        $apiProduct = [
            "foreignId" => $product->getId(),
            "title" => $product->getName(),
            "vendor" => $product->getAttributeText('manufacturer') ? $product->getAttributeText('manufacturer') : '',
            "description" => $product->getDescription(),
            "locale" => $localeCode,
        ];

        if (count($productStores) > 1) {
            foreach ($productStores as $productStoreId) {
                if ($storeId == $productStoreId) continue;
                $storeLocale = Mage::getStoreConfig('general/locale/code', $storeId);
                $localeCode = substr($storeLocale, 0, 2);

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
            $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
        }

        foreach ($childProducts as $childProduct) {

            $childProduct = Mage::getModel('catalog/product')->load($childProduct->getId());

            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);

            $apiProduct["variants"][] = [
                "foreignId" => $childProduct->getId(),
                "identifiers" => [
                    "sku" => $childProduct->getSku(),
                    "barcode" => $childProduct->getBarcode() ? $childProduct->getBarcode() : ''
                ],
                "inventory" => (int)$stock->getQty(),
                "isAvailable" => $deleted === false && $product->getStatus() == 1 && $childProduct->getStatus() == 1 && $deletedChildId != $childProduct->getId(),
                "zones" => [
                    [
                        "code" => $countryCode,
                        "currency" => strtolower($store->getCurrentCurrencyCode()),
                        "tradePrice" => $childProduct->getFinalPrice() * 100,
                        "recommendedRetailPrice" => $childProduct->getMsrp() * 100,
                    ]
                ]
            ];

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

        Mage::getModel('stedgerintegration/api')->request('POST', 'connected_products/batch_upsert', [$apiProduct]);
    }

    public function salesShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        try {
            $shipment = $observer->getShipment();
            $order = $shipment->getOrder();

            $orderStedgerIntegrationId = $order->getStedgerIntegrationId();
            if ($orderStedgerIntegrationId) {

                $stedgerShipment = [
                    'trackingCompany' => '',
                    'trackingNumber' => '',
                    'trackingUrl' => '',
                ];

                $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipment->getIncrementId());

                if (count($shipment->getAllTracks())) {
                    foreach ($shipment->getAllTracks() as $track) {
                        $stedgerShipment['trackingCompany'] = $track->getTitle();
                        $stedgerShipment['trackingNumber'] = $track->getTrackNumber();
                        break;
                    }
                }

                foreach ($shipment->getAllItems() as $item) {
                    $stedgerShipment['lineItems'][] = [
                        'orderLineId' => $item->getOrderItem()->getStedgerIntegrationId(),
                        'quantity' => (int)$item->getQty(),
                    ];
                }

                Mage::getModel('stedgerintegration/api')->request('POST', "orders/$orderStedgerIntegrationId/fulfillments", $stedgerShipment);
            }
        } catch (\Exception $e) {
            Mage::log('Error "shipment save after": ' . $e->getMessage(), null, 'stedgerintegration.log', true);
        }
    }
}