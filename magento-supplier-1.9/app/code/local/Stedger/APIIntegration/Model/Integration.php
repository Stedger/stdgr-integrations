<?php

class Stedger_APIIntegration_Model_Integration
{
    public function createMagentoOrder($apiData)
    {
        $store = Mage::app()->getStore();
        $website = Mage::app()->getWebsite();

        $quote = Mage::getModel('sales/quote')->setStoreId($store->getStoreId());

        $shippingAddress = $billingAddress = $this->_getAddress($apiData);

        $customerId = isset($apiData['parentOrder']['company']['recipientRelation']['appSettings'][0]['accountNumber']) ?
            $apiData['parentOrder']['company']['recipientRelation']['appSettings'][0]['accountNumber'] : 0;

        $customer = Mage::getModel('customer/customer')->setWebsiteId($website->getId())->load($customerId);

        if (!$customer->getId()) {
            throw new Exception(Mage::helper('stedgerintegration')->__('Customer not found.'));
        } else {
            $billingAddress['email'] = $customer->getEmail();
            $billingAddress['firstname'] = $customer->getFirstname();
            $billingAddress['lastname'] = $customer->getLastname();
        }

        $quote->assignCustomer($customer);
        $quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());

        foreach ($apiData['lineItems'] as $lineItem) {
            $product = Mage::getModel('catalog/product')->load($lineItem['productVariantForeignId']);
            $quote->addProduct($product, $lineItem['quantity'])->setOriginalCustomPrice($lineItem['price'] / 100)->setStedgerIntegrationId($lineItem['id']);
        }

        $quote->setStedgerIntegrationId($apiData['id']);

        $billingAddressData = $quote->getBillingAddress()->addData($billingAddress);
        $shippingAddressData = $quote->getShippingAddress()->addData($shippingAddress);

        $shippingAddressData->setCollectShippingRates(true)->collectShippingRates();

        $shippingAddressData->setShippingMethod('flatrate_flatrate')->setPaymentMethod('checkmo');

        $quote->getPayment()->importData(['method' => 'checkmo']);

        //Collect totals & save quote
        $quote->collectTotals()->save();

        //Create order from quote
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();

        $incrementId = $service->getOrder()->getRealOrderId();

        return $incrementId;
    }

    private function _getAddress($apiData, $type = 'shippingAddress')
    {
        return [
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $apiData[$type]['firstName'],
            'middlename' => '',
            'lastname' => $apiData[$type]['lastName'],
            'suffix' => '',
            'company' => '',
            'street' => [
                '0' => $apiData[$type]['address1'],
                '1' => $apiData[$type]['address2'],
            ],
            'city' => $apiData[$type]['city'],
            'country_id' => $apiData[$type]['country'],
            'region_id' => '',
            'region' => $apiData[$type]['state'],
            'postcode' => $apiData[$type]['zipCode'],
            'telephone' => $apiData[$type]['phone'] ?? '-',
            'fax' => '',
            'save_in_address_book' => 1
        ];
    }
}