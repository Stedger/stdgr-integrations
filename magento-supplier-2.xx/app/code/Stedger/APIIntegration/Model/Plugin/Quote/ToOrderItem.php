<?php

namespace Stedger\APIIntegration\Model\Plugin\Quote;

class ToOrderItem
{
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem  $subject,
        \Closure                                     $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
                                                     $additional = []
    )
    {
        /** @var $orderItem Item */
        $orderItem = $proceed($item, $additional);
        $orderItem->setStedgerIntegrationId($item->getStedgerIntegrationId());
        return $orderItem;
    }
}