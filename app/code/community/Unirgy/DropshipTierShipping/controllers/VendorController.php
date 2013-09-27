<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

require_once "app/code/community/Unirgy/Dropship/controllers/VendorController.php";

class Unirgy_DropshipTierShipping_VendorController extends Unirgy_Dropship_VendorController
{
    public function ratesAction()
{
    $this->_renderPage(null, 'tiership_rates');
}
    public function ratesPostAction()
    {
        $session = Mage::getSingleton('udropship/session');
        $hlp = Mage::helper('udropship');
        $r = $this->getRequest();
        if ($r->isPost()) {
            $p = $r->getPost();
            try {
                $v = $session->getVendor();
                $v->setTiershipRates($p['tiership_rates']);
                $v->save();
#echo "<pre>"; print_r($v->debug()); exit;
                $session->addSuccess('Rates has been saved');
            } catch (Exception $e) {
                $session->addError($e->getMessage());
            }
        }
        $this->_redirect('udtiership/vendor/rates');
    }
    public function simpleratesAction()
    {
        $this->_renderPage(null, 'tiership_simple_rates');
    }
    public function simpleratesPostAction()
    {
        $session = Mage::getSingleton('udropship/session');
        $hlp = Mage::helper('udropship');
        $r = $this->getRequest();
        if ($r->isPost()) {
            $p = $r->getPost();
            try {
                $v = $session->getVendor();
                $v->setTiershipSimpleRates($p['tiership_simple_rates']);
                $v->save();
#echo "<pre>"; print_r($v->debug()); exit;
                $session->addSuccess('Rates has been saved');
            } catch (Exception $e) {
                $session->addError($e->getMessage());
            }
        }
        $this->_redirect('udtiership/vendor/simplerates');
    }
}