<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('udshipping_matrixrate')} (
  pk int(10) unsigned NOT NULL auto_increment,
  website_id int(11) NOT NULL default '0',
  dest_country_id varchar(4) NOT NULL default '0',
  dest_region_id int(10) NOT NULL default '0',
  dest_city varchar(30) NOT NULL default '',
  dest_zip varchar(10) NOT NULL default '',
  dest_zip_to varchar(10) NOT NULL default '',
  condition_name varchar(20) NOT NULL default '',
  condition_from_value decimal(12,4) NOT NULL default '0.0000',
  condition_to_value decimal(12,4) NOT NULL default '0.0000',
  price decimal(12,4) NOT NULL default '0.0000',
  cost decimal(12,4) NOT NULL default '0.0000',
  delivery_type varchar(255) NOT NULL default '',
  udropship_vendor int(10) unsigned DEFAULT NULL,
  PRIMARY KEY(`pk`),
  UNIQUE KEY `dest_country` (`website_id`,`dest_country_id`,`dest_region_id`,`dest_city`,`dest_zip`,`dest_zip_to`,`condition_name`,`condition_from_value`,`condition_to_value`,`delivery_type`,`udropship_vendor`),
  KEY `FK_UDMATRIXRATE_UDROPSHIP_VENDOR` (`udropship_vendor`),
  CONSTRAINT `FK_UDMATRIXRATE_UDROPSHIP_VENDOR` FOREIGN KEY (`udropship_vendor`) REFERENCES `udropship_vendor` (`vendor_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



   ");

$installer->endSetup();


