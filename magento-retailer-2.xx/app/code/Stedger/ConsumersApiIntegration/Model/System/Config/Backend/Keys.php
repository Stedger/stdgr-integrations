<?php

namespace Stedger\ConsumersApiIntegration\Model\System\Config\Backend;

class Keys extends \Magento\Framework\App\Config\Value
{
    protected $urlHelper;
    protected $storeManager;
    protected $request;
    protected $api;

    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface      $config,
        \Magento\Framework\App\Cache\TypeListInterface          $cacheTypeList,
        \Magento\Store\Model\StoreManagerInterface              $storeManager,
        \Magento\Framework\Url                                  $urlHelper,
        \Magento\Framework\App\Request\Http                     $request,
        \Stedger\ConsumersApiIntegration\Model\Api              $api,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
    )
    {
        $this->storeManager = $storeManager;
        $this->urlHelper = $urlHelper;
        $this->request = $request;
        $this->api = $api;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        $secretKey = $this->getValue();

        if ($secretKey) {
            $storeId = $this->request->getParam('store') ?? $this->storeManager->getDefaultStoreView()->getStoreId();

            $url = $this->urlHelper->getUrl('stedgerconsumerintegration/api/product', ['_scope' => $storeId]);

            $events = ['connected_product.added'];

            $webhooks = $this->api->request('get', 'webhooks', [], $secretKey);

            $addWebhook = true;
            if ($webhooks && $webhooks["totalCount"]) {
                foreach ($webhooks["nodes"] as $webhook) {
                    if ($webhook["url"] == $url && $webhook["enabledEvents"] == $events) {
                        $addWebhook = false;
                        break;
                    }
                }
            }

            if ($addWebhook === true) {

                $this->api->request('post', 'webhooks', [
                    'description' => 'Products',
                    'enabledEvents' => $events,
                    'url' => $url,
                ], $secretKey);

                $this->api->request('post', 'webhooks', [
                    'description' => 'Update Product',
                    'enabledEvents' => ['connected_product.variant.updated'],
                    'url' => $this->urlHelper->getUrl('stedgerconsumerintegration/api/updateproduct', ['_scope' => $storeId]),
                ], $secretKey);

                $this->api->request('post', 'webhooks', [
                    'description' => 'Fulfillment Created',
                    'enabledEvents' => ['fulfillment.created'],
                    'url' => $this->urlHelper->getUrl('stedgerconsumerintegration/api/shipment', ['_scope' => $storeId]),
                ], $secretKey);
            }
        }
        return parent::afterSave();
    }
}
