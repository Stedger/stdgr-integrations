<?php

class Stedger_ConsumersApiIntegration_Model_System_Config_Source_Product_Inventory
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
            'stedger_qty' => Mage::helper('catalog')->__('Stedger QTY'),
            'magento_qty' => Mage::helper('catalog')->__('Magento QTY')
        );
    }
}