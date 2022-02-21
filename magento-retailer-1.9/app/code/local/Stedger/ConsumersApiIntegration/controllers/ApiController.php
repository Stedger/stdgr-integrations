<?php

class Stedger_ConsumersApiIntegration_ApiController extends Mage_Core_Controller_Front_Action
{
    public function productAction()
    {
        try {
            $post = json_decode(file_get_contents('php://input'), true);
//            $post = json_decode('{"publicKey":"pubk_ursi5sU7uMc46Q7t3cZ1mQ","webhookId":"wh_1wNBo6jRFQytyDyMKNa3aw","topic":"connected_product.added","data":{"__typename":"Product","id":"pr_aF4MPViD584eHAupSNGbwm","title":"T\u00e6ppe, Sigrid, Snow","description":"Tilf\u00f8j et strejf af luksuri\u00f8s komfort til dit sovev\u00e6relse med denne l\u00f8ber fra byNORD. Det lange og smalle t\u00e6ppe hedder Sigrid og er lavet af jute samlet i tykke l\u00f8kker, som skaber et diamantlignende m\u00f8nster. Takket v\u00e6re den skinnende finish p\u00e5 naturmaterialet tilf\u00f8jer t\u00e6ppet kontrast til dit senget\u00f8j og tr\u00e6gulv. Plac\u00e9r t\u00e6ppet ved siden af din seng eller for foden af den, og lad den lyse farve tilf\u00f8je et beroligende og let touch til dit sovev\u00e6relse. Et t\u00e6ppe som dette er med til at give dig en vidunderlig start p\u00e5 din dag.","tags":["Home texiles","Rugs"],"vendor":"By Nord","createdAt":1643877785910,"images":[{"__typename":"ProductImage","src":"https:\/\/ik.imagekit.io\/stdgr\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05a46-7bf8-11ec-93e3-272e57499de3.jpeg","thumbnailSrc":"https:\/\/ik.imagekit.io\/stdgr\/tr:w-100,h-100,c-at_max\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05a46-7bf8-11ec-93e3-272e57499de3.jpeg"},{"__typename":"ProductImage","src":"https:\/\/ik.imagekit.io\/stdgr\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05bc2-7bf8-11ec-93e3-7b4688af1fa5.jpeg","thumbnailSrc":"https:\/\/ik.imagekit.io\/stdgr\/tr:w-100,h-100,c-at_max\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05bc2-7bf8-11ec-93e3-7b4688af1fa5.jpeg"},{"__typename":"ProductImage","src":"https:\/\/ik.imagekit.io\/stdgr\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05d34-7bf8-11ec-93e3-2b7a51cdaac1.jpeg","thumbnailSrc":"https:\/\/ik.imagekit.io\/stdgr\/tr:w-100,h-100,c-at_max\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05d34-7bf8-11ec-93e3-2b7a51cdaac1.jpeg"},{"__typename":"ProductImage","src":"https:\/\/ik.imagekit.io\/stdgr\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05eb0-7bf8-11ec-93e3-e748391355d8.jpeg","thumbnailSrc":"https:\/\/ik.imagekit.io\/stdgr\/tr:w-100,h-100,c-at_max\/85406d2a-d59a-11ea-9128-47d425a518f5\/pi\/9fd05eb0-7bf8-11ec-93e3-e748391355d8.jpeg"}],"variants":[{"__typename":"ProductVariant","id":"pvar_aFeZmKZQmDuWdvzzi4YVh9","identifiers":{"sku":"sku'.time().'","barcode":"5707644552296"},"createdAt":1643877785951,"inventory":null,"dropshipStatus":{"inventory":106,"onStock":true,"isAvailable":true},"zonePrice":{"currency":"dkk","recommendedRetailPrice":110250,"retailPrice":110250,"costPrice":57356,"retailProfit":30844}}]}}', true);

            Mage::helper('stedgerconsumerintegration')->log(file_get_contents('php://input'));

            if ($post['publicKey'] != Mage::getStoreConfig('stedgerconsumerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }

            Mage::getModel('stedgerconsumerintegration/integration')->createMagentoProduct($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];

        } catch (\Exception $e) {
            Mage::helper('stedgerconsumerintegration')->log('Error "product": ' . $e->getMessage() . ' | ' . json_encode([$post]));

            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function updateproductAction()
    {
        try {
            $post = json_decode(file_get_contents('php://input'), true);

            if ($post['publicKey'] != Mage::getStoreConfig('stedgerconsumerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }

            Mage::getModel('stedgerconsumerintegration/integration')->updateMagentoProduct($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];

        } catch (\Exception $e) {
            Mage::helper('stedgerconsumerintegration')->log('Error "product": ' . $e->getMessage() . ' | ' . json_encode([$post]));

            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function shipmentAction()
    {
        try {
            $post = json_decode(file_get_contents('php://input'), true);

            Mage::helper('stedgerconsumerintegration')->log(file_get_contents('php://input'));

            if ($post['publicKey'] != Mage::getStoreConfig('stedgerconsumerintegration/settings/public_key')) {
                throw new \Exception('The public key is invalid.');
            }

            Mage::getModel('stedgerconsumerintegration/integration')->createMagentoShipment($post['data']);

            $response = ['status' => 'accepted', 'message' => ''];

        } catch (\Exception $e) {
            Mage::helper('stedgerconsumerintegration')->log('Error "product": ' . $e->getMessage() . ' | ' . json_encode([$post]));

            $response = ['status' => 'rejected', 'message' => $e->getMessage()];
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
