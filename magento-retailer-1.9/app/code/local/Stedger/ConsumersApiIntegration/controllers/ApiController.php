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
            $product = Mage::getModel('catalog/product');
            $product->setStoreId(1);
            $product->setWebsiteIds(array(1));
            $product->setTypeId('simple');
            $product->addData(array(
                'name' => 'Custom Name 123',
                'attribute_set_id' => $product->getDefaultAttributeSetId(),
                'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,      // because I don't want to show on frontend
                'weight' => 1,
                'sku' => 'custom1233',
                'tax_class_id' => 0,
                'description' => 'desciption',
                'short_description' => 'short desciption',
                'stock_data' => array(
                    'manage_stock' => 1,
                    'qty' => 999,
                    'is_in_stock' => 1
                ),
            ));
            $product->save();
            d('sdf');

            $post = json_decode(file_get_contents('php://input'), true);

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
