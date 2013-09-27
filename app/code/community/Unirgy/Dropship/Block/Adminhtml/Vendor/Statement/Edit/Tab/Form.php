<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_Dropship_Block_Adminhtml_Vendor_Statement_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setDestElementId('statement_form');
    }

    protected function _prepareForm()
    {
        $statement = Mage::registry('statement_data');
        $hlp = Mage::helper('udropship');
        $id = $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('statement_form', array(
            'legend'=>$hlp->__('Statement Info')
        ));

        $fieldset->addField('pay_flag', 'hidden', array(
            'name'      => 'pay_flag',
        ));
        
        $fieldset->addField('refresh_flag', 'hidden', array(
            'name'      => 'refresh_flag',
        ));
        
        $fieldset->addField('vendor_id', 'note', array(
            'name'      => 'vendor_id',
            'label'     => $hlp->__('Vendor'),
            'text'      => Mage::getSingleton('udropship/source')->setPath('vendors')->getOptionLabel($statement->getVendorId()),
        ));
        
        $fieldset->addField('statement_id', 'note', array(
            'name'      => 'statement_id',
            'label'     => $hlp->__('Statement ID'),
            'text'      => $statement->getStatementId(),
        ));
        
        $fieldset->addField('po_type', 'select', array(
            'name'      => 'po_type',
            'label'     => $hlp->__('Po Type'),
            'disabled'  => true,
            'options'   => Mage::getSingleton('udropship/source')->setPath('statement_po_type')->toOptionHash(),
        ));

        $fieldset->addField('total_orders', 'note', array(
            'name'      => 'total_orders',
            'label'     => $hlp->__('Number of Orders'),
            'text'      => $statement->getData('total_orders')
        ));
        
        $fieldset->addField('total_payout', 'note', array(
            'name'      => 'total_payout',
            'label'     => $hlp->__('Total Payout'),
            'text'      => Mage::helper('core')->formatPrice($statement->getData('total_payout'))
        ));
        
        if (Mage::helper('udropship')->isUdpayoutActive()) {
            $fieldset->addField('total_paid', 'note', array(
                'name'      => 'total_paid',
                'label'     => $hlp->__('Total Paid'),
                'text'      => Mage::helper('core')->formatPrice($statement->getData('total_paid'))
            ));
            
            $fieldset->addField('total_due', 'note', array(
                'name'      => 'total_due',
                'label'     => $hlp->__('Total Due'),
                'text'      => Mage::helper('core')->formatPrice($statement->getData('total_due'))
            ));
        }
        
        $fieldset->addField('notes', 'textarea', array(
            'name'      => 'notes',
            'label'     => $hlp->__('Notes'),
        ));
        
        $fieldset->addField('adjustment', 'text', array(
            'name'      => 'adjustment',
            'label'     => $hlp->__('Adjustment'),
            'value_filter' => new Varien_Filter_Sprintf('%s', 2),
        ))
        ->setRenderer(
            $this->getLayout()->createBlock('udropship/adminhtml_vendor_helper_renderer_adjustment')->setStatement($statement)
        );
        
        if ($statement) {
            $form->setValues($statement->getData());
        }

        return parent::_prepareForm();
    }

}
