<?php

class Stedger_ConsumersApiIntegration_Model_Integration
{
    public function createMagentoProduct($apiData)
    {
        $name = $apiData['title'];
        $description = $apiData['description'];

        $website = Mage::app()->getWebsite();

        $attributeSetId = Mage::getModel('catalog/product')->getDefaultAttributeSetId();

        $images = $apiData['images'];

        $productIds = [];

        foreach ($apiData['variants'] as $i => $itemData) {

            $product = Mage::getModel('catalog/product');
            $product->load($product->getIdBySku($itemData['identifiers']['sku']));

            if ($product && $product->getId()) {
                $productIds[] = $product->getId();

                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

                $stockItem->setData('use_config_backorders', '0');
                $stockItem->setData('backorders', '1');
                $stockItem->setData('is_in_stock', '1');
                $stockItem->save();

                Mage::getSingleton('catalog/product_action')->updateAttributes(
                    [$product->getId()],
                    ['stedger_qty' => $itemData['dropshipStatus']['inventory']],
                    Mage::app()->getStore()->getStoreId()
                );

                continue;
            } else {
                $product = Mage::getModel('catalog/product');
            }

            $product->setSku($itemData['identifiers']['sku']);
            $product->setTypeId('simple');
            $product->setAttributeSetId($attributeSetId);
            $product->setWebsiteIds([$website->getId()]);
            $product->setName($name . ' (' . $itemData['identifiers']['sku'] . ')');
            $product->setDescription($description);
            $product->setShortDescription($description);
            $product->setCost($itemData['zonePrice']['costPrice'] / 100);
            $product->setPrice($itemData['zonePrice']['retailPrice'] / 100);
            $product->setMsrp($itemData['zonePrice']['recommendedRetailPrice'] / 100);
            $product->setBarcode($itemData['identifiers']['barcode']);
            $product->setStedgerIntegrationId($itemData['id']);
            $product->setCreatedFromStedger(1);
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
            $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
            $product->setStoreId(Mage::app()->getStore()->getStoreId());

            if (array_key_exists($i, $images)) {

                $urlToImage = $images[$i]['src'];
                $imageDir = Mage::getBaseDir('media') . DS . 'tmp' . DS . 'stedger' . DS . 'images' . DS;

                if (!file_exists($imageDir)) {
                    mkdir($imageDir, 0777, true);
                }

                $filename = basename($urlToImage);
                $localImage = $imageDir . $filename;

                try {
                    file_put_contents($localImage, file_get_contents($urlToImage));
                } catch (Exception $e) {
                    Mage::helper('stedgerconsumerintegration')->log('Error "product create image": ' . $e->getMessage());
                }

                $product->setMediaGallery(['images' => [], 'values' => []]);
                $product->addImageToMediaGallery($localImage, ['image', 'thumbnail', 'small_image'], false, false);
            }

            $product->setStockData([
                'is_in_stock' => $itemData['dropshipStatus']['onStock'] ? 1 : 0,
                'qty' => 0,
                'use_config_backorders' => 0,
                'backorders' => 1
            ]);

            $product->setStedgerQty($itemData['dropshipStatus']['inventory']);
            $product->setCreatedAt(strtotime('now'));

            try {
                $product->save();

//                Mage::getSingleton('catalog/product_action')->updateAttributes(
//                    [$product->getId()],
//                    ['status' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED],
//                    Mage::app()->getStore()->getStoreId()
//                );

//                $product = Mage::getModel('catalog/product')->load($product->getId());
//                $product->setStatus();
//                $product->save();

            } catch (Exception $e) {
                Mage::helper('stedgerconsumerintegration')->log('Error "product create": ' . $e->getMessage());
            }

            $productIds[] = $product->getId();
        }

        $addTags = Mage::getStoreConfig('stedgerconsumerintegration/products/add_tags');

        if ($addTags && $apiData['tags']) {
            foreach ($apiData['tags'] as $tagName) {
                $tag = Mage::getModel('tag/tag')->load($tagName, 'name');

                if (!$tag->getId()) {
                    $tag = (new Mage_Tag_Model_Tag())->setName($tagName)->save();
                }

                Mage::getModel('tag/tag_relation')->addRelations($tag, $productIds);
            }
        }
    }

    public function updateMagentoProduct($apiData)
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('stedger_integration_id', $apiData['id']);

        if ($product->getId()) {

            try {
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
                $stockItem->setData('is_in_stock', $apiData['dropshipStatus']['onStock'] ? 1 : 0);
                $stockItem->setData('use_config_backorders', '0');
                $stockItem->setData('backorders', '1');
                $stockItem->save();

                Mage::getSingleton('catalog/product_action')->updateAttributes(
                    [$product->getId()],
                    ['stedger_qty' => $apiData['dropshipStatus']['inventory']],
                    Mage::app()->getStore()->getStoreId()
                );

            } catch (Exception $e) {
                Mage::helper('stedgerconsumerintegration')->log('Error "product update": ' . $e->getMessage());
            }
        }
    }

    public function createMagentoShipment($apiData)
    {
        $order = Mage::getModel('sales/order')->loadByAttribute('stedger_integration_id', $apiData['originOrderId']);

        if ($order->canShip()) {
            try {
                $shipProducts = [];

                foreach ($order->getAllItems() as $eachItem) {
                    foreach ($apiData['lineItems'] as $shipItem) {
                        if ($eachItem->getStedgerIntegrationId() == $shipItem['originOrderLineId']) {
                            if ($eachItem->getParentItemId()) {
                                $shipProducts[$eachItem->getParentItemId()] = $shipItem["quantity"];
                            } else {
                                $shipProducts[$eachItem->getId()] = $shipItem["quantity"];
                            }
                        }
                    }
                }

                $shipmentApi = Mage::getModel('sales/order_shipment_api');

                $shipmentIncrementId = $shipmentApi->create($order->getIncrementId(), $shipProducts, '', $order->getCustomerEmail(), 0);

                $shipmentApi->addTrack($shipmentIncrementId, 'custom', $apiData['trackingCompany'], $apiData['trackingNumber']);

                $shipmentApi->sendInfo($shipmentIncrementId);

            } catch (Exception $e) {
                Mage::helper('stedgerconsumerintegration')->log('Error "product update": ' . $e->getMessage());
            }
        }
    }
}