<?php

namespace Stedger\ConsumersApiIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class SalesOrderSaveAfter implements ObserverInterface
{
    private $stockRegistry;
    private $api;

    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Stedger\ConsumersApiIntegration\Model\Api           $api
    )
    {
        $this->stockRegistry = $stockRegistry;
        $this->api = $api;
    }

    public function execute(Observer $observer)
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
                continue;
            }

            $stockItem = $this->stockRegistry->getStockItem($product->getId());

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

        if (array_key_exists('lineItems', $apiOrder) === false) {
            return $this;
        }

        $stedgerOrder = $this->api->request('POST', 'orders', $apiOrder);

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
