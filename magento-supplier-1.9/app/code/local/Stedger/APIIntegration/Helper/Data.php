<?php

class Stedger_APIIntegration_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getProcessIntegrationFilePath()
    {
        return Mage::getBaseDir('var') . DS . 'log' . DS . 'stedgerintegrationprocess.log';
    }
}