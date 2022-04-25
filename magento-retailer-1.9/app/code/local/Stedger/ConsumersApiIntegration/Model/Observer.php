<?php

class Stedger_ConsumersApiIntegration_Model_Observer
{
    public function checkoutCartAdd(Varien_Event_Observer $observer)
    {
        $qty = Mage::app()->getRequest()->getParam('qty');
        $productId = Mage::app()->getRequest()->getParam('product', 0);

        $product = Mage::getModel('catalog/product')->load($productId);

        if (!$product->getId()) {
            return $this;
        }

        if (!$product->getStedgerIntegrationId() || !$product->getCreatedFromStedger()) {
            return $this;
        }

        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());

        $qtyOrdered = $qty ? $qty : 1;
        $stockQty = $stockItem->getQty();
        $stedgerQty = $product->getStedgerQty();

        if ($qtyOrdered > ($stockQty + $stedgerQty)) {
            throw new Exception(Mage::helper('stedgerconsumerintegration')->__('Product #%s: The requested qty is not available', $product->getName()));
        }
    }

    public function salesOrderPlaceBefore(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        foreach ($order->getAllItems() as $item) {

            $product = $item->getProduct();

            if (!$product->getStedgerIntegrationId() || !$product->getCreatedFromStedger()) {
                return $this;
            }

            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());

            $qtyOrdered = $item->getQtyOrdered();
            $stockQty = $stockItem->getQty();
            $stedgerQty = $product->getStedgerQty();

            if ($qtyOrdered > ($stockQty + $stedgerQty)) {
                throw new Exception(Mage::helper('stedgerconsumerintegration')->__('Product #%s: The requested qty is not available', $product->getName()));
            }
        }
    }

    public function salesOrderSaveAfter(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        $apiOrder = [
            "name" => '#' . $order->getIncrementId(),
            "currency" => $order->getOrderCurrencyCode(),
            "shippingMethod" => [
                "name" => $order->getShippingDescription(),
                "code" => null,
                "carrier" => 'UPS',
                "method" => 'home',
            ],
            "customerAddress" => [
                "firstName" => $billingAddress->getFirstname(),
                "lastName" => $billingAddress->getLastname(),
                "address1" => is_array($billingAddress->getStreet()) && $billingAddress->getStreet() ? $billingAddress->getStreet()[0] : '',
                "address2" => is_array($billingAddress->getStreet()) && array_key_exists('1', $billingAddress->getStreet()) ? $billingAddress->getStreet()[1] : '',
                "zipCode" => $billingAddress->getPostcode(),
                "city" => $billingAddress->getCity(),
                "state" => $billingAddress->getRegion(),
                "country" => $billingAddress->getCountryId(),
                "company" => $billingAddress->getCompany(),
                "phone" => $billingAddress->getTelephone(),
                "email" => $billingAddress->getEmail(),
            ],
            "shippingAddress" => [
                "firstName" => $shippingAddress->getFirstname(),
                "lastName" => $shippingAddress->getLastname(),
                "address1" => is_array($shippingAddress->getStreet()) && $shippingAddress->getStreet() ? $shippingAddress->getStreet()[0] : '',
                "address2" => is_array($shippingAddress->getStreet()) && array_key_exists('1', $shippingAddress->getStreet()) ? $shippingAddress->getStreet()[1] : '',
                "zipCode" => $shippingAddress->getPostcode(),
                "city" => $shippingAddress->getCity(),
                "state" => $shippingAddress->getRegion(),
                "country" => $shippingAddress->getCountryId(),
                "company" => $shippingAddress->getCompany(),
                "phone" => $shippingAddress->getTelephone(),
                "email" => $shippingAddress->getEmail(),
            ],
        ];

        foreach ($order->getAllItems() as $item) {

            $product = $item->getProduct();

            if (!$product->getStedgerIntegrationId()) {
                return $this;
            }

            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());

            $qtyOrdered = $item->getQtyOrdered();
            $stockQty = $stockItem->getQty();

            $qty = $qtyOrdered;

            if ($stockQty >= $qtyOrdered) {
                continue;
            } elseif ($stockQty > 0) {
                $qty = $qtyOrdered - $stockQty;
            }

            $apiOrder['lineItems'][] = [
                "name" => $item->getName(),
                "identifiers" => [
                    "barcode" => $product->getBarcode(),
                    "sku" => $item->getSku()
                ],
                "quantity" => $qty,
                "price" => $item->getPrice() * 100,
                "priceWithTax" => ($item->getPrice() + $item->getTaxAmount()) * 100,
                "productVariantId" => $product->getStedgerIntegrationId(),
            ];
        }

        $stedgerOrder = Mage::getModel('stedgerconsumerintegration/api')->request('POST', 'orders', $apiOrder);

        if ($stedgerOrder && array_key_exists('id', $stedgerOrder)) {
            $order->setStedgerIntegrationId($stedgerOrder['id'])->save();

            foreach ($order->getAllItems() as $item) {
                foreach ($stedgerOrder['lineItems'] as $lineItem) {
                    if ($item->getSku() == $lineItem['identifiers']['sku']) {
                        $item->setStedgerIntegrationId($lineItem['id'])->save();
                    }
                }
            }
        }

        return $this;
    }
}