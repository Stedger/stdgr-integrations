<?php

namespace Stedger\ConsumersApiIntegration\Model;

class Integration
{
    private $storeManager;
    private $productFactory;
    private $stockItemRepository;
    private $productAction;
    private $directoryList;
    private $logger;
    private $api;
    private $convertOrder;
    private $shipmentNotifier;
    private $trackFactory;
    private $orderFactory;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface                $storeManager,
        \Magento\Catalog\Model\ProductFactory                     $productFactory,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Magento\Catalog\Model\ResourceModel\Product\Action       $productAction,
        \Magento\Framework\App\Filesystem\DirectoryList           $directoryList,
        \Psr\Log\LoggerInterface                                  $logger,
        \Stedger\ConsumersApiIntegration\Model\Api                $api,
        \Magento\Sales\Model\Convert\Order                        $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier                  $shipmentNotifier,
        \Magento\Sales\Model\Order\Shipment\TrackFactory          $trackFactory,
        \Magento\Sales\Model\OrderFactory                         $orderFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->productAction = $productAction;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->api = $api;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->trackFactory = $trackFactory;
        $this->orderFactory = $orderFactory;
    }

    public function createMagentoProduct($apiData)
    {
        $name = $apiData['title'];
        $description = $apiData['description'];

        $websiteId = $this->storeManager->getStore()->getWebsiteId();;

        $images = $apiData['images'];

        $productIds = [];

        foreach ($apiData['variants'] as $i => $itemData) {

            $product = $this->productFactory->create();
            $product->load($product->getIdBySku($itemData['identifiers']['sku']));

            if ($product && $product->getId()) {
                $productIds[] = $product->getId();

                $stockItem = $this->stockItemRepository->get($product->getId());

                $stockItem->setData('use_config_backorders', '0');
                $stockItem->setData('backorders', '1');
                $stockItem->setData('is_in_stock', '1');
                $stockItem->save();

                $this->productAction->updateAttributes(
                    [$product->getId()],
                    ['stedger_qty' => $itemData['dropshipStatus']['inventory']],
                    $this->storeManager->getStore()->getId()
                );

                continue;
            } else {
                $product = $this->productFactory->create();
            }

            $product->setSku($itemData['identifiers']['sku']);
            $product->setTypeId('simple');
            $product->setAttributeSetId($product->getDefaultAttributeSetId());
            $product->setWebsiteIds([$websiteId]);
            $product->setName($name);
            $product->setDescription($description);
            $product->setShortDescription($description);
            $product->setCost($itemData['zonePrice']['costPrice'] / 100);
            $product->setPrice($itemData['zonePrice']['retailPrice'] / 100);
            $product->setMsrp($itemData['zonePrice']['recommendedRetailPrice'] / 100);
            $product->setBarcode($itemData['identifiers']['barcode']);
            $product->setStedgerIntegrationId($itemData['id']);
            $product->setCreatedFromStedger(1);
            $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
            $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
            $product->setStoreId($this->storeManager->getStore()->getId());

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
            } catch (\Exception $e) {
                $this->logger->critical('Error "product create": ', ['exception' => $e]);
            }

            if ($images) {

//                $storeID = Mage_Core_Model_App::ADMIN_STORE_ID;
//
//                Mage::app()->setCurrentStore($storeID);

                $product = $this->productFactory->create()->load($product->getId());

                $product->setMediaGallery(['images' => [], 'values' => []]);

                foreach ($images as $i => $image) {

                    $urlToImage = $image['src'];

                    $imageDir = $this->directoryList->getPath('media') . '/tmp/stedger/images/';

                    if (!file_exists($imageDir)) {
                        mkdir($imageDir, 0777, true);
                    }

                    $filename = basename($urlToImage);
                    $localImage = $imageDir . $filename;

                    try {
                        file_put_contents($localImage, file_get_contents($urlToImage));
                    } catch (\Exception $e) {
                        $this->logger->critical('Error "product create image": ', ['exception' => $e]);
                    }

                    $imageRole = [];
                    if ($i == 0) {
                        $imageRole = ['image', 'thumbnail', 'small_image'];
                    }

                    $product->addImageToMediaGallery($localImage, $imageRole, false, false);
                }

                try {
                    $product->save();
                } catch (\Exception $e) {
                    $this->logger->critical('Error "product  add images": ', ['exception' => $e]);
                }
            }

            $productIds[] = $product->getId();
        }

        $this->api->request('POST', 'connected_products/' . $apiData['id'] . '/status', ['status' => 'connected']);
    }

    public function updateMagentoProduct($apiData)
    {
        $product = $this->productFactory->create()->loadByAttribute('stedger_integration_id', $apiData['id']);

        if ($product->getId()) {

            try {
                $stockItem = $this->stockItemRepository->get($product->getId());
                $stockItem->setData('is_in_stock', $apiData['dropshipStatus']['onStock'] ? 1 : 0);
                $stockItem->setData('use_config_backorders', '0');
                $stockItem->setData('backorders', '1');
                $stockItem->save();

                $this->productAction->updateAttributes(
                    [$product->getId()],
                    ['stedger_qty' => $apiData['dropshipStatus']['inventory']],
                    $this->storeManager->getStore()->getId()
                );

            } catch (Exception $e) {
                $this->logger->critical('Error "product update": ', ['exception' => $e]);
            }
        }
    }

    public function createMagentoShipment($apiData)
    {
        $order = $this->orderFactory->create()->loadByAttribute('stedger_integration_id', $apiData['originOrderId']);

        if ($order->canShip()) {
            try {
                $shipment = $this->convertOrder->toShipment($order);

                foreach ($order->getAllItems() as $orderItem) {
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }

                    foreach ($apiData['lineItems'] as $shipItem) {
                        if ($orderItem->getStedgerIntegrationId() == $shipItem['originOrderLineId']) {
                            if ($orderItem->getParentItemId()) {
                                $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem->getParentItem())->setQty($shipItem["quantity"]);
                                $shipment->addItem($shipmentItem);
                            } else {
                                $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($shipItem["quantity"]);
                                $shipment->addItem($shipmentItem);
                            }
                        }
                    }
                }

                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);

                $shipment->save();
                $shipment->getOrder()->save();


                $data = [
                    'carrier_code' => 'custom',
                    'title' => $apiData['trackingCompany'],
                    'number' => $apiData['trackingNumber'],
                ];

                $track = $this->trackFactory->create()->addData($data);
                $shipment->addTrack($track)->save();

                $this->shipmentNotifier->notify($shipment);

            } catch (Exception $e) {
                $this->logger->critical('Error "shipment create": ', ['exception' => $e]);
            }
        }
    }
}