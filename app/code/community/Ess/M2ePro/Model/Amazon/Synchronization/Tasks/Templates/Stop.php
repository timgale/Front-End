<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Synchronization_Tasks_Templates_Stop
    extends Ess_M2ePro_Model_Amazon_Synchronization_Tasks
{
    const PERCENTS_START = 50;
    const PERCENTS_END = 60;
    const PERCENTS_INTERVAL = 10;

    private $_synchronizations = array();

    /**
     * @var Ess_M2ePro_Model_Amazon_Template_Synchronization_ProductInspector
     */
    private $_productInspector = NULL;

    //####################################

    public function __construct()
    {
        parent::__construct();

        $this->_synchronizations = Mage::helper('M2ePro')->getGlobalValue('synchTemplatesArray');

        $tempParams = array('runner_actions'=>$this->_runnerActions);
        $this->_productInspector = Mage::getModel(
            'M2ePro/Amazon_Template_Synchronization_ProductInspector',
            $tempParams
        );
    }

    //####################################

    public function process()
    {
        // PREPARE SYNCH
        //---------------------------
        $this->prepareSynch();
        //---------------------------

        // RUN SYNCH
        //---------------------------
        $this->execute();
        //---------------------------

        // CANCEL SYNCH
        //---------------------------
        $this->cancelSynch();
        //---------------------------
    }

    //####################################

    private function prepareSynch()
    {
        $this->_lockItem->activate();

        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) > 1) {
            $componentName = Ess_M2ePro_Helper_Component_Amazon::TITLE.' ';
        } else {
            $componentName = '';
        }

        $this->_profiler->addEol();
        $this->_profiler->addTitle($componentName.'Stop Actions');
        $this->_profiler->addTitle('--------------------------');
        $this->_profiler->addTimePoint(__CLASS__,'Total time');
        $this->_profiler->increaseLeftPadding(5);

        $this->_lockItem->setPercents(self::PERCENTS_START);
        $this->_lockItem->setStatus(Mage::helper('M2ePro')->__('The "Stop" action is started. Please wait...'));
    }

    private function cancelSynch()
    {
        $this->_lockItem->setPercents(self::PERCENTS_END);
        $this->_lockItem->setStatus(Mage::helper('M2ePro')->__('The "Stop" action is finished. Please wait...'));

        $this->_profiler->decreaseLeftPadding(5);
        $this->_profiler->addTitle('--------------------------');
        $this->_profiler->saveTimePoint(__CLASS__);

        $this->_lockItem->activate();
    }

    //####################################

    private function execute()
    {
        $this->immediatelyChangeAmazonStatus();

        $this->_lockItem->setPercents(self::PERCENTS_START + 1*self::PERCENTS_INTERVAL/2);
        $this->_lockItem->activate();

        $this->immediatelyChangedProducts();
    }

    //####################################

    private function immediatelyChangeAmazonStatus()
    {
        $this->_profiler->addTimePoint(__METHOD__,'Immediately when Amazon status active');

        // Get changed listings products
        //------------------------------------
        $changedListingsProducts = Mage::getModel('M2ePro/Listing_Product')
            ->getChangedItemsByAttributes(array('amazon_listing_product_status'),
                                          Ess_M2ePro_Helper_Component_Amazon::NICK);
        //------------------------------------

        // Filter only needed listings products
        //------------------------------------
        foreach ($changedListingsProducts as $changedListingProduct) {

            $tempNewValue = explode('_status_',$changedListingProduct['changed_to_value']);

            if (!is_array($tempNewValue) || count($tempNewValue) != 2) {
                continue;
            }

            $tempListingProductId = (int)str_replace('listing_product_id_','',$tempNewValue[0]);

            if ($tempListingProductId != (int)$changedListingProduct['id']) {
                continue;
            }

            $changedListingProduct['changed_to_value'] = (int)$tempNewValue[1];

            if ((int)$changedListingProduct['changed_to_value'] != Ess_M2ePro_Model_Listing_Product::STATUS_LISTED) {
                continue;
            }

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject(
                'Listing_Product',
                $changedListingProduct['id']
            );

            if (!$this->_productInspector->isMeetStopRequirements($listingProduct)) {
                continue;
            }

            $this->_runnerActions
                 ->setProduct($listingProduct,
                              Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_STOP,
                              array());
        }
        //------------------------------------

        $this->_profiler->saveTimePoint(__METHOD__);
    }

    private function immediatelyChangedProducts()
    {
        $this->_profiler->addTimePoint(__METHOD__,'Immediately when product was changed');

        // Get changed listings products
        //------------------------------------
        $changedListingsProducts = Mage::getModel('M2ePro/Listing_Product')
            ->getChangedItemsByAttributesWithOptions(array(Ess_M2ePro_Model_ProductChange::UPDATE_ATTRIBUTE_CODE),
                                                     Ess_M2ePro_Helper_Component_Amazon::NICK);
        //------------------------------------

        // Filter only needed listings products
        //------------------------------------
        foreach ($changedListingsProducts as $changedListingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct = Mage::helper('M2ePro/Component_Amazon')
                ->getObject('Listing_Product',$changedListingProduct['id']);

            if (!$this->_productInspector->isMeetStopRequirements($listingProduct)) {
                continue;
            }

            $this->_runnerActions
                 ->setProduct($listingProduct,
                              Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_STOP,
                              array());
        }
        //------------------------------------

        $this->_profiler->saveTimePoint(__METHOD__);
    }

    //####################################
}