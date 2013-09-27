<?php

class Unirgy_DropshipTierShipping_Model_Observer
{
    public function udropship_adminhtml_vendor_tabs_after($observer)
    {
        $block = $observer->getBlock();
        $block->addTab('udtiership', array(
            'label'     => Mage::helper('udtiership')->__('Shipping Rates'),
            'after'     => 'shipping_section',
            'content'   => Mage::app()->getLayout()->createBlock('udtiership/adminhtml_vendorEditTab_shippingRates_form', 'vendor.tiership.form')
                ->toHtml()
        ));
    }
    public function udropship_vendor_load_after($observer)
    {
        Mage::helper('udtiership')->processTiershipRates($observer->getVendor());
        Mage::helper('udtiership')->processTiershipSimpleRates($observer->getVendor());
    }
    public function udropship_vendor_save_after($observer)
    {
        Mage::helper('udtiership')->processTiershipRates($observer->getVendor());
        Mage::helper('udtiership')->processTiershipSimpleRates($observer->getVendor());
    }
    public function udropship_vendor_save_before($observer)
    {
        Mage::helper('udtiership')->processTiershipRates($observer->getVendor(), true);
        Mage::helper('udtiership')->processTiershipSimpleRates($observer->getVendor(), true);
    }

}