<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Order_Change extends Ess_M2ePro_Model_Abstract
{
    const ACTION_UPDATE_PAYMENT  = 'update_payment';
    const ACTION_UPDATE_SHIPPING = 'update_shipping';

    const CREATOR_TYPE_OBSERVER = 1;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Order_Change');
    }

    //####################################

    public function getOrderId()
    {
        return (int)$this->getData('order_id');
    }

    public function getAction()
    {
        return (int)$this->getData('action');
    }

    public function getCreatorType()
    {
        return (int)$this->getData('creator_type');
    }

    public function getParams()
    {
        $params = json_decode($this->getData('params'), true);

        return is_array($params) ? $params : array();
    }

    //####################################
}