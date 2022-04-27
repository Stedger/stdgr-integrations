<?php

class Stedger_ConsumersApiIntegration_Model_System_Config_Source_Product_Taxclass extends Mage_Tax_Model_Class_Source_Product
{
    public function toOptionArray()
    {
        return $this->getAllOptions(true);
    }
}
