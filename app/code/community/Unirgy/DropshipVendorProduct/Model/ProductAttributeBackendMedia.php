<?php

class Unirgy_DropshipVendorProduct_Model_ProductAttributeBackendMedia extends Mage_Catalog_Model_Product_Attribute_Backend_Media
{
    protected $_isInVendorEdit=false;
    protected $_allowUseRenamedImage=false;
    public function beforeSave($object)
    {
        $this->_isInVendorEdit = $object->getData('_edit_in_vendor');
        $this->_allowUseRenamedImage = $object->getData('_allow_use_renamed_image');
        parent::beforeSave($object);
        if ($this->_isInVendorEdit
            && !Mage::getSingleton('udprod/source')->isMediaCfgPerOptionHidden()
            && !Mage::getSingleton('udprod/source')->isCfgUploadImagesSimple()
            && !Mage::getSingleton('udprod/source')->isMediaCfgShowExplicit()
        ) {
            $attrCode = $this->getAttribute()->getAttributeCode();
            $value = $object->getData($attrCode);
            if (is_array($value) && is_array($value['images'])) {
                $useImage = null;
                foreach ($value['images'] as $img) {
                    if (@$img['super_attribute']['main'] && !@$img['removed']) {
                        $useImage = $img;
                        break;
                    }
                }
                if ($useImage) {
                    foreach ($object->getMediaAttributes() as $mediaAttribute) {
                        $mediaAttrCode = $mediaAttribute->getAttributeCode();
                        $attrData = $object->getData($mediaAttrCode);
                        $object->setData($mediaAttrCode, @$useImage['file']);
                        $object->setData($mediaAttrCode.'_label', @$useImage['label']);
                    }
                }
            }
        }
        $this->_isInVendorEdit = false;
        $this->_allowUseRenamedImage = false;
        return $this;
    }
    protected function _moveImageFromTmp($file)
    {
        if ($this->_allowUseRenamedImage
            && isset($this->_renamedImages[$file])
        ) {
            return $this->_renamedImages[$file];
        } else {
            return parent::_moveImageFromTmp($file);
        }
    }
    public function afterSave($object)
    {
        if ($object->getIsDuplicate() == true) {
            $this->duplicate($object);
            return;
        }

        $attrCode = $this->getAttribute()->getAttributeCode();
        $value = $object->getData($attrCode);
        if (!is_array($value) || !isset($value['images']) || $object->isLockedAttribute($attrCode)) {
            return;
        }
        $toDelete = array();
        $filesToValueIds = array();
        foreach ($value['images'] as &$image) {
            if(!empty($image['removed'])) {
                if(isset($image['value_id'])) {
                    $toDelete[] = $image['value_id'];
                }
                continue;
            }

            if(!isset($image['value_id'])) {
                $data = array();
                $data['entity_id']      = $object->getId();
                $data['attribute_id']   = $this->getAttribute()->getId();
                $data['value']          = $image['file'];
                try {
                    $jsDecoded = Mage::helper('core')->jsonEncode(@$image['super_attribute']);
                    $data['super_attribute'] = $jsDecoded;
                } catch (Exception $e) {}
                $image['value_id']      = $this->_getResource()->insertGallery($data);
            } else {
                $data = array();
                $data['super_attribute'] = Mage::helper('core')->jsonEncode(@$image['super_attribute']);
                $this->_getResource()->updateGallery($data, $image['value_id']);
            }

            $this->_getResource()->deleteGalleryValueInStore($image['value_id'], $object->getStoreId());

            // Add per store labels, position, disabled
            $data = array();
            $data['value_id'] = $image['value_id'];
            $data['label']    = $image['label'];
            $data['position'] = (int) @$image['position'];
            $data['disabled'] = (int) @$image['disabled'];
            $data['store_id'] = (int) $object->getStoreId();

            $this->_getResource()->insertGalleryValueInStore($data);
        }

        $this->_getResource()->deleteGallery($toDelete);
    }
}
