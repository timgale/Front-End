<?php

class Unirgy_DropshipTierCommission_Model_VendorStatement extends Unirgy_Dropship_Model_Vendor_Statement
{
    public function fetchOrders()
    {
        $hlp = Mage::helper('udropship');
        $core = Mage::helper('core');
        $vendor = $this->getVendor();

        $this->setPoType($vendor->getStatementPoType());

        $this->_resetOrders();
        $this->_resetTotals();
        $this->_cleanAdjustments();
        $this->_payouts = array();
        $this->setTotalPaid(0);

        $pos = $this->getPoCollection();
        $hlp->collectPoAdjustments($pos, true);

        Mage::dispatchEvent('udropship_vendor_statement_pos', array(
            'statement'=>$this,
            'pos'=>$pos
        ));

        $totals_amount = $this->_totals_amount;

        foreach ($pos as $id=>$po) {

            Mage::helper('udropship')->addVendorSkus($po);
            $onlySubtotal = false;
            foreach ($po->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) continue;
                $order = $this->initPoItem($item, $onlySubtotal);
                $onlySubtotal = true;

                Mage::dispatchEvent('udropship_vendor_statement_item_row', array(
                    'statement'=>$this,
                    'po'=>$po,
                    'po_item'=>$item,
                    'order'=>&$order
                ));

                $order = $this->calculateOrder($order);
                $totals_amount = $this->accumulateOrder($order, $totals_amount);

                $this->_orders[$id.'-'.$item->getId()] = $order;
            }
        }

        if (Mage::helper('udropship')->isStatementRefundsEnabled()) {

        $refunds = $this->getRefundCollection();

        $processedRefundIds = array();
        foreach ($refunds as $id=>$refund) {

            $refundRow = $this->initRefundItem($refund, in_array($refund->getRefundId(), $processedRefundIds));
            $processedRefundIds[] = $refund->getRefundId();

            Mage::dispatchEvent('udropship_vendor_statement_refund_item_row', array(
                'statement'=>$this,
                'refund'=>$refund,
                'refund_row'=>&$refundRow
            ));

            $refundRow = $this->calculateRefund($refundRow);
            $totals_amount = $this->accumulateRefund($refundRow, $totals_amount);

            $this->_refunds[$id] = $refundRow;
        }

        }

        Mage::dispatchEvent('udropship_vendor_statement_totals', array(
            'statement'=>$this,
        	'totals'=>&$totals_amount,
            'totals_amount'=>&$totals_amount
        ));

        $this->_totals_amount = $totals_amount;

        Mage::dispatchEvent('udropship_vendor_statement_collect_payouts', array(
            'statement'=>$this,
        ));

        $this->_calculateAdjustments();
        $this->finishStatement();

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

    public function initRefundItem($refundItem, $onlySubtotal)
    {
        $pOptions = $refundItem->getProductOptions();
        if (!is_array($pOptions)) {
            $pOptions = unserialize($pOptions);
        }
        $hlp = Mage::helper('udropship');
        $order = array(
            'po_id' => $refundItem->getPoId(),
            'date' => $refundItem->getPoCreatedAt(),
            'id' => $refundItem->getPoIncrementId(),
            'com_percent' => $refundItem->getCommissionPercent(),
            'order_id' => $refundItem->getOrderId(),
            'order_created_at' => $refundItem->getOrderCreatedAt(),
            'order_increment_id' => $refundItem->getOrderIncrementId(),
            'refund_created_at' => $refundItem->getRefundCreatedAt(),
            'refund_increment_id' => $refundItem->getRefundIncrementId(),
            'po_increment_id' => $refundItem->getPoIncrementId(),
            'po_created_at' => $refundItem->getPoCreatedAt(),
            'po_type' => $refundItem->getPoType(),
            'sku' => $refundItem->getSku(),
            'simple_sku' => @$pOptions['simple_sku'],
            'vendor_sku' => $refundItem->getVendorSku(),
            'vendor_simple_sku' => $refundItem->getVendorSimpleSku(),
            'product' => $refundItem->getName(),
            'po_item_id' => $refundItem->getPoItemId(),
            'refund_item_id' => $refundItem->getRefundItemId()
        );
        $refundQty = min($refundItem->getQty(),$refundItem->getRefundQty());
        $iTax = $refundItem->getBaseTaxAmount()/max(1,$refundItem->getQtyOrdered());
        $iTax = $iTax*$refundQty;
        $iDiscount = $refundItem->getBaseDiscountAmount()/max(1,$refundItem->getQtyOrdered());
        $iDiscount = $iDiscount*$refundQty;
        if ($this->getVendor()->getStatementSubtotalBase() == 'cost') {
            $subtotal = $refundItem->getBaseCost()*$refundQty;
        } else {
            $subtotal = $refundItem->getBasePrice()*$refundQty;
        }
        $amountRow = array(
            'subtotal' => $subtotal,
            'shipping' => $onlySubtotal ? 0 : min($refundItem->getBaseShippingAmount(),$refundItem->getRefundShippingAmount()),
            'tax' => $onlySubtotal ? 0 : $iTax,
            'discount' => $onlySubtotal ? 0 : $iDiscount,
        );
        foreach ($amountRow as &$_ar) {
            $_ar = is_null($_ar) ? 0 : $_ar;
        }
        unset($_ar);
        $order['amounts'] = array_merge($this->_getEmptyTotals(), $amountRow);
        return $order;
    }

    public function calculateRefund($order)
    {
        if (is_null($order['com_percent'])) {
            $order['com_percent'] = $this->getVendor()->getCommissionPercent();
        }
        $order['com_percent'] *= 1;
        $order['amounts']['com_amount'] = round($order['amounts']['subtotal']*$order['com_percent']/100, 2);
        $order['amounts']['total_refund'] = $order['amounts']['subtotal']-$order['amounts']['com_amount'];
        if (isset($order['amounts']['tax']) && in_array($this->getVendor()->getStatementTaxInPayout(), array('', 'include'))) {
            if ($this->getVendor()->getApplyCommissionOnTax()) {
                $taxCom = round($order['amounts']['tax']*$order['com_percent']/100, 2);
                $order['amounts']['com_amount'] += $taxCom;
                $order['amounts']['total_refund'] -= $taxCom;
            }
            $order['amounts']['total_refund'] += $order['amounts']['tax'];
        }
        if (isset($order['amounts']['discount']) && in_array($this->getVendor()->getStatementDiscountInPayout(), array('', 'include'))) {
            if ($this->getVendor()->getApplyCommissionOnDiscount()) {
                $discountCom = round($order['amounts']['discount']*$order['com_percent']/100, 2);
                $order['amounts']['com_amount'] -= $discountCom;
                $order['amounts']['total_refund'] += $discountCom;
            }
            $order['amounts']['total_refund'] -= $order['amounts']['discount'];
        }
        if (isset($order['amounts']['shipping']) && in_array($this->getVendor()->getStatementShippingInPayout(), array('', 'include'))) {
            $order['amounts']['total_refund'] += $order['amounts']['shipping'];
        }

        return $order;
    }

    protected function _getRefundCollection()
    {
        $baseCost = Mage::helper('udropship')->hasMageFeature('order_item.base_cost');
        $fields = array('base_price', 'base_tax_amount', 'base_discount_amount', 'qty_ordered');
        if ($baseCost) $fields[] = 'base_cost';
        $poType = $this->getVendor()->getStatementPoType();
        $res = Mage::getSingleton('core/resource');
        $refunds = Mage::getResourceModel('sales/order_creditmemo_item_collection');
        $refunds->addFieldToSelect(array('refund_item_id'=>'entity_id','refund_qty'=>'qty'));
        $refunds->getSelect()
            ->join(
                array('r'=>$res->getTableName('sales/creditmemo')),
                'r.entity_id=main_table.parent_id',
                array('refund_increment_id'=>'increment_id','refund_created_at'=>'created_at','refund_id'=>'entity_id','refund_shipping_amount'=>'base_shipping_amount')
            )
            ->join(
                array('o'=>$res->getTableName('sales/order')),
                'o.entity_id=r.order_id',
                array()
            )
            ->join(
                array('tg'=>$poType == 'po' ? $res->getTableName('udpo/po_grid') : $res->getTableName('sales/shipment_grid')),
                'tg.order_id=o.entity_id',
                array('order_increment_id','po_increment_id'=>'increment_id','order_id','po_id'=>'entity_id','order_created_at','po_created_at'=>'created_at')
            )
            ->join(
                array('t'=>$poType == 'po' ? $res->getTableName('udpo/po') : $res->getTableName('sales/shipment')),
                't.entity_id=tg.entity_id',
                array('base_shipping_amount')
            )
            ->join(array('i'=>$res->getTableName('sales/order_item')), 'i.item_id=main_table.order_item_id', $fields)
            ->join(array('pi'=>$poType == 'po' ? $res->getTableName('udpo/po_item') : $res->getTableName('sales/shipment_item')), 'i.item_id=pi.order_item_id and t.entity_id=pi.parent_id', array('po_item_id'=>'entity_id','qty','commission_percent'))
            ->columns(array('po_type'=>new Zend_Db_Expr("'$poType'")))
            ->where("t.udropship_vendor=?", $this->getVendorId())
            ->where("r.created_at>=?", $this->getOrderDateFrom())
            ->where("r.created_at<=?", $this->getOrderDateTo())
            ->order('main_table.entity_id asc');

        return $refunds;
    }

    protected $_refundCollection;
    public function getRefundCollection($reload=false)
    {
        if (is_null($this->_refundCollection) || $reload) {
            $this->_refundCollection = $this->_getRefundCollection();
        }
        return $this->_refundCollection;
    }
}
