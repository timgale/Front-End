<?php

class Unirgy_DropshipMrate_Block_Adminhtml_MatrixrateGrid extends Webshopapps_Matrixrate_Block_Adminhtml_Shipping_Carrier_Matrixrate_Grid
{
    protected function _prepareColumns()
    {
        $this->addColumnAfter('udropship_vendor', array(
            'header'    => Mage::helper('udropship')->__('Dropship Vendor'),
            'index'     => 'udropship_vendor',
            'options' => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionHash(),
        ), 'delivery_type');
        parent::_prepareColumns();
    }
}