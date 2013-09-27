<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Order_Item_Proxy extends Ess_M2ePro_Model_Order_Item_Proxy
{
    // ########################################

    public function getGiftMessage()
    {
        if ($this->item->getGiftMessage() == '') {
            return parent::getGiftMessage();
        }

        return array(
            'sender'    => '', //$this->item->getAmazonOrder()->getBuyerName(),
            'recipient' => '', //$this->item->getAmazonOrder()->getShippingAddress()->getData('recipient_name'),
            'message'   => $this->item->getGiftMessage()
        );
    }

    // ########################################

    public function getPrice()
    {
        return $this->item->getPrice() + $this->item->getGiftPrice();
    }

    public function getOriginalQty()
    {
        return $this->item->getQtyPurchased();
    }

    // ########################################
}