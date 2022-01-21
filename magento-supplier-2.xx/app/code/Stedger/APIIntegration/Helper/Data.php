<?php

namespace Stedger\APIIntegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Filesystem\DirectoryList;

class Data extends AbstractHelper
{
    protected $dir;

    public function __construct(
        Context       $context,
        DirectoryList $dir
    )
    {
        parent::__construct($context);
        $this->dir = $dir;
    }

    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getProcessIntegrationFilePath()
    {
        return $this->dir->getPath('var') . '/log/stedgerintegrationprocess.log';
    }
}