<?php

class Unirgy_DropshipVendorProduct_Model_Observer
{
    public function udropship_adminhtml_vendor_tabs_after($observer)
    {
        $block = $observer->getBlock();
        /*
        $block->addTab('udprod', array(
            'label'     => Mage::helper('udprod')->__('Template SKUs'),
            'after'     => 'shipping_section',
            'content'   => Mage::app()->getLayout()->createBlock('udprod/adminhtml_vendorEditTab_templateSku_form', 'vendor.udprod.form')
                ->toHtml()
        ));
        */
    }
    public function udropship_vendor_load_after($observer)
    {
        Mage::helper('udprod')->processTemplateSkus($observer->getVendor());
    }
    public function udropship_vendor_save_after($observer)
    {
        Mage::helper('udprod')->processTemplateSkus($observer->getVendor());
    }
    public function udropship_vendor_save_before($observer)
    {
        Mage::helper('udprod')->processTemplateSkus($observer->getVendor(), true);
    }
    public function core_block_abstract_prepare_layout_after($observer)
    {
        $block = $observer->getBlock();
        if ($block->getTemplate()=='media/uploader.phtml') {
            $block->setTemplate('udprod/mediaUploader.phtml');
        }
        if (!$block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs
            && !$block instanceof Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs
        ) {
            return;
        }
    }
    public function controller_action_layout_load_before($observer)
    {
        if ($observer->getAction()
            && $observer->getAction()->getFullActionName()=='catalog_product_view'
            && Mage::getStoreConfigFlag('udprod/general/use_product_zoom')
        ) {
            $observer->getAction()->getLayout()->getUpdate()->addHandle('_udprod_product_zoom');
            if ((($p = Mage::registry('current_product'))
                || ($p = Mage::registry('product')))
                && $p->getTypeId()=='configurable'
            ) {
                $observer->getAction()->getLayout()->getUpdate()->addHandle('_udprod_product_zoom_configurable');
            }
        }
    }
    public function controller_front_init_before($observer)
    {
        $this->_initConfigRewrites();
    }
    public function udropship_init_config_rewrites()
    {
        $this->_initConfigRewrites();
    }
    protected function _initConfigRewrites()
    {
        if (Mage::helper('udropship')->isOSPActive()) {
            if (Mage::helper('udropship')->compareMageVer('1.5.0','1.10.0')) {
                Mage::getConfig()->setNode('global/models/catalog/rewrite/product_type_simple', 'Unirgy_DropshipVendorProduct_Model_ProductType_Simple15');
            }
        }
        if (Mage::getStoreConfigFlag('udprod/general/use_product_zoom')) {
            if (Mage::helper('udropship')->isOSPActive()) {
                Mage::getConfig()->setNode('global/models/catalog/rewrite/product_type_configurable', 'Unirgy_DropshipVendorProduct_Model_ProductTypeConfigurableOSP');
                Mage::getConfig()->setNode('global/blocks/catalog/rewrite/product_view_type_configurable', 'Unirgy_DropshipVendorProduct_Block_ProductViewTypeConfigurableOSP');
                Mage::getConfig()->setNode('global/blocks/catalog/rewrite/product_view_media', 'Unirgy_DropshipVendorProduct_Block_ProductViewMediaOSP');
            } else {
                Mage::getConfig()->setNode('global/blocks/catalog/rewrite/product_view_media', 'Unirgy_DropshipVendorProduct_Block_ProductViewMedia');
                Mage::getConfig()->setNode('global/blocks/catalog/rewrite/product_view_type_configurable', 'Unirgy_DropshipVendorProduct_Block_ProductViewTypeConfigurable');
            }
        }
    }
    public function sales_quote_config_get_product_attributes($observer)
    {
        $attributes = $observer->getAttributes()->getData();
        $res = Mage::getSingleton('core/resource');
        $conn = $res->getConnection('core_read');
        $cfgAttrIds = $conn->fetchCol(
            $conn->select()->from($res->getTableName('catalog/product_super_attribute'), 'attribute_id')->distinct(true)
        );
        $cfgAttrs = $conn->fetchPairs(
            $conn->select()->from(array('ea' => $res->getTableName('eav/attribute')), array('attribute_code', 'attribute_id'))
                ->where('attribute_id in (?)', $cfgAttrIds)
        );
        if (!empty($cfgAttrs)) {
            $observer->getAttributes()->addData($cfgAttrs);
        }
    }
    public function sales_quote_load_after($observer)
    {
        $quote = $observer->getQuote();
        $usedProducts = array();
        $cfgProducts = array();
        foreach ($quote->getAllItems() as $item) {
            if (($cpOpt = $item->getOptionByCode('cpid'))) {
                $cpId = $cpOpt->getValue();
                if (empty($usedProducts[$cpId])) {
                    $usedProducts[$cpId] = array();
                }
                $item->setName($cpOpt->getProduct()->getName());
                $cfgProducts[$cpId] = $cpOpt->getProduct();
                $usedProducts[$cpId][$item->getProduct()->getId()] = $item->getProduct();
            }
        }
        foreach ($usedProducts as $cpId => $ups) {
            if (!$cfgProducts[$cpId]->hasData('_cache_instance_products')) {
                $cfgProducts[$cpId]->setData('_cache_instance_products', $ups);
            }
        }
    }
}