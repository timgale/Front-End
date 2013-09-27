<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_Dropship_Model_Observer extends Varien_Object
{
    protected $_cartUpdateActionFlag=false;
    public function setIsCartUpdateActionFlag($flag)
    {
        $this->_cartUpdateActionFlag=(bool)$flag;
        return $this;
    }
    public function controller_action_predispatch_checkout_cart_add($observer)
    {
        $this->setIsCartUpdateActionFlag(true);
    }
    public function controller_action_predispatch_checkout_cart_updatePost($observer)
    {
        $this->setIsCartUpdateActionFlag(true);
    }
    public function sales_quote_load_after($observer)
    {
        try {
            $hlp = Mage::helper('udropship/protected');
            $items = $observer->getQuote()->getAllItems();
            if ($observer->getQuote()->getUdSkipQuoteLoadAfterEvent() || $this->_cartUpdateActionFlag) return;
            //$hlp->setAllowReorginizeQuote(true);
            $hlp->applyDefaultVendorIds($items)->applyStockAvailability($items);
            //$hlp->setAllowReorginizeQuote(false);
        } catch (Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
    }
    /**
    * Create shipments based on dropship vendors
    *
    * @param mixed $observer
    */
    public function sales_order_save_after($observer)
    {
        if (!$observer->getEvent()->getOrder()->getNoDropshipFlag()) {
            if (!Mage::helper('udropship')->isSalesFlat()) {
                Mage::helper('udropship/protected')->sales_order_save_after($observer);
            }
        }
    }
    public function sales_order_save_commit_after($observer)
    {
        if (!$observer->getEvent()->getOrder()->getNoDropshipFlag()) {
            Mage::helper('udropship/protected')->sales_order_save_after($observer);
        }
    }

    /**
    * Before 1.4.1.x
    *
    * @param Varien_Object $observer
    */
    public function sales_order_item_save_before__helper($observer)
    {
        $this->setOrderItem($observer->getEvent()->getItem());
    }
    public function sales_order_item_save_before($observer)
    {
        $this->unsOrderItem();
    }
    public function sales_quote_item_qty_set_after__helper($observer)
    {
        $this->setQuoteItem($observer->getEvent()->getItem());
    }

    /**
    * After 1.4.1.x
    *
    * @param mixed $observer
    */
    public function sales_model_service_quote_submit_before__helper($observer)
    {
        $this->setQuote($observer->getEvent()->getQuote());
    }
    public function sales_model_service_quote_submit_before($observer)
    {
        $this->unsQuote();
    }

    /**
    * Skip reducing stock level if item is shipped from dropship vendor
    *
    * @deprecated
    * @param Varien_Object $observer
    */
    /*
    public function sales_order_item_save_before($observer)
    {
        $item = $observer->getEvent()->getItem();
        $store = $item->getOrder()->getStore();
        //$children = $item->getChildrenItems();
        $localVendorId = Mage::helper('udropship')->getLocalVendorId($store);

        if (!$item->getUdropshipVendor()) {
            $item->setUdropshipVendor($localVendorId);
        }
        /*
        if (!$item->getId() && empty($children) && $item->getUdropshipVendor()==$localVendorId) {
            //disabled
            //Mage::getSingleton('cataloginventory/stock')->registerItemSale($item);
        }
        * /
    }
    */

    // not in use
    public function checkout_cart_add_product_complete($observer)
    {
        if (!Mage::helper('udropship')->isActive()) {
           return;
        }
        try {
            $hlp = Mage::helper('udropship/protected');
            $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
            $hlp->applyDefaultVendorIds($items)->applyStockAvailability($items);
        } catch (Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
    }

    public function sales_quote_save_before($observer)
    {
        if (!Mage::helper('udropship')->isActive()) {
           return;
        }
        if (($quote = $observer->getQuote()) && $quote->getRefreshVendorsFlag()) {
            $hlp = Mage::helper('udropship/protected');
            $items = $quote->getAllItems();
            $hlp->applyDefaultVendorIds($items)->applyStockAvailability($items);
        }
    }

    public function sales_quote_product_add_after($observer)
    {
        if (!Mage::helper('udropship')->isActive()) {
            return;
        }
        try {
            $items = $observer->getItems();
            if (!empty($items)) {
                foreach ($items as $item) {
                    if ($item->getQuote()) {
                        $item->getQuote()->setRefreshVendorsFlag(true);
                    }
                }
            }
            /*
            if (($quote = Mage::getSingleton('checkout/session')->getQuote())){
                $quote->setRefreshVendorsFlag(true);
            }
            */
        } catch (Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
        $this->setIsCartUpdateActionFlag(false);
    }

    public function checkout_cart_product_add_after($observer)
    {
        if (!Mage::helper('udropship')->isActive()) {
           return;
        }
        try {
            if ($observer->getQuoteItem() && ($quote = $observer->getQuoteItem()->getQuote())) {
                $quote->setRefreshVendorsFlag(true);
            }
        } catch (Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
        $this->setIsCartUpdateActionFlag(false);
    }

    public function checkout_cart_update_items_after($observer)
    {
        if (!Mage::helper('udropship')->isActive()) {
           return;
        }
        try {
            $hlp = Mage::helper('udropship/protected');
            if ($observer->getCart() && ($quote = $observer->getCart()->getQuote())) {
                $items = $quote->getAllItems();
                $hlp->applyDefaultVendorIds($items)->applyStockAvailability($items);
            }
        } catch (Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
        $this->setIsCartUpdateActionFlag(false);
    }

    /**
     * Check quote items stock level qty live
     *
     * @param Varien_Object $observer
     */
    public function sales_quote_item_qty_set_after($observer)
    {
        if (!Mage::helper('udropship')->isActive()) {
            $this->unsQuoteItem();
            return;
        }
        //return $this; //disabled
        $quoteItem = $observer->getEvent()->getItem();
        /* @var $quoteItem Mage_Sales_Model_Quote_Item */
        if (!$quoteItem || !$quoteItem->getProductId() || $quoteItem->getQuote()->getIsSuperMode()) {
            $this->unsQuoteItem();
            return $this;
        }
        /* //deprecated
        if ($quoteItem->getHasError()) {
            $availability = Mage::getSingleton('udropship/stock_availability');
            $store = $quoteItem->getStoreId();
            $vendor = Mage::helper('udropship')->getVendor($quoteItem->getProduct());
            if ($availability->getUseLocalStockIfAvailable($store, $vendor)) {
                $quoteItem->setHasError(false);
            }
        }
        */
        try {
            $hlp = Mage::helper('udropship/protected');
            $items = array($quoteItem);
            $hlp->applyDefaultVendorIds($items)->applyStockAvailability($items);
        } catch (Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
        $this->unsQuoteItem();
        return $this;
    }

    public function sales_quote_item_set_product($observer)
    {
        $iHlp = Mage::helper('udropship/item');
        $item = $observer->getEvent()->getQuoteItem();
        $product = $observer->getEvent()->getProduct();
        $quote = $item->getQuote();
        $sId = $quote->getStoreId();
        
        $specialCost = Mage::getStoreConfig('udropship/vendor/special_cost_attribute');
        if ($specialCost && ($specialCost = $product->getData($specialCost))) {
            $baseCost = $item->getBaseCost();
            $specialFrom = $product->getSpecialFromDate();
            $specialTo = $product->getSpecialToDate();
            if (Mage::app()->getLocale()->isStoreDateInInterval($sId, $specialFrom, $specialTo)) {
                $item->setBaseCost(min($baseCost, $specialCost));
            };
        }

        if (($parent = $item->getParentItem()) && $parent->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $iHlp->setUdropshipVendor($parent, $item->getUdropshipVendor());
            $parent->setBaseCost($item->getBaseCost());
        }
    }

    /**
    * Make sure local vendor is set for products that do not have this attribute
    *
    * @param Varien_Object $observer
    */
    public function catalog_product_load_after($observer)
    {
        $product = $observer->getEvent()->getProduct();
        if (!$product->getUdropshipVendor()) {
            //$product->setUdropshipVendor(Mage::helper('udropship')->getLocalVendorId($product->getStoreId()));
        }
    }

    /**
    * Set default local vendor for new products
    *
    * @param Varien_Object $observer
    */
    public function catalog_product_new_action($observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product->setUdropshipVendor(Mage::helper('udropship')->getLocalVendorId($product->getStoreId()));
    }

    /**
    * Update stock status for product collection if augmented stock status is used
    *
    * @param mixed $observer
    */
    public function catalog_product_collection_load_after($observer)
    {
        $productCollection = $observer->getEvent()->getCollection();
        if (version_compare(Mage::getVersion(), '1.3.0', '>=') && $productCollection->hasFlag('require_stock_items')) {
            return;
        }

        $hlp = Mage::helper('udropship');
        $storeId = null;
        foreach ($productCollection as $product) {
            if (is_null($storeId)) {
                $storeId = $product->getStoreId();
                $localVendorId = $hlp->getLocalVendorId($storeId);
            }
            $vendorId = $hlp->getProductVendorId($product);
            if ($vendorId==$localVendorId) {
                continue;
            }
            if (Mage::getSingleton('udropship/stock_availability')->getUseLocalStockIfAvailable($storeId, $vendorId)) {
                $product->setIsSalable(true);
                $product->getStockItem()->setIsInStock(true);
            }
        }
    }

    public function cataloginventory_stock_item_save_before($observer)
    {
        return;
        // NOT USED
        $item = $observer->getEvent()->getItem();
        if (Mage::getSingleton('udropship/stock_availability')->getUseLocalStockIfAvailable()) {
            $item->setIsInStock(1);
        }
    }

    public function catalog_product_is_salable_after($observer)
    {
        /* //deprecated
        $product = $observer->getEvent()->getProduct();
        $store = $product->getStoreId();
        $vendor = Mage::helper('udropship')->getVendor($product);
        $object = $observer->getEvent()->getSalable();
        $availability = Mage::getSingleton('udropship/stock_availability');
        if ($availability->getUseLocalStockIfAvailable($store, $vendor)) {
            $object->setIsSalable(true);
        }
        */
    }

    public function adminhtml_sales_order_shipment_view($observer)
    {
        if (($soi = Mage::app()->getLayout()->getBlock('order_info'))
            && ($shipment = Mage::registry('current_shipment'))
        ) {
            if (($vName = Mage::helper('udropship')->getVendorName($shipment->getUdropshipVendor()))) {
                $soi->setVendorName($vName);
            }
            if (($stId = $shipment->getStatementId())) {
                $soi->setStatementId($stId);
                if (($st = Mage::getModel('udropship/vendor_statement')->load($stId, 'statement_id')) && $st->getId()) {
                    $soi->setStatementUrl(Mage::getModel('adminhtml/url')->getUrl('udropshipadmin/adminhtml_vendor_statement/edit', array('id'=>$st->getId())));
                }
            }
            if (Mage::helper('udropship')->isUdpayoutActive() && ($ptId = $shipment->getPayoutId())) {
                $soi->setPayoutId($ptId);
                if (($pt = Mage::getModel('udpayout/payout')->load($ptId)) && $pt->getId()) {
                    $soi->setPayoutUrl(Mage::getModel('adminhtml/url')->getUrl('udpayoutadmin/payout/edit', array('id'=>$pt->getId())));
                }
            }
        	if (Mage::helper('udropship')->isUdpoActive() && ($ptId = $shipment->getUdpoId())) {
                $soi->setUdpoId($ptId);
                if (Mage::helper('udpo')->getShipmentPo($shipment)) {
                	$soi->setUdpoId($shipment->getUdpo()->getIncrementId());
                    $soi->setUdpoUrl(Mage::getModel('adminhtml/url')->getUrl('udpoadmin/order_po/view', array('udpo_id'=>$shipment->getUdpo()->getId())));
                }
            }
            if (Mage::helper('udropship')->isUdropshipOrder($shipment->getOrder())) {
                $shipment->getOrder()->setShippingDescription(sprintf('%s [%s]',
                    $shipment->getOrder()->getShippingDescription(),
                    $shipment->getUdropshipMethodDescription()
                ));
                $shipment->getOrder()->setBaseShippingAmount($shipment->getBaseShippingAmount());
                $shipment->getOrder()->setShippingAmount($shipment->getShippingAmount());
            }
        }
    }

    public function adminhtml_version($observer)
    {
        Mage::helper('udropship')->addAdminhtmlVersion('Unirgy_Dropship');
    }

    public function cronCollectTracking()
    {
        if (Mage::helper('udropship')->isSalesFlat()) {

            $res  = Mage::getSingleton('core/resource');
            $conn = $res->getConnection('sales_read');

            $sIdsSel = $conn->select()->distinct()
                ->from($res->getTableName('sales/shipment_track'), array('parent_id'))
                ->where('udropship_status=?', 'P')
                ->where('next_check<=?', now())
                ->limit(50);
            $sIds = $conn->fetchCol($sIdsSel);

        } else {
            $res  = Mage::getSingleton('core/resource');
            $conn = $res->getConnection('sales_read');

            $eav = Mage::getSingleton('eav/config');
            $trackEt = $eav->getEntityType('shipment_track');
            $udStatusAttr = $eav->getAttribute('shipment_track', 'udropship_status');
            $nextCheckAttr = $eav->getAttribute('shipment_track', 'next_check');

            $sIdsSel = $conn->select()->distinct()
                ->from(array('e' => $trackEt->getValueTablePrefix()), array('parent_id'))
                ->join(
                    array('us' => $udStatusAttr->getBackendTable()),
                    $conn->quoteInto('e.entity_id=us.entity_id and us.entity_type_id=?', $trackEt->getId())
                    .$conn->quoteInto(' and us.attribute_id=?', $udStatusAttr->getId()),
                    array())
                ->join(
                    array('nc' => $nextCheckAttr->getBackendTable()),
                    $conn->quoteInto('e.entity_id=nc.entity_id and nc.entity_type_id=?', $trackEt->getId())
                    .$conn->quoteInto(' and nc.attribute_id=?', $nextCheckAttr->getId()),
                    array())
                ->where('us.value=?', 'P')
                ->where('nc.value<=?', now())
                ->limit(50);
            $sIds = $conn->fetchCol($sIdsSel);
        }

        if (!empty($sIds)) {
            $tracks = Mage::getModel('sales/order_shipment_track')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('udropship_status', 'P')
                ->addAttributeToFilter('parent_id', array('in'=>$sIds))
                ->addAttributeToSort('parent_id')
            ;

            try {
                Mage::helper('udropship')->collectTracking($tracks);
            } catch (Exception $e) {
                $tracksByStore = array();
                foreach ($tracks as $track) {
                    $tracksByStore[$track->getShipment()->getOrder()->getStoreId()][] = $track;
                }
                foreach ($tracksByStore as $sId => $_tracks) {
                    Mage::helper('udropship/error')->sendPollTrackingFailedNotification($_tracks, "$e", $sId);
                }
            }
        }

        if (0<Mage::getStoreConfig('udropship/error_notifications/poll_tracking_limit')) {
            $limit = date('Y-m-d H:i:s', time()-24*60*60*Mage::getStoreConfig('udropship/error_notifications/poll_tracking_limit'));

            $tracks = Mage::getModel('sales/order_shipment_track')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('udropship_status', 'P')
                ->addAttributeToFilter('created_at', array('datetime'=>true, 'to'=>$limit))
                ->setPageSize(50)
            ;
            $tracksByStore = array();
            foreach ($tracks as $track) {
                $cCode = $track->getCarrierCode();
                if (!$cCode) {
                    continue;
                }
                $vId = $track->getShipment()->getUdropshipVendor();
                $v = Mage::helper('udropship')->getVendor($vId);
                if (!$v->getTrackApi($cCode)) {
                    continue;
                }
                $tracksByStore[$track->getShipment()->getOrder()->getStoreId()][] = $track;
            }
            foreach ($tracksByStore as $sId => $_tracks) {
                Mage::helper('udropship/error')->sendPollTrackingLimitExceededNotification($_tracks, $sId);
            }
        }
    }

    /**
    * Check for extension update news
    *
    * @param Varien_Event_Observer $observer
    */
    public function adminhtml_controller_action_predispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('udropship/admin/notifications')) {
            try {
                Mage::getModel('udropship/feed')->checkUpdate();
            } catch (Exception $e) {
                // silently ignore
            }
        }
    }

    public function adminhtml_catalog_product_edit_element_types(Varien_Event_Observer $observer)
    {
        $observer->getResponse()->setTypes(array_merge($observer->getResponse()->getTypes(),
            array('udropship_vendor'=>Mage::getConfig()->getBlockClassName('udropship/vendor_htmlselect'))
        ));
    }

    public function vendorNotifyLowstock()
    {
        Mage::getSingleton('udropship/vendor_notifyLowstock')->vendorNotifyLowstock();
    }
    public function vendorCleanLowstock()
    {
        Mage::getSingleton('udropship/vendor_notifyLowstock')->vendorCleanLowstock();
    }

    public function udropship_shipment_status_save_after($observer)
    {
        $this->_sales_order_shipment_save_before($observer, true);
    }
    public function sales_order_shipment_save_before($observer)
    {
        $this->_sales_order_shipment_save_before($observer, false);
    }
    protected function _sales_order_shipment_save_before($observer, $isStatusEvent)
    {
        $po = $observer->getEvent()->getShipment();
        if ($po->getUdropshipVendor()
            && ($vendor = Mage::helper('udropship')->getVendor($po->getUdropshipVendor()))
            && $vendor->getId()
            && (!$po->getStatementDate() || $po->getStatementDate() == '0000-00-00 00:00:00')
            && $vendor->getStatementPoType() == 'shipment'
        ) {
            $stPoStatuses = $vendor->getStatementPoStatus();
            if (!is_array($stPoStatuses)) {
                $stPoStatuses = explode(',', $stPoStatuses);
            }
            if (in_array($po->getUdropshipStatus(), $stPoStatuses)) {
                $po->setStatementDate(now());
                if ($isStatusEvent) {
                    $po->getResource()->saveAttribute($po, 'statement_date');
                }
            }
        }
    }

    public function before_submit_order($observer)
    {
        $observer->getEvent()->getOrder()->setNoDropshipFlag(true);
        $this->unsQuote();
    }
    public function after_submit_order($observer)
    {
        $observer->getEvent()->getOrder()->setNoDropshipFlag(false);
        Mage::helper('udropship/protected')->sales_order_save_after($observer);
    }
    public function after_submit_order1400($observer)
    {
        $observer->getEvent()->getOrder()->setNoDropshipFlag(false);
        if (!Mage::helper('udropship')->isSalesFlat()) {
            Mage::helper('udropship/protected')->sales_order_save_after($observer);
        }
    }
    public function multishipping_before_submit_order($observer)
    {
        $observer->getEvent()->getOrder()->setNoDropshipFlag(true);
    }
    public function multishipping_after_submit_order($observer)
    {
        $orders = $observer->getEvent()->getOrders();
        if (!empty($orders)) {
            foreach ($observer->getEvent()->getOrders() as $order) {
                $observer->getEvent()->setOrder($order->setNoDropshipFlag(false));
                Mage::helper('udropship/protected')->sales_order_save_after($observer);
            }
        }
    }

    public function udropship_admin_validate($observer)
    {
        try {
            Unirgy_Dropship_Helper_Protected::validateLicense($observer->getModule());
        } catch (Exception $e) {
            Mage::getSingleton('udropship/session')->addError($e->getMessage());
        }
    }

    public function usimpleup_uninstall_unirgy_dropship($observer)
    {
        Mage::helper('usimpleup')->removeFiles('
app/etc/modules/Unirgy_Dropship.xml
app/code/community/Unirgy/Dropship/
app/code/community/Unirgy/DropshipHelper/
app/design/adminhtml/default/default/layout/udropship.xml
app/design/adminhtml/default/default/template/udropship/
app/design/frontend/base/default/layout/udropship.xml
app/design/frontend/base/default/template/unirgy/dropship/
app/design/frontend/default/default/layout/udropship.xml
app/design/frontend/default/default/template/unirgy/dropship/
app/locale/en_US/Unirgy_Dropship.csv
app/locale/en_US/template/email/udropship_password.html
app/locale/en_US/template/email/udropship_statement.html
app/locale/en_US/template/email/udropship_vendor.html
app/locale/en_US/template/email/udropship_vendor_notify_lowstock.html
app/locale/en_US/template/email/udropship_vendor_shipment_comment.html
skin/frontend/base/default/css/udropship.css
skin/frontend/default/default/css/udropship.css
app/design/frontend/default/udropship/
skin/frontend/default/udropship/
js/udropship.js
        ');
    }

    public function core_block_abstract_to_html_before($observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View_Items
            && (($order = $block->getOrder())
                || $block->getParentBlock() && ($order = $block->getParentBlock()->getOrder()))
        ) {
            $vendors = Mage::getSingleton('udropship/source')->getVendors();
            foreach ($order->getAllItems() as $oItem) {
                if (($vId = $oItem->getUdropshipVendor())
                    && isset($vendors[$vId])
                ) {
                    Mage::helper('udropship/item')->setVendorIdOption($oItem, $vId, true);
                }
            }
            Mage::helper('udropship/item')->attachOrderItemPoInfo($order);
        }
    }

    public function sales_quote_config_get_product_attributes($observer)
    {
        $paths = $this->getRuntimeProductAttributesConfigPaths();
        foreach ($paths as $path) {
            $path = str_replace('-', '/', $path);
            if (($attrCode = Mage::getStoreConfig($path))
                && Mage::helper('udropship')->checkProductCollectionAttribute($attrCode)
            ) {
                $observer->getAttributes()->setData($attrCode, $attrCode);
            }
        }
    }
    public function customer_address_format($observer)
    {
        $address = $observer->getAddress();
        $type = $observer->getType();
        if (!$type->getData('__udorig_default_format')) {
            $type->setData('__udorig_default_format', $type->getDefaultFormat());
        }
        $type->setDefaultFormat($type->getData('__udorig_default_format'));
        $vendor = $address->getData('__udropship_vendor');
        if ($vendor) {
            $flagKey = 'custom_'.$type->getCode().'_address_format';
            $customKey = $type->getCode().'_address_format';
            $store = $address->getConfig()->getStore();
            if ($vendor->getData($flagKey)) {
                if (-1 !== $vendor->getData($flagKey)
                    && ($format = $vendor->getData($customKey))
                ) {
                    $type->setDefaultFormat($format);
                } elseif (Mage::getStoreConfigFlag('udropship/customer/'.$flagKey, $store)
                    && ($format = Mage::getStoreConfig('udropship/customer/'.$customKey, $store))
                ) {
                    $type->setDefaultFormat($format);
                }
            }
        }
    }
    public function udropship_vendor_save_commit_after($observer)
    {
        $vendor = $observer->getVendor();
        Mage::getModel('udropship/productImage')->clearCache($vendor);
    }

    public function sales_convert_quote_item_to_order_item($observer)
    {
        $orderItem = $observer->getEvent()->getOrderItem();
        $quoteItem = $observer->getEvent()->getItem();
        $weightType = $quoteItem->getProduct()->getWeightType();

        if (!$this->getParentItem() && $weightType!==null) {
            $productOptions = $orderItem->getProductOptions();
            $productOptions['weight_type'] = $weightType;
            $productOptions['udropship_vendor'] = $quoteItem->getUdropshipVendor();
            $orderItem->setProductOptions($productOptions);
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
        foreach (Mage::app()->getStores(true) as $store) {
            if (Mage::getStoreConfig('carriers/dhlint', $store)
                && !Mage::getStoreConfig('carriers/dhlint/content_type', $store)
            ) {
                $store->setConfig('carriers/dhlint/content_type', 'D');
                Mage::getConfig()->setNode('default/carriers/dhlint/content_type', 'D');
            }
        }
        $paths = $this->getRuntimeProductAttributesConfigPaths();
        $runtimeAttrCodes = array();
        foreach ($paths as $path) {
            $path = str_replace('-', '/', $path);
            if (($attrCode = Mage::getStoreConfig($path))
                && Mage::helper('udropship')->checkProductCollectionAttribute($attrCode)
            ) {
                $runtimeAttrCodes[$attrCode] = $attrCode;
            }
        }
        if (!empty($runtimeAttrCodes)) {
            $flatAttrNode = Mage::getConfig()->getNode('global/catalog/product/flat/attribute_nodes');
            $flatAttrNode->addChild('udropship_runtime_product_attributes', 'global/udropship/runtime_product_attributes');
            $runtimeAttrCodesParentNode = Mage::getConfig()->getNode('global/udropship/runtime_product_attributes');
            foreach ($runtimeAttrCodes as $runtimeAttrCode) {
                $runtimeAttrCodesParentNode->addChild($runtimeAttrCode);
            }
        }
    }

    public function beforeCrontab()
    {
        $this->initConfigRewrites();
    }
    public function initConfigRewrites()
    {
        Mage::dispatchEvent('udropship_init_config_rewrites', array());
    }

    public function core_config_data_save_commit_after($observer)
    {
        $configData = $observer->getEvent()->getConfigData();
        $paths = $this->getRuntimeProductAttributesConfigPaths();
        $addFilterable  = Mage::helper('catalog/product_flat')->isAddFilterableAttributes();
        $reindex = false;
        foreach ($paths as $path) {
            $path = str_replace('-', '/', $path);
            if ($configData->getPath() == $path
                && $configData->isValueChanged()
                && $configData->getValue()
                && ($attribute = Mage::helper('udropship')->getProductAttribute($configData->getValue()))
                && !(($attribute->getData('backend_type') == 'static')
                    || ($addFilterable && $attribute->getData('is_filterable') > 0)
                    || ($attribute->getData('used_in_product_listing') == 1)
                    || ($attribute->getData('is_used_for_promo_rules') == 1)
                    || ($attribute->getData('used_for_sort_by') == 1))
            ) {
                $reindex = true;
                break;
            }
        }
        if ($reindex && Mage::helper('catalog/product_flat')->isBuilt()) {
            Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_flat')->changeStatus(
                Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
    }

    public function udropship_order_save_before($observer)
    {
        $order = $observer->getOrder();
        $this->changeOrderStatusAfterPosGenarated($order);
    }
    public function udpo_order_save_before($observer)
    {
        $order = $observer->getOrder();
        $this->changeOrderStatusAfterPosGenarated($order);
    }
    public function changeOrderStatusAfterPosGenarated($order)
    {
        $strict = Mage::getStoreConfigFlag('udropship/vendor/strict_change_order_status_after_po');
        $cosAfterPoStatus = Mage::getStoreConfig('udropship/vendor/change_order_status_after_po');
        $madStatuses = explode(',', Mage::getStoreConfig('udropship/vendor/make_available_to_dropship', $order->getStoreId()));
        if ($cosAfterPoStatus
            && (in_array($order->getStatus(), $madStatuses) || !$strict)
            && $order->getStatus()!=$cosAfterPoStatus
        ) {
            $order->addStatusHistoryComment(
                Mage::helper('udropship')->__('Order status changed after POs generated'),
                $cosAfterPoStatus);
        }
    }

    public function getRuntimeProductAttributesConfigPaths()
    {
        $paths = array();
        if (($pathsNode = Mage::getConfig()->getNode('global/udropship/runtime_product_attributes_config_paths'))) {
            $paths = $pathsNode->asArray();
            $paths = array_keys($paths);
        }
        return $paths;
    }

}