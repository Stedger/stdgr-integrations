<?php

namespace Stedger\ConsumersApiIntegration\Controller\Api;

class Shipment extends \Stedger\ConsumersApiIntegration\Controller\Api
{
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info(file_get_contents('php://input'));
            
            $post = json_decode(file_get_contents('php://input'), true);

//            $post = json_decode(
//                '{
//  "publicKey": "pubk_ursi5sU7uMc46Q7t3cZ1mQ",
//  "webhookId": "wh_idwScxxXrCV5ihjwTicRhJ",
//  "topic": "fulfillment.created",
//  "data": {
//    "id": "11111",
//    "trackingCompany": "GLS",
//    "trackingNumber": "08167735579232",
//    "trackingUrl": "https://gls-group.eu/DK/da/find-pakke.html?match=08167735579232",
//    "originOrderId": "111111",
//		"createdAt": 1628438769807,
//    "lineItems": [
//      {
//        "id": "fln_if5FUnSQ1FZa3EbRc7vW9p",
//        "quantity": 3Z,
//        "originOrderLineId": "22222"
//      }
//    ]
//  }
//}'
//                , true);


            if (
                !$this->helper->getConfig('stedgerconsumerintegration/settings/public_key') ||
                $post['publicKey'] != $this->helper->getConfig('stedgerconsumerintegration/settings/public_key')
            ) {
                throw new \Exception('The public key is invalid.');
            }

            $this->integration->createMagentoShipment($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];
        } catch (\Exception $e) {
            $this->logger->critical('Error "shipment": ', ['exception' => $e]);
            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $resultJson->setData($response);
    }
}
