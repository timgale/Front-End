<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Mysql4_Config_Module extends Ess_M2ePro_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('M2ePro/Config_Module', 'id');
    }
}