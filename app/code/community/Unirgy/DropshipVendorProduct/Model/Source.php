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
 * @package    Unirgy_DropshipSplit
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

/**
* Currently not in use
*/
class Unirgy_DropshipVendorProduct_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{
    const MEDIA_CFG_SHOW_EXPLICIT=1;
    const MEDIA_CFG_PER_OPTION_HIDDEN=2;
    public function isCfgUploadImagesSimple($store=null)
    {
        return Mage::getStoreConfigFlag('udprod/general/cfg_upload_images_simple', $store);
    }
    public function isMediaCfgPerOptionHidden($store=null)
    {
        return self::MEDIA_CFG_PER_OPTION_HIDDEN==Mage::getStoreConfig('udprod/general/cfg_show_media_gallery', $store);
    }
    public function isMediaCfgShowExplicit($store=null)
    {
        return self::MEDIA_CFG_SHOW_EXPLICIT==Mage::getStoreConfig('udprod/general/cfg_show_media_gallery', $store);
    }
    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udropship');
        $prHlp = Mage::helper('udprod');

        switch ($this->getPath()) {

        case 'udprod/general/cfg_show_media_gallery':
            $options = array(
                0 => $hlp->__('No'),
                1 => $hlp->__('Yes'),
                2 => $hlp->__('Yes and hide per option upload'),
            );
            break;
        case 'udprod/quick_create_layout/cfg_attributes':
            $options = array(
                'one_column'      => $prHlp->__('One Column'),
                'separate_column' => $prHlp->__('Separate Columns'),
            );
            break;
        case 'udprod_unpublish_actions':
        case 'udprod/general/unpublish_actions':
            $options = array(
                'none'               => $prHlp->__('None'),
                'all'                => $prHlp->__('All'),
                'image_added'        => $prHlp->__('Image Added'),
                'image_removed'      => $prHlp->__('Image Removed'),
                'cfg_simple_added'   => $prHlp->__('Configurable Simple Added'),
                'cfg_simple_removed' => $prHlp->__('Configurable Simple Removed'),
                'attribute_changed'  => $prHlp->__('Attribute Value Changed'),
                'stock_changed'      => $prHlp->__('Stock Changed'),
            );
            break;
        case 'udprod_allowed_types':
        case 'udprod/general/allowed_types':
            $at = Mage::getStoreConfig('udprod/general/type_of_product');
            if (is_string($at)) {
                $at = unserialize($at);
            }
            $options = array(
                '*none*' => $prHlp->__('* None *'),
                '*all*'  => $prHlp->__('* All *'),
            );
            if (is_array($at)) {
                foreach ($at as $_at) {
                    $options[$_at['type_of_product']] = $_at['type_of_product'];
                }
            }
            break;
        case 'stock_status':
            $options = array(
                0 => $prHlp->__('Out of stock'),
                1 => $prHlp->__('In stock'),
            );
            break;
        case 'system_status':
            $options = array(
                1 => $prHlp->__('Published'),
                2 => $prHlp->__('Disabled'),
                3 => $prHlp->__('Under Review'),
                4 => $prHlp->__('Fix'),
                5 => $prHlp->__('Discard'),
            );
            break;

        case 'udprod/template_sku/type_of_product':
            $selector = true;
            $_options = Mage::getStoreConfig('udprod/general/type_of_product');
            if (!is_array($_options)) {
                $_options = unserialize($_options);
            }
            $options = array();
            if (!empty($_options) && is_array($_options)) {
                foreach ($_options as $opt) {
                    $_val = $opt['type_of_product'];
                    $options[$_val] = $_val;
                }
            }
            break;

        case 'product_websites':
            $collection = Mage::getModel('core/website')->getResourceCollection();
            $options = array('' => $prHlp->__('* None'));
            foreach ($collection as $w) {
                $options[$w->getId()] = $w->getName();
            }
            break;

        default:
            Mage::throwException($hlp->__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = array(''=>$hlp->__('* Please select')) + $options;
        }

        return $options;
    }
}