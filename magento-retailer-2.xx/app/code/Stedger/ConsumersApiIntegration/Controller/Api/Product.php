<?php

namespace Stedger\ConsumersApiIntegration\Controller\Api;

class Product extends \Stedger\ConsumersApiIntegration\Controller\Api
{
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
//            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/stedgerconsumerintegration.log');
//            $logger = new \Zend_Log();
//            $logger->addWriter($writer);
//            $logger->info(file_get_contents('php://input'));

            $this->logger->info(file_get_contents('php://input'));

            $post = json_decode(file_get_contents('php://input'), true);

            if (
                !$this->helper->getConfig('stedgerconsumerintegration/settings/public_key') ||
                !$post || !is_array($post) || in_array('publicKey', $post) ||
                $post['publicKey'] != $this->helper->getConfig('stedgerconsumerintegration/settings/public_key')
            ) {
                throw new \Exception('The public key is invalid.');
            }

            $this->integration->createMagentoProduct($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];
        } catch (\Exception $e) {
            $this->logger->critical('Error "product": ', ['exception' => $e]);
            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $resultJson->setData($response);
    }
}
