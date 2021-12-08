<?php

class Stedger_ConsumersApiIntegration_Model_System_Config_Backend_Keys extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        if ($this->getValue()) {

            $url = Mage::getUrl('stedgerconsumerintegration/api/product');
            $events = ["connected_product.added"];

            $webhooks = Mage::getModel('stedgerconsumerintegration/api')->request('get', 'webhooks');

            $addWebhook = true;
            if ($webhooks && $webhooks["totalCount"]) {
                foreach ($webhooks["nodes"] as $webhook) {
                    if ($webhook["url"] == $url && $webhook["enabledEvents"] == $events) {
                        $addWebhook = false;
                        break;
                    }
                }
            }

            if ($addWebhook === true) {
                Mage::getModel('stedgerconsumerintegration/api')->request('post', 'webhooks', [
                    'description' => 'Products',
                    'enabledEvents' => $events,
                    'url' => $url,
                ]);

                Mage::getModel('stedgerconsumerintegration/api')->request('post', 'webhooks', [
                    'description' => 'Update Product',
                    'enabledEvents' => ["connected_product.variant.updated"],
                    'url' => Mage::getUrl('stedgerconsumerintegration/api/updateproduct'),
                ]);

                Mage::getModel('stedgerconsumerintegration/api')->request('post', 'webhooks', [
                    'description' => 'Update Product',
                    'enabledEvents' => ["fulfillment.created"],
                    'url' => Mage::getUrl('stedgerconsumerintegration/api/shipment'),
                ]);
            }
        }
    }
}