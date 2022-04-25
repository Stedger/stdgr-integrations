<?php

class Stedger_ConsumersApiIntegration_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function log($text)
    {
        Mage::log($text, null, 'stedgerintegration.log', true);
    }
}