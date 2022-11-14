<?php

namespace Stedger\APIIntegration\Model;

use Stedger\APIIntegration\Helper\Data;

class Api
{
    private $_url = 'https://api.stedger.com/v1/';

    protected $helper;

    public function __construct(
        Data $helper
    )
    {
        $this->helper = $helper;
    }

    public function request($type = 'GET', $endpoint, $params = [], $secretKey = null, $storeId = null)
    {
        if (!$secretKey) {
            $secretKey = $this->helper->getConfig('stedgerintegration/settings/secret_key');
        } elseif ($storeId) {
            $secretKey = $this->helper->getConfig('stedgerintegration/settings/secret_key', $storeId);
        }

        if ($secretKey) {
            $ch = curl_init($this->_url . $endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "stedger-version: 2022-03-01",
                "Content-Type: application/json",
                "Authorization: Bearer " . $secretKey
            ]);

            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($type));

            if (count($params)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
            $result = curl_exec($ch);

            if($result) {
                return json_decode($result, true);
            }
        }
        return false;
    }
}
