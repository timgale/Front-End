<?php

class Unirgy_DropshipVendorProduct_Helper_Form extends Mage_Core_Helper_Abstract
{
    public function getSkinUrl($path)
    {
        return Mage::getDesign()->getSkinUrl($path);
    }

    public function getSkinBaseUrl()
    {
        return Mage::getDesign()->getSkinBaseUrl();
    }

    public function isIE6()
    {
        return preg_match('/MSIE [1-6]\./i', Mage::app()->getRequest()->getServer('HTTP_USER_AGENT'));
    }

    public function isIE7()
    {
        return preg_match('/MSIE [1-7]\./i', Mage::app()->getRequest()->getServer('HTTP_USER_AGENT'));
    }
    const MAX_QTY_VALUE = 99999999.9999;
    public function isQty($product)
    {
        return Mage::helper('cataloginventory')->isQty($product->getTypeId());
    }
    public function getStockItemField($field, $values)
    {
        $fieldDef = array();
        switch ($field) {
            case 'is_in_stock':
                $fieldDef = array(
                    'id'       => 'stock_data_is_in_stock',
                    'type'     => 'select',
                    'name'     => 'stock_data[is_in_stock]',
                    'label'    => Mage::helper('cataloginventory')->__('Stock Status'),
                    'options'  => Mage::getSingleton('udprod/source')->setPath('stock_status')->toOptionHash(),
                    'value'    => @$values['stock_data']['is_in_stock']
                );
                break;
            case 'qty':
                $fieldDef = array(
                    'id'       => 'stock_data_qty',
                    'type'     => 'text',
                    'name'     => 'stock_data[qty]',
                    'label'    => Mage::helper('cataloginventory')->__('Stock Qty'),
                    'value'    => @$values['stock_data']['qty']*1
                );
                break;
        }
        return $fieldDef;
    }
    public function getSystemField($field, $values)
    {
        $fieldDef = array();
        switch ($field) {
            case 'product_categories':
                $fieldDef = array(
                    'id'       => 'product_categories',
                    'type'     => 'product_categories',
                    'name'     => 'category_ids',
                    'label'    => Mage::helper('catalog')->__('Categories'),
                    'value'    => @$values['product_categories'],
                );
                break;
            case 'product_websites':
                $fieldDef = array(
                    'id'       => 'product_websites',
                    'type'     => 'multiselect',
                    'name'     => 'website_ids',
                    'label'    => Mage::helper('catalog')->__('Websites'),
                    'value'    => @$values['product_websites'],
                    'values'   => Mage::getSingleton('udprod/source')->setPath('product_websites')->toOptionArray()
                );
                break;
        }
        return $fieldDef;
    }
    public function getAttributeField($attribute)
    {
        $fieldDef = array();
        if ($attribute && (!$attribute->hasIsVisible() || $attribute->getIsVisible())
            && ($inputType = $attribute->getFrontend()->getInputType())
        ) {
            $fieldType      = $inputType;
            //if ($fieldType=='weight') $fieldType='text';
            $rendererClass  = $attribute->getFrontend()->getInputRendererClass();
            if (!empty($rendererClass)) {
                $fieldType  = $inputType . '_' . $attribute->getAttributeCode();
            }
            $fieldDef = array(
                'id'       => $attribute->getAttributeCode(),
                'type'     => $fieldType,
                'name'     => $attribute->getAttributeCode(),
                'label'    => $attribute->getFrontend()->getLabel(),
                'class'    => $attribute->getFrontend()->getClass(),
                'note'     => $attribute->getNote(),
                'input_renderer' => $rendererClass,
                'entity_attribute' => $attribute
            );
            if ($inputType == 'select') {
                $fieldDef['values'] = $attribute->getSource()->getAllOptions(true, true);
            } else if ($inputType == 'multiselect') {
                $fieldDef['values'] = $attribute->getSource()->getAllOptions(false, true);
            } else if ($inputType == 'date') {
                $fieldDef['image'] = $this->getSkinUrl('images/grid-cal.gif');
                $fieldDef['format'] = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
            } else if ($inputType == 'multiline') {
                $fieldDef['line_count'] = $attribute->getMultilineCount();
            }
        }
        return $fieldDef;
    }
    public function getUdmultiField($field, $mvData)
    {
        $fieldDef = array();
        switch ($field) {
            case 'status':
                $fieldDef = array(
                    'id' => 'udmulti_status',
                    'type'     => 'select',
                    'name'     => 'udmulti[status]',
                    'label'    => Mage::helper('udmulti')->__('Status'),
                    'options'   => Mage::getSingleton('udmulti/source')->setPath('vendor_product_status')->toOptionHash(),
                    'value'     => @$mvData['status']
                );
                break;
            case 'state':
                $fieldDef = array(
                    'id' => 'udmulti_state',
                    'type'     => 'select',
                    'name'     => 'udmulti[state]',
                    'label'    => Mage::helper('udmulti')->__('State (Condition)'),
                    'options'  => Mage::getSingleton('udmultiprice/source')->setPath('vendor_product_state')->toOptionHash(),
                    'value'    => @$mvData['state']
                );
                break;
            case 'stock_qty':
                $v = @$mvData['stock_qty'];
                $fieldDef = array(
                    'id' => 'udmulti_stock_qty',
                    'type'     => 'text',
                    'name'     => 'udmulti[stock_qty]',
                    'label'    => Mage::helper('cataloginventory')->__('Stock Qty'),
                    'value'    => null !== $v ? $v*1 : ''
                );
                break;
            case 'state_descr':
                $fieldDef = array(
                    'id' => 'udmulti_state_descr',
                    'type'     => 'text',
                    'name'     => 'udmulti[state_descr]',
                    'label'    => Mage::helper('udmulti')->__('State description'),
                    'value'    => @$mvData['state_descr']
                );
                break;
            case 'vendor_title':
                $fieldDef = array(
                    'id' => 'udmulti_vendor_title',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_title]',
                    'label'    => Mage::helper('udmulti')->__('Vendor Title'),
                    'value'    => @$mvData['vendor_title']
                );
                break;
            case 'vendor_cost':
                $v = @$mvData['vendor_cost'];
                $fieldDef = array(
                    'id' => 'udmulti_vendor_cost',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_cost]',
                    'label'    => Mage::helper('udmulti')->__('Vendor Cost'),
                    'value'    => null !== $v ? $v*1 : ''
                );
                break;
            case 'vendor_price':
                $v = @$mvData['vendor_price'];
                $fieldDef = array(
                    'id' => 'udmulti_vendor_price',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_price]',
                    'label'    => Mage::helper('udmulti')->__('Vendor Price'),
                    'value'    => null !== $v ? $v*1 : ''
                );
                break;
            case 'special_price':
                $v = @$mvData['special_price'];
                $fieldDef = array(
                    'id' => 'udmulti_special_price',
                    'type'     => 'text',
                    'name'     => 'udmulti[special_price]',
                    'label'    => Mage::helper('udmulti')->__('Vendor Special Price'),
                    'value'    => null !== $v ? $v*1 : ''
                );
                break;
            case 'special_from_date':
                $fieldDef = array(
                    'id' => 'udmulti_special_from_date',
                    'type'     => 'date',
                    'image'    => $this->getSkinUrl('images/grid-cal.gif'),
                    'format'   => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
                    'name'     => 'udmulti[special_from_date]',
                    'label'    => Mage::helper('udmulti')->__('Vendor Special From Date'),
                    'value'    => @$mvData['special_from_date']
                );
                break;
            case 'special_to_date':
                $fieldDef = array(
                    'id' => 'udmulti_special_to_date',
                    'type'     => 'date',
                    'image'    => $this->getSkinUrl('images/grid-cal.gif'),
                    'format'   => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
                    'name'     => 'udmulti[special_to_date]',
                    'label'    => Mage::helper('udmulti')->__('Vendor Special From Date'),
                    'value'    => @$mvData['special_to_date']
                );
                break;
            case 'vendor_sku':
                $fieldDef = array(
                    'id' => 'udmulti_vendor_sku',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_sku]',
                    'label'    => Mage::helper('udmulti')->__('Vendor Sku'),
                    'value'    => @$mvData['vendor_sku']
                );
                break;
            case 'freeshipping':
                $fieldDef = array(
                    'id' => 'udmulti_freeshipping',
                    'type'     => 'select',
                    'name'     => 'udmulti[freeshipping]',
                    'label'    => Mage::helper('udmulti')->__('Is Free Shipping'),
                    'options'  => Mage::getSingleton('udropship/source')->setPath('yesno')->toOptionHash(),
                    'value'    => @$mvData['freeshipping']*1
                );
                break;
            case 'shipping_price':
                $fieldDef = array(
                    'id' => 'udmulti_shipping_price',
                    'type'     => 'text',
                    'name'     => 'udmulti[shipping_price]',
                    'label'    => Mage::helper('udmulti')->__('Shipping Price'),
                    'value'    => @$mvData['shipping_price']
                );
                break;
        }
        return $fieldDef;
    }
}