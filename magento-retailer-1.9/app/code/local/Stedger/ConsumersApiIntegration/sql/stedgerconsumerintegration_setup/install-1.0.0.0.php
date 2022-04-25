<?php
/* @var $installer Mage_Eav_Model_Entity_Setup */

$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$installer->addAttribute('catalog_product', 'stedger_qty', [
    'group' => 'General',
    'label' => 'Stedger QTY',
    'input' => 'text',
    'type' => 'int',
    'required' => 0,
    'visible_on_front' => 1,
    'filterable' => 0,
    'searchable' => 0,
    'comparable' => 0,
    'user_defined' => 1,
    'is_configurable' => 0,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note' => '',
]);

$installer->addAttribute('catalog_product', 'created_from_stedger', [
    'group' => 'General',
    'label' => 'Created From Stedger',
    'input' => 'boolean',
    'type' => 'varchar',
    'required' => 0,
    'visible_on_front' => 1,
    'filterable' => 0,
    'searchable' => 0,
    'comparable' => 0,
    'user_defined' => 1,
    'is_configurable' => 0,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note' => '',
]);

$installer->addAttribute('catalog_product', 'stedger_integration_id', [
    'label' => 'Stedger Integration Id',
    'input' => 'text',
    'type' => 'static',
    'required' => 0,
    'visible_on_front' => 1,
    'filterable' => 0,
    'searchable' => 0,
    'comparable' => 0,
    'user_defined' => 1,
    'is_configurable' => 0,
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'note' => '',
]);
$installer->endSetup();

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

$tables = [
    $installer->getTable('sales_flat_order'),
    $installer->getTable('sales_flat_order_item'),
    $installer->getTable('sales_flat_shipment'),
    $installer->getTable('sales_flat_shipment_item'),
    $installer->getTable('catalog_product_entity')
];

foreach ($tables as $table) {
    $installer->getConnection()->addColumn(
        $table,
        'stedger_integration_id',
        [
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable' => true,
            'comment' => 'Stedger Integration Id'
        ]
    );
}

$installer->endSetup();