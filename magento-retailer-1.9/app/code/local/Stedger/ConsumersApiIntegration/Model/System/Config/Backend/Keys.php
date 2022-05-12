<?php

class Stedger_ConsumersApiIntegration_Model_System_Config_Backend_Keys extends Mage_Core_Model_Config_Data
{
    private function _getUrl($path)
    {
        $storeCode = $this->getStoreCode();
        $websiteCode = $this->getWebsiteCode();

        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        if ($storeCode) {
            $store = Mage::app()->getStore($storeCode);
            $baseUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        } elseif ($websiteCode) {
            $store = Mage::app()->getWebsite($websiteCode)->getDefaultStore();
            $baseUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        }

        return $baseUrl . $path;
    }

    protected function _afterSave()
    {
        if ($this->getValue()) {

            $secretKey = $this->getValue();

            $url = $this->_getUrl('stedgerconsumerintegration/api/product');
            $events = ["connected_product.added"];

            $webhooks = Mage::getModel('stedgerconsumerintegration/api')->request('get', 'webhooks', [], $secretKey);

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
                ], $secretKey);

                Mage::getModel('stedgerconsumerintegration/api')->request('post', 'webhooks', [
                    'description' => 'Update Product',
                    'enabledEvents' => ["connected_product.variant.updated"],
                    'url' => $this->_getUrl('stedgerconsumerintegration/api/updateproduct'),
                ], $secretKey);

                Mage::getModel('stedgerconsumerintegration/api')->request('post', 'webhooks', [
                    'description' => 'Fulfillment Created',
                    'enabledEvents' => ["fulfillment.created"],
                    'url' => $this->_getUrl('stedgerconsumerintegration/api/shipment'),
                ], $secretKey);
            }
        }
    }
}