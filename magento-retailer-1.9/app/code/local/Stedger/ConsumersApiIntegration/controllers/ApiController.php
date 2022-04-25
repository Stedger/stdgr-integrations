<?php

class Stedger_ConsumersApiIntegration_ApiController extends Mage_Core_Controller_Front_Action
{
    public function productAction()
    {
        try {
            $post = json_decode(file_get_contents('php://input'), true);

            Mage::helper('stedgerconsumerintegration')->log(file_get_contents('php://input'));

            if ($post['publicKey'] != Mage::getStoreConfig('stedgerconsumerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }

            Mage::getModel('stedgerconsumerintegration/integration')->createMagentoProduct($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];

        } catch (\Exception $e) {
            Mage::helper('stedgerconsumerintegration')->log('Error "product": ' . $e->getMessage() . ' | ' . json_encode([$post]));

            $response = ['status' => 'rejected', 'message' => $e->getMessage()];

            var_dump($response);die();
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function updateproductAction()
    {
        try {
            $post = json_decode(file_get_contents('php://input'), true);

            if ($post['publicKey'] != Mage::getStoreConfig('stedgerconsumerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }

            Mage::getModel('stedgerconsumerintegration/integration')->updateMagentoProduct($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];

        } catch (\Exception $e) {
            Mage::helper('stedgerconsumerintegration')->log('Error "product": ' . $e->getMessage() . ' | ' . json_encode([$post]));

            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function shipmentAction()
    {
        try {
            $post = json_decode(file_get_contents('php://input'), true);

            Mage::helper('stedgerconsumerintegration')->log(file_get_contents('php://input'));

            if ($post['publicKey'] != Mage::getStoreConfig('stedgerconsumerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }

            Mage::getModel('stedgerconsumerintegration/integration')->createMagentoShipment($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];

        } catch (\Exception $e) {
            Mage::helper('stedgerconsumerintegration')->log('Error "product": ' . $e->getMessage() . ' | ' . json_encode([$post]));

            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
