<?php

class Unirgy_DropshipTierCommission_Model_PayoutObserver extends Unirgy_DropshipPayout_Model_Observer
{
    public function udropship_vendor_statement_item_row($observer)
    {
        $statementId = $observer->getEvent()->getStatement()->getStatementId();
        $sId = $observer->getEvent()->getPo()->getId();
        $eData = $observer->getEvent()->getData();
        $order = &$eData['order'];
        if (isset($this->_statementPayoutsByPo[$statementId][$sId])) {
            $order['paid'] = $this->_statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Unirgy_DropshipPayout_Model_Payout::STATUS_PAID
                || $this->_statementPayoutsByPo[$statementId][$sId]->getPayoutStatus()==Unirgy_DropshipPayout_Model_Payout::STATUS_PAYPAL_IPN;
        }
    }
}