<?php

namespace Stedger\APIIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Stedger\APIIntegration\Observer\ProductTrait;

class CatalogProductDeleteAfter implements ObserverInterface
{
    use ProductTrait;

    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $this->updateProduct($product, true);
        } catch (\Exception $e) {
            $this->logger->critical('Error "product delete after": ' . $e->getMessage(), ['exception' => $e]);
        }
        return $this;
    }
}
