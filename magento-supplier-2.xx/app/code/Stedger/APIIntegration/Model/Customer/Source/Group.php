<?php

namespace Stedger\APIIntegration\Model\Customer\Source;

class Group extends \Magento\Customer\Model\Customer\Source\Group
{
    public function toOptionArray()
    {
        $customerGroups = parent::toOptionArray();

        array_unshift($customerGroups, [
            'label' => __('Please select'),
            'value' => '',
        ]);

        return $customerGroups;
    }
}
