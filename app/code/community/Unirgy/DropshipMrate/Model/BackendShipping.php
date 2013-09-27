<?php

class Unirgy_DropshipMrate_Model_BackendShipping extends Mage_Core_Model_Config_Data
{
    public function _afterSave()
    {
		Mage::getResourceModel('udmrate/matrixrate2')->uploadAndImport($this);
    }
}
