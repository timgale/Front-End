<?php

class Unirgy_DropshipTierCommission_Model_Payout extends Unirgy_DropshipPayout_Model_Payout
{
    public function addPo($po)
    {
        $core = Mage::helper('core');
        $hlp = Mage::helper('udropship');
        $ptHlp = Mage::helper('udpayout');
        $vendor = $this->getVendor();

        $this->initTotals();

        $hlp->collectPoAdjustments(array($po));
        Mage::helper('udropship')->addVendorSkus($po);

        $onlySubtotal = false;
        foreach ($po->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) continue;
            $order = $this->initPoItem($item, $onlySubtotal);
            $onlySubtotal = true;

            Mage::dispatchEvent('udropship_vendor_payout_item_row', array(
                'payout'  => $this,
                'po'      => $po,
                'po_item' => $item,
                'order'   => &$order
            ));

            $order = $this->calculateOrder($order);
            $this->_totals_amount = $this->accumulateOrder($order, $this->_totals_amount);

            $this->_orders[$po->getId().'-'.$item->getId()] = $order;
        }

        return $this;
    }

    public function initPoItem($poItem, $onlySubtotal)
    {
        $po = $poItem->getPo() ? $poItem->getPo() : $poItem->getShipment();
        $orderItem = $poItem->getOrderItem();
        $hlp = Mage::helper('udropship');
        $order = array(
            'po_id' => $po->getId(),
            'date' => $hlp->getPoOrderCreatedAt($po),
            'id' => $hlp->getPoOrderIncrementId($po),
            'com_percent' => $poItem->getCommissionPercent(),
            'trans_fee' => $poItem->getTransactionFee(),
        	'adjustments' => $onlySubtotal ? array() : $po->getAdjustments(),
            'order_id' => $po->getOrderId(),
            'order_created_at' => $hlp->getPoOrderCreatedAt($po),
            'order_increment_id' => $hlp->getPoOrderIncrementId($po),
            'po_increment_id' => $po->getIncrementId(),
            'po_created_at' => $po->getCreatedAt(),
            'po_statement_date' => $po->getStatementDate(),
            'po_type' => $po instanceof Unirgy_DropshipPo_Model_Po ? 'po' : 'shipment',
            'sku' => $poItem->getSku(),
            'simple_sku' => $poItem->getOrderItem()->getProductOptionByCode('simple_sku'),
            'vendor_sku' => $poItem->getVendorSku(),
            'vendor_simple_sku' => $poItem->getVendorSimpleSku(),
            'product' => $poItem->getName(),
            'po_item_id' => $poItem->getId()
        );
        if ($this->getVendor()->getStatementSubtotalBase() == 'cost') {
            if (abs($poItem->getBaseCost())>0.001) {
                $subtotal = $poItem->getBaseCost()*$poItem->getQty();
            } else {
                $subtotal = $orderItem->getBaseCost()*$poItem->getQty();
            }
        } else {
            $subtotal = $orderItem->getBasePrice()*$poItem->getQty();
        }
        $amountRow = array(
            'subtotal' => $subtotal,
            'shipping' => $onlySubtotal ? 0 : $po->getBaseShippingAmount(),
            'tax' => $onlySubtotal ? 0 : $po->getBaseTaxAmount(),
            'discount' => $onlySubtotal ? 0 : $po->getBaseDiscountAmount(),
            'handling' => $onlySubtotal ? 0 : $po->getBaseHandlingFee(),
            'trans_fee' => $onlySubtotal ? 0 : $po->getTransactionFee(),
            'adj_amount' => $onlySubtotal ? 0 : $po->getAdjustmentAmount(),
        );
        foreach ($amountRow as &$_ar) {
            $_ar = is_null($_ar) ? 0 : $_ar;
        }
        unset($_ar);
        $order['amounts'] = array_merge($this->_getEmptyTotals(), $amountRow);
        return $order;
    }

    public function calculateOrder($order)
    {
        if (is_null($order['com_percent'])) {
            $order['com_percent'] = $this->getVendor()->getCommissionPercent();
        }
        $order['com_percent'] *= 1;
        /*
        if (is_null($order['amounts']['trans_fee'])) {
            $order['amounts']['trans_fee'] = $this->getVendor()->getTransactionFee();
        }
        */
        //$order['amounts']['trans_fee'] = @$order['trans_fee'];
        $order['amounts']['com_amount'] = round($order['amounts']['subtotal']*$order['com_percent']/100, 2);
        $order['amounts']['total_payout'] = $order['amounts']['subtotal']-$order['amounts']['com_amount']-$order['amounts']['trans_fee']+$order['amounts']['adj_amount'];
        //+$order['tax']+$order['handling']+$order['shipping'];

        /*
        foreach ($this->getWithholdOptions() as $k=>$l) {
            if (!$this->hasWithhold($k) && isset($order['amounts'][$k])) {
                $order['amounts']['total_payout'] += $order['amounts'][$k];
            }
        }
        */

        if (isset($order['amounts']['tax']) && in_array($this->getVendor()->getStatementTaxInPayout(), array('', 'include'))) {
            if ($this->getVendor()->getApplyCommissionOnTax()) {
                $taxCom = round($order['amounts']['tax']*$order['com_percent']/100, 2);
                $order['amounts']['com_amount'] += $taxCom;
                $order['amounts']['total_payout'] -= $taxCom;
            }
            $order['amounts']['total_payout'] += $order['amounts']['tax'];
        }
        if (isset($order['amounts']['discount']) && in_array($this->getVendor()->getStatementDiscountInPayout(), array('', 'include'))) {
            if ($this->getVendor()->getApplyCommissionOnDiscount()) {
                $discountCom = round($order['amounts']['discount']*$order['com_percent']/100, 2);
                $order['amounts']['com_amount'] -= $discountCom;
                $order['amounts']['total_payout'] += $discountCom;
            }
            $order['amounts']['total_payout'] -= $order['amounts']['discount'];
        }
        if (isset($order['amounts']['shipping']) && in_array($this->getVendor()->getStatementShippingInPayout(), array('', 'include'))) {
            $order['amounts']['total_payout'] += $order['amounts']['shipping'];
        }

        return $order;
    }

    protected function _compactTotals()
    {
        parent::_compactTotals();
        $ordersCnt = array();
        foreach ($this->getOrders() as $order) {
            $ordersCnt[$order['po_id']] = 1;
        }
        $this->setTotalOrders(array_sum($ordersCnt));
        return $this;
    }
}