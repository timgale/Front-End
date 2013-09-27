<?php

class Unirgy_DropshipMrate_Model_Matrixrate
    extends Webshopapps_Matrixrate_Model_Carrier_Matrixrate
{
    /**
    * Unirgy: Update rate codes to delivery_type
    *
    * @param Mage_Shipping_Model_Rate_Request $request
    * @return Mage_Shipping_Model_Rate_Result
    */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $origConditionName = $request->getConditionName();
        $result = parent::collectRates($request);
        if (false===$result) {
            $request->setConditionName($origConditionName);
            return false;
        }

        $rates = $result->getAllRates();
        foreach ($rates as $rate) {
            if ($rate->getMethod()==='matrixrate_free') {
                continue;
            }
            if ($rate->getDeliveryType()) {
                $rate->setMethod($rate->getDeliveryType());
            } elseif ($rate->getMethodTitle()) {
                $rate->setMethod($rate->getMethodTitle());
            }
        }
        $request->setConditionName($origConditionName);

        return $result;
    }

    /**
    * Unirgy: display the real service methods from the table
    *
    */
    public function getAllowedMethods()
    {
        $methods = Mage::getResourceModel('matrixrate_shipping/carrier_matrixrate')->getAllMethods();
        $methods['matrixrate_free'] = $this->getConfigData('free_method_text');
        return $methods;
    }
}