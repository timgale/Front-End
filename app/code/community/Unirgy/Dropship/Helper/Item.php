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

class Unirgy_Dropship_Helper_Item extends Mage_Core_Helper_Abstract
{
    const SKIP_STOCK_CHECK_VENDOR_OPTION   = 'udropship_skip_stock_check';
    const PRIORITY_UDROPSHIP_VENDOR_OPTION = 'priority_udropship_vendor';
    const FORCED_UDROPSHIP_VENDOR_OPTION   = 'forced_udropship_vendor';
    const STICKED_UDROPSHIP_VENDOR_OPTION  = 'sticked_udropship_vendor';
    const UDROPSHIP_VENDOR_OPTION          = 'udropship_vendor';

    public function getSkipStockCheckVendorOption($item)
    {
        return $this->_getItemOption($item, self::SKIP_STOCK_CHECK_VENDOR_OPTION);
    }
    public function setSkipStockCheckVendorOption($item, $flag)
    {
        $this->_saveItemOption($item, self::SKIP_STOCK_CHECK_VENDOR_OPTION, $flag, false);
        return $this;
    }
    public function deleteSkipStockCheckVendorOption($item)
    {
        $this->deleteItemOption($item, self::SKIP_STOCK_CHECK_VENDOR_OPTION);
        return $this;
    }

    /* priority vendor option methods */
    public function getPriorityVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION);
    }
    public function setPriorityVendorIdOption($item, $vId, $visible=false)
    {
        $this->_saveItemOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisiblePriorityVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisiblePriorityVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deletePriorityVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisiblePriorityVendorIdOption($item);
        return $this;
    }
    public function deleteVisiblePriorityVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::PRIORITY_UDROPSHIP_VENDOR_OPTION);
        return $this;
    }

    /* forced vendor option methods */
    public function getForcedVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION);
    }
    public function setForcedVendorIdOption($item, $vId, $visible=false)
    {
        $this->_saveItemOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisibleForcedVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisibleForcedVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deleteForcedVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisibleForcedVendorIdOption($item);
        return $this;
    }
    public function deleteVisibleForcedVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::FORCED_UDROPSHIP_VENDOR_OPTION);
        return $this;
    }

    /* sticked vendor option methods */
    public function getStickedVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION);
    }
    public function setStickedVendorIdOption($item, $vId, $visible=false)
    {
        $this->_saveItemOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisibleStickedVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisibleStickedVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deleteStickedVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisibleStickedVendorIdOption($item);
        return $this;
    }
    public function deleteVisibleStickedVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::STICKED_UDROPSHIP_VENDOR_OPTION);
        return $this;
    }

    /* general vendor option methods */
    public function getVendorIdOption($item)
    {
        return $this->_getItemOption($item, self::UDROPSHIP_VENDOR_OPTION);
    }
    public function setVendorIdOption($item, $vId, $visible=false)
    {
        $this->saveItemOption($item, self::UDROPSHIP_VENDOR_OPTION, $vId, false);
        if ($visible) {
            $this->setVisibleVendorIdOption($item, $vId);
        }
        return $this;
    }
    public function setVisibleVendorIdOption($item, $vId)
    {
        $this->_saveVisibleVendorIdOption($item, self::UDROPSHIP_VENDOR_OPTION, $vId);
        return $this;
    }
    public function deleteVendorIdOption($item)
    {
        $this->deleteItemOption($item, self::UDROPSHIP_VENDOR_OPTION);
        $this->deleteVisibleVendorIdOption($item);
        return $this;
    }
    public function deleteVisibleVendorIdOption($item)
    {
        $this->_deleteVisibleVendorIdOption($item, self::UDROPSHIP_VENDOR_OPTION);
        return $this;
    }



    protected function _deleteVisibleVendorIdOption($item, $optCode)
    {
        $addOptions = $this->getAdditionalOptions($item);
        if (!empty($addOptions) && is_string($addOptions)) {
            $addOptions = unserialize($addOptions);
        }
        if (!is_array($addOptions)) {
            $addOptions = array();
        }
        foreach ($addOptions as $idx => $option) {
            if (!empty($option['code']) && $optCode==$option['code']) {
                $vendorOptionIdx = $idx;
                break;
            }
        }
        if (isset($vendorOptionIdx)) unset($addOptions[$vendorOptionIdx]);
        $this->saveAdditionalOptions($item, $addOptions);
        return $this;
    }

    protected function _saveVisibleVendorIdOption($item, $optCode, $value)
    {
        $addOptions = $this->getAdditionalOptions($item);
        if (!empty($addOptions) && is_string($addOptions)) {
            $addOptions = unserialize($addOptions);
        }
        if (!is_array($addOptions)) {
            $addOptions = array();
        }
        foreach ($addOptions as $idx => $option) {
            if (!empty($option['code']) && $optCode==$option['code']) {
                $vendorOptionIdx = $idx;
                break;
            }
        }
        $vendorOption['code']  = $optCode;
        $vendorOption['label'] = Mage::helper('udropship')->__('Vendor');
        $vendorOption['value'] = Mage::helper('udropship')->getVendor($value)->getVendorName();
        if (isset($vendorOptionIdx)) {
            $addOptions[$vendorOptionIdx] = $vendorOption;
        } else {
            $addOptions[] = $vendorOption;
        }
        $this->saveAdditionalOptions($item, $addOptions);
        return $this;
    }
    public function getAdditionalOptions($item)
    {
        return $this->_getItemOption($item, 'additional_options');
    }
    public function getItemOption($item, $code)
    {
        return $this->_getItemOption($item, $code);
    }
    protected function _getItemOption($item, $code)
    {
        $optValue = null;
        if ($item instanceof Mage_Catalog_Model_Product
            && $item->getCustomOption($code)
        ) {
            $optValue = $item->getCustomOption($code)->getValue();
        } elseif ($item instanceof Mage_Sales_Model_Quote_Item
            && $item->getOptionByCode($code)
        ) {
            $optValue = $item->getOptionByCode($code)->getValue();
        } elseif ($item instanceof Mage_Sales_Model_Quote_Address_Item && $item->getQuoteItem()
            && $item->getQuoteItem()->getOptionByCode($code)
        ) {
            $optValue = $item->getQuoteItem()->getOptionByCode($code)->getValue();
        } elseif ($item instanceof Mage_Sales_Model_Order_Item) {
            $options = $item->getProductOptions();
            if (isset($options[$code])) {
                $optValue = $options[$code];
            }
        } elseif ($item instanceof Varien_Object && $item->getOrderItem()) {
            $options = $item->getOrderItem()->getProductOptions();
            if (isset($options[$code])) {
                $optValue = $options[$code];
            }
        }
        return $optValue;
    }
    public function saveAdditionalOptions($item, $options)
    {
        return $this->_saveItemOption($item, 'additional_options', $options, true);
    }
    public function saveItemOption($item, $code, $value, $serialize)
    {
        return $this->_saveItemOption($item, $code, $value, $serialize);
    }
    protected function _saveItemOption($item, $code, $value, $serialize)
    {
        if ($item->isDeleted()) return false;
        $currentTime = now();
        if ($item instanceof Mage_Catalog_Model_Product) {
            if ($item->getCustomOption($code)) {
                $item->getCustomOption($code)->setValue($serialize ? serialize($value) : $value);
            } else {
                $item->addCustomOption($code, $serialize ? serialize($value) : $value);
            }
            $item->setUpdatedAt($currentTime);
        } elseif ($item instanceof Mage_Sales_Model_Quote_Item) {
            $optionsByCode = $item->getOptionsByCode();
            if (isset($optionsByCode[$code])) {
                $optionsByCode[$code]->isDeleted(false);
                $optionsByCode[$code]->setValue($serialize ? serialize($value) : $value);
            } else {
                $item->addOption(array(
                    'product' => $item->getProduct(),
                    'product_id' => $item->getProduct()->getId(),
                    'code' => $code,
                    'value' => $serialize ? serialize($value) : $value
                ));
            }
            $item->setUpdatedAt($currentTime);
        } elseif ($item instanceof Mage_Sales_Model_Quote_Address_Item && $item->getQuoteItem()) {
            $optionsByCode = $item->getQuoteItem()->getOptionsByCode();
            if (isset($optionsByCode[$code])) {
                $optionsByCode[$code]->isDeleted(false);
                $optionsByCode[$code]->setValue($serialize ? serialize($value) : $value);
            } else {
                $item->getQuoteItem()->addOption(array(
                    'product' => $item->getQuoteItem()->getProduct(),
                    'product_id' => $item->getQuoteItem()->getProduct()->getId(),
                    'code' => $code,
                    'value' => $serialize ? serialize($value) : $value
                ));
            }
            $item->getQuoteItem()->setUpdatedAt($currentTime);
        } elseif ($item instanceof Mage_Sales_Model_Order_Item) {
            $options = $item->getProductOptions();
            $options[$code] = $value;
            $item->setProductOptions($options);
            $item->setUpdatedAt($currentTime);
        } elseif ($item instanceof Varien_Object && $item->getOrderItem()) {
            $options = $item->getOrderItem()->getProductOptions();
            $options[$code] = $value;
            $item->getOrderItem()->setProductOptions($options);
            $item->getOrderItem()->setUpdatedAt($currentTime);
        }
        return $value;
    }
    public function deleteItemOption($item, $code)
    {
        return $this->_deleteItemOption($item, $code);
    }
    protected function _deleteItemOption($item, $code)
    {
        if ($item instanceof Mage_Catalog_Model_Product) {
            $customOptions = $item->getCustomOptions();
            unset($customOptions[$code]);
            $item->setCustomOptions($customOptions);
        } elseif ($item instanceof Mage_Sales_Model_Quote_Item) {
            $item->removeOption($code);
        } elseif ($item instanceof Mage_Sales_Model_Quote_Address_Item && $item->getQuoteItem()) {
            $item->getQuoteItem()->removeOption($code);
        } elseif ($item instanceof Mage_Sales_Model_Order_Item) {
            $options = $item->getProductOptions();
            unset($options[$code]);
            $item->setProductOptions($options);
        } elseif ($item instanceof Varien_Object && $item->getOrderItem()) {
            $options = $item->getOrderItem()->getProductOptions();
            unset($options[$code]);
            $item->getOrderItem()->setProductOptions($options);
        }
        return $this;
    }

    public function setUdropshipVendor($item, $vId)
    {
        $oldVendorId = $item->getUdropshipVendor();
        $item->setUdropshipVendor($vId);
        Mage::dispatchEvent('udropship_quote_item_setUdropshipVendor',
            array('item'=>$item, 'old_vendor_id'=>$oldVendorId, 'new_vendor_id'=>$vId)
        );
        return $this;
    }

    public function compareQuoteItems($item1, $item2)
    {
        if ($item1->getProductId() != $item2->getProductId()) {
            return false;
        }
        foreach ($item1->getOptions() as $option) {
            if ($option->isDeleted() || in_array($option->getCode(), array('info_buyRequest'))) {
                continue;
            }
            if ($item2Option = $item2->getOptionByCode($option->getCode())) {
                $item2OptionValue = $item2Option->getValue();
                $optionValue     = $option->getValue();

                // dispose of some options params, that can cramp comparing of arrays
                if (is_string($item2OptionValue) && is_string($optionValue)) {
                    $_itemOptionValue = @unserialize($item2OptionValue);
                    $_optionValue     = @unserialize($optionValue);
                    if (is_array($_itemOptionValue) && is_array($_optionValue)) {
                        $item2OptionValue = $_itemOptionValue;
                        $optionValue     = $_optionValue;
                        // looks like it does not break bundle selection qty
                        unset($item2OptionValue['qty'], $item2OptionValue['uenc'], $optionValue['qty'], $optionValue['uenc']);
                    }
                }

                if ($item2OptionValue != $optionValue) {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        return true;
    }

    public function getQuote($item)
    {
        $quote = false;
        if ($item instanceof Mage_Sales_Model_Quote_Item_Abstract) {
            $quote = $item->getQuote();
        } elseif (is_array($item) || $item instanceof Traversable) {
            foreach ($item as $_item) {
                $quote = $_item->getQuote();
                break;
            }
        }
        return $quote;
    }
    public function getAddress($item)
    {
        $address = false;
        if ($item instanceof Mage_Sales_Model_Quote_Item_Abstract) {
            $quote = $item->getQuote();
            $address = $item->getAddress();
        } elseif (is_array($item) || $item instanceof Traversable) {
            foreach ($item as $_item) {
                $quote = $_item->getQuote();
                $address = $_item->getAddress();
                break;
            }
        }
        if ($quote instanceof Varien_Object && !$address) {
            $address = $quote->getShippingAddress();
        }
        return $address;
    }

    public function createClonedQuoteItem($item, $qty)
    {
        $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($item->getProductId());
        if (!$product->getId()) {
            return false;
        }

        $info = $this->getItemOption($item, 'info_buyRequest');
        $info = new Varien_Object(unserialize($info));
        $info->setQty($qty);

        $item = $item->getQuote()->addProduct($product, $info);
        return $item;
    }

    public function attachOrderItemPoInfo($order)
    {
        if (Mage::helper('udropship')->isModuleActive('udpo')) {
            $statuses = Mage::getSingleton('udpo/source')->setPath('po_statuses')->toOptionHash();
        } else {
            $statuses = Mage::getSingleton('udropship/source')->setPath('shipment_statuses')->toOptionHash();
        }
        $poInfo = Mage::getResourceSingleton('udropship/helper')->getOrderItemPoInfo($order);
        $vendors = Mage::getSingleton('udropship/source')->getVendors(true);
        foreach ($poInfo as $poi) {
            $optKey = 'udropship_poinfo';
            $optVal = $poi['item_id'].'-'.$poi['increment_id'];
            $item = $order->getItemById($poi['item_id']);
            $addOptions = $this->getAdditionalOptions($item);
            if (!empty($addOptions) && is_string($addOptions)) {
                $addOptions = unserialize($addOptions);
            }
            if (!is_array($addOptions)) {
                $addOptions = array();
            }
            foreach ($addOptions as $idx => $option) {
                if (@$option[$optKey] == $optVal) {
                    $vendorOptionIdx = $idx;
                    break;
                }
            }
            $vendorOption['label'] = Mage::helper('udropship')->__('PO #%s Vendor', $poi['increment_id']);
            //$vendorOption['value'] = Mage::helper('udropship')->__('%s (qty: x%s) [status: %s]', @$vendors[$poi['udropship_vendor']], 1*$poi['qty'], @$statuses[$poi['udropship_status']]);
            $vendorOption['value'] = Mage::helper('udropship')->__('%s (qty: %s)', @$vendors[$poi['udropship_vendor']], 1*$poi['qty']);
            if (isset($vendorOptionIdx)) {
                $addOptions[$vendorOptionIdx] = $vendorOption;
            } else {
                $addOptions[] = $vendorOption;
            }
            $this->saveAdditionalOptions($item, $addOptions);
        }
    }

    public function hideVendorIdOption($po)
    {
        foreach ($po->getAllItems() as $poItem) {
            $item = $poItem->getOrderItem();
            $this->deleteVisibleVendorIdOption($item);
        }
    }

    public function initPoTotals($po)
    {
        $hlp = Mage::helper('udropship');
        $isTierCom = $hlp->isModuleActive('Unirgy_DropshipTierCommission');
        $vendor = $hlp->getVendor($po->getUdropshipVendor());
        $order = $po->getOrder();
        $statement = Mage::getModel('udropship/vendor_statement')->setVendor($vendor)->setVendorId($vendor->getId());
        $totals = $statement->getEmptyTotals(true);
        $totals_amount = $statement->getEmptyTotals();
        $hlp->collectPoAdjustments(array($po), true);
        $stOrders = array();
        if ($isTierCom) {
            $onlySubtotal = false;
            foreach ($po->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) continue;
                $stOrder = $statement->initPoItem($item, $onlySubtotal);
                $onlySubtotal = true;
                $stOrder = $statement->calculateOrder($stOrder);
                $totals_amount = $statement->accumulateOrder($stOrder, $totals_amount);
                $stOrders[$item->getId()] = $stOrder;
            }
        } else {
            $stOrder = $statement->initOrder($po);
            $stOrder = $statement->calculateOrder($stOrder);
            $totals_amount = $statement->accumulateOrder($stOrder, $totals_amount);
        }
        $this->formatOrderAmounts($order, $totals, $totals_amount, 'merge');
        $poTotals = array();
        foreach ($totals as $tKey=>$tValue) {
            $tLabel = false;
            switch ($tKey) {
                case 'subtotal':
                    $tLabel = $hlp->__('Subtotal');
                    break;
                case 'com_percent':
                    if (!$isTierCom) {
                        $tLabel = $hlp->__('Commission Percent');
                    }
                    break;
                case 'trans_fee':
                    $tLabel = $hlp->__('Transaction Fee');
                    break;
                case 'com_amount':
                    $tLabel = $hlp->__('Commission Amount');
                    break;
                case 'adj_amount':
                    if ($tValue>0) {
                        $tLabel = $hlp->__('Adjustment');
                    }
                    break;
                case 'total_payout':
                    $tLabel = $hlp->__('Total Payout');
                    break;
                case 'tax':
                    if (in_array($vendor->getStatementTaxInPayout(), array('', 'include'))) {
                        $tLabel = $hlp->__('Tax Amount');
                    }
                    break;
                case 'discount':
                    if (in_array($vendor->getStatementDiscountInPayout(), array('', 'include'))) {
                        $tLabel = $hlp->__('Discount');
                    }
                    break;
                case 'shipping':
                    if (in_array($vendor->getStatementShippingInPayout(), array('', 'include'))) {
                        $tLabel = $hlp->__('Shipping');
                    }
                    break;
            }
            if ($tLabel) {
                $poTotals[] = array(
                    'label' => $tLabel,
                    'value' => $tValue
                );
            }
        }
        $po->setUdropshipTotals($poTotals);

        foreach ($po->getAllItems() as $poItem) {
            if ($poItem->getOrderItem()->getParentItem()) continue;
            $item = $poItem->getOrderItem();
            $addOptions = $this->getAdditionalOptions($item);
            if (!empty($addOptions) && is_string($addOptions)) {
                $addOptions = unserialize($addOptions);
            }
            if (!is_array($addOptions)) {
                $addOptions = array();
            }
            $addOptions = array();
            if ($vendor->getStatementSubtotalBase() == 'cost') {
                $addOptions[] = array(
                    'label' => $hlp->__('Cost'),
                    'value' => $this->formatBasePrice($order, $item->getBaseCost())
                );
                if ($poItem->getQty()>1) {
                    $addOptions[] = array(
                        'label' => $hlp->__('Row Cost'),
                        'value' => $this->formatBasePrice($order, $item->getBaseCost()*$poItem->getQty())
                    );
                }
            } else {
                $addOptions[] = array(
                    'label' => $hlp->__('Price'),
                    'value' => $this->formatBasePrice($order, $item->getBasePrice())
                );
                if ($poItem->getQty()>1) {
                    $addOptions[] = array(
                        'label' => $hlp->__('Row Total'),
                        'value' => $this->formatBasePrice($order, $item->getBasePrice()*$poItem->getQty())
                    );
                }
            }
            if ($item->getBaseTaxAmount() && in_array($vendor->getStatementTaxInPayout(), array('', 'include'))) {
                $iTax = $item->getBaseTaxAmount()/max(1,$item->getQtyOrdered());
                $iTax = $iTax*$poItem->getQty();
                $addOptions[] = array(
                    'label' => $hlp->__('Tax Amount'),
                    'value' => $this->formatBasePrice($order, $iTax)
                );
            }
            if ($item->getBaseDiscountAmount() && in_array($vendor->getStatementDiscountInPayout(), array('', 'include'))) {
                $iDiscount = $item->getBaseDiscountAmount()/max(1,$item->getQtyOrdered());
                $iDiscount = $iDiscount*$poItem->getQty();
                $addOptions[] = array(
                    'label' => $hlp->__('Discount'),
                    'value' => $this->formatBasePrice($order, $iDiscount)
                );
            }
            if ($isTierCom && isset($stOrders[$poItem->getId()]['com_percent']) && $stOrders[$poItem->getId()]['com_percent']>0) {
                $addOptions[] = array(
                    'label' => $hlp->__('Commission Percent'),
                    'value' => sprintf('%s%%', $stOrders[$poItem->getId()]['com_percent'])
                );
                if (isset($stOrders[$poItem->getId()]['amounts']['com_amount'])) {
                $addOptions[] = array(
                    'label' => $hlp->__('Commission Amount'),
                    'value' => $this->formatBasePrice($order, $stOrders[$poItem->getId()]['amounts']['com_amount'])
                );
                }
            }
            $poItem->setUdropshipTotals($addOptions);
            //$this->saveAdditionalOptions($item, $addOptions);
        }
    }

    public function formatBasePrice($order, $cost)
    {
        if (!$order->getBaseCurrency()) {
            $order->setBaseCurrency(Mage::getModel('directory/currency')->load($order->getBaseCurrencyCode()));
        }
        return $order->getBaseCurrency()->formatTxt($cost);
    }

    public function formatOrderAmounts($order, &$data, $defaultAmounts=null, $useDefault=false)
    {
        $core = Mage::helper('core');
        $iter = (is_null($defaultAmounts) ? $data : $defaultAmounts);
        if (is_array($iter)) {
            foreach ($iter as $k => $v) {
                if ($useDefault == 'merge' || $useDefault && !isset($data[$k])) {
                    $data[$k] = $this->formatBasePrice($order, (float)$v);
                } elseif (isset($data[$k])) {
                    $data[$k] = $this->formatBasePrice($order, (float)$data[$k]);
                }
            }
        }
        return $this;
    }
}