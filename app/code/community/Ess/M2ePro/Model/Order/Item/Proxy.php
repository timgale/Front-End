<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

abstract class Ess_M2ePro_Model_Order_Item_Proxy
{
    /** @var Ess_M2ePro_Model_Ebay_Order_Item|Ess_M2ePro_Model_Amazon_Order_Item|Ess_M2ePro_Model_Buy_Order_Item */
    protected $item = NULL;

    protected $qty = NULL;

    // ########################################

    public function __construct(Ess_M2ePro_Model_Component_Child_Abstract $item)
    {
        $this->item = $item;
    }

    // ########################################

    public function equals(Ess_M2ePro_Model_Order_Item_Proxy $that)
    {
        if (is_null($this->getProduct()->getId()) || is_null($that->getProduct()->getId())) {
            return false;
        }

        if ($this->getProduct()->getId() != $that->getProduct()->getId()) {
            return false;
        }

        $thisVariations = $this->getLowerCasedVariation();
        $thatVariations = $that->getLowerCasedVariation();

        $thisVariationsKeys = array_keys($thisVariations);
        $thatVariationsKeys = array_keys($thatVariations);

        $thisVariationValues = array_values($thisVariations);
        $thatVariationValues = array_values($thatVariations);

        if (count($thisVariations) == count($thatVariations)
            && count(array_diff($thisVariationsKeys, $thatVariationsKeys)) <= 0
            && count(array_diff($thisVariationValues,$thatVariationValues)) <= 0
        ) {
            return true;
        }

        return false;
    }

    // ########################################

    /**
     * Return product associated with order item
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return $this->item->getParentObject()->getProduct();
    }

    // ########################################

    /**
     * Return variation information
     *
     * 'option_name' => 'option_value'
     *
     * @return array
     */
    public function getVariation()
    {
        return array();
    }

    /**
     * Return both options names and values in lower case
     *
     * @return array
     */
    public function getLowerCasedVariation()
    {
        $variation = $this->getVariation();

        if (empty($variation)) {
            return array();
        }

        $lowerCasedVariation = array();
        foreach ($variation as $optionName => $optionValue) {
            $lowerCasedVariation[trim(strtolower($optionName))] = trim(strtolower($optionValue));
        }

        return array_filter($lowerCasedVariation);
    }

    // ########################################

    /**
     * Return item purchase price
     *
     * @abstract
     * @return float
     */
    abstract public function getPrice();

    /**
     * Return item purchase qty
     *
     * @abstract
     * @return int
     */
    abstract public function getOriginalQty();

    public function setQty($qty)
    {
        if ((int)$qty <= 0) {
            throw new InvalidArgumentException('Qty cannot be less than zero.');
        }

        $this->qty = (int)$qty;

        return $this;
    }

    public function getQty()
    {
        if (!is_null($this->qty)) {
            return $this->qty;
        }
        return $this->getOriginalQty();
    }

    /**
     * Return item tax rate
     *
     * @return float
     */
    public function getTaxRate()
    {
        return $this->item->getParentObject()->getOrder()->getProxy()->getTaxRate();
    }

    /**
     * Check whether item has Tax (not VAT)
     *
     * @return bool
     */
    public function hasTax()
    {
        return $this->item->getParentObject()->getOrder()->getProxy()->hasTax();
    }

    /**
     * Check whether item has VAT (value added tax)
     *
     * @return bool
     */
    public function hasVat()
    {
        return $this->item->getParentObject()->getOrder()->getProxy()->hasVat();
    }

    // ########################################

    public function getGiftMessage()
    {
        return null;
    }

    // ########################################

    public function getAdditionalData()
    {
        return array();
    }

    // ########################################
}