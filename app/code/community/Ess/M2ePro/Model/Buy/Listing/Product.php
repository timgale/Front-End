<?php

/*
 * @copyright  Copyright (c) 2012 by  ESS-UA.
 */

/**
 * @method Ess_M2ePro_Model_Listing_Product getParentObject()
 */
class Ess_M2ePro_Model_Buy_Listing_Product extends Ess_M2ePro_Model_Component_Child_Buy_Abstract
{
    const GENERAL_ID_SEARCH_STATUS_NONE  = 0;
    const GENERAL_ID_SEARCH_STATUS_SET_MANUAL  = 1;
    const GENERAL_ID_SEARCH_STATUS_SET_AUTOMATIC  = 2;
    const GENERAL_ID_SEARCH_STATUS_PROCESSING = 3;

    const EXISTANCE_CHECK_STATUS_NONE = 0;
    const EXISTANCE_CHECK_STATUS_NOT_FOUND = 1;
    const EXISTANCE_CHECK_STATUS_FOUND = 2;

    const TRIED_TO_LIST_YES = 1;
    const TRIED_TO_LIST_NO  = 0;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Buy_Listing_Product');
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Listing
     */
    public function getListing()
    {
        return $this->getParentObject()->getListing();
    }

    /**
     * @return Ess_M2ePro_Model_Magento_Product
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    //-----------------------------------------

    /**
     * @return Ess_M2ePro_Model_Template_General
     */
    public function getGeneralTemplate()
    {
        return $this->getParentObject()->getGeneralTemplate();
    }

    /**
     * @return Ess_M2ePro_Model_Template_SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getParentObject()->getSellingFormatTemplate();
    }

    /**
     * @return Ess_M2ePro_Model_Template_Description
     */
    public function getDescriptionTemplate()
    {
        return $this->getParentObject()->getDescriptionTemplate();
    }

    /**
     * @return Ess_M2ePro_Model_Template_Synchronization
     */
    public function getSynchronizationTemplate()
    {
        return $this->getParentObject()->getSynchronizationTemplate();
    }

    //-----------------------------------------

    /**
     * @return Ess_M2ePro_Model_Buy_Listing
     */
    public function getBuyListing()
    {
        return $this->getListing()->getChildObject();
    }

    //-----------------------------------------

    /**
     * @return Ess_M2ePro_Model_Buy_Template_General
     */
    public function getBuyGeneralTemplate()
    {
        return $this->getGeneralTemplate()->getChildObject();
    }

    /**
     * @return Ess_M2ePro_Model_Buy_Template_SellingFormat
     */
    public function getBuySellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    /**
     * @return Ess_M2ePro_Model_Buy_Template_Description
     */
    public function getBuyDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    /**
     * @return Ess_M2ePro_Model_Buy_Template_Synchronization
     */
    public function getBuySynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    // ########################################

    public function getVariations($asObjects = false, array $filters = array())
    {
        return $this->getParentObject()->getVariations($asObjects,$filters);
    }

    // ########################################

    public function getTemplateNewProductId()
    {
        return $this->getData('template_new_product_id');
    }

    //-----------------------------------------

    public function getSku()
    {
        return $this->getData('sku');
    }

    public function getGeneralId()
    {
        return (int)$this->getData('general_id');
    }

    //-----------------------------------------

    public function getOnlinePrice()
    {
        return (float)$this->getData('online_price');
    }

    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    //-----------------------------------------

    public function getCondition()
    {
        return (int)$this->getData('condition');
    }

    public function getConditionNote()
    {
        return $this->getData('condition_note');
    }

    //-----------------------------------------

    public function getShippingStandardRate()
    {
        return $this->getData('shipping_standard_rate');
    }

    public function getShippingExpeditedMode()
    {
        return (int)$this->getData('shipping_expedited_mode');
    }

    public function getShippingExpeditedRate()
    {
        return $this->getData('shipping_expedited_rate');
    }

    //-----------------------------------------

    public function getStartDate()
    {
        return $this->getData('start_date');
    }

    public function getEndDate()
    {
        return $this->getData('end_date');
    }

    //-----------------------------------------

    public function isTriedToList()
    {
        return $this->getData('tried_to_list') == self::TRIED_TO_LIST_YES;
    }

    // ########################################

    public function getGeneralIdSearchStatus()
    {
        return (int)$this->getData('general_id_search_status');
    }

    public function isGeneralIdSearchStatusNone()
    {
        return $this->getGeneralIdSearchStatus() == self::GENERAL_ID_SEARCH_STATUS_NONE;
    }

    public function isGeneralIdSearchStatusSetManual()
    {
        return $this->getGeneralIdSearchStatus() == self::GENERAL_ID_SEARCH_STATUS_SET_MANUAL;
    }

    public function isGeneralIdSearchStatusSetAutomatic()
    {
        return $this->getGeneralIdSearchStatus() == self::GENERAL_ID_SEARCH_STATUS_SET_AUTOMATIC;
    }

    //-----------------------------------------

    public function getGeneralIdSearchSuggestData()
    {
        $temp = $this->getData('general_id_search_suggest_data');
        return is_null($temp) ? array() : json_decode($temp,true);
    }

    // ########################################

    public function getExistanceCheckStatus()
    {
        return (int)$this->getData('existance_check_status');
    }

    public function isExistanceCheckNone()
    {
        return $this->getExistanceCheckStatus() == self::EXISTANCE_CHECK_STATUS_NONE;
    }

    public function isExistanceCheckNotFound()
    {
        return $this->getExistanceCheckStatus() == self::EXISTANCE_CHECK_STATUS_NOT_FOUND;
    }

    public function isExistanceCheckFound()
    {
        return $this->getExistanceCheckStatus() == self::EXISTANCE_CHECK_STATUS_FOUND;
    }

   // ########################################

    public function getAddingSku()
    {
        $temp = $this->getData('cache_adding_sku');

        if (!empty($temp)) {
            return $temp;
        }

        $result = '';
        $src = $this->getBuyGeneralTemplate()->getSkuSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SKU_MODE_NOT_SET) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SKU_MODE_DEFAULT) {
            $result = $this->getMagentoProduct()->getSku();
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SKU_MODE_PRODUCT_ID) {
            $result = $this->getParentObject()->getProductId();
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SKU_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        is_string($result) && $result = trim($result);
        $this->setData('cache_adding_sku',$result);

        return $result;
    }

    public function getAddingGeneralId()
    {
        $temp = $this->getData('cache_adding_general_id');

        if (!empty($temp)) {
            return $temp;
        }

        $result = '';
        $src = $this->getBuyGeneralTemplate()->getGeneralIdSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::GENERAL_ID_MODE_NOT_SET) {
            $result = NULL;
        } else {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        is_string($result) && $result = trim($result);
        $this->setData('cache_adding_general_id',$result);

        return $result;
    }

    //-----------------------------------------

    public function getAddingCondition()
    {
        $temp = $this->getData('cache_adding_condition');

        if (!empty($temp)) {
            return $temp;
        }

        $result = 1;
        $src = $this->getBuyGeneralTemplate()->getConditionSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::CONDITION_MODE_NOT_SET) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::CONDITION_MODE_DEFAULT) {
            $result = (int)$src['value'];
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::CONDITION_MODE_CUSTOM_ATTRIBUTE) {
            $result = (int)$this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        if (is_int($result)) {
            $result < 0  && $result = 0;
            $result > 10  && $result = 10;
        }

        $this->setData('cache_adding_condition',$result);

        return $result;
    }

    public function getAddingConditionNote()
    {
        $temp = $this->getData('cache_adding_condition_note');

        if (!empty($temp)) {
            return $temp;
        }

        $result = '';
        $src = $this->getBuyGeneralTemplate()->getConditionNoteSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::CONDITION_NOTE_MODE_NOT_SET) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::CONDITION_NOTE_MODE_CUSTOM_VALUE) {
            $result = $src['value'];
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::CONDITION_NOTE_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        is_string($result) && $result = trim($result);
        $this->setData('cache_adding_condition_note',$result);

        return $result;
    }

    // ########################################

    public function getAddingShippingExpeditedMode()
    {
        $temp = $this->getData('cache_adding_shipping_expedited_mode');

        if (!empty($temp)) {
            return $temp;
        }

        $src = $this->getBuyGeneralTemplate()->getShippingExpeditedModeSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_NOT_SET) {
            $result = NULL;
        } else {
            $result = (int)($src['mode'] != Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DISABLED);
        }

        $this->setData('cache_adding_shipping_expedited_mode',$result);

        return $result;
    }

    public function getAddingShippingOneDayMode()
    {
        $temp = $this->getData('cache_adding_shipping_one_day_mode');

        if (!empty($temp)) {
            return $temp;
        }

        $src = $this->getBuyGeneralTemplate()->getShippingOneDayModeSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_NOT_SET) {
            $result = NULL;
        } else {
            $result = (int)($src['mode'] != Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DISABLED);
        }

        $this->setData('cache_adding_shipping_one_day_mode',$result);

        return $result;
    }

    public function getAddingShippingTwoDayMode()
    {
        $temp = $this->getData('cache_adding_shipping_two_day_mode');

        if (!empty($temp)) {
            return $temp;
        }

        $src = $this->getBuyGeneralTemplate()->getShippingTwoDayModeSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_NOT_SET) {
            $result = NULL;
        } else {
            $result = (int)($src['mode'] != Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DISABLED);
        }

        $this->setData('cache_adding_shipping_two_day_mode',$result);

        return $result;
    }

    //-----------------------------------------

    public function getAddingShippingStandardRate()
    {
        $temp = $this->getData('cache_adding_shipping_standard_rate');

        if (!empty($temp)) {
            return $temp;
        }

        $result = 0;
        $src = $this->getBuyGeneralTemplate()->getShippingStandardModeSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_NOT_SET) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DEFAULT) {
            $result = '';
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_FREE) {
            $result = 0;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_VALUE) {
            $result = (float)$src['value'];
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_CUSTOM_ATTRIBUTE) {
            $result = (float)$this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        if (is_int($result) || is_float($result)) {
            $result < 0  && $result = 0;
        }

        $this->setData('cache_adding_shipping_standard_rate',$result);

        return $result;
    }

    public function getAddingShippingExpeditedRate()
    {
        $temp = $this->getData('cache_adding_shipping_expedited_rate');

        if (!empty($temp)) {
            return $temp;
        }

        $result = 0;
        $src = $this->getBuyGeneralTemplate()->getShippingExpeditedModeSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_NOT_SET) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DEFAULT ||
            $src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DISABLED) {
            $result = '';
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_FREE) {
            $result = 0;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_VALUE) {
            $result = (float)$src['value'];
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_CUSTOM_ATTRIBUTE) {
            $result = (float)$this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        if (is_int($result) || is_float($result)) {
            $result < 0  && $result = 0;
        }

        $this->setData('cache_adding_shipping_expedited_rate',$result);

        return $result;
    }

    public function getAddingShippingOneDayRate()
    {
        $temp = $this->getData('cache_adding_shipping_one_day_rate');

        if (!empty($temp)) {
            return $temp;
        }

        $result = 0;
        $src = $this->getBuyGeneralTemplate()->getShippingOneDayModeSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_NOT_SET) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DEFAULT ||
            $src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DISABLED) {
            $result = '';
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_FREE) {
            $result = 0;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_VALUE) {
            $result = (float)$src['value'];
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_CUSTOM_ATTRIBUTE) {
            $result = (float)$this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        if (is_int($result) || is_float($result)) {
            $result < 0  && $result = 0;
        }

        $this->setData('cache_adding_shipping_one_day_rate',$result);

        return $result;
    }

    public function getAddingShippingTwoDayRate()
    {
        $temp = $this->getData('cache_adding_shipping_two_day_rate');

        if (!empty($temp)) {
            return $temp;
        }

        $result = 0;
        $src = $this->getBuyGeneralTemplate()->getShippingTwoDayModeSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_NOT_SET) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DEFAULT ||
            $src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_DISABLED) {
            $result = '';
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_FREE) {
            $result = 0;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_VALUE) {
            $result = (float)$src['value'];
        }

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_General::SHIPPING_MODE_CUSTOM_ATTRIBUTE) {
            $result = (float)$this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        if (is_int($result) || is_float($result)) {
            $result < 0  && $result = 0;
        }

        $this->setData('cache_adding_shipping_two_day_rate',$result);

        return $result;
    }

    // ########################################

    public function getPrice()
    {
        $price = 0;

        $src = $this->getBuySellingFormatTemplate()->getPriceSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_NONE) {
            return $price;
        }

        $price = $this->getBaseProductPrice($src['mode'],$src['attribute']);
        return $this->getSellingFormatTemplate()->parsePrice($price, $src['coefficient']);
    }

    //-----------------------------------------

    public function getBaseProductPrice($mode, $attribute = '')
    {
        $price = 0;

        switch ($mode) {

            case Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_SPECIAL:
                if ($this->getMagentoProduct()->isGroupedType()) {
                    $specialPrice = Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_SPECIAL;
                    $price = $this->getBaseGroupedProductPrice($specialPrice);
                } else {
                    $price = $this->getMagentoProduct()->getSpecialPrice();
                    $price <= 0 && $price = $this->getMagentoProduct()->getPrice();
                }
                break;

            case Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_ATTRIBUTE:
                $price = $this->getMagentoProduct()->getAttributeValue($attribute);
                break;

            case Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_FINAL:
                if ($this->getMagentoProduct()->isGroupedType()) {
                    $price = $this->getBaseGroupedProductPrice(
                        Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_FINAL
                    );
                } else {
                    $customerGroupId = $this->getBuySellingFormatTemplate()->getCustomerGroupId();
                    $price = $this->getMagentoProduct()->getFinalPrice($customerGroupId);
                }
                break;

            default:
            case Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_PRODUCT:
                if ($this->getMagentoProduct()->isGroupedType()) {
                    $productPrice = Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_PRODUCT;
                    $price = $this->getBaseGroupedProductPrice($productPrice);
                } else {
                    $price = $this->getMagentoProduct()->getPrice();
                }
                break;
        }

        $price < 0 && $price = 0;

        return $price;
    }

    protected function getBaseGroupedProductPrice($priceType)
    {
        $price = 0;

        $product = $this->getMagentoProduct()->getProduct();

        foreach ($product->getTypeInstance()->getAssociatedProducts() as $tempProduct) {

            $tempPrice = 0;

            /** @var $tempProduct Ess_M2ePro_Model_Magento_Product */
            $tempProduct = Mage::getModel('M2ePro/Magento_Product')->setProduct($tempProduct);

            switch ($priceType) {
                case Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_PRODUCT:
                    $tempPrice = $tempProduct->getPrice();
                    break;
                case Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_SPECIAL:
                    $tempPrice = $tempProduct->getSpecialPrice();
                    $tempPrice <= 0 && $tempPrice = $tempProduct->getPrice();
                    break;
                case Ess_M2ePro_Model_Buy_Template_SellingFormat::PRICE_FINAL:
                    $tempProduct = Mage::getModel('M2ePro/Magento_Product')
                                            ->setProductId($tempProduct->getProductId())
                                            ->setStoreId($this->getListing()->getStoreId());
                    $customerGroupId = $this->getBuySellingFormatTemplate()->getCustomerGroupId();
                    $tempPrice = $tempProduct->getFinalPrice($customerGroupId);
                    break;
            }

            $tempPrice = (float)$tempPrice;

            if ($tempPrice < $price || $price == 0) {
                $price = $tempPrice;
            }
        }

        $price < 0 && $price = 0;

        return $price;
    }

    // ########################################

    public function getQty($productMode = false)
    {
        $qty = 0;
        $src = $this->getBuySellingFormatTemplate()->getQtySource();

        switch ($src['mode']) {
            case Ess_M2ePro_Model_Buy_Template_SellingFormat::QTY_MODE_SINGLE:
                if ($productMode) {
                    $qty = $this->_getProductGeneralQty();
                } else {
                    $qty = 1;
                }
                break;

            case Ess_M2ePro_Model_Buy_Template_SellingFormat::QTY_MODE_NUMBER:
                if ($productMode) {
                    $qty = $this->_getProductGeneralQty();
                } else {
                    $qty = $src['value'];
                }
                break;

            case Ess_M2ePro_Model_Buy_Template_SellingFormat::QTY_MODE_ATTRIBUTE:
                $qty = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
                break;

            default:
            case Ess_M2ePro_Model_Buy_Template_SellingFormat::QTY_MODE_PRODUCT:
                $qty = $this->_getProductGeneralQty();
                break;
        }

        $qty < 0 && $qty = 0;

        return (int)floor($qty);
    }

    //-----------------------------------------

    protected function _getProductGeneralQty()
    {
        if ($this->getMagentoProduct()->isStrictVariationProduct()) {
            return $this->getParentObject()->_getOnlyVariationProductQty();
        }
        return (int)floor($this->getMagentoProduct()->getQty());
    }

    // ########################################

    public function listAction(array $params = array())
    {
        return $this->processDispatcher(Ess_M2ePro_Model_Buy_Connector_Product_Dispatcher::ACTION_LIST, $params);
    }

    public function relistAction(array $params = array())
    {
        return $this->processDispatcher(Ess_M2ePro_Model_Buy_Connector_Product_Dispatcher::ACTION_RELIST, $params);
    }

    public function reviseAction(array $params = array())
    {
        return $this->processDispatcher(Ess_M2ePro_Model_Buy_Connector_Product_Dispatcher::ACTION_REVISE, $params);
    }

    public function stopAction(array $params = array())
    {
        return $this->processDispatcher(Ess_M2ePro_Model_Buy_Connector_Product_Dispatcher::ACTION_STOP, $params);
    }

    //-----------------------------------------

    protected function processDispatcher($action, array $params = array())
    {
        $dispatcherObject = Mage::getModel('M2ePro/Buy_Connector')->getProductDispatcher();
        return $dispatcherObject->process($action, $this->getId(), $params);
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Buy_Template_NewProduct
    */
    public function getTemplateNewProduct()
    {
        return Mage::getModel('M2ePro/Buy_Template_NewProduct')->loadInstance((int)$this->getTemplateNewProductId());
    }

    // ----------------------------------------

    /**
     * @return Ess_M2ePro_Model_Buy_Template_NewProduct_Source
    */
    public function getTemplateNewProductSource()
    {
        return $this->getTemplateNewProduct()->getSource($this);
    }

    // ########################################
}
