<?php

namespace Stedger\ConsumersApiIntegration\Controller\Api;

class Updateproduct extends \Stedger\ConsumersApiIntegration\Controller\Api
{
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $post = json_decode(file_get_contents('php://input'), true);

//            $post = json_decode('{"publicKey":"pubk_ccx2ZExcxGBTL1Cgrz2d9s","webhookId":"wh_eF4Fb4JzkvDB7USiHY1LuH","topic":"connected_product.variant.updated","data":{"__typename":"ProductVariant","id":"pvar_vaQJFicz1JLVjGZCviwPLq","identifiers":{"sku":"203660458","barcode":"5707644696266"},"createdAt":1652884597214,"inventory":null,"colliSize":1,"selectedOptions":[],"weight":{"net":1700,"tare":2750},"dropshipStatus":{"inventory":90,"onStock":true,"isAvailable":true},"zonePrice":{"currency":"dkk","recommendedRetailPrice":239900,"retailPrice":239900,"costPrice":134344,"retailProfit":57576,"tradePrice":null,"dropshipTradeFee":38384}}}', true);

//            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Stedger.log');
//            $logger = new \Zend_Log();
//            $logger->addWriter($writer);
//            $logger->info(json_encode([$post]));

            if (
                !$this->helper->getConfig('stedgerconsumerintegration/settings/public_key') ||
                $post['publicKey'] != $this->helper->getConfig('stedgerconsumerintegration/settings/public_key')
            ) {
                throw new \Exception('The public key is invalid.');
            }

            $this->integration->updateMagentoProduct($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];
        } catch (\Exception $e) {
            $this->logger->critical('Error "update product": ', ['exception' => $e]);
            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $resultJson->setData($response);
    }
}
