<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Helper_Component_Buy extends Mage_Core_Helper_Abstract
{
    // Parser hack -> Mage::helper('M2ePro')->__('Rakuten.com (Beta)');

    const NICK  = 'buy';
    const TITLE = 'Rakuten.com (Beta)';

    const MARKETPLACE_VIRTUAL_ID = 33;

    // ########################################

    public function isEnabled()
    {
        return (bool)Mage::helper('M2ePro/Module')->getConfig()->getGroupValue('/component/'.self::NICK.'/', 'mode');
    }

    public function isAllowed()
    {
        return (bool)Mage::helper('M2ePro/Module')->getConfig()->getGroupValue('/component/'.self::NICK.'/', 'allowed');
    }

    public function isActive()
    {
        return $this->isEnabled() && $this->isAllowed();
    }

    public function isDefault()
    {
        return Mage::helper('M2ePro/Component')->getDefaultComponent() == self::NICK;
    }

    public function isObject($modelName, $value, $field = NULL)
    {
        $mode = Mage::helper('M2ePro/Component')->getComponentMode($modelName, $value, $field);
        return !is_null($mode) && $mode == self::NICK;
    }

    //-----------------------------------------

    public function getModel($modelName)
    {
        return Mage::helper('M2ePro/Component')->getComponentModel(self::NICK,$modelName);
    }

    public function getObject($modelName, $value, $field = NULL)
    {
        return Mage::helper('M2ePro/Component')->getComponentObject(self::NICK, $modelName, $value, $field);
    }

    public function getCollection($modelName)
    {
        return $this->getModel($modelName)->getCollection();
    }

    // ########################################

    public function getAccount($value, $field = NULL)
    {
        is_null($field) && $field = 'id';

        $cacheKey = self::NICK.'_ACCOUNT_DATA_'.$field.'_'.$value;
        $cacheData = Mage::helper('M2ePro')->getCacheValue($cacheKey);

        if ($cacheData === false) {
            $cacheData = $this->getObject('Account',$value,$field);
            if (is_null($cacheData->getId())) {
                throw new Exception('Such account does not exist!');
            }
            Mage::helper('M2ePro')->setCacheValue($cacheKey,$cacheData,array(self::NICK),60*60*24);
        }

        return $cacheData;
    }

    public function getMarketplace($value = NULL, $field = NULL)
    {
        is_null($field) && $field = 'id';
        is_null($value) && $value = self::MARKETPLACE_VIRTUAL_ID;

        $cacheKey = self::NICK.'_MARKETPLACE_DATA_'.$field.'_'.$value;
        $cacheData = Mage::helper('M2ePro')->getCacheValue($cacheKey);

        if ($cacheData === false) {
            $cacheData = $this->getObject('Marketplace',$value,$field);
            Mage::helper('M2ePro')->setCacheValue($cacheKey,$cacheData,array(self::NICK),60*60*24);
        }

        return $cacheData;
    }

    public function getVirtualMarketplaceId()
    {
        return self::MARKETPLACE_VIRTUAL_ID;
    }

    // ########################################

    public function getItemUrl($productId, $marketplaceId = NULL)
    {
        $marketplaceId = (int)$marketplaceId;
        $marketplaceId <= 0 && $marketplaceId = self::MARKETPLACE_VIRTUAL_ID;

        $domain = $this->getMarketplace($marketplaceId)->getUrl();

        return 'http://'.$domain.'/pr/SellerListings.aspx?sku='.$productId;
    }

    // ########################################

    public function clearAllCache()
    {
        Mage::helper('M2ePro')->removeTagCacheValues(self::NICK);
    }

    public function getCarriers()
    {
        return array(
            1  => 'UPS',
            2  => 'FedEx',
            3  => 'USPS',
            4  => 'DHL',
            5  => 'Other',
            6  => 'UPS-MI',
            7  => 'FedEx SmartPost',
            8  => 'DHL Global Mail',
            9  => 'LTL_A. Duie Pyle',
            10 => 'LTL_ABF',
            11 => 'LTL_AIM Trans',
            12 => 'LTL_AIT',
            13 => 'LTL_CEVA Logistics',
            14 => 'LTL_Conway',
            15 => 'LTL_Ensenda',
            16 => 'LTL_Estes',
            17 => 'LTL_FedEx Freight',
            18 => 'LTL_FedEx LTL Freight East',
            19 => 'LTL_Fox Brother',
            20 => 'LTL_Home Direct',
            21 => 'LTL_Lakeville Motor',
            22 => 'LTL_Manna',
            23 => 'LTL_New England Motor Freight',
            24 => 'LTL_Old Dominion',
            25 => 'LTL_Pilot',
            26 => 'LTL_Pitt Ohio',
            27 => 'LTL_R&L Global',
            28 => 'LTL_S&J Transportation',
            29 => 'LTL_SAIA',
            30 => 'LTL_UPS Freight',
            31 => 'LTL_USF Holland',
            32 => 'LTL_USF Reddaway',
            33 => 'LTL_Vitran Express',
            34 => 'LTL_Watkins Motor Line Freight Standard',
            35 => 'LTL_Wilson Trucking',
            36 => 'LTL_Yellow Freight'
        );
    }

    // ########################################
}