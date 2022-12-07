<?php

namespace Stedger\APIIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Stedger\APIIntegration\Model\StedgerProductRepository;
use Magento\Framework\Message\ManagerInterface;

class CatalogProductDeleteAfter implements ObserverInterface
{
    private $stedgerProductRepository;
    private $messageManager;
    private $logger;

    public function __construct(
        StedgerProductRepository $stedgerProductRepository,
        ManagerInterface         $messageManager,
        LoggerInterface          $logger
    )
    {
        $this->stedgerProductRepository = $stedgerProductRepository;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $this->stedgerProductRepository->updateProduct($product, true);
        } catch (\Exception $e) {
            $this->logger->critical('Error "product delete after": ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this;
    }
}
