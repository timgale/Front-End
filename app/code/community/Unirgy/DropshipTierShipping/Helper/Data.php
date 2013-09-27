<?php

class Unirgy_DropshipTierShipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function processTiershipRates($vendor, $serialize=false)
    {
        $tiershipRates = $vendor->getData('tiership_rates');
        if ($serialize) {
            if (is_array($tiershipRates)) {
                $tiershipRates = serialize($tiershipRates);
            }
        } else {
            if (is_string($tiershipRates)) {
                $tiershipRates = unserialize($tiershipRates);
            }
            if (!is_array($tiershipRates)) {
                $tiershipRates = array();
            }
        }
        $vendor->setData('tiership_rates', $tiershipRates);
    }
    public function processTiershipSimpleRates($vendor, $serialize=false)
    {
        $tiershipRates = $vendor->getData('tiership_simple_rates');
        if ($serialize) {
            if (is_array($tiershipRates)) {
                $tiershipRates = serialize($tiershipRates);
            }
        } else {
            if (is_string($tiershipRates)) {
                $tiershipRates = unserialize($tiershipRates);
            }
            if (!is_array($tiershipRates)) {
                $tiershipRates = array();
            }
        }
        $vendor->setData('tiership_simple_rates', $tiershipRates);
    }
    public function getVendorTiershipRates($vendor)
    {
        $vendor = Mage::helper('udropship')->getVendor($vendor);
        $value = $vendor->getTiershipRates();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = array();
        }
        return $value;
    }
    public function getVendorTiershipSimpleRates($vendor)
    {
        $vendor = Mage::helper('udropship')->getVendor($vendor);
        $value = $vendor->getTiershipSimpleRates();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = array();
        }
        return $value;
    }
    public function getGlobalTierShipConfig()
    {
        $value = Mage::getStoreConfig('carriers/udtiership/rates');
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }
    public function getGlobalTierShipConfigSimple()
    {
        $value = Mage::getStoreConfig('carriers/udtiership/simple_rates');
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }
    public function getRateId($path)
    {
        return implode(':', $path);
    }
    public function getRateToUse($tierRates, $globalTierRates, $catId, $vscId, $cscId, $field)
    {
        $_curClassId = $this->getRateId(array($catId, $vscId, $cscId));
        return isset($tierRates[$_curClassId]) && isset($tierRates[$_curClassId][$field]) && $tierRates[$_curClassId][$field] !== ''
            ? $tierRates[$_curClassId][$field]
            : (isset($tierRates[$catId]) && isset($tierRates[$catId][$field]) && $tierRates[$catId][$field] !== ''
                ? $tierRates[$catId][$field]
                : (isset($globalTierRates[$_curClassId]) && isset($globalTierRates[$_curClassId][$field]) && $globalTierRates[$_curClassId][$field] !== ''
                    ? $globalTierRates[$_curClassId][$field]
                    : @$globalTierRates[$catId][$field]
        ));
    }

    protected $_topCats;
    public function getTopCategories()
    {
        if (null === $this->_topCats) {
            $cHlp = Mage::helper('udropship/catalog');
            $topCatId = Mage::getStoreConfig('carriers/udtiership/tiered_category_parent');
            $topCat = Mage::getModel('catalog/category')->load($topCatId);
            if (!$topCat->getId()) {
                $topCat = $cHlp->getStoreRootCategory();
            }
            $this->_topCats = $cHlp->getCategoryChildren(
                $topCat
            );
        }
        return $this->_topCats;
    }

    public function getFallbackRateValue($type, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/fallback_rate_%s', $type);
        $cfgVal = Mage::getStoreConfig($cfgKey, $store);
        return $cfgVal;
    }

    public function isMultiplyCalculationMethod($store=null)
    {
        return $this->_isMultiplyCalculationMethod(
            Mage::getStoreConfig('carriers/udtiership/calculation_method', $store)
        );
    }

    protected function _isMultiplyCalculationMethod($calcMethod)
    {
        return in_array($calcMethod, array(
            Unirgy_DropshipTierShipping_Model_Source::CM_MULTIPLY_FIRST,
        ));
    }

    public function isSumCalculationMethod($store=null)
    {
        return $this->_isSumCalculationMethod(
            Mage::getStoreConfig('carriers/udtiership/calculation_method', $store)
        );
    }

    protected function _isSumCalculationMethod($calcMethod)
    {
        return in_array($calcMethod, array(
            Unirgy_DropshipTierShipping_Model_Source::CM_SUM_FIRST_ADDITIONAL,
            Unirgy_DropshipTierShipping_Model_Source::CM_SUM_FIRST,
        ));
    }

    public function isMaxCalculationMethod($store=null)
    {
        return $this->_isMaxCalculationMethod(
            Mage::getStoreConfig('carriers/udtiership/calculation_method', $store)
        );
    }

    protected function _isMaxCalculationMethod($calcMethod)
    {
        return in_array($calcMethod, array(
            Unirgy_DropshipTierShipping_Model_Source::CM_MAX_FIRST_ADDITIONAL,
            Unirgy_DropshipTierShipping_Model_Source::CM_MAX_FIRST,
        ));
    }

    public function getCalculationMethod($store=null)
    {
        return Mage::getStoreConfig('carriers/udtiership/calculation_method', $store);
    }
    public function getFallbackLookupMethod($store=null)
    {
        return Mage::getStoreConfig('carriers/udtiership/fallback_lookup', $store);
    }

    public function useAdditional($store=null)
    {
        return $this->_useAdditional(
            Mage::getStoreConfig('carriers/udtiership/calculation_method', $store)
        );
    }

    protected function _useAdditional($calcMethod)
    {
        return !in_array($calcMethod, array(
            Unirgy_DropshipTierShipping_Model_Source::CM_MULTIPLY_FIRST,
            Unirgy_DropshipTierShipping_Model_Source::CM_SUM_FIRST,
            Unirgy_DropshipTierShipping_Model_Source::CM_MAX_FIRST
        ));
    }

    public function usePercentHandling($store=null)
    {
        return $this->_percentHandling(
            Mage::getStoreConfig('carriers/udtiership/handling_apply_method', $store)
        );
    }

    protected function _percentHandling($handling)
    {
        return in_array($handling, array(
            'percent',
        ));
    }

    public function useFixedHandling($store=null)
    {
        return $this->_fixedHandling(
            Mage::getStoreConfig('carriers/udtiership/handling_apply_method', $store)
        );
    }

    protected function _fixedHandling($handling)
    {
        return in_array($handling, array(
            'fixed',
        ));
    }

    public function useMaxFixedHandling($store=null)
    {
        return $this->_maxFixedHandling(
            Mage::getStoreConfig('carriers/udtiership/handling_apply_method', $store)
        );
    }

    protected function _maxFixedHandling($handling)
    {
        return in_array($handling, array(
            'fixed_max',
        ));
    }

    protected function _maxHandling($handling)
    {
        return in_array($handling, array(
            'fixed_max',
        ));
    }

    public function useHandling($store=null)
    {
        return $this->_useHandling(
            Mage::getStoreConfig('carriers/udtiership/handling_apply_method', $store)
        );
    }

    protected function _useHandling($applyMethod)
    {
        return !$this->isNoneValue($applyMethod);
    }

    public function isShowPerVendorBaseRate($calcType)
    {
        return in_array($calcType, array(
            Unirgy_DropshipTierShipping_Model_Source::CT_BASE_PLUS_ZONE_FIXED,
            Unirgy_DropshipTierShipping_Model_Source::CT_BASE_PLUS_ZONE_PERCENT,
        ));
    }
    public function getCalculationType($type, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/%s_calculation_type', $type);
        $cfgVal = Mage::getStoreConfig($cfgKey, $store);
        return $cfgVal;
    }

    public function isCtCustomPerCustomerZone($type, $store=null)
    {
        return $this->_isCtCustomPerCustomerZone(
            $this->getCalculationType($type, $store)
        );
    }
    protected function _isCtCustomPerCustomerZone($calcType)
    {
        return in_array($calcType, array(
            Unirgy_DropshipTierShipping_Model_Source::CT_SEPARATE,
        ));
    }

    public function isCtPercentPerCustomerZone($type, $store=null)
    {
        return $this->_isCtPercentPerCustomerZone(
            $this->getCalculationType($type, $store)
        );
    }
    protected function _isCtPercentPerCustomerZone($calcType)
    {
        return in_array($calcType, array(
            Unirgy_DropshipTierShipping_Model_Source::CT_BASE_PLUS_ZONE_PERCENT,
        ));
    }
    public function isCtFixedPerCustomerZone($type, $store=null)
    {
        return $this->_isCtFixedPerCustomerZone(
            $this->getCalculationType($type, $store)
        );
    }
    protected function _isCtFixedPerCustomerZone($calcType)
    {
        return in_array($calcType, array(
            Unirgy_DropshipTierShipping_Model_Source::CT_BASE_PLUS_ZONE_FIXED,
        ));
    }

    public function getProductAttribute($key, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/rate_%s_attribute', $key);
        $cfgVal = Mage::getStoreConfig($cfgKey, $store);
        return $cfgVal;
    }

    public function getApplyMethod($method, $store=null)
    {
        $cfgKey = sprintf('carriers/udtiership/%s_apply_method', $method);
        $cfgVal = Mage::getStoreConfig($cfgKey, $store);
        return $cfgVal;
    }

    public  function isApplyMethodPercent($method, $store=null)
    {
        return $this->isPercentValue(
            $this->getApplyMethod($method, $store)
        );
    }

    public  function isApplyMethodNone($method, $store=null)
    {
        return $this->isNoneValue(
            $this->getApplyMethod($method, $store)
        );
    }

    public function isPercentValue($type)
    {
        return in_array($type, array(
            'percent',
        ));
    }
    public function isNoneValue($type)
    {
        return in_array($type, array(
            'none',
        ));
    }

    public function getVendorEditUrl()
    {
        if (Mage::getStoreConfigFlag('carriers/udtiership/use_simple_rates')) {
            return Mage::app()->getStore()->getUrl('udtiership/vendor/simplerates');
        } else {
            return Mage::app()->getStore()->getUrl('udtiership/vendor/rates');
        }
    }

}
