<?php
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

$tables = [
    $installer->getTable('sales_flat_order'),
    $installer->getTable('sales_flat_order_item')
];

foreach ($tables as $table) {
    $installer->getConnection()->addColumn(
        $table,
        'stedger_integration_id',
        [
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 32,
            'nullable' => true,
            'comment' => 'Stedger Integration Id'
        ]
    );
}

$installer->endSetup();