# Controllers

1 Stedger/APIIntegration/controllers/ApiController.php
class: Stedger_APIIntegration_ApiController

action: "orderAction" - Callback from Stedger (Webhooks callback). Creating orders in Magento.  Returns json.

2 Stedger/APIIntegration/controllers/AdminController.php
class: Stedger_APIIntegration_AdminController

action: integrationAction - render admin page.
action: startintegrationAction - start products synchronizing.  Returns json.
action: getintegrationprocessAction - return status of products synchronizing. Returns json.

# Configurations

1 Stedger/APIIntegration/etc/config.xml
Initializing module. Configuration defining the module. Creating observers, etc.

2 Stedger/APIIntegration/etc/adminhtml.xml
Adding menu to the admin of Magento.

3 Stedger/APIIntegration/etc/system.xml
Adding setting to System - Configuration section of Magento.

# Helpers

1 Stedger/APIIntegration/Helper/Data.php

class: Stedger_APIIntegration_Helper_Data

# Models

1 Stedger/APIIntegration/Model/System/Config/Backend/Keys.php
class: Stedger_APIIntegration_Model_System_Config_Backend_Keys

method: _afterSave()
After saving the private key in the administrator's system settings, we check the system for existing webhooks. If the webhook is not found, create it.

2 Stedger/APIIntegration/Model/Api.php
class: Stedger_APIIntegration_Model_Api
API model used to send all requests from.

Notice:
2.1 private $_url = 'https://api.stedger.com/v1/'; - hardcoded api url;
2.2 the "request" method uses the secret key from the settings.

3 Stedger/APIIntegration/Model/Integration.php
class: Stedger_APIIntegration_Model_Integration
Model which creating order in Magento.

4 Stedger/APIIntegration/Model/Observer.php
Observer model with 3 events:
	 	 product save
	 	 product removal
	 	 creating shipment

catalogProductSaveAfter
catalogProductDeleteAfter
sends products to the Stedger.

Notice: 
4.1 We can't remove products from the Stedger. That is why we turn them off in the Stedger.
4.2 When a simple product is saved in Magento, we send to the Stedger it and all the configurable products it belongs to.

salesShipmentSaveAfter
sends shipments to the Stedger
	 
# Setup

Stedger/APIIntegration/sql/stedgerintegration_setup/install-1.0.0.0.php
When installing module we adding columns to table (order
and order item). We use them to send IDs from Stedger after order creation because we will need
them later to send shipments. 

Described in etc/config.xml.
