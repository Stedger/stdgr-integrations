<?php

namespace Stedger\APIIntegration\Controller\Adminhtml\Integration;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Stedger\APIIntegration\Helper\Data;
use Magento\Framework\Event\ManagerInterface;

class Start extends \Magento\Backend\App\Action
{
    private $helper;
    private $productCollectionFactory;
    private $resultJsonFactory;
    private $eventManager;

    public function __construct(
        Context $context,
        Data $helper,
        CollectionFactory $productCollectionFactory,
        JsonFactory $resultJsonFactory,
        ManagerInterface $eventManager
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eventManager = $eventManager;
    }

    public function execute()
    {
        session_write_close();

        $resultJson = $this->resultJsonFactory->create();

        $productCollection = $this->productCollectionFactory->create()->addAttributeToSelect('*');

        try {
            $fp = fopen($this->helper->getProcessIntegrationFilePath(), 'w');
            ftruncate($fp, 0);

            $productsCount = count($productCollection);

            if ($productsCount) {
                $i = 1;
                foreach ($productCollection as $product) {

                    $error = new \Magento\Framework\DataObject(['message' => '']);

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $product = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->getById($product->getId());

                    try {
                        $product->setOrigData('website_ids', $product->getWebsiteIds());

                        $this->eventManager->dispatch('catalog_product_save_after', ['product' => $product, 'data_object' => $product, 'error' => $error]);

                        fwrite($fp, $productsCount . ' / ' . $i . ' : ' . $product->getName() . ' ' . $error->getMessage() . ' |');

                    } catch (\Exception $e) {
                        fwrite($fp, $productsCount . ' / ' . $i . ' : ' . $product->getName() . ' ' . $e->getMessage() . ' |');

                    }

                    $i++;
                }
            }
            fclose($fp);

            $response = ['status' => 'success'];

        } catch (\Exception $e) {
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $resultJson->setData($response);
    }
}