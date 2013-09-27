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

class Unirgy_Dropship_Block_Vendor_Shipment_Info extends Mage_Sales_Block_Items_Abstract
{
    protected function _construct()
    {
        Mage_Core_Block_Template::_construct();
        $this->addItemRender('default', 'sales/order_item_renderer_default', 'sales/order/shipment/items/renderer/default.phtml');
    }

    public function getVendor()
    {
        return Mage::getSingleton('udropship/session')->getVendor();
    }
    public function isShowTotals()
    {
        return Mage::helper('udropship')->getVendorFallbackField(
            $this->getVendor(),
            'portal_show_totals', 'udropship/vendor/portal_show_totals'
        );
    }

    public function getShipment()
    {
        if (!$this->hasData('shipment')) {
            $id = (int)$this->getRequest()->getParam('id');
            $shipment = Mage::getModel('sales/order_shipment')->load($id);
            $this->setData('shipment', $shipment);
            Mage::helper('udropship')->assignVendorSkus($shipment);
            Mage::helper('udropship/item')->hideVendorIdOption($shipment);
            if ($this->isShowTotals()) {
                Mage::helper('udropship/item')->initPoTotals($shipment);
            }
        }
        return $this->getData('shipment');
    }

    public function getRemainingWeight()
    {
        $weight = 0;
        foreach ($this->getShipment()->getAllItems() as $item) {
            $weight += $item->getWeight()*$item->getQty();
        }
        foreach ($this->getShipment()->getAllTracks() as $track) {
            $weight -= $track->getWeight();
        }
        return max(0, $weight);
    }

    public function getRemainingValue()
    {
        $value = 0;
        foreach ($this->getShipment()->getAllItems() as $item) {
            $value += $item->getPrice()*$item->getQty();
        }
        foreach ($this->getShipment()->getAllTracks() as $track) {
            $value -= (float)$track->getValue();
        }
        return max(0, $value);
    }

    public function getUdpo($shipment)
    {
        if (Mage::helper('udropship')->isUdpoActive()) {
            return Mage::helper('udpo')->getShipmentPo($shipment);
        } else {
            return false;
        }
    }

    public function getCarriers()
    {
        $carriers = array();
        $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers(
            $this->getShipment()->getStoreId()
        );
        $carriers[''] = Mage::helper('sales')->__('* Use PO carrier *');
        $carriers['custom'] = Mage::helper('sales')->__('Custom Value');
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[$code] = $carrier->getConfigData('title');
            }
        }
        return $carriers;
    }

    public function getCarrierTitle($code)
    {
        if ($carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($code)) {
            return $carrier->getConfigData('title');
        }
        else {
            return Mage::helper('sales')->__('Custom Value');
        }
        return false;
    }

}
