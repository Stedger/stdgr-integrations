​/****************************************************Controllers******************************************************/
​
1 Stedger/ConsumersApiIntegration/Controller/Api/Product.php
class: Stedger\ConsumersApiIntegration\Controller\Api\Product
​
action: "execute" - Callback from Stedger (Webhooks callback). Creating products in Magento.  Returns JSON.
​
2 Stedger/ConsumersApiIntegration/Controller/Api/Shipment.php
class: Stedger\ConsumersApiIntegration\Controller\Api\Shipment
​
action: "execute" - Callback from Stedger (Webhooks callback). Creating shipments in Magento.  Returns JSON.

3 Stedger/ConsumersApiIntegration/Controller/Api/Updateproduct.php
class: Stedger\ConsumersApiIntegration\Controller\Api\Updateproduct
​
action: "execute" - Callback from Stedger (Webhooks callback). Update products in Magento.  Returns JSON.

3 Stedger/ConsumersApiIntegration/Controller/Api.php
class: Stedger\ConsumersApiIntegration\Controller\Api
​
abstract class
​
/**********************************************Configurations***********************************************************/
​
1. Stedger/ConsumersApiIntegration/etc/module.xml
Initializing module.

2 Stedger/ConsumersApiIntegration/etc/events.xml
Creating observers.
​
3 Stedger/ConsumersApiIntegration/etc/db_schema.xml
When installing module we adding columns to the table (order and order item). We use them to send IDs from Stedger after an order creation because we will need them later to send shipments. 

4 Stedger/ConsumersApiIntegration/etc/acl.xml
Describing permissions.

5 Stedger/ConsumersApiIntegration/etc/adminhtml/system.xml
Adding a setting to the System - Configuration section of Magento.

6 Stedger/ConsumersApiIntegration/etc/frontend/routes.xml
​Creating frontend area URL route.

/***************************************************Helpers***********************************************************/
​
1 Stedger/ConsumersApiIntegration/Helper/Data.php
​
class: Stedger\ConsumersApiIntegration\Helper\Data
​
/***************************************************Models***********************************************************/
​
1 Stedger/ConsumersApiIntegration/Model/System/Config/Backend/Keys.php
class: Stedger\ConsumersApiIntegration\Model\System\Config\Backend\Keys
​
method: _afterSave()
After saving the private key in the administrator's system settings, we checking the system for existing webhooks. If none webhook found, we creating them.
​
2 Stedger/ConsumersApiIntegration/Model/Api.php
class: Stedger\ConsumersApiIntegration\Model\Api
API model used to send all requests from.
​
Notice:
2.1 private $_url = 'https://api.stedger.com/v1/'; - hardcoded api url;
2.2 the "request" method uses the secret key from the settings.
​
3 Stedger/ConsumersApiIntegration/Model/Integration.php
class: Stedger\ConsumersApiIntegration\Model\Integration
Model which creating, updating products and creating shipments in Magento.
​
/***************************************************Observers***********************************************************/
1 Stedger/ConsumersApiIntegration/Observer/CheckoutCartAdd.php
Сompares Product qty and stedger qty
2 Stedger/ConsumersApiIntegration/Observer/SalesOrderPlaceBefore.php
Сompares Product qty and stedger qty
3 Stedger/ConsumersApiIntegration/Observer/SalesOrderSaveAfter.php
Sending order to stedger api
4 Stedger/ConsumersApiIntegration/Observer/SalesOrderShipmentSaveBefore.php
allow create shipments with stedger qty
/***************************************************Views***********************************************************/
Stedger\ConsumersConsumersApiIntegration\Setup\Patch
Create product attributes.
