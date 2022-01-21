<?php

namespace Stedger\APIIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');

        $order->setData('stedger_integration_id', $quote->getData('stedger_integration_id'));

        return $this;
    }
}