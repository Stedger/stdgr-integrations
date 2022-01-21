<?php

namespace Stedger\APIIntegration\Controller\Adminhtml\Integration;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Stedger\APIIntegration\Helper\Data;

class Getprocess extends \Magento\Backend\App\Action
{
    private $helper;
    private $resultJsonFactory;

    public function __construct(
        Context $context,
        Data $helper,
        JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();


        $logFile = $this->helper->getProcessIntegrationFilePath();

        $lines = explode('|', file_get_contents($logFile));
        $html = '<p>' . implode('</p><p>', $lines) . '</p>';

        return $resultJson->setData(['content' => $html]);
    }
}