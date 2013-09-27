<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Controller_Adminhtml_MainController extends Ess_M2ePro_Controller_Adminhtml_BaseController
{
    //#############################################

    public function preDispatch()
    {
        parent::preDispatch();

        if ($this->getRequest()->isXmlHttpRequest() &&
            !Mage::getSingleton('admin/session')->isLoggedIn()) {

            exit(json_encode( array(
                'ajaxExpired' => 1,
                'ajaxRedirect' => $this->_getRefererUrl()
            )));
        }

        if ($this->getRequest()->isGet() &&
            !$this->getRequest()->isPost() &&
            !$this->getRequest()->isXmlHttpRequest() &&
            Mage::helper('M2ePro/Wizard')->isInstallationFinished()) {

            try {
                Mage::getModel('M2ePro/License_Server')->updateStatus(false);
                Mage::getModel('M2ePro/License_Server')->updateLock(false);
                Mage::getModel('M2ePro/License_Server')->updateMessages(false);
            } catch (Exception $exception) {}
        }

        return $this;
    }

    public function dispatch($action)
    {
        try {

            $this->getRequest()->isGet() &&
                !$this->getRequest()->isPost() &&
                !$this->getRequest()->isXmlHttpRequest() &&
                $this->updateBackupConnectionData();

            Mage::helper('M2ePro/Exception')->setFatalErrorHandler();
            return parent::dispatch($action);

        } catch (Exception $exception) {

            if ($this->getRequest()->getControllerName() == 'adminhtml_support') {
                exit($exception->getMessage());
            } else {

                if (Mage::helper('M2ePro/Server')->isDeveloper()) {
                    throw $exception;
                } else {

                    Mage::helper('M2ePro/Exception')->process($exception,true);

                    if (($this->getRequest()->isGet() || $this->getRequest()->isPost()) &&
                        !$this->getRequest()->isXmlHttpRequest()) {

                        $this->_getSession()->addError(Mage::helper('M2ePro/Exception')->getUserMessage($exception));
                        $this->_redirect('*/adminhtml_support/index', array('error' => 'true'));
                    } else {
                        exit($exception->getMessage());
                    }
                }
            }
        }
    }

    //#############################################

    public function loadLayout($ids=null, $generateBlocks=true, $generateXml=true)
    {
        if ($this->getRequest()->isGet() &&
            !$this->getRequest()->isPost() &&
            !$this->getRequest()->isXmlHttpRequest()) {

            $lockNotification = $this->addLockNotifications();

            /** @var $wizardHelper Ess_M2ePro_Helper_Wizard */
            $wizardHelper = Mage::helper('M2ePro/Wizard');

            $lockNotification ||
                !Mage::helper('M2ePro/Wizard')->isInstallationFinished() ||
                $this->addLicenseActivationNotifications() ||
                $this->addLicenseValidationFailNotifications() ||
                $this->addLicenseModesNotifications() ||
                $this->addLicenseStatusesNotifications() ||
                $this->addLicenseExpirationDatesNotifications() ||
                $this->addLicenseTrialNotifications() ||
                $this->addLicensePreExpirationDateNotifications();

            $this->addServerNotifications();
            $this->addBrowserNotifications();

            $lockNotification ||
                !$wizardHelper->isInstallationFinished() ||
                Mage::helper('M2ePro/Server')->isDeveloper() ||
                $this->addCronNotifications();

            $lockNotification ||
                !$wizardHelper->isInstallationFinished() ||
                Mage::helper('M2ePro/Server')->isDeveloper() ||
                $this->addCronErrors();

            $lockNotification ||
                !$wizardHelper->isFinished('ebay') ||
                $this->addFeedbackNotifications();

            $lockNotification ||
                !$wizardHelper->isFinished('ebay') ||
                $this->addMyMessageNotifications();

        }

        is_array($ids) ? $ids[] = 'm2epro' : $ids = array('default','m2epro');
        return parent::loadLayout($ids, $generateBlocks, $generateXml);
    }

    protected function _addContent(Mage_Core_Block_Abstract $block)
    {
        if ($this->getRequest()->isGet() &&
            !$this->getRequest()->isPost() &&
            !$this->getRequest()->isXmlHttpRequest() &&
            Mage::getModel('M2ePro/License_Model')->isLock()) {
            return $this;
        }

        $blockGeneral = $this->getLayout()->createBlock('M2ePro/adminhtml_general');
        $this->getLayout()->getBlock('content')->append($blockGeneral);

        $this->addWizardUpgradeNotification();

        return parent::_addContent($block);
    }

    //#############################################

    private function addLockNotifications()
    {
        if (Mage::getModel('M2ePro/License_Model')->isLock()) {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('M2E Pro module is locked because of security reason. Please contact us.')
            );
            return true;
        }
        return false;
    }

    private function addServerNotifications()
    {
        $messages = Mage::getModel('M2ePro/License_Model')->getMessages();

        foreach ($messages as $message) {

            if (isset($message['text']) && isset($message['type']) && $message['text'] != '') {

                switch ($message['type']) {
                    case Ess_M2ePro_Model_License_Model::MESSAGE_TYPE_ERROR:
                        $this->_getSession()->addError(Mage::helper('M2ePro')->__($message['text']));
                        break;
                    case Ess_M2ePro_Model_License_Model::MESSAGE_TYPE_WARNING:
                        $this->_getSession()->addWarning(Mage::helper('M2ePro')->__($message['text']));
                        break;
                    case Ess_M2ePro_Model_License_Model::MESSAGE_TYPE_SUCCESS:
                        $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__($message['text']));
                        break;
                    case Ess_M2ePro_Model_License_Model::MESSAGE_TYPE_NOTICE:
                    default:
                        $this->_getSession()->addNotice(Mage::helper('M2ePro')->__($message['text']));
                        break;
                }
            }

        }
    }

    private function addBrowserNotifications()
    {
        // Check MS Internet Explorer 6
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') !== false) {
            $warning = 'Magento and M2E Pro has Internet Explorer 7 as minimal browser requirement. ';
            $warning .= 'Please upgrade your browser.';
            $this->_getSession()->addWarning(Mage::helper('M2ePro')->__($warning));
            return true;
        }
        return false;
    }

    // --------------------------------------------

    private function addLicenseActivationNotifications()
    {
        if (!Mage::getModel('M2ePro/License_Model')->getKey() ||
            !Mage::getModel('M2ePro/License_Model')->getDomain() ||
            !Mage::getModel('M2ePro/License_Model')->getIp() ||
            !Mage::getModel('M2ePro/License_Model')->getDirectory()) {

            $startLink = '<a href="'.$this->getUrl('*/adminhtml_license/index').'" target="_blank">';
            $endLink = '</a>';

            $message = Mage::helper('M2ePro')->__(
                'M2E Pro module requires activation. Go to the %slicense page%s.', $startLink, $endLink
            );

            $this->_getSession()->addError($message);
            return true;
        }

        return false;
    }

    private function addLicenseValidationFailNotifications()
    {
        // MAGENTO GO UGLY HACK
        //#################################
        if (Mage::helper('M2ePro/Magento')->isGoEdition()) {
            return false;
        }
        //#################################

        $domainNotify = (bool)(int)Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
            '/license/validation/domain/notification/', 'mode'
        );

        if ($domainNotify &&
            Mage::getModel('M2ePro/License_Model')->getDomain() != Mage::helper('M2ePro/Server')->getDomain()) {

            $startLink = '<a href="'.$this->getUrl("*/adminhtml_license/index").'" target="_blank">';
            $endLink = '</a>';

            $message = 'M2E Pro license key validation is failed for this domain. ';
            $message .= 'Go to the %slicense page%s.';
            $message = Mage::helper('M2ePro')->__($message, $startLink, $endLink);

            $this->_getSession()->addError($message);
            return true;
        }

        $ipNotify = (bool)(int)Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
            '/license/validation/ip/notification/', 'mode'
        );

        if ($ipNotify && Mage::getModel('M2ePro/License_Model')->getIp() != Mage::helper('M2ePro/Server')->getIp()) {

            $startLink = '<a href="'.$this->getUrl('*/adminhtml_license/index').'" target="_blank">';
            $endLink = '</a>';

            $message = 'M2E Pro license key validation is failed for this IP. ';
            $message .= 'Go to the %slicense page%s.';
            $message = Mage::helper('M2ePro')->__($message, $startLink, $endLink);

            $this->_getSession()->addError($message);
            return true;
        }

        $directoryNotify = (bool)(int)Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
            '/license/validation/directory/notification/', 'mode'
        );

        $licenseDirectory = Mage::getModel('M2ePro/License_Model')->getDirectory();

        if ($directoryNotify &&
            $licenseDirectory != Mage::helper('M2ePro/Server')->getBaseDirectory()) {

            $startLink = '<a href="'.$this->getUrl("*/adminhtml_license/index").'" target="_blank">';
            $endLink = '</a>';

            $message = 'M2E Pro license key validation is failed for this base directory. ';
            $message .= 'Go to the %slicense page%s.';
            $message = Mage::helper('M2ePro')->__($message, $startLink, $endLink);

            $this->_getSession()->addError($message);
            return true;
        }

        return false;
    }

    private function addLicenseModesNotifications()
    {
        $hasMessage = false;

        foreach (Mage::helper('M2ePro/Component')->getActiveComponents() as $component) {

            if (Mage::getModel('M2ePro/License_Model')->isNoneMode($component)) {

                $startLink = '<a href="'.$this->getUrl('*/adminhtml_license/index').'" target="_blank">';
                $endLink = '</a>';

                $message = 'M2E Pro module requires activation for "%s" component. ';
                $message .= 'Go to the %slicense page%s.';
                $message = Mage::helper('M2ePro')->__($message, ucwords($component), $startLink, $endLink);

                $this->_getSession()->addError($message);
                $hasMessage = true;
            }
        }

        return $hasMessage;
    }

    private function addLicenseStatusesNotifications()
    {
        $hasMessage = false;

        foreach (Mage::helper('M2ePro/Component')->getActiveComponents() as $component) {

            if (Mage::getModel('M2ePro/License_Model')->isSuspendedStatus($component)) {

                $startLink = '<a href="'.$this->getUrl('*/adminhtml_license/index').'" target="_blank">';
                $endLink = '</a>';

                $message = 'M2E Pro module license suspended for "%s" component. ';
                $message .= 'Go to the %slicense page%s.';
                $message = Mage::helper('M2ePro')->__($message, ucwords($component), $startLink, $endLink);

                $this->_getSession()->addError($message);
                $hasMessage = true;
            }

            if (Mage::getModel('M2ePro/License_Model')->isClosedStatus($component)) {

                $message = 'M2E Pro module license closed for "%s" component. ';
                $message .= 'Go to the %slicense page%s.';

                $startLink = '<a href="'.$this->getUrl('*/adminhtml_license/index').'" target="_blank">';
                $endLink = '</a>';

                $message = Mage::helper('M2ePro')->__($message, ucwords($component), $startLink, $endLink);

                $this->_getSession()->addError($message);
                $hasMessage = true;
            }
        }

        return $hasMessage;
    }

    private function addLicenseExpirationDatesNotifications()
    {
        $hasMessage = false;

        foreach (Mage::helper('M2ePro/Component')->getActiveComponents() as $component) {

            if (Mage::getModel('M2ePro/License_Model')->isExpirationDate($component)) {

                $startLink = '<a href="'.$this->getUrl('*/adminhtml_license/index').'" target="_blank">';
                $endLink = '</a>';

                $message = 'M2E Pro module license has expired for "%s" component. ';
                $message .= 'Go to the %slicense page%s.';
                $message = Mage::helper('M2ePro')->__($message, ucwords($component), $startLink, $endLink);

                $this->_getSession()->addError($message);
                $hasMessage = true;
            }
        }

        return $hasMessage;
    }

    private function addLicenseTrialNotifications()
    {
        $hasMessage = false;

        foreach (Mage::helper('M2ePro/Component')->getActiveComponents() as $component) {

            if (Mage::getModel('M2ePro/License_Model')->isTrialMode($component)) {

                $expirationDate = Mage::getModel('M2ePro/License_Model')->getTextExpirationDate($component);

                $message = 'M2E Pro module is running under Trial License for "%s" component, ';
                $message .= 'that will expire on %s.';
                $message = Mage::helper('M2ePro')->__($message, ucwords($component), $expirationDate);

                $this->_getSession()->addWarning($message);

                $hasMessage = true;
            }
        }

        return $hasMessage;
    }

    private function addLicensePreExpirationDateNotifications()
    {
        $hasMessage = false;

        foreach (Mage::helper('M2ePro/Component')->getActiveComponents() as $component) {

            if (Mage::getModel('M2ePro/License_Model')->getIntervalBeforeExpirationDate($component) <= 60*60*24*3) {

                $startLink = '<a href="'.$this->getUrl("*/adminhtml_license/index").'" target="_blank">';
                $endLink = '</a>';
                $expirationDate = Mage::getModel('M2ePro/License_Model')->getTextExpirationDate($component);

                $message = 'M2E Pro module license will expire on %s for "%s" component. ';
                $message .= 'Go to the %slicense page%s.';
                $message = Mage::helper('M2ePro')->__($message, $expirationDate, ucwords($component),
                                                      $startLink, $endLink);

                $this->_getSession()->addWarning($message);

                $hasMessage = true;
            }
        }

        return $hasMessage;
    }

    // --------------------------------------------

    private function addCronNotifications()
    {
        if (Mage::getModel('M2ePro/Cron')->isShowNotification()) {

            $allowedInactiveHours = (int)Mage::helper('M2ePro/Module')->getConfig()->getGroupValue(
                '/cron/notification/', 'max_inactive_hours'
            );

            $url = 'http://support.m2epro.com/knowledgebase/articles/42054-how-to-set-up-cron-job-for-m2e-pro-';
            $startLinkArticle = '<a href="'.$url.'" target="_blank">';

            $url = $this->getUrl('*/adminhtml_about/index').'#magento_block_about_cron_info';
            $startLinkHere = '<a href="'.$url.'" target="_blank">';

            $endLink = '</a>';

            $message = 'Attention! Last eBay AUTOMATIC synchronization was performed by cron ';
            $message .= 'more than %s hours ago. You should set up cron job, otherwise no automatic synchronization ';
            $message .= 'will be performed. <br/>You can check this %sarticle%s to get how to set cron job. ';
            $message .= 'The command for cron job for your server environment can be found %shere%s.';
            $message = Mage::helper('M2ePro')->__(
                $message, $allowedInactiveHours, $startLinkArticle, $endLink, $startLinkHere, $endLink
            );

            $this->_getSession()->addNotice($message);
            return true;
        }

        return false;
    }

    private function addCronErrors()
    {
        if (Mage::getModel('M2ePro/Cron')->isShowError()) {

            $url = 'http://support.m2epro.com/knowledgebase/articles/';
            $url .= '162927-why-cron-job-is-required-for-amazon-and-rakuten-co';
            $startLinkArticle = '<a href="'.$url.'" target="_blank">';

            $url  = $this->getUrl('*/adminhtml_about/index').'#magento_block_about_cron_info';
            $startLinkHere = '<a href="'.$url.'" target="_blank">';

            $endLink = '</a>';

            $message = 'Attention! The Cron job is not running at the moment. ';
            $message .= 'The Amazon/Rakuten.com Integration DOES NOT WORK ';
            $message .= 'without automatic task scheduled by cron job. <br/>You can check this %sarticle%s ';
            $message .= 'to get better idea why cron job is mandatory. The command for cron job ';
            $message .= 'for your server environment can be found %shere%s.';
            $message = Mage::helper('M2ePro')->__($message, $startLinkArticle, $endLink, $startLinkHere, $endLink);

            $this->_getSession()->addError($message);
            return true;
        }

        return false;
    }

    private function addFeedbackNotifications()
    {
        if (Mage::getModel('M2ePro/Ebay_Feedback')->haveNew(true)) {

            $startLink = '<a href="'.$this->getUrl('*/adminhtml_ebay_feedback/index').'" target="_blank">';
            $endLink = '</a>';

            // ->__('New buyer negative feedback(s) was received. Go to the %sfeedbacks page%s.')
            $message = 'New buyer negative feedback(s) was received. Go to the %sfeedbacks page%s.';
            $message = Mage::helper('M2ePro')->__($message, $startLink, $endLink);

            $this->_getSession()->addNotice($message);
            return true;
        }

        return false;
    }

    private function addMyMessageNotifications()
    {
        if (Mage::getModel('M2ePro/Ebay_Message')->haveNew()) {

            $startLink = '<a href="'.$this->getUrl('*/adminhtml_ebay_message/index').'" target="_blank">';
            $endLink = '</a>';

            // ->__('New buyer message(s) was received. Go to the %smessages page%s.')
            $message = 'New buyer message(s) was received. Go to the %smessages page%s.';
            $message = Mage::helper('M2ePro')->__($message, $startLink, $endLink);

            $this->_getSession()->addNotice($message);
            return true;
        }

        return false;
    }

    // --------------------------------------------

    private function addWizardUpgradeNotification()
    {
        /** @var $wizardHelper Ess_M2ePro_Helper_Wizard */
        $wizardHelper = Mage::helper('M2ePro/Wizard');

        $activeWizard = $wizardHelper->getActiveWizard();

        if (!$activeWizard) {
            return;
        }

        $wizardEdition = $wizardHelper->getEdition();
        $activeWizardNick = $wizardHelper->getNick($activeWizard);

        if ($this->getRequest()->getParam('hide_upgrade_notification') == 'yes' ||
            $this->getRequest()->getControllerName() == 'adminhtml_'.$wizardEdition.'_'.$activeWizardNick) {
            return;
        }

        $wizardHelper->addWizardHandlerJs();

        // Video tutorial
        //-------------
        $this->getLayout()->getBlock('head')
            ->addItem('js_css', 'prototype/windows/themes/default.css')
            ->addJs('prototype/window.js');

        Mage::helper('M2ePro/Magento')->addMagentoCss($this->getLayout());

        $this->getLayout()->getBlock('head')->addJs('M2ePro/VideoTutorialHandler.js');
        //-------------

        $this->getLayout()->getBlock('content')->append(
            $wizardHelper->createBlock('notification',$activeWizardNick)
        );
    }

    //#############################################

    private function updateBackupConnectionData()
    {
        $dateLastCheck = Mage::helper('M2ePro/Module')->getConfig()
                                ->getGroupValue('/cache/location_info/', 'date_last_check');

        if (is_null($dateLastCheck)) {
            $dateLastCheck = Mage::helper('M2ePro')->getCurrentGmtDate(true)-60*60*365;
        } else {
            $dateLastCheck = strtotime($dateLastCheck);
        }

        if (Mage::helper('M2ePro')->getCurrentGmtDate(true) >= $dateLastCheck + 60*60*24) {

            $domainBackup = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
            strpos($domainBackup,'www.') === 0 && $domainBackup = substr($domainBackup,4);
            Mage::helper('M2ePro/Module')->getConfig()
                ->setGroupValue('/cache/location_info/', 'domain', $domainBackup);

            $ipBackup = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : NULL;
            is_null($ipBackup) && $ipBackup = isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : '127.0.0.1';
            Mage::helper('M2ePro/Module')->getConfig()
                ->setGroupValue('/cache/location_info/', 'ip', $ipBackup);

            $directoryBackup = Mage::getBaseDir();
            Mage::helper('M2ePro/Module')->getConfig()
                ->setGroupValue('/cache/location_info/', 'directory', $directoryBackup);

            Mage::helper('M2ePro/Module')->getConfig()->setGroupValue(
                '/cache/location_info/', 'date_last_check', Mage::helper('M2ePro')->getCurrentGmtDate()
            );
        }
    }

    //#############################################
}