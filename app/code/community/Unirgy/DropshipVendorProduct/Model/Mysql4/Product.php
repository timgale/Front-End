<?php

class Unirgy_DropshipVendorProduct_Model_Mysql4_Product extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product
{
    protected function _beforeSave(Varien_Object $object)
    {
        if ($object->hasCategoryIds()) {
            $categoryIds = Mage::getResourceSingleton('catalog/category')->verifyIds(
                $object->getCategoryIds()
            );
            $object->setCategoryIds($categoryIds);
        }
        if (!$object->getSku() && Mage::getStoreConfigFlag('udprod/general/auto_sku')) {
            $adapter = $this->_getReadAdapter();
            $pidSuffix = $adapter->fetchOne($adapter->select()
                ->from($this->getEntityTable(), 'max(entity_id)'));
            do {
                $object->setSku($object->getUdropshipVendor().'-'.(++$pidSuffix));
            } while (Mage::helper('udropship/catalog')->getPidBySku($object->getSku(), $object->getId()));
        }
        $vId = Mage::getSingleton('udropship/session')->getVendorId();
        if (!$vId && Mage::app()->getStore()->isAdmin()
            && Mage::helper('udropship')->isModuleActive('umicrosite')
            && ($v = Mage::helper('umicrosite')->getAdminhtmlVendor())
        ) {
            $vId = $v->getId();
        }
        if (Mage::getStoreConfigFlag('udprod/general/prefix_sku_vid')
            && $vId && 0 !== strpos($object->getSku(), $vId.'-')
        ) {
            $object->setSku($vId.'-'.$object->getSku());
        }
        if (Mage::getStoreConfigFlag('udprod/general/unique_vendor_sku')
            && !Mage::helper('udropship')->isUdmultiActive()
        ) {
            $vSkuAttr = Mage::getStoreConfig('udropship/vendor/vendor_sku_attribute');
            if ($vSkuAttr && $vSkuAttr!='sku') {
                if (!$object->getData($vSkuAttr)) {
                    Mage::throwException('Vendor SKU attribute is empty');
                } elseif (Mage::helper('udropship/catalog')->getPidByVendorSku($object->getData($vSkuAttr), $object->getId())) {
                    Mage::throwException(Mage::helper('udropship')->__('Vendor SKU "%s" is already used', $object->getData($vSkuAttr)));
                }
            }
        }

        if (Mage::helper('udropship')->hasMageFeature('resource_1.6')) {
            return Mage_Catalog_Model_Resource_Abstract::_beforeSave($object);
        } else {
            return Mage_Catalog_Model_Resource_Eav_Mysql4_Abstract::_beforeSave($object);
        }
    }
}