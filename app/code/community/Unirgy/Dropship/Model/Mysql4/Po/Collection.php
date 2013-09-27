<?php

class Unirgy_Dropship_Model_Mysql4_Po_Collection extends Mage_Sales_Model_Mysql4_Order_Shipment_Collection
{
    protected function _construct()
    {
        $this->_init('udropship/po');
    }

    public function addPendingBatchStatusFilter()
    {
    	$exportOnPoStatus = Mage::getStoreConfig('udropship/batch/export_on_po_status');
    	if (!is_array($exportOnPoStatus)) {
    		$exportOnPoStatus = explode(',', $exportOnPoStatus);
    	}
        if (!Mage::helper('udropship')->isSalesFlat()) {
            $attr = Mage::getSingleton('eav/config')->getAttribute('shipment', 'udropship_status');
            $this->getSelect()->joinLeft(
                array('_udbatch_status'=>$attr->getBackend()->getTable()),
                "_udbatch_status.entity_id=e.entity_id and _udbatch_status.attribute_id={$attr->getId()}",
                array()
            )->where("_udbatch_status.value in (?)", $exportOnPoStatus);
        } else {
            $this->getSelect()->where("udropship_status in (?)", $exportOnPoStatus);
        }
        return $this;
    }

    public function addOrders()
    {
        if (!Mage::helper('udropship')->isSalesFlat()) {
            $this->addAttributeToSelect('order_id', 'inner');
        }

        $orderIds = array();
        foreach ($this as $po) {
            if ($po->getOrderId()) {
                $orderIds[$po->getOrderId()] = 1;
            }
        }

        if ($orderIds) {
            $orders = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in'=>array_keys($orderIds)));
            foreach ($this as $po) {
                $po->setOrder($orders->getItemById($po->getOrderId()));
            }
        }
        return $this;
    }
}