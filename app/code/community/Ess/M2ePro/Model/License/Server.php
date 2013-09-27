<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_License_Server extends Ess_M2ePro_Model_Abstract
{
    const INTERVAL_UPDATE = 3600;

    // ########################################

    public function updateStatus($forceUpdate = false, $interval = NULL)
    {
        $timeLastCheck = $this->getLastCheckTimestamp('status');
        is_null($interval) && $interval = self::INTERVAL_UPDATE;

        if (!(bool)$forceUpdate &&
            $timeLastCheck + (int)$interval > Mage::helper('M2ePro')->getCurrentGmtDate(true)) {
            return;
        }

        Mage::getModel('M2ePro/License_Model')->setDefaults();

        $data = Mage::getModel('M2ePro/Connector_Server_Api_Dispatcher')
                        ->processVirtual('license','get','status');

        if (isset($data['validation']['domain'])) {
            Mage::getModel('M2ePro/License_Model')->setDomain($data['validation']['domain']);
        } else {
            Mage::getModel('M2ePro/License_Model')->setDomain('');
        }

        if (isset($data['validation']['ip'])) {
            Mage::getModel('M2ePro/License_Model')->setIp($data['validation']['ip']);
        } else {
            Mage::getModel('M2ePro/License_Model')->setIp('');
        }

        if (isset($data['validation']['directory'])) {
            Mage::getModel('M2ePro/License_Model')->setDirectory($data['validation']['directory']);
        } else {
            Mage::getModel('M2ePro/License_Model')->setDirectory('');
        }

        if (isset($data['validation']['checks'])) {

            $tempChecks = $data['validation']['checks'];

            if (isset($tempChecks['domain'])) {
                Mage::helper('M2ePro/Module')->getConfig()->setGroupValue('/license/validation/domain/notification/',
                                                                       'mode',(int)$tempChecks['domain']);
            }
            if (isset($tempChecks['ip'])) {
                Mage::helper('M2ePro/Module')->getConfig()->setGroupValue('/license/validation/ip/notification/',
                                                                       'mode',(int)$tempChecks['ip']);
            }
            if (isset($tempChecks['directory'])) {
                Mage::helper('M2ePro/Module')->getConfig()->setGroupValue('/license/validation/directory/notification/',
                                                                       'mode',(int)$tempChecks['directory']);
            }
        }

        foreach (Mage::helper('M2ePro/Component')->getComponents() as $component) {

            if (isset($data['components'][$component]['mode'])) {
                Mage::getModel('M2ePro/License_Model')->setMode($component,$data['components'][$component]['mode']);
            } else {
                Mage::getModel('M2ePro/License_Model')->setMode($component,Ess_M2ePro_Model_License_Model::MODE_NONE);
            }

            if (isset($data['components'][$component]['status'])) {
                Mage::getModel('M2ePro/License_Model')->setStatus($component,$data['components'][$component]['status']);
            } else {
                Mage::getModel('M2ePro/License_Model')->setStatus(
                    $component,Ess_M2ePro_Model_License_Model::STATUS_NONE
                );
            }

            if (isset($data['components'][$component]['expiration_date'])) {
                Mage::getModel('M2ePro/License_Model')->setExpirationDate(
                    $component,$data['components'][$component]['expiration_date']
                );
            } else {
                Mage::getModel('M2ePro/License_Model')->setExpirationDate($component,'');
            }

            if (isset($data['components'][$component]['is_free'])) {
                Mage::getModel('M2ePro/License_Model')->setIsFree(
                    $component,$data['components'][$component]['is_free']
                );
            } else {
                Mage::getModel('M2ePro/License_Model')->setIsFree(
                    $component,$component,Ess_M2ePro_Model_License_Model::IS_FREE_NO
                );
            }
        }

        $this->setLastCheckDateTime('status');
    }

    public function updateLock($forceUpdate = false, $interval = NULL)
    {
        $timeLastCheck = $this->getLastCheckTimestamp('lock');
        is_null($interval) && $interval = self::INTERVAL_UPDATE;

        if (!(bool)$forceUpdate &&
            $timeLastCheck + (int)$interval > Mage::helper('M2ePro')->getCurrentGmtDate(true)) {
            return;
        }

        Mage::getModel('M2ePro/License_Model')->setDefaults();

        $lock = Mage::getModel('M2ePro/Connector_Server_Api_Dispatcher')
                        ->processVirtual('domain','get','lock',
                                         array(),'lock');
        is_null($lock) && $lock = 0;

        Mage::getModel('M2ePro/License_Model')->setLock($lock);

        $this->setLastCheckDateTime('lock');
    }

    public function updateMessages($forceUpdate = false, $interval = NULL)
    {
        $timeLastCheck = $this->getLastCheckTimestamp('messages');
        is_null($interval) && $interval = self::INTERVAL_UPDATE;

        if (!(bool)$forceUpdate &&
            $timeLastCheck + (int)$interval > Mage::helper('M2ePro')->getCurrentGmtDate(true)) {
            return;
        }

        Mage::getModel('M2ePro/License_Model')->setDefaults();

        $messages = Mage::getModel('M2ePro/Connector_Server_Api_Dispatcher')
                        ->processVirtual('messages','get','items',
                                         array(),'messages');
        is_null($messages) && $messages = array();

        Mage::getModel('M2ePro/License_Model')->setMessages($messages);

        $this->setLastCheckDateTime('messages');
    }

    // ########################################

    private function getLastCheckTimestamp($cacheTimeKey)
    {
        $lastCheckDate = Mage::helper('M2ePro/Module')->getConfig()
                        ->getGroupValue('/cache/license/'.strtolower($cacheTimeKey).'/',
                                        'last_check_time');

        if (is_null($lastCheckDate)) {
            return Mage::helper('M2ePro')->getCurrentGmtDate(true) - 3600*24*30;
        }

        return Mage::helper('M2ePro')->getDate($lastCheckDate,true);
    }

    private function setLastCheckDateTime($cacheTimeKey)
    {
        Mage::helper('M2ePro/Module')->getConfig()
            ->setGroupValue('/cache/license/'.strtolower($cacheTimeKey).'/', 'last_check_time',
                            Mage::helper('M2ePro')->getCurrentGmtDate());
    }

    // ########################################
}