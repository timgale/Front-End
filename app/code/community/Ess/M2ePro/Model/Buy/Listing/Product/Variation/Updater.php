<?php

/*
 * @copyright  Copyright (c) 2012 by  ESS-UA.
 */

class Ess_M2ePro_Model_Buy_Listing_Product_Variation_Updater extends
                                                                    Ess_M2ePro_Model_Listing_Product_Variation_Updater
{
    // ########################################

    public function __construct()
    {
        $this->setComponentMode(Ess_M2ePro_Helper_Component_Buy::NICK);
    }

    // ########################################

    public function updateVariations(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        return;
    }

    public function isAddedNewVariationsAttributes(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        return false;
    }

    // ########################################

    protected function validateChannelConditions($sourceVariations, $writeLogs = true)
    {
        return $sourceVariations;
    }

    // ########################################
}