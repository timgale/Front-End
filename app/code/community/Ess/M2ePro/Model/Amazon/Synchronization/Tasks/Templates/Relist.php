<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Synchronization_Tasks_Templates_Relist
    extends Ess_M2ePro_Model_Amazon_Synchronization_Tasks
{
    const PERCENTS_START = 35;
    const PERCENTS_END = 50;
    const PERCENTS_INTERVAL = 15;

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
        $this->_productInspector = Mage::getModel('M2ePro/Amazon_Template_Synchronization_ProductInspector',
                                                  $tempParams);
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
        $this->_profiler->addTitle($componentName.'Relist Actions');
        $this->_profiler->addTitle('--------------------------');
        $this->_profiler->addTimePoint(__CLASS__,'Total time');
        $this->_profiler->increaseLeftPadding(5);

        $this->_lockItem->setPercents(self::PERCENTS_START);
        $this->_lockItem->setStatus(Mage::helper('M2ePro')->__('The "Relist" action is started. Please wait...'));
    }

    private function cancelSynch()
    {
        $this->_lockItem->setPercents(self::PERCENTS_END);
        $this->_lockItem->setStatus(Mage::helper('M2ePro')->__('The "Relist" action is finished. Please wait...'));

        $this->_profiler->decreaseLeftPadding(5);
        $this->_profiler->addTitle('--------------------------');
        $this->_profiler->saveTimePoint(__CLASS__);

        $this->_lockItem->activate();
    }

    //####################################

    private function execute()
    {
        // Relist immediatelied
        //---------------------
        $this->executeImmediately();
        //---------------------

        // Relist scheduled
        //---------------------
        $this->executeScheduled();
        //---------------------
    }

    //------------------------------------

    private function executeImmediately()
    {
        $this->immediatelyChangeAmazonStatus();

        $this->_lockItem->setPercents(self::PERCENTS_START + 1*self::PERCENTS_INTERVAL/2);
        $this->_lockItem->activate();

        $this->immediatelyChangedProducts();
    }

    private function executeScheduled()
    {
        $this->_profiler->addTimePoint(__METHOD__,'Synchronization templates with schedule');

        foreach ($this->_synchronizations as &$synchronization) {

            if (!$synchronization['instance']->getChildObject()->isRelistMode()) {
                continue;
            }

            if (!$synchronization['instance']->getChildObject()->isRelistSchedule()) {
                continue;
            }

            if ($synchronization['instance']->getChildObject()->getRelistScheduleType() ==
                Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_SCHEDULE_TYPE_WEEK) {

                if (!$synchronization['instance']->getChildObject()->isRelistScheduleWeekDayNow() ||
                    !$synchronization['instance']->getChildObject()->isRelistScheduleWeekTimeNow()) {
                    continue;
                }
            }

            $this->scheduledListings($synchronization['listings']);
            $this->_lockItem->activate();
        }

        $this->_profiler->saveTimePoint(__METHOD__);
    }

    //####################################

    private function immediatelyChangeAmazonStatus()
    {
        $this->_profiler->addTimePoint(__METHOD__,'Immediately when Amazon status inactive');

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

            if ((int)$changedListingProduct['changed_to_value'] == Ess_M2ePro_Model_Listing_Product::STATUS_LISTED) {
                continue;
            }

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject(
                'Listing_Product',
                $changedListingProduct['id']
            );

            if ($listingProduct->getSynchronizationTemplate()->getChildObject()->isRelistSchedule()) {
                continue;
            }

            if (!$this->_productInspector->isMeetRelistRequirements($listingProduct)) {
                continue;
            }

            $this->_runnerActions->setProduct(
                $listingProduct,
                Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_RELIST,
                array()
            );
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
                                                     Ess_M2ePro_Helper_Component_Amazon::NICK );
        //------------------------------------

        // Filter only needed listings products
        //------------------------------------
        foreach ($changedListingsProducts as $changedListingProduct) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject(
                'Listing_Product',
                $changedListingProduct['id']
            );

            if ($listingProduct->getSynchronizationTemplate()->getChildObject()->isRelistSchedule()) {
                continue;
            }

            if (!$this->_productInspector->isMeetRelistRequirements($listingProduct)) {
                continue;
            }

            $this->_runnerActions->setProduct(
                $listingProduct,
                Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_RELIST,
                array()
            );
        }
        //------------------------------------

        $this->_profiler->saveTimePoint(__METHOD__);
    }

    //------------------------------------

    private function scheduledListings(&$listings)
    {
        $listingsIds = array();

        foreach ($listings as &$listing) {

            /** @var $listing Ess_M2ePro_Model_Listing */

            if (!$listing->isSynchronizationNowRun()) {
                continue;
            }

            $listingsIds[] = (int)$listing->getId();
        }

        if (count($listingsIds) <= 0) {
            return;
        }

        $listingsProductsCollection = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing_Product')
                                                                             ->getCollection();
        $listingsProductsCollection->getSelect()->where(
            '`status` != '.(int)Ess_M2ePro_Model_Listing_Product::STATUS_LISTED
        );
        $listingsProductsCollection->getSelect()->where('`listing_id` IN ('.implode(',',$listingsIds).')');

        $listingsProductsArray = $listingsProductsCollection->toArray();

        if ((int)$listingsProductsArray['totalRecords'] <= 0) {
            return;
        }

        foreach ($listingsProductsArray['items'] as $listingProductArray) {

            /** @var $listingProduct Ess_M2ePro_Model_Listing_Product */
            $listingProduct = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Product',
                                                                                 $listingProductArray['id']);

            if ($listingProduct->getSynchronizationTemplate()->getChildObject()->getRelistScheduleType() ==
                Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_SCHEDULE_TYPE_THROUGH &&
                !$this->isScheduleThroughNow($listingProduct)) {
                continue;
            }

            if (!$this->_productInspector->isMeetRelistRequirements($listingProduct)) {
                continue;
            }

            $this->_runnerActions->setProduct($listingProduct,
                                              Ess_M2ePro_Model_Amazon_Connector_Product_Dispatcher::ACTION_RELIST,
                                              array());
        }
    }

    //####################################

    private function isScheduleThroughNow(Ess_M2ePro_Model_Listing_Product $listingProduct)
    {
        $dateEnd = $listingProduct->getChildObject()->getEndDate();
        if (is_null($dateEnd) || $dateEnd == '') {
            return false;
        }

        $interval = 60;
        $metric = $listingProduct->getSynchronizationTemplate()->getChildObject()->getRelistScheduleThroughMetric();
        $value = (int)$listingProduct->getSynchronizationTemplate()->getChildObject()->getRelistScheduleThroughValue();

        if ($metric == Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_SCHEDULE_THROUGH_METRIC_DAYS) {
            $interval = 60*60*24;
        }
        if ($metric == Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_SCHEDULE_THROUGH_METRIC_HOURS) {
            $interval = 60*60;
        }
        if ($metric == Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_SCHEDULE_THROUGH_METRIC_MINUTES) {
            $interval = 60;
        }

        $interval = $interval*$value;
        $dateEnd = strtotime($dateEnd);

        if (Mage::helper('M2ePro')->getCurrentGmtDate(true) < $dateEnd + $interval) {
            return false;
        }

        return true;
    }

    //####################################
}