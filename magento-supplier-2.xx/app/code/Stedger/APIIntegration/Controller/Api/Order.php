<?php

namespace Stedger\APIIntegration\Controller\Api;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Stedger\APIIntegration\Helper\Data;
use Stedger\APIIntegration\Model\Integration;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;

class Order extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    private $helper;
    private $integration;
    private $logger;
    private $resultJsonFactory;

    public function __construct(
        Context         $context,
        Integration     $integration,
        Data            $helper,
        LoggerInterface $logger,
        JsonFactory     $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
        $this->integration = $integration;
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(file_get_contents('php://input'));

        $resultJson = $this->resultJsonFactory->create();
        try {
            $post = json_decode(file_get_contents('php://input'), true);

//            $post = json_decode('{"publicKey":"pubk_e27CXE9jbohDm3ezKEmXB2","webhookId":"wh_qg5cAhYLsExabLZK5xAzWp","topic":"child_order.received","data":{"__typename":"Order","id":"or_vztbh6PpR5YUDQtXBuwCvt","foreignId":null,"name":"#100000104","metadata":{},"fulfillmentStatus":"unfulfilled","requestResponse":{"status":"submitted","message":""},"currency":"dkk","canceledAt":null,"createdAt":1642673317998,"parentOrder":{"__typename":"Order","id":"or_ubMSWDUm87HVFwTc8ftexQ","name":"#100000104","company":{"__typename":"Company","id":"comp_iZ7cnkVDEeSo2zVAGiwPFd","name":"Retailer Demo","recipientRelation":{"__typename":"RecipientRelation","appSettings":{"accountNumber":"2"}}}},"shippingMethod":{"name":"Flat Rate - Fixed","code":null,"carrier":"UPS","method":"home","locationId":null},"senderAddress":{"name":null,"address1":null,"address2":null,"zipCode":null,"city":null,"country":null,"phone":null,"email":null},"customerAddress":{"firstName":"Artyom","lastName":"Proshyn","address1":"23 August Street, 67-a, 12","address2":"","zipCode":"61103","city":"Kharkiv","state":null,"country":"DK","company":"Lantera","phone":"+380933567844","email":"art@lantera.co"},"shippingAddress":{"firstName":"Artyom","lastName":"Proshyn","address1":"23 August Street, 67-a, 12","address2":"","zipCode":"61103","city":"Kharkiv","state":null,"country":"DK","company":"Lantera","phone":"+380933567844"},"lineItems":[{"__typename":"OrderLine","id":"oln_vzuhMzEuY7zfP4nccFzohw","name":"Very new test for Kristoffer","identifiers":{"barcode":null,"sku":"Very new test for Kristoffer"},"quantity":1,"metadata":{},"isIgnored":false,"ignoredReason":null,"price":700,"priceWithTax":875,"productVariantId":"pvar_dtZJm7nZyjuVNrjQ8wmZY8","productVariantForeignId":"8"}]}}', true);

            if ($post['publicKey'] != $this->helper->getConfig('stedgerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }
            $this->integration->createMagentoOrder($post['data']);
            $response = ['status' => 'accepted', 'message' => ''];
        } catch (\Exception $e) {
            $this->logger->critical('Error "order": ', ['exception' => $e]);
            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }
        return $resultJson->setData($response);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
