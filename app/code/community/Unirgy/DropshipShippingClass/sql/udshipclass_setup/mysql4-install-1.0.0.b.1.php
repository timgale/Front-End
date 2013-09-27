<?php

/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer = $this;
$conn = $this->_conn;
$installer->startSetup();

$conn->addColumn($installer->getTable('udropship/shipping'), 'vendor_ship_class', 'varchar(255)');
$conn->addColumn($installer->getTable('udropship/shipping'), 'customer_ship_class', 'varchar(255)');

$installer->endSetup();
