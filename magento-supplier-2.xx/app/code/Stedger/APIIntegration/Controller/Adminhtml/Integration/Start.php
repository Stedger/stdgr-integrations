<?php

namespace Stedger\APIIntegration\Controller\Adminhtml\Integration;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Stedger\APIIntegration\Helper\Data;
use Stedger\APIIntegration\Model\StedgerProductRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Start extends \Magento\Backend\App\Action
{
    private $helper;
    private $productCollectionFactory;
    private $resultJsonFactory;
    private $stedgerProductRepository;
    private $productRepository;

    public function __construct(
        Context                    $context,
        Data                       $helper,
        CollectionFactory          $productCollectionFactory,
        JsonFactory                $resultJsonFactory,
        StedgerProductRepository   $stedgerProductRepository,
        ProductRepositoryInterface $productRepository
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->stedgerProductRepository = $stedgerProductRepository;
        $this->productRepository = $productRepository;
    }

    public function execute()
    {
        session_write_close();

        $resultJson = $this->resultJsonFactory->create();

        $productCollection = $this->productCollectionFactory->create()->addAttributeToSelect('*');

        $allowCategories = $this->helper->getConfig('stedgerintegration/product_settings/categories');

        if ($allowCategories) {
            $allowCategories = explode(',', str_replace(' ', '', $allowCategories));
            $productCollection->addCategoriesFilter(['in' => $allowCategories]);
        }

        try {
            $fp = fopen($this->helper->getProcessIntegrationFilePath(), 'w');
            ftruncate($fp, 0);

            $productsCount = count($productCollection);

            if ($productsCount) {
                $i = 1;

                foreach ($productCollection as $product) {
                    try {
                        $product = $this->productRepository->getById($product->getId());
                        $product->setOrigData('website_ids', $product->getWebsiteIds());
                        $this->stedgerProductRepository->updateProduct($product);
                        fwrite($fp, $productsCount . ' / ' . $i . ' : ' . $product->getName() . ' |');
                    } catch (\Exception $e) {
                        fwrite($fp, $productsCount . ' / ' . $i . ' : ' . $product->getName() . ' ' . $e->getMessage() . ' |');
                    }

                    $i++;
                }
            } else {
                fwrite($fp, 'Products not found.');
            }
            fclose($fp);

            $response = ['status' => 'success'];

        } catch (\Exception $e) {
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $resultJson->setData($response);
    }
}