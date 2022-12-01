<?php

namespace Stedger\APIIntegration\Block\Adminhtml;

class Integration extends \Magento\Backend\Block\Template
{
    private $helper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Stedger\APIIntegration\Helper\Data     $helper,
        array                                   $data = []
    )
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    public function canSync()
    {
        if (
            $this->helper->getConfig('stedgerintegration/settings/public_key') &&
            $this->helper->getConfig('stedgerintegration/settings/secret_key')
        ) {
            return true;
        }

        return false;
    }
}