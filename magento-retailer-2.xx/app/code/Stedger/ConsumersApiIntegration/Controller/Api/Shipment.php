<?php

namespace Stedger\ConsumersApiIntegration\Controller\Api;

class Shipment extends \Stedger\ConsumersApiIntegration\Controller\Api
{
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $post = json_decode(file_get_contents('php://input'), true);

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
