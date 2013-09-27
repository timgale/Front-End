<?php

/*
 * @copyright  Copyright (c) 2012 by  ESS-UA.
 */

class Ess_M2ePro_Model_Mysql4_Buy_Template_SellingFormat extends Ess_M2ePro_Model_Mysql4_Component_Child_Abstract
{
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init('M2ePro/Buy_Template_SellingFormat', 'template_selling_format_id');
        $this->_isPkAutoIncrement = false;
    }
}