<?php

namespace Stedger\APIIntegration\Controller\Adminhtml\Integration;

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory = false;

    public function __construct(
        \Magento\Backend\App\Action\Context        $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Stedger_APIIntegration::menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Integration'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Stedger_APIIntegration::menu');
    }
}