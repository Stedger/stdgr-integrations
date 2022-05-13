<?php

namespace Stedger\ConsumersApiIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class CheckoutCartAdd implements ObserverInterface
{
    private $request;
    private $productFactory;
    private $stockRegistry;

    public function __construct(
        \Magento\Framework\App\RequestInterface              $request,
        \Magento\Catalog\Model\ProductFactory                $productFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
    )
    {
        $this->request = $request;
        $this->productFactory = $productFactory;
        $this->stockRegistry = $stockRegistry;
    }

    public function execute(Observer $observer)
    {
        $qty = $this->request->getParam('qty');
        $productId = $this->request->getParam('product', 0);

        $product = $this->productFactory->create()->load($productId);

        if (!$product->getId()) {
            return $this;
        }

        if (!$product->getStedgerIntegrationId() || !$product->getCreatedFromStedger()) {
            return $this;
        }

        $stockItem = $this->stockRegistry->getStockItem($product->getId());

        $qtyOrdered = $qty ? $qty : 1;
        $stockQty = $stockItem->getQty();
        $stedgerQty = $product->getStedgerQty();

        if ($qtyOrdered > ($stockQty + $stedgerQty)) {
            throw new \Exception(__('Product #%s: The requested qty is not available', $product->getName()));
        }

        return $this;
    }
}
