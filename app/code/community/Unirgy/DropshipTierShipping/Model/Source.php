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
class Unirgy_DropshipTierShipping_Model_Source extends Unirgy_Dropship_Model_Source_Abstract
{
    const CM_MAX_FIRST_ADDITIONAL = 1;
    const CM_SUM_FIRST_ADDITIONAL = 2;
    const CM_MULTIPLY_FIRST       = 3;
    const CM_MAX_FIRST = 4;
    const CM_SUM_FIRST = 5;

    const CT_SEPARATE = 1;
    const CT_BASE_PLUS_ZONE_PERCENT = 2;
    const CT_BASE_PLUS_ZONE_FIXED   = 3;

    const FL_VENDOR_BASE = 1;
    const FL_VENDOR_DEFAULT = 2;
    const FL_TIER = 2;

    public function toOptionHash($selector=false)
    {
        $hlp = Mage::helper('udropship');
        $hlpv = Mage::helper('udtiership');

        switch ($this->getPath()) {

        case 'carriers/udtiership/additional_calculation_type':
        case 'carriers/udtiership/cost_calculation_type':
        case 'carriers/udtiership/handling_calculation_type':
            $options = array(
                self::CT_SEPARATE => 'Separate per customer shipclass',
                self::CT_BASE_PLUS_ZONE_PERCENT => 'Base plus percent per customer shipclass',
                self::CT_BASE_PLUS_ZONE_FIXED   => 'Base plus fixed per customer shipclass',
            );
            break;
        case 'carriers/udtiership/calculation_method':
            $options = array(
                self::CM_MAX_FIRST_ADDITIONAL => 'Max first item other additional',
                self::CM_MAX_FIRST => 'Max first item (discard qty)',
                self::CM_SUM_FIRST_ADDITIONAL => 'Sum first item other additional',
                self::CM_SUM_FIRST => 'Sum first item (discard qty)',
                self::CM_MULTIPLY_FIRST       => 'Multiply first item (additional not used)',
            );
            break;

        case 'carriers/udtiership/fallback_lookup':
            $options = array(
                self::FL_VENDOR_BASE => 'Vendor up to BASE',
                self::FL_VENDOR_DEFAULT => 'Vendor up to DEFAULT',
                self::FL_TIER => 'Vendor/Global by tier',
            );
            break;

        case 'carriers/udtiership/handling_apply_method':
               $options = array(
                   'none'      => 'None',
                   'fixed'     => 'Fixed Per Category',
                   'fixed_max' => 'Max Fixed',
                   'percent'   => 'Percent',
               );
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