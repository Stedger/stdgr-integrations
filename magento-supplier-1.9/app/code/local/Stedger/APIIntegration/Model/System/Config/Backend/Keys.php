<?php

class Stedger_APIIntegration_Model_System_Config_Backend_Keys extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        if ($this->getValue()) {

            $url = Mage::getUrl('stedgerintegration/api/order');
            $events = ["child_order.received"];

            $webhooks = Mage::getModel('stedgerintegration/api')->request('get', 'webhooks');

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
                Mage::getModel('stedgerintegration/api')->request('post', 'webhooks', [
                    'description' => 'Orders',
                    'enabledEvents' => $events,
                    'url' => $url,
                ]);

                Mage::getModel('stedgerintegration/api')->request('post', 'recipient_relations/settings',
                    [
                        'appSettingsSchema' => [
                            [
                                'key' => 'accountNumber',
                                'label' => 'Account Number',
                                'required' => true
                            ]
                        ]
                    ]);
            }
        }
    }
}