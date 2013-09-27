<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Connector_Server_Amazon_Product_List_Multiple
    extends Ess_M2ePro_Model_Connector_Server_Amazon_Product_Requester
{
    // ########################################

    public function getCommand()
    {
        return array('product','add','entities');
    }

    // ########################################

    protected function getActionIdentifier()
    {
        return 'list';
    }

    protected function getResponserModel()
    {
        return 'Amazon_Product_List_MultipleResponser';
    }

    protected function getListingsLogsCurrentAction()
    {
        return Ess_M2ePro_Model_Listing_Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function prepareListingsProducts($listingsProducts)
    {
        $tempListingsProducts = array();

        foreach ($listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            if (!$listingProduct->isNotListed()) {

                // Parser hack -> Mage::helper('M2ePro')->__('Item is already on Amazon, or not available.');
                $this->addListingsProductsLogsMessage($listingProduct, 'Item is already on Amazon, or not available.',
                                                      Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                                      Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);

                continue;
            }

            if ($listingProduct->isLockedObject(NULL) ||
                $listingProduct->isLockedObject('in_action') ||
                $listingProduct->isLockedObject($this->getActionIdentifier().'_action')) {

                // ->__('Another action is being processed. Try again when the action is completed.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct, 'Another action is being processed. Try again when the action is completed.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );

                continue;
            }

            $addingSku = $listingProduct->getChildObject()->getSku();
            empty($addingSku) && $addingSku = $listingProduct->getChildObject()->getAddingSku();

            if (empty($addingSku)) {

                // Parser hack -> Mage::helper('M2ePro')->__('SKU is not provided. Please, check Listing settings.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct, 'SKU is not provided. Please, check Listing settings.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );

                continue;
            }

            if (strlen($addingSku) > 40) {

                // Parser hack -> Mage::helper('M2ePro')->__('The length of sku must be less than 40 characters.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct, 'The length of sku must be less than 40 characters.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );

                continue;
            }

            $tempListingsProducts[] = $listingProduct;
        }

        $tempListingsProducts2 = $this->checkOnlineSkuExistance($tempListingsProducts);

        $tempListingsProducts = array();

        $this->params['list_types'] = array();

        foreach ($tempListingsProducts2 as $listingProduct) {

            if ($this->params['status_changer'] == Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_USER) {
                $listType = $this->getListTypeChangerUser($listingProduct);
            } else {
                $listType = $this->getListTypeChangerAutomatic($listingProduct);
            }

            if ($listType === false) {
                continue;
            }

            if (!$this->validateConditions($listingProduct)) {
                continue;
            }

            $price = $listingProduct->getChildObject()->getPrice();

            if ($price <= 0) {

            // ->__('The price must be greater than 0. Please, check the Selling Format Template and Product settings.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
                    'The price must be greater than 0. Please, check the Selling Format Template and Product settings.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );

                continue;
            }

            $this->params['list_types'][$listingProduct->getId()] = $listType;
            $tempListingsProducts[] = $listingProduct;
        }

        return $tempListingsProducts;
    }

    // ########################################

    protected function getRequestData()
    {
        $requestData = array();

        $requestData['items'] = array();
        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */

            $productVariations = $listingProduct->getVariations(true);

            foreach ($productVariations as $variation) {
                /** @var $variation Ess_M2ePro_Model_Listing_Product_Variation */
                $variation->deleteInstance();
            }

            $nativeData = Mage::getModel('M2ePro/Amazon_Connector_Product_Helper')
                                         ->getListRequestData($listingProduct,$this->params);

            $sendedData = $nativeData;
            $sendedData['id'] = $listingProduct->getId();

            $this->listingProductRequestsData[$listingProduct->getId()] = array(
                'native_data' => $nativeData,
                'sended_data' => $sendedData
            );

            $requestData['items'][] = $sendedData;
        }

        return $requestData;
    }

    // ########################################

    private function getListTypeChangerUser(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $generalId = $listingProduct->getChildObject()->getGeneralId();

        if (!empty($generalId)) {
            if (!$this->validateGeneralId($generalId)) {
                // ->__('ASIN/ISBN has a wrong format.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
                    'ASIN/ISBN has a wrong format.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }
            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_GENERAL_ID;
        }

        $worldWideId = $listingProduct->getChildObject()->getWorldWideId();

        if (!empty($worldWideId)) {
            if (!$this->validateWorldWideId($worldWideId)) {
                // ->__('UPC/EAN has a wrong format.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
                    'UPC/EAN has a wrong format.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }
            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_WORLDWIDE_ID;
        }

        $templateNewProductId = $listingProduct->getChildObject()->getTemplateNewProductId();

        if (empty($templateNewProductId)) {
            // ->__('ASIN/ISBN or New ASIN template is required.');
            $this->addListingsProductsLogsMessage(
                $listingProduct,
                'ASIN/ISBN or New ASIN template is required.',
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );
            return false;
        }

        $worldWideId = $listingProduct->getChildObject()->getTemplateNewProductSource()->getWorldWideId();
        $isWorldWideIdValid = !empty($worldWideId) && $this->validateWorldWideId($worldWideId);

        if ($isWorldWideIdValid && $this->isWorldWideIdAlreadyExists($worldWideId,$listingProduct)) {
            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_TEMPLATE_NEW_PRODUCT_WORLDWIDE_ID;
        }

        $registeredParameter = $listingProduct->getChildObject()->getTemplateNewProduct()->getRegisteredParameter();

        if (!$registeredParameter && !$isWorldWideIdValid) {
    // ->__('Valid EAN/UPC or Product ID Override is required for adding new ASIN. Please check Template Settings.');
            $this->addListingsProductsLogsMessage(
                $listingProduct,
            'Valid EAN/UPC or Product ID Override is required for adding new ASIN. Please check Template Settings.',
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );
            return false;
        }

        if (!empty($worldWideId) && !$isWorldWideIdValid) {
            // ->__('UPC/EAN has a wrong format. Please check New ASIN Template Settings.');
            $this->addListingsProductsLogsMessage(
                $listingProduct,
                'UPC/EAN has a wrong format. Please check New ASIN Template Settings.',
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );
            return false;
        }

        return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_TEMPLATE_NEW_PRODUCT;
    }

    private function getListTypeChangerAutomatic(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $generalId = $listingProduct->getChildObject()->getGeneralId();

        if (!empty($generalId)) {
            if (!$this->validateGeneralId($generalId)) {
                // ->__('ASIN/ISBN has a wrong format.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
                    'ASIN/ISBN has a wrong format.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }
            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_GENERAL_ID;
        }

        $worldWideId = $listingProduct->getChildObject()->getWorldWideId();

        if (!empty($worldWideId)) {
            if (!$this->validateWorldWideId($worldWideId)) {
                // ->__('UPC/EAN has a wrong format.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
                    'UPC/EAN has a wrong format.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }
            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_WORLDWIDE_ID;
        }

        $templateNewProductId = $listingProduct->getChildObject()->getTemplateNewProductId();

        if (!empty($templateNewProductId)) {

            $worldWideId = $listingProduct->getChildObject()->getTemplateNewProductSource()->getWorldWideId();
            $isWorldWideIdValid = !empty($worldWideId) && $this->validateWorldWideId($worldWideId);

            if ($isWorldWideIdValid && $this->isWorldWideIdAlreadyExists($worldWideId,$listingProduct)) {
                return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_TEMPLATE_NEW_PRODUCT_WORLDWIDE_ID;
            }

            $registeredParameter = $listingProduct->getChildObject()->getTemplateNewProduct()->getRegisteredParameter();

            if (!$registeredParameter && !$isWorldWideIdValid) {
    // ->__('Valid EAN/UPC or Product ID Override is required for adding new ASIN. Please check Template Settings.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
            'Valid EAN/UPC or Product ID Override is required for adding new ASIN. Please check Template Settings.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }

            if (!empty($worldWideId) && !$isWorldWideIdValid) {
                // ->__('UPC/EAN has a wrong format. Please check New ASIN Template Settings.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
                    'UPC/EAN has a wrong format. Please check New ASIN Template Settings.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }

            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_TEMPLATE_NEW_PRODUCT;
        }

        $generalId = $listingProduct->getChildObject()->getAddingGeneralId();

        if (!empty($generalId)) {
            if (!$this->validateGeneralId($generalId)) {
// ->__('ASIN/ISBN has a wrong format. Please check Search Settings for ASIN / ISBN  in Listing -> Channel Settings.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
        'ASIN/ISBN has a wrong format. Please check Search Settings for ASIN / ISBN  in Listing -> Channel Settings.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }
            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_GENERAL_ID;
        }

        $worldWideId = $listingProduct->getChildObject()->getAddingWorldWideId();

        if (!empty($worldWideId)) {
            if (!$this->validateWorldWideId($worldWideId)) {
// ->__('UPC/EAN has a wrong format. Please check Search Settings for ASIN / ISBN  in Listing -> Channel Settings.');
                $this->addListingsProductsLogsMessage(
                    $listingProduct,
            'UPC/EAN has a wrong format. Please check Search Settings for ASIN / ISBN  in Listing -> Channel Settings.',
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                );
                return false;
            }
            return Ess_M2ePro_Model_Amazon_Connector_Product_Helper::LIST_TYPE_WORLDWIDE_ID;
        }

        // ->__('ASIN/ISBN or New ASIN template is required.');
        $this->addListingsProductsLogsMessage(
            $listingProduct,
            'ASIN/ISBN or UPC/EAN or New ASIN template is required.',
            Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
            Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
        );

        return false;
    }

    // ########################################

    private function validateGeneralId($generalId)
    {
        $isAsin = Mage::helper('M2ePro/Component_Amazon')->isASIN($generalId);

        if (!$isAsin) {

            $isIsbn = Mage::helper('M2ePro/Component_Amazon')->isISBN($generalId);

            if (!$isIsbn) {
                return false;
            }
        }

        return true;
    }

    private function validateWorldWideId($worldWideId)
    {
        $isUpc = Mage::helper('M2ePro/Component_Amazon')->isUPC($worldWideId);

        if (!$isUpc) {

            $isEan = Mage::helper('M2ePro/Component_Amazon')->isEAN($worldWideId);

            if (!$isEan) {
                return false;
            }
        }

        return true;
    }

    private function validateConditions(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $addingCondition = $listingProduct->getChildObject()->getCondition();
        $validConditions = $listingProduct->getGeneralTemplate()->getChildObject()->getConditionValues();

        if (empty($addingCondition) || !in_array($addingCondition,$validConditions)) {

            // ->__('Condition is invalid or missed. Please, check Listing and product settings.');
            $this->addListingsProductsLogsMessage(
                $listingProduct, 'Condition is invalid or missed. Please, check Listing and product settings.',
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            return false;
        }

        $addingConditionNote = $listingProduct->getChildObject()->getConditionNote();

        if (is_null($addingConditionNote)) {

            // ->__('Condition note is invalid or missed. Please, check Listing and product settings.');
            $this->addListingsProductsLogsMessage(
                $listingProduct, 'Condition note is invalid or missed. Please, check Listing and product settings.',
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            return false;
        }

        if (!empty($addingConditionNote) && strlen($addingConditionNote) > 2000) {

            // ->__('The length of condition note must be less than 2000 characters.');
            $this->addListingsProductsLogsMessage(
                $listingProduct, 'The length of condition note must be less than 2000 characters.',
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
            );

            return false;
        }

        return true;
    }

    // ########################################

    private function checkOnlineSkuExistance($listingProducts)
    {
        $result = array();
        $listingProductsPacks = array_chunk($listingProducts,20,true);

        foreach ($listingProductsPacks as $listingProductsPack) {

            $skus = array();

            foreach ($listingProductsPack as $key => $listingProduct) {
                $skus[$key] = $listingProduct->getChildObject()->getAddingSku();
            }

            try {

                $countTriedTemp = 0;

                do {

                    $countTriedTemp != 0 && sleep(3);

                    /** @var $dispatcherObject Ess_M2ePro_Model_Connector_Server_Amazon_Dispatcher */
                    $dispatcherObject = Mage::getModel('M2ePro/Amazon_Connector')->getDispatcher();
                    $response = $dispatcherObject->processVirtualAbstract('product','search','asinBySku',
                        array('items' => $skus),'items', $this->marketplace->getId(), $this->account->getId());

                } while (is_null($response) && ++$countTriedTemp <= 3);

                if (is_null($response)) {
                    throw new Exception('Requests are throttled many times.');
                }

            } catch (Exception $exception) {

                Mage::helper('M2ePro/Exception')->process($exception,true);

                foreach ($listingProductsPack as $listingProduct) {

                    $this->addListingsProductsLogsMessage(
                        $listingProduct, Mage::helper('M2ePro')->__($exception->getMessage()),
                        Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                        Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
                    );
                }

                continue;
            }

            foreach($response as $key => $value) {
                if ($value === false || empty($value['asin']) ) {
                    $result[] = $listingProductsPack[$key];
                } else {
                    $this->updateListingProduct($listingProductsPack[$key], $value['asin']);
                }
            }
        }

        return $result;
    }

    private function updateListingProduct(Ess_M2ePro_Model_Listing_Product $listingProduct, $generalId)
    {
        $tempSku = $listingProduct->getChildObject()->getAddingSku();

        $data = array(
            'general_id' => $generalId,
            'is_isbn_general_id' => Ess_M2ePro_Helper_Component_Amazon::isISBN($generalId),
            'sku' => $tempSku,
            'existance_check_status' => Ess_M2ePro_Model_Amazon_Listing_Product::EXISTANCE_CHECK_STATUS_FOUND,
            'status' => Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED
        );

        $listingProduct->addData($data)->save();

        $dataForAdd = array(
            'account_id' => $listingProduct->getListing()->getGeneralTemplate()->getAccountId(),
            'marketplace_id' => $listingProduct->getListing()->getGeneralTemplate()->getMarketplaceId(),
            'sku' => $tempSku,
            'product_id' => $listingProduct->getProductId(),
            'store_id' => $listingProduct->getListing()->getStoreId()
        );

        Mage::getModel('M2ePro/Amazon_Item')->setData($dataForAdd)->save();

        $message = Mage::helper('M2ePro')->__('The product was found in your Amazon inventory and linked by SKU.');

        $this->addListingsProductsLogsMessage(
            $listingProduct, $message,
            Ess_M2ePro_Model_Log_Abstract::TYPE_SUCCESS,
            Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM
        );
    }

    //-----------------------------------------

    private function isWorldWideIdAlreadyExists($worldwideId,Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        if (!$worldwideId) {
            return false;
        }

        $marketplaceObj = $listingProduct->getGeneralTemplate()->getMarketplace();
        $accountObj = $listingProduct->getGeneralTemplate()->getAccount();

        /** @var $dispatcher Ess_M2ePro_Model_Amazon_Search_Dispatcher */
        $dispatcher = Mage::getModel('M2ePro/Amazon_Search_Dispatcher');
        $results = $dispatcher->runManual($listingProduct,$worldwideId,$marketplaceObj,$accountObj);

        if (empty($results)) {
            return false;
        }

        return true;
    }

    // ########################################
}