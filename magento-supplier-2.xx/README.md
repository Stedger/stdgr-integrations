​/****************************************************Controllers******************************************************/
​
1 Stedger/APIIntegration/Controller/Api/Order.php
Class: Stedger\APIIntegration\Controller\Api\Order
​
Action: "execute" - Callback from Stedger (Webhooks callback). Creating orders in Magento.  Returns json.
​
2 Stedger/APIIntegration/Controller/Adminhtml/Integration/Index.php
Class: Stedger\APIIntegration\Controller\Adminhtml\Integration\Index
​
Action: execute - render for an admin page.

3 Stedger/APIIntegration/Controller/Adminhtml/Integration/Start.php
Class: Stedger\APIIntegration\Controller\Adminhtml\Integration\Start

Action: startintegrationAction - starting products synchronisation.  Returns JSON.

4 Stedger/APIIntegration/Controller/Adminhtml/Integration/Getprocess.php
Class: Stedger\APIIntegration\Controller\Adminhtml\Integration\Getprocess

Action: execute - return status of products synchronizing. Returns json.
​
/**********************************************Configurations***********************************************************/
​
1. Stedger/APIIntegration/etc/module.xml
Initialising module.

2 Stedger/APIIntegration/etc/events.xml
Creating observers.
​
3 Stedger/APIIntegration/etc/db_schema.xml
When installing the module we adding columns to the table (order and order item). We using them to send IDs from Stedger after order creation because we will need them later to send shipments. 

4 Stedger/APIIntegration/etc/acl.xml
Describing permissions.

5 Stedger/APIIntegration/etc/adminhtml/menu.xml
Adding menu item to the admin of Magento.

6 Stedger/APIIntegration/etc/adminhtml/routes.xml
Creating an admin area's URL route.

7 Stedger/APIIntegration/etc/adminhtml/system.xml
Adding setting to Stores - Configuration section of Magento.

8 Stedger/APIIntegration/etc/frontend/routes.xml
​Creating frontend area URL route.

/***************************************************Helpers***********************************************************/
​
1 Stedger/APIIntegration/Helper/Data.php
​
Class: Stedger\APIIntegration\Helper\Data
​
/***************************************************Models***********************************************************/
​
1 Stedger/APIIntegration/Model/System/Config/Backend/Keys.php
Class: Stedger\APIIntegration\Model\System\Config\Backend\Keys
​
method: _afterSave()
After saving the private key in the administrator's system settings, we checking the system for existing webhooks. If the webhook not found, create it.
​
2 Stedger/APIIntegration/Model/Api.php
class: Stedger\APIIntegration\Model\Api
API model used to send all API requests.
​
Notice:
2.1 private $_url = 'https://api.stedger.com/v1/'; - hardcoded api url;
2.2 the "request" method uses the secret key from the settings.
​
3 Stedger/APIIntegration/Model/Integration.php
class: Stedger\APIIntegration\Model\Integration
Model which creating an order in Magento.
​
/***************************************************Observers***********************************************************/

1 Stedger/APIIntegration/Observer/CatalogProductDeleteAfter.php
Product removal event.

2 Stedger/APIIntegration/Observer/CatalogProductSaveAfter.php
Product save event.
 
3 Stedger/APIIntegration/Observer/ShipmentSaveAfter.php
Creating shipment event.

4 Stedger/APIIntegration/Observer/ProductTrait.php
Trait with general functionality.

Sending products to the Stedger.
​
Notice: 
4.1 We can't remove products from the Stedger. That is why we turn them off in the Stedger.
4.2 When a simple product is saved in Magento, we sending it to the Stedger - as a simple and configurable products.
​
salesShipmentSaveAfter
Sending shipments to the Stedger
	
/***************************************************Views***********************************************************/

Stedger/APIIntegration/view/adminhtml
Admin page.