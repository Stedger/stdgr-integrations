<?php

namespace Stedger\ConsumersApiIntegration\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Stedger\ConsumersApiIntegration\Helper\Data;
use Stedger\ConsumersApiIntegration\Model\Integration;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CsrfAwareActionInterface;

abstract class Api extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $helper;
    protected $integration;
    protected $resultJsonFactory;
    protected $logger;

    public function __construct(
        Context         $context,
        Integration     $integration,
        Data            $helper,
        JsonFactory     $resultJsonFactory,
        LoggerInterface $logger
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
        $this->integration = $integration;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
