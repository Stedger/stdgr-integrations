<?php

class Stedger_ConsumersApiIntegration_Model_System_Config_Source_Product_Attributecode
{
    public function toOptionArray()
    {
        $eavConfig = Mage::getSingleton('eav/config');

        $entityType = $eavConfig->getEntityType('catalog_product');

        $attributeCollection = $entityType->getEntityAttributeCollection();
        $attributesInfo = Mage::getResourceModel($attributeCollection)
            ->setEntityTypeFilter($entityType)
            ->getData();

        $items = array(
            array(
                'value' => '',
                'label' => Mage::helper('catalog')->__('-- Please Select --')
            )
        );

        foreach ($attributesInfo as $attribute) {
            if ($attribute['frontend_label']) {
                $items[$attribute['attribute_code']] = $attribute['frontend_label'];
            }
        }

        return $items;
    }
}
