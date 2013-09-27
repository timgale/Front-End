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

class Unirgy_Dropship_Model_Vendor_Source extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    protected static $_isEnabled;
    protected function _isEnabled()
    {
        if (is_null(self::$_isEnabled)) {
            $module = Mage::getConfig()->getNode('modules/Unirgy_Dropship');
            self::$_isEnabled = $module && $module->is('active');
        }
        return self::$_isEnabled;
    }

    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        $options = $this->toOptionArray();
        if ($withEmpty) {
            array_unshift($options, array('label' => '', 'value' => ''));
        }
        return $options;
    }

    public function toOptionArray()
    {
        $source = $this->_getSource();
        return $source ? $source->toOptionArray() : array();
    }

    public function toOptionHash()
    {
        $source = $this->_getSource();
        return $source ? $source->toOptionHash() : array();
    }

    protected function _getSource()
    {
        if (!$this->_isEnabled()) {
            return false;
        }
        return Mage::getSingleton('udropship/source')->setPath('vendors');
    }

    public function getFlatColums()
    {
        $columns = array();
        $columns[$this->getAttribute()->getAttributeCode()] = array(
            'type'      => 'int',
            'unsigned'  => true,
            'is_null'   => true,
            'default'   => null,
            'extra'     => null
        );

        return $columns;
    }
    
    public function getFlatUpdateSelect($store)
    {
        return Mage::getResourceModel('eav/entity_attribute_option')
            ->getFlatUpdateSelect($this->getAttribute(), $store, false);
    }
}