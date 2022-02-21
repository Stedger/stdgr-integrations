<?php

namespace Stedger\ConsumersApiIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class SalesOrderShipmentSaveBefore implements ObserverInterface
{
    protected $stockState;
    protected $sourceItemsSaveInterface;
    protected $sourceItemFactory;

    public function __construct(
        \Magento\CatalogInventory\Api\StockStateInterface         $stockState,
        \Magento\InventoryApi\Api\SourceItemsSaveInterface        $sourceItemsSaveInterface,
        \Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory $sourceItemFactory
    )
    {
        $this->stockState = $stockState;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->sourceItemFactory = $sourceItemFactory;
    }

    public function execute(Observer $observer)
    {
        $items = $observer->getEvent()->getShipment()->getAllItems();
        if ($items) {
            foreach ($items as $item) {
                $productId = $item->getProductId();
                $qty = $this->stockState->getStockQty($productId);
                $itemQty = $item->getQty();
                if ($qty < $itemQty) {
                    $sourceItem = $this->sourceItemFactory->create();
                    $sourceItem->setSourceCode('default');
                    $sourceItem->setSku($item->getSku());
                    $sourceItem->setQuantity($itemQty);
                    $sourceItem->setStatus(1);
                    $this->sourceItemsSaveInterface->execute([$sourceItem]);
                }
            }
        }
    }
}
