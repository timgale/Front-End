<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Ebay_Order_Item_Proxy extends Ess_M2ePro_Model_Order_Item_Proxy
{
    // ########################################

    public function getVariation()
    {
        return $this->item->getVariation();
    }

    public function getPrice()
    {
        return $this->item->getPrice();
    }

    public function getOriginalQty()
    {
        return $this->item->getQtyPurchased();
    }

    public function getTaxRate()
    {
        return $this->item->getEbayOrder()->getTaxRate();
    }

    public function getAdditionalData()
    {
        $additionalData = parent::getAdditionalData();

        $additionalData['ebay_item_id'] = $this->item->getItemId();
        $additionalData['ebay_transaction_id'] = $this->item->getTransactionId();

        return $additionalData;
    }

    // ########################################
}