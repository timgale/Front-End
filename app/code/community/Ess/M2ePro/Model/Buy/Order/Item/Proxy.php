<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Buy_Order_Item_Proxy extends Ess_M2ePro_Model_Order_Item_Proxy
{
    // ########################################

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
        // todo
        return 0;
    }

    public function hasVat()
    {
        // todo
        return true;
    }

    public function hasTax()
    {
        // todo
        return false;
    }

    // ########################################
}