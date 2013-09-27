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

class Unirgy_Dropship_Model_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
    /**
    * Should we make Magento think that the product is in stock,
    * when dropship vendors are configured to have unlimited stock?
    *
    * Inactive during udropship calculations logic to get true picture
    * Active all other times to fool Magento into thinking that the product is in stock
    *
    * @return boolean
    */
    public function getAlwaysInStock()
    {
        $hlp = Mage::helper('udropship');
        $availability = Mage::getSingleton('udropship/stock_availability');
        $store = Mage::app()->getStore();

        if (!$hlp->isActive($store) || !$availability->getUseLocalStockIfAvailable($store) || $availability->getTrueStock()) {
            return false;
        }

        if ($this->getProduct()) {
            $productVendor = $this->getProduct()->getUdropshipVendor();
        } else {
            $res = Mage::getSingleton('core/resource');
            $eav = Mage::getSingleton('eav/config');
            $read = $res->getConnection('catalog_read');
            $udvAttr = $eav->getAttribute('catalog_product', 'udropship_vendor');
            $select = $read->select()
                ->from($udvAttr->getBackend()->getTable(), array('value'))
                ->where('attribute_id=?', $udvAttr->getAttributeId())
                ->where('entity_id=?', $this->getProductId())
                ->where('store_id in (0, ?)', $store->getId())
                ->order('store_id', 'desc');
            $productVendor = $read->fetchOne($select);
        }

        $result = $productVendor && ($productVendor != $hlp->getLocalVendorId($store));
        return $result;
    }

    public function getIsInStock()
    {
        $result = $this->getAlwaysInStock() || parent::getIsInStock();
        Mage::dispatchEvent('udropship_stock_item_getIsInStock', array('item'=>$this, 'vars'=>array('result'=>&$result)));
        return $result;
    }

    public function checkQty($qty)
    {
        $result = $this->getAlwaysInStock() || parent::checkQty($qty);
        Mage::dispatchEvent('udropship_stock_item_checkQty', array('item'=>$this, 'vars'=>array('result'=>&$result), 'qty'=>$qty));
        return $result;
    }

    public function getQty()
    {
        $qty = $this->getData('qty');#$this->getAlwaysInStock() ? 999999999 : $this->getData('qty');
        Mage::dispatchEvent('udropship_stock_item_getQty', array('item'=>$this, 'vars'=>array('qty'=>&$qty)));
        return $qty;
    }

    public function getBackorders()
    {
        $backorders = parent::getBackorders();
        Mage::dispatchEvent('udropship_stock_item_getBackorders', array('item'=>$this, 'vars'=>array('backorders'=>&$backorders)));
        return $backorders;
    }

    public function assignProduct(Mage_Catalog_Model_Product $product)
    {
        parent::assignProduct($product);

        if ($this->getAlwaysInStock()) {
            $product->setIsSalable(true);
        }

        return $this;
    }

    public function checkQuoteItemQty($qty, $summaryQty, $origQty = 0)
    {
        $result = parent::checkQuoteItemQty($qty, $summaryQty, $origQty);
        Mage::dispatchEvent('udropship_stock_item_checkQuoteItemQty', array('item'=>$this, 'vars'=>array('result'=>&$result)));
        return $result;
    }

    /*
    public function getProductObject()
    {
        if ($this->getProductId() && !$this->getData('product_object')) {
            $this->setData('product_object', Mage::getModel('catalog/product')->load($this->getProductId()));
        }
        return $this->getData('product_object');
    }
    */

    // override is required, since Magento 1.4.0.1 removed the event cataloginventory_stock_item_save_before
    protected function _beforeSave()
    {
        parent::_beforeSave();

        Mage::dispatchEvent('udropship_stock_item_save_before', array('item' => $this));
    }

    public function verifyStock($qty = null)
    {
        $result = parent::verifyStock($qty);
        Mage::dispatchEvent('udropship_stock_item_verifyStock', array('item'=>$this, 'qty'=>$qty, 'vars'=>array('result'=>&$result)));
        return $result;
    }

    public function verifyNotification($qty = null)
    {
        $result = parent::verifyNotification($qty);
        Mage::dispatchEvent('udropship_stock_item_verifyNotification', array('item'=>$this, '$qty'=>$qty, 'vars'=>array('result'=>&$result)));
        return $result;
    }

    public function canSubtractQty()
    {
        $hlp = Mage::helper('udropship');
        $obs = Mage::getSingleton('udropship/observer');
        if ($this->getAlwaysInStock()) {
            $localVendorId = $hlp->getLocalVendorId();
            if (($item = $obs->getOrderItem())) {
                if ($item->getUdropshipVendor()!=$localVendorId) {
                    return false;
                }
            } elseif (($quote = $obs->getQuote())) {
                foreach ($quote->getAllItems() as $item) {
                    if ($item->getProductId()==$this->getProductId() && $item->getUdropshipVendor()!=$localVendorId) {
                        return false;
                    }
                }
            }
        }
        return $hlp->hasMageFeature('stock_can_subtract_qty') ? parent::canSubtractQty() : true;
    }

    public function subtractQty($qty)
    {
        if (Mage::helper('udropship')->hasMageFeature('stock_can_subtract_qty') || $this->canSubtractQty()) {
            return parent::subtractQty($qty);
        }
        return $this;
    }

    public function getProduct()
    {
        return $this->_productInstance;
    }
}