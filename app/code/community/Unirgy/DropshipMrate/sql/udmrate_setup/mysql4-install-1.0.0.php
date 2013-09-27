<?php

$this->startSetup();

$this->_conn->addColumn($this->getTable('shipping_matrixrate'), 'udropship_vendor', 'int unsigned');
$this->_conn->addConstraint('FK_MATRIXRATE_UDROPSHIP_VENDOR', $this->getTable('shipping_matrixrate'), 'udropship_vendor', $this->getTable('udropship_vendor'), 'vendor_id');

$this->endSetup();