<?php

namespace Stedger\APIIntegration\Model;

class Integration
{
    private $storeManager;
    private $product;
    private $formkey;
    private $quote;
    private $quoteManagement;
    private $customerFactory;
    private $customerRepository;
    private $orderService;
    private $country;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface        $storeManager,
        \Magento\Catalog\Model\Product                    $product,
        \Magento\Framework\Data\Form\FormKey              $formkey,
        \Magento\Quote\Model\QuoteFactory                 $quote,
        \Magento\Quote\Model\QuoteManagement              $quoteManagement,
        \Magento\Customer\Model\CustomerFactory           $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService         $orderService,
        \Magento\Directory\Model\Country                  $country
    )
    {
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->country = $country;
    }

    public function createMagentoOrder($apiData)
    {
        $store = $this->storeManager->getStore();

        $shippingAddress = $billingAddress = $this->_getAddress($apiData);

        $customerId = isset($apiData['parentOrder']['company']['recipientRelation']['appSettings']['accountNumber']) ?
            $apiData['parentOrder']['company']['recipientRelation']['appSettings']['accountNumber'] : 0;

        $customer = $this->customerRepository->getById($customerId);

        if (!$customer->getId()) {
            throw new \Exception(__('Customer not found.'));
        } else {
            $billingAddress['email'] = $customer->getEmail();
            $billingAddress['firstname'] = $customer->getFirstname();
            $billingAddress['lastname'] = $customer->getLastname();
        }

        $quote = $this->quote->create();
        $quote->setStore($store);
        $quote->setCurrency();
        $quote->assignCustomer($customer); //Assign quote to customer

        foreach ($apiData['lineItems'] as $lineItem) {
            $product = $this->product->load($lineItem['productVariantForeignId']);
            $quote->addProduct($product, $lineItem['quantity'])
                ->setOriginalCustomPrice($lineItem['price'] / 100)
                ->setStedgerIntegrationId($lineItem['id']);
        }

        $quote->setStedgerIntegrationId($apiData['id']);

        $quote->getBillingAddress()->addData($billingAddress);
        $quote->getShippingAddress()->addData($shippingAddress);

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('flatrate_flatrate');
        $quote->setPaymentMethod('checkmo');
        $quote->setInventoryProcessed(false);
        $quote->save();

        $quote->getPayment()->importData(['method' => 'checkmo']);

        $quote->collectTotals()->save();

        $order = $this->quoteManagement->submit($quote);

        $order->setEmailSent(0);

        return $order->getRealOrderId();
    }

    private function _getAddress($apiData, $type = 'shippingAddress')
    {
        $regions = $this->getRegionsOfCountry($apiData[$type]['country']);

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
            'region_id' => $regions ? $regions[1]['value'] : '',
            'region' => $apiData[$type]['state'],
            'postcode' => $apiData[$type]['zipCode'],
            'telephone' => $apiData[$type]['phone'] ?? '-',
            'fax' => '',
            'save_in_address_book' => 1
        ];
    }

    private function getRegionsOfCountry($countryCode)
    {
        $regionCollection = $this->country->loadByCode($countryCode)->getRegions();
        if (count($regionCollection)) {
            return $regionCollection->loadData()->toOptionArray(false);
        }
        return false;
    }
}