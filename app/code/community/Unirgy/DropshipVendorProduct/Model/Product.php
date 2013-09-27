<?php

class Unirgy_DropshipVendorProduct_Model_Product extends Mage_Catalog_Model_Product
{
    protected function _construct()
    {
        $this->_init('udprod/product');
    }
    public function resetTypeInstance()
    {
        $this->_typeInstanceSingleton = null;
        $this->_typeInstance = null;
        return $this;
    }
}