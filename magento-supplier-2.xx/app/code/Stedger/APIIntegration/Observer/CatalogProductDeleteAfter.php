<?php

namespace Stedger\APIIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Stedger\APIIntegration\Model\StedgerProductRepository;

class CatalogProductDeleteAfter implements ObserverInterface
{
    private $stedgerProductRepository;
    private $logger;

    public function __construct(
        StedgerProductRepository $stedgerProductRepository,
        LoggerInterface          $logger
    )
    {
        $this->stedgerProductRepository = $stedgerProductRepository;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $this->stedgerProductRepository->updateProduct($product, true);
        } catch (\Exception $e) {
            $this->logger->critical('Error "product delete after": ' . $e->getMessage(), ['exception' => $e]);
        }
        return $this;
    }
}
