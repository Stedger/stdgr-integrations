<?php

namespace Stedger\ConsumersApiIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class CheckoutCartAdd implements ObserverInterface
{
    private $request;
    private $productFactory;
    private $stockItemRepository;

    public function __construct(
        \Magento\Framework\App\RequestInterface                   $request,
        \Magento\Catalog\Model\ProductFactory                     $productFactory,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
    )
    {
        $this->request = $request;
        $this->productFactory = $productFactory;
        $this->stockItemRepository = $stockItemRepository;
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

        $stockItem = $this->stockItemRepository->get($product->getId());

        $qtyOrdered = $qty ? $qty : 1;
        $stockQty = $stockItem->getQty();
        $stedgerQty = $product->getStedgerQty();

        if ($qtyOrdered > ($stockQty + $stedgerQty)) {
            throw new \Exception(__('Product #%s: The requested qty is not available', $product->getName()));
        }

        return $this;
    }
}
