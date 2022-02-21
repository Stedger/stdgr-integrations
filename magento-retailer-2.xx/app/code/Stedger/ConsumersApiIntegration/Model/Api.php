<?php

namespace Stedger\ConsumersApiIntegration\Model;

class Api
{
    private $_url = 'https://api.stedger.com/v1/';

    protected $helper;

    public function __construct(
        \Stedger\ConsumersApiIntegration\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    public function request($type = 'GET', $endpoint, $params = [])
    {
        $secretKey = $this->helper->getConfig('stedgerconsumerintegration/settings/secret_key');

        if ($secretKey) {
            $ch = curl_init($this->_url . $endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $secretKey
            ]);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($type));

            if (count($params)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
            $result = curl_exec($ch);

            return json_decode($result, true);
        }
        return false;
    }
}