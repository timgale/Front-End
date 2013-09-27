<?php

class Unirgy_DropshipMrate_Block_Adminhtml_SysConfigField_Exportmatrix2 extends Mage_Adminhtml_Block_System_Config_Form_Field

{

      protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);

        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'website' => $buttonBlock->getRequest()->getParam('website')
        );

         $data = array(
            'label'     => Mage::helper('adminhtml')->__('Export CSV'),

        'onclick'   => 'setLocation(\''.Mage::helper('adminhtml')->getUrl("udmrateadmin/config/exportmatrix", $params) . 'conditionName/\' + $(\'carriers_matrixrate_condition_name\').value + \'/matrixrate.csv\' )',
            'class'     => '',
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }


}
