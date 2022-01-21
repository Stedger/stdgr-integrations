<?php

namespace Stedger\APIIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Stedger\APIIntegration\Observer\ProductTrait;

class CatalogProductSaveAfter implements ObserverInterface
{
    use ProductTrait;

    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $this->updateProduct($product);
        } catch (\Exception $e) {
            $errors = $observer->getError();
            if ($observer->getError()) {
                $errors->setMessage('ERROR: ' . $e->getMessage());
            }
            $this->logger->critical('Error "product save after": ' . $e->getMessage(), ['exception' => $e]);
        }

        return $this;
    }
}
