<?php

class Stedger_APIIntegration_AdminController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('stedger/integration');
    }

    public function integrationAction()
    {
        $this->loadLayout();
        $this->_title($this->__("Stedger Integration"));
        $this->renderLayout();
    }

    public function startintegrationAction()
    {
        session_write_close();

        $productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

        try {
            $fp = fopen(Mage::helper('stedgerintegration')->getProcessIntegrationFilePath(), 'w');
            ftruncate($fp, 0);

            $productsCount = count($productCollection);

            if ($productsCount) {
                $i = 1;
                foreach ($productCollection as $product) {
                    $error = new \stdClass();

                    Mage::dispatchEvent('catalog_product_save_after', ['product' => $product, 'error' => $error]);

                    fwrite($fp, $productsCount . ' / ' . $i . ' : ' . $product->getName() . ' ' . $error->message . ' |');

                    $i++;
                }
            }
            fclose($fp);

            $response = ['status' => 'success'];

        } catch (\Exception $e) {
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function getintegrationprocessAction()
    {
        $logFile = Mage::helper('stedgerintegration')->getProcessIntegrationFilePath();

        $lines = explode('|', file_get_contents($logFile));
        $html = '<p>' . implode('</p><p>', $lines) . '</p>';
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['content' => $html]));
    }


}