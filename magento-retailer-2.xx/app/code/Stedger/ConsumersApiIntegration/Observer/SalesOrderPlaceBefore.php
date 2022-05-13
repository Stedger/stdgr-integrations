<?php

namespace Stedger\ConsumersApiIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class SalesOrderPlaceBefore implements ObserverInterface
{
    private $stockRegistry;

    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    )
    {
        $this->stockRegistry = $stockRegistry;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        foreach ($order->getAllItems() as $item) {

            $product = $item->getProduct();

            if (!$product->getStedgerIntegrationId() || !$product->getCreatedFromStedger()) {
                return $this;
            }

            $stockItem = $this->stockRegistry->getStockItem($product->getId());

            $qtyOrdered = $item->getQtyOrdered();
            $stockQty = $stockItem->getQty();
            $stedgerQty = $product->getStedgerQty();

            if ($qtyOrdered > ($stockQty + $stedgerQty)) {
                throw new \Exception(__('Product #%s: The requested qty is not available', $product->getName()));
            }
        }

        return $this;
    }
}
