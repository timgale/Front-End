<?php

/*
 * @copyright  Copyright (c) 2012 by  ESS-UA.
 */

class Ess_M2ePro_Model_Buy_Marketplace extends Ess_M2ePro_Model_Component_Child_Buy_Abstract
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Buy_Marketplace');
    }

    // ########################################

    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return (bool)Mage::getModel('M2ePro/Buy_Account')->getCollection()->getSize();
    }

    public function deleteInstance()
    {
        if ($this->isLocked()) {
            return false;
        }

        $categoriesTable  = Mage::getSingleton('core/resource')->getTableName('m2epro_buy_dictionary_category');
        Mage::getSingleton('core/resource')->getConnection('core_write')->delete($categoriesTable);

        $items = $this->getRelatedSimpleItems('Buy_Item','marketplace_id',true);
        foreach ($items as $item) {
            $item->deleteInstance();
        }

        $this->delete();
        return true;
    }

    // ########################################
}
