<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Observer_Shipment
{
    //####################################

    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        try {

            if (Mage::helper('M2ePro')->getGlobalValue('skip_shipment_observer')) {
                // Not process invoice observer when set such flag
                Mage::helper('M2ePro')->unsetGlobalValue('skip_shipment_observer');
                return;
            }

            /** @var $shipment Mage_Sales_Model_Order_Shipment */
            $shipment = $observer->getEvent()->getShipment();
            $magentoOrder = $shipment->getOrder();

            if (is_null($magentoOrderId = $magentoOrder->getData('entity_id'))) {
                return;
            }

            try {
                /** @var $loadedOrder Ess_M2ePro_Model_Order */
                $loadedOrder = Mage::helper('M2ePro/Component')
                    ->getUnknownObject('Order', $magentoOrderId, 'magento_order_id');
            } catch (Exception $e) {
                return;
            }

            // Prepare tracking information
            // -------------
            $track = $shipment->getTracksCollection()->getLastItem();
            $trackingDetails = array();

            if ($track->getData('number') != '') {
                $trackingDetails = array(
                    'carrier_title'   => trim($track->getData('title')),
                    'carrier_code'    => trim($track->getData('carrier_code')),
                    'tracking_number' => (string)$track->getData('number')
                );
            }
            // -------------

            if (!$loadedOrder->getChildObject()->canUpdateShippingStatus($trackingDetails)) {
                return;
            }

            Mage::getSingleton('M2ePro/Order_Log_Manager')
                ->setInitiator(Ess_M2ePro_Model_Order_Log::INITIATOR_EXTENSION);

            $result = $this->processOrder($loadedOrder, $shipment, $trackingDetails);

            if (is_null(Mage::helper('M2ePro')->getGlobalValue('cron_running'))) {
                $result ? $this->addSessionSuccessMessage($loadedOrder) : $this->addSessionErrorMessage($loadedOrder);
            }

        } catch (Exception $exception) {

            Mage::helper('M2ePro/Exception')->process($exception,true);

        }
    }

    //####################################

    private function processOrder(
        Ess_M2ePro_Model_Order $order,
        Mage_Sales_Model_Order_Shipment $shipment,
        array $trackingDetails = array()
    ) {
        if ($order->isSingle() || !$order->isComponentModeEbay() || count($shipment->getAllItems()) != 1) {
            return $order->getChildObject()->updateShippingStatus($trackingDetails);
        }

        // combined eBay orders only

        $shipmentItems = $shipment->getAllItems();
        $shipmentItem = reset($shipmentItems);

        /** @var $shipmentItem Mage_Sales_Model_Order_Shipment_Item */
        $additionalData = $shipmentItem->getOrderItem()->getAdditionalData();
        $additionalData = is_string($additionalData) ? @unserialize($additionalData) : array();

        if (empty($additionalData['ebay_item_id']) || empty($additionalData['ebay_transaction_id'])) {
            return $order->getChildObject()->updateShippingStatus($trackingDetails);
        }

        $itemToShip = null;
        foreach ($order->getItemsCollection()->getItems() as $item) {
            /** @var $item Ess_M2ePro_Model_Order_Item */
            if ($item->getChildObject()->getItemId() == $additionalData['ebay_item_id']
                || $item->getChildObject()->getTransactionId() == $additionalData['ebay_transaction_id']
            ) {
                $itemToShip = $item;
                break;
            }
        }

        if (is_null($itemToShip)) {
            return $order->getChildObject()->updateShippingStatus($trackingDetails);
        }

        $params = array();
        if (count($trackingDetails) > 0) {
            $params['tracking_number'] = $trackingDetails['tracking_number'];
            $params['carrier_code'] = Mage::helper('M2ePro/Component_Ebay')->getCarrierTitle(
                $trackingDetails['carrier_code'], $trackingDetails['carrier_title']
            );
        }

        /** @var $dispatcher Ess_M2ePro_Model_Connector_Server_Ebay_OrderItem_Dispatcher */
        $dispatcher = Mage::getModel('M2ePro/Connector_Server_Ebay_OrderItem_Dispatcher');
        $action = Ess_M2ePro_Model_Connector_Server_Ebay_OrderItem_Dispatcher::ACTION_UPDATE_STATUS;

        return $dispatcher->process($action, $itemToShip, $params);
    }

    private function addSessionSuccessMessage(Ess_M2ePro_Model_Order $order)
    {
        $message = '';
        switch ($order->getComponentMode()) {
            case Ess_M2ePro_Helper_Component_Ebay::NICK:
                $message = Mage::helper('M2ePro')->__('Shipping Status for eBay Order was updated.');
                break;
            case Ess_M2ePro_Helper_Component_Amazon::NICK:
                $message = Mage::helper('M2ePro')->__('Updating Amazon Order Status to Shipped in Progress...');
                break;
            case Ess_M2ePro_Helper_Component_Buy::NICK:
                $message = Mage::helper('M2ePro')->__('Updating Rakuten.com Order Status to Shipped in Progress...');
                break;

        }

        if ($message) {
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        }
    }

    private function addSessionErrorMessage(Ess_M2ePro_Model_Order $order)
    {
        $url = Mage::helper('adminhtml')->getUrl(
            'M2ePro/adminhtml_'.$order->getComponentMode().'_order/view',
            array('id' => $order->getId())
        );
        $startLink = '<a href="' . $url . '" target="_blank">';
        $endLink = '</a>';

        $message = Mage::helper('M2ePro')->__(
            'Shipping Status for %s Order was not updated. View %sorder log%s for more details.',
            $channel, $startLink, $endLink
        );

        Mage::getSingleton('adminhtml/session')->addError($message);
    }

    //####################################
}