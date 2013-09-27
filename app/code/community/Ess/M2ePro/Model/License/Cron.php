<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_License_Cron
{
    const INTERVAL_UPDATE_MAX = 86400;

    // ########################################

    public function process()
    {
        Mage::getModel('M2ePro/License_Server')->updateStatus(false,$this->getInterval('status'));
        Mage::getModel('M2ePro/License_Server')->updateLock(false,$this->getInterval('lock'));
        Mage::getModel('M2ePro/License_Server')->updateMessages(false,$this->getInterval('messages'));
    }

    // ########################################

    private function getInterval($type)
    {
        $type = strtolower($type);

        $interval = Mage::helper('M2ePro/Module')->getConfig()
                        ->getGroupValue('/cache/license/'.$type.'/cron/', 'interval');

        if (is_null($interval)) {

            $interval = rand(Ess_M2ePro_Model_License_Server::INTERVAL_UPDATE, self::INTERVAL_UPDATE_MAX);

            Mage::helper('M2ePro/Module')->getConfig()
                ->setGroupValue('/cache/license/'.$type.'/cron/', 'interval', $interval);
        }

        return (int)$interval;
    }

    // ########################################
}