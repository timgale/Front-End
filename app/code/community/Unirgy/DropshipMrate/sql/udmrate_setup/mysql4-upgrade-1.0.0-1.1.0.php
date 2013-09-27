<?php

$this->startSetup();

$conn = $this->_conn;
$t = $this->getTable('shipping_matrixrate');

$conn->addKey($t, 'dest_country', array('website_id','dest_country_id','dest_region_id','dest_city','dest_zip','dest_zip_to','condition_name','condition_from_value','condition_to_value','delivery_type', 'udropship_vendor'), 'unique');

$this->endSetup();