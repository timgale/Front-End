<?php

class Unirgy_DropshipMrate_Model_Mysql4_Matrixrate2
    extends Unirgy_DropshipMrate_Model_Mysql4_Matrixrate
{
    protected function _construct()
    {
        $this->_init('udmrate/matrixrate', 'pk');
    }
    protected function _getUploadedCsvFile()
    {
        return $_FILES["groups"]["tmp_name"]["udmrate"]["fields"]["import"]["value"];
    }
    protected function _getPostedConditionName()
    {
        if (isset($_POST['groups']['udmrate']['fields']['condition_name']['inherit'])) {
            $conditionName = (string)Mage::getConfig()->getNode('default/carriers/udmrate/condition_name');
        } else {
            $conditionName = $_POST['groups']['udmrate']['fields']['condition_name']['value'];
        }
        return $conditionName;
    }
}