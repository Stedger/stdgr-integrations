<?php

class Stedger_APIIntegration_ApiController extends Mage_Core_Controller_Front_Action
{
    public function orderAction()
    {
        try {
            $post = json_decode(file_get_contents('php://input'), true);

            Mage::log(file_get_contents('php://input'), null, 'stedgerintegration.log', true);

            if ($post['publicKey'] != Mage::getStoreConfig('stedgerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }

            Mage::getModel('stedgerintegration/integration')->createMagentoOrder($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];

        } catch (\Exception $e) {
            Mage::log('Error "order": ' . $e->getMessage() . ' | ' . json_encode([$post]), null, 'stedgerintegration.log', true);

            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
