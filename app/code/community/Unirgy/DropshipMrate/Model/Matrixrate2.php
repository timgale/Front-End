<?php

class Unirgy_DropshipMrate_Model_Matrixrate2 extends Unirgy_DropshipMrate_Model_Matrixrate
{
    protected $_code = 'udmrate';
    protected $_default_condition_name = 'package_value';
    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        return Mage::getResourceModel('udmrate/matrixrate2')->getNewRate($request,$this->getConfigFlag('zip_range'));
    }
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $result = parent::collectRates($request);
        if (false===$result) {
            return false;
        }

        $rates = $result->getAllRates();
        foreach ($rates as $rate) {
            $rate->setCarrier($this->_code);
        }

        return $result;
    }
}