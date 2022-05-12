<?php

class Stedger_ConsumersApiIntegration_Model_System_Config_Source_Product_Status
{
    public function toOptionArray()
    {
        $res = array(
            array(
                'value' => '',
                'label' => Mage::helper('catalog')->__('-- Please Select --')
            )
        );
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = array(
                'value' => $index,
                'label' => $value
            );
        }
        return $res;
    }

    public function getOptionArray()
    {
        return array(
            Mage_Catalog_Model_Product_Status::STATUS_ENABLED => Mage::helper('catalog')->__('Enabled'),
            Mage_Catalog_Model_Product_Status::STATUS_DISABLED => Mage::helper('catalog')->__('Disabled')
        );
    }
}
