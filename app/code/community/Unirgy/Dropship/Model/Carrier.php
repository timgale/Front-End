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

class Unirgy_Dropship_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'udropship';

    protected $_methods = array();
    protected $_allowedMethods = array();

    protected $_rawRequest;

    /**
    * Collect and combine rates from vendor carriers
    *
    * @param Mage_Shipping_Model_Rate_Request $request
    * @return Mage_Shipping_Model_Rate_Result|boolean
    */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $this->_rawRequest = $request;

        $hlp = Mage::helper('udropship');
        $hlpd = Mage::helper('udropship/protected');

        $carrierNames = Mage::getSingleton('udropship/source')->getCarriers();

        // get available dropship shipping methods
        $shipping = $hlp->getShippingMethods();

        // prepare data
        $items = $request->getAllItems();

        try {
            $hlpd->prepareQuoteItems($items);
        } catch (Exception $e) {
            return;
        }

        if (!$hlpd->getQuote()) {
            return;
        }

        $quote = $hlpd->getQuote();
        $address = $hlpd->getQuote()->getShippingAddress();
        foreach ($items as $item) {
            if ($item->getAddress()) {
                $address = $item->getAddress();
            }
            break;
        }

        Mage::dispatchEvent('udropship_carrier_collect_before', array('request'=>$request, 'address'=>$address));

        $requests = $hlpd->getRequestsByVendor($items, $request);

        $evTransport = new Varien_Object(array('requests'=>$requests, 'orig_request'=>$request));
        Mage::dispatchEvent('udropship_carrier_process_vendor_requests', array('transport'=>$evTransport, 'address'=>$address));
        $requests = $evTransport->getRequests();

        if ($quote->getUdropshipCarrierError()) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $errorMsg = $this->getConfigData('specificerrmsg');
            $error->setErrorMessage($quote->getUdropshipCarrierError() ? $quote->getUdropshipCarrierError() : $errorMsg);
            return $error;
        }
        
        $requestVendors = array_keys($requests);
        // build separate requests grouped by carrier and vendor
        $carriers = array();
        $numMethodsPerVendor = array();
        $firstVendorRequests = array();
        foreach ($requests as $vId=>$vRequests) {
            foreach ($vRequests as $cCode=>$r) {
                if (empty($firstVendorRequests[$vId])) {
                    $firstVendorRequests[$vId] = $r;
                }
                #$cCode = $r->getCarrierCode();
                $carriers[$cCode][$vId]['request'] = $r;
                $methods = $hlp->getVendor($vId)->getShippingMethods();
                foreach ($methods as $__m) {
                    foreach ($__m as $m) {
                        if (($s = $shipping->getItemById($m['shipping_id']))) {
                            $s->useProfile($hlp->getVendor($vId));
                            $carriers[$cCode][$vId]['methods'][$s->getShippingCode()] = $s->getSystemMethods($cCode);
                            $s->resetProfile();
                        }
                    }
                }
            }
            // skip methods that are not shared by ALL vendors
            $vendorMethods = $hlp->getVendor($vId)->getShippingMethods();
            foreach ($shipping as $s) {
                if (empty($vendorMethods[$s->getId()])) {
                    $s->setIsSkipped(true);
                } else {
                    $_isSkippedShipping = new Varien_Object(array('result'=>false));
                    Mage::dispatchEvent('udropship_vendor_shipping_check_skipped', array(
                        'shipping'=>$s,
                        'address'=>$address,
                        'vendor'=>$hlp->getVendor($vId),
                        'request'=>$firstVendorRequests[$vId],
                        'result'=>$_isSkippedShipping
                    ));
                    $s->setIsSkipped($_isSkippedShipping->getResult());
                }
            }
        }

        $freeMethods = explode(',', Mage::getStoreConfig('carriers/udropship/free_method', $hlpd->getStore()));
        if ($freeMethods) {
            $_freeMethods = array();
            foreach ($freeMethods as $freeMethod) {
                if (is_numeric($freeMethod)) {
                    if ($shipping->getItemById($freeMethod)) {
                        $_freeMethods[] = $freeMethod;
                    }
                } else {
                    if ($shipping->getItemByColumnValue('shipping_code', $freeMethod)) {
                        $_freeMethods[] = $freeMethod;
                    }
                }
                $_freeMethods[] = $freeMethod;
            }
            $freeMethods = $_freeMethods;
        }

        // quote.udropship_shipping_details
        $details = array('version' => $hlp->getVersion());
        // vendors participating in the estimate
        $vendors = array();

        $errorAction = $hlpd->getStore()->getConfig('udropship/customer/estimate_error_action');

        // send actual requests and collect results
        foreach ($carriers as $cCode=>$requests) {
            $keys = array_keys($requests);
            foreach ($keys as $k) {
                $vendor = $hlp->getVendor($k);
                $systemMethods = $hlp->getMultiSystemShippingMethodsByProfile($vendor);
                $vMethods = $vendor->getShippingMethods();
                $result = $hlpd->collectVendorCarrierRates($requests[$k]['request']);
                if ($result===false) {
                    if ($errorAction=='fail') {
                        //return $hlpd->errorResult();
                        continue;
                    } elseif ($errorAction=='skip') {
                        continue;
                    }
                }

                $rates = $result->getAllRates();
                $keys1 = array_keys($rates);
                foreach ($keys1 as $k1) {
                    if ($rates[$k1] instanceof Mage_Shipping_Model_Rate_Result_Error) {
                        if ($errorAction=='fail') {
                            //return $hlpd->errorResult('udropship', $rates[$k1]->getErrorMessage());
                            continue 2;
                        } elseif ($errorAction=='skip') {
                            continue 2;
                        }
                    }
                    $wildcardUsed = false;
                    if (empty($systemMethods[$rates[$k1]->getCarrier()][$rates[$k1]->getMethod()])) {
                        if (!empty($systemMethods[$rates[$k1]->getCarrier()]['*'])) {
                            $wildcardUsed = true;
                        } else {
                            continue;
                        }
                    }

                    if ($wildcardUsed) {
                        $smArray = $systemMethods[$rates[$k1]->getCarrier()]['*'];
                    } else {
                        $smArray = $systemMethods[$rates[$k1]->getCarrier()][$rates[$k1]->getMethod()];
                    }

                    foreach ($smArray as $s) {
                        $s->useProfile($vendor);
                        if ($s->getIsSkipped() || !isset($vMethods[$s->getId()])) {
                            continue;
                        }
                    foreach ($vMethods[$s->getId()] as $vMethod) {
                        $vendorCode = $vendor->getCarrierCode();
                        if ($requests[$k]['request']->getForcedCarrierFlag()) {
                            $ecCode = $ocCode = $rates[$k1]->getCarrier();
                        } else {
                            $ecCode = !empty($vMethod['est_carrier_code'])
                                ? $vMethod['est_carrier_code']
                                : (!empty($vMethod['carrier_code']) ? $vMethod['carrier_code'] : $vendorCode);
                            $ocCode = !empty($vMethod['carrier_code']) ? $vMethod['carrier_code'] : $vendorCode;
                        }
                        $oldEstCode = null;
                        if (!empty($details['methods'][$s->getShippingCode()]['vendors'][$k]['est_code'])) {
                            list($oldEstCode, ) = explode('_', $details['methods'][$s->getShippingCode()]['vendors'][$k]['est_code'], 2);
                        }
                        if ($ecCode!=$rates[$k1]->getCarrier()) {
                            if ($vendor->getUseRatesFallback()
                                && !Mage::helper('udropship')->isUdsprofileActive()
                            ) {
                                if ($oldEstCode==$ecCode) {
                                    continue;
                                } elseif ($oldEstCode!=$ecCode && $ecCode==$rates[$k1]->getCarrier()) {
                                } elseif ($oldEstCode!=$ocCode && $ocCode==$rates[$k1]->getCarrier()) {
                                    $ecCode = $ocCode;
                                } elseif (!$oldEstCode && $vendorCode==$rates[$k1]->getCarrier()) {
                                    $ecCode = $vendorCode;
                                } else {
                                    continue;
                                }
                            } else {
                                continue;
                            }
                        }
                        if ('**estimate**' == $ocCode) {
                            $ocCode = $ecCode;
                        }
                        if ($wildcardUsed && $ecCode!=$ocCode) {
                            continue;
                        }

                        if (Mage::helper('udropship')->isUdsprofileActive()) {
                            $codeToCompare = $vMethod['carrier_code'].'_'.$vMethod['method_code'];
                            if (!empty($vMethod['est_use_custom'])) {
                                $codeToCompare = $vMethod['est_carrier_code'].'_'.$vMethod['est_method_code'];
                            }
                            if ($codeToCompare!=$rates[$k1]->getCarrier().'_'.$rates[$k1]->getMethod()) {
                                continue;
                            }
                        }

                        // set free method if required
                        if ($freeMethods
                            && $this->getConfigData('free_shipping_allowed')
                            && $this->getConfigData('freeweight_allowed')
                            && $this->isRuleFreeshipping($requests[$k]['request'])
                            && in_array($s->getShippingCode(), $freeMethods)
                        ) {
                            $rates[$k1]->setPrice(0);
                            $rates[$k1]->setIsFwFreeShipping(true);
                        }

                        Mage::dispatchEvent('udropship_process_vendor_carrier_single_rate_result', array(
                            'vendor_method'=>$vMethod,
                            'udmethod'=>$s,
                            'address'=>$address,
                            'vendor'=>$vendor,
                            'request'=>$requests[$k]['request'],
                            'rate'=>$rates[$k1]
                        ));

                        $detail = array(
                            'cost' => sprintf('%.4f', $rates[$k1]->getCost()),
                            'price' => sprintf('%.4f', $rates[$k1]->getPrice()),
                            'est_code' => $rates[$k1]->getCarrier().'_'.$rates[$k1]->getMethod(),
                            'est_carrier_title' => $rates[$k1]->getCarrierTitle(),
                            'est_method_title' => $rates[$k1]->getMethodTitle(),
                        );
                        if (Mage::helper('udropship')->isUdsprofileActive()) {
                            $detail['sort_order'] = $vMethod['sort_order'];
                        }
                        $detail['is_free_shipping'] = (int)$rates[$k1]->getIsFwFreeShipping();
                        if ($ecCode==$ocCode) {
                            $detail['code'] = $detail['est_code'];
                            $detail['carrier_title'] = $detail['est_carrier_title'];
                            $detail['method_title'] = $detail['est_method_title'];
                        } else {
                            $ocMethod = $s->getSystemMethods($ocCode);
                            if (Mage::helper('udropship')->isUdsprofileActive()) {
                                $ocMethod = $vMethod['method_code'];
                            }
                            if (empty($ocMethod)) {
                                continue;
                            }
                            $methodNames = $hlp->getCarrierMethods($ocCode);
                            $detail['code'] = $ocCode.'_'.$ocMethod;
                            $detail['carrier_title'] = $carrierNames[$ocCode];
                            $detail['method_title'] = $methodNames[$ocMethod];
                        }
                        $vendors[$k] = 1;
                        $scKey = !$wildcardUsed ? $s->getShippingCode() : $s->getShippingCode().'___'.$detail['code'];
                        $details['methods'][$scKey]['id'] = $s->getShippingId();
                        if (Mage::helper('udropship')->isUdsprofileActive()
                            && !empty($details['methods'][$scKey]['vendors'][$k])
                            && $details['methods'][$scKey]['vendors'][$k]['sort_order']<$detail['sort_order']
                        ) {
                            continue;
                        }
                        if (($curUdpoSeqNumber = $requests[$k]['request']->getUdpoSeqNumber())
                            && !empty($details['methods'][$scKey]['vendors'][$k])
                        ) {
                            $snByVendor = $address->getSeqNumbersByVendor();
                            $snByVendor[$k][$curUdpoSeqNumber][$scKey] = true;
                            $address->setSeqNumbersByVendor($snByVendor);
                            $ratesBySeqNumber = @$details['methods'][$scKey]['vendors'][$k]['rates_by_seq_number'];
                            if (!is_array($ratesBySeqNumber)) {
                                $ratesBySeqNumber = array();
                            }
                            if (($oldRateBySeqNumber = @$ratesBySeqNumber[$curUdpoSeqNumber])) {
                                $details['methods'][$scKey]['vendors'][$k]['cost'] -= $oldRateBySeqNumber['cost'];
                                $details['methods'][$scKey]['vendors'][$k]['price'] -= $oldRateBySeqNumber['price'];
                            }
                            $ratesBySeqNumber[$curUdpoSeqNumber] = $detail;
                            //if (is_array($snByVendor) && $curUdpoSeqNumber>=max(array_keys($snByVendor))) {
                                $_resCost = $detail['cost'] + $details['methods'][$scKey]['vendors'][$k]['cost'];
                                $_resPrice = $detail['price'] + $details['methods'][$scKey]['vendors'][$k]['price'];
                                $details['methods'][$scKey]['vendors'][$k] = $detail;
                                $details['methods'][$scKey]['vendors'][$k]['cost'] = $_resCost;
                                $details['methods'][$scKey]['vendors'][$k]['price'] = $_resPrice;
                            //}
                            $details['methods'][$scKey]['vendors'][$k]['is_free_shipping'] = $detail['is_free_shipping'];
                            $details['methods'][$scKey]['vendors'][$k]['rates_by_seq_number'] = $ratesBySeqNumber;
                        } else {
                            $details['methods'][$scKey]['vendors'][$k] = $detail;
                            if (!empty($curUdpoSeqNumber)) {
                                $snByVendor = $address->getSeqNumbersByVendor();
                                $snByVendor[$k][$curUdpoSeqNumber][$scKey] = true;
                                $address->setSeqNumbersByVendor($snByVendor);
                                $details['methods'][$scKey]['vendors'][$k]['rates_by_seq_number'] = array(
                                    $curUdpoSeqNumber => $detail
                                );
                            }
                        }
                        if ($wildcardUsed) {
                            $details['methods'][$scKey]['wildcard_code'] = $detail['code'];
                            $details['methods'][$scKey]['wildcard_carrier_title'] = $detail['carrier_title'];
                            $details['methods'][$scKey]['wildcard_method_title'] = $detail['method_title'];
                        }
                    }
                    }
                    foreach ($smArray as $s) {
                        $s->resetProfile();
                    }
                }
            }
        }
        $snByVendor = $address->getSeqNumbersByVendor();
        if (!empty($snByVendor) && is_array($snByVendor)) {
            $totalSnUsed = array();
            $methodsUsedBySn = array();
            foreach ($snByVendor as $vId => $snData) {
                foreach ($snData as $__sn => $snMethods) {
                    $totalSnUsed[$vId.'-'.$__sn] = 1;
                    foreach ($snMethods as $_snMethod => $_dummy) {
                        $methodsUsedBySn[$_snMethod][$vId.'-'.$__sn] = 1;
                    }
                }
            }
            $totalSnUsed = array_sum($totalSnUsed);
            foreach ($methodsUsedBySn as $_snMethod => $_snMethodTotals) {
                if (array_sum($_snMethodTotals)<$totalSnUsed) {
                    unset($details['methods'][$_snMethod]);
                }
            }
        }
        $address->setUdropshipShippingDetails(Zend_Json::encode($details));
#Mage::log($hlpd->getQuote()->getUdropshipShippingDetails());
#exit;
        // googlecheckout merchant calculations don't save address
        if (Mage::app()->getRequest()->getRouteName()=='googlecheckout') {
            $transaction = Mage::getModel('core/resource_transaction');
            $transaction->addObject($address);
            foreach ($request->getAllItems() as $item) {
                $transaction->addObject($item);
            }
            $transaction->save();
        }

        if (empty($vendors) || ($errorAction == 'fail' && count($vendors)<count($requestVendors))) {
            return $hlpd->errorResult('udropship');
        }

        $totalMethod = Mage::getStoreConfig('udropship/customer/estimate_total_method');

        // collect prices from details
        $totals = array();
        $numVendors = sizeof($vendors);
        $processedMethods = array();
        foreach ($details['methods'] as $mCode=>$method) {
            $sId = $method['id'];
            unset($method['id']);
            if (in_array($method, $processedMethods)) continue;
            $processedMethods[] = $method;
            $method['id'] = $sId;
            $s = $shipping->getItemById($method['id']);
            // skip not common methods
            if ($s->getIsSkipped() || sizeof($method['vendors'])<$numVendors) {
                continue;
            }
            if (empty($totals[$mCode])) {
                $totals[$mCode] = array(
                    'is_free_shipping' => 1,
                    'cost' => 0,
                    'price' => 0,
                    'title' => $s->getStoreTitle($quote->getStoreId())
                );
                if (!empty($method['wildcard_code'])) {
                    $totals[$mCode]['title'] .= sprintf(' [%s - %s]', $method['wildcard_carrier_title'], $method['wildcard_method_title']);
                }
            }
            foreach ($method['vendors'] as $vId=>$rate) {
                $totals[$mCode]['is_free_shipping'] = @$totals[$mCode]['is_free_shipping'] && @$rate['is_free_shipping'];
                $totals[$mCode]['cost'] = $hlp->applyEstimateTotalCostMethod($totals[$mCode]['cost'], $rate['cost']);
                $totals[$mCode]['price'] = $hlp->applyEstimateTotalPriceMethod($totals[$mCode]['price'], $rate['price']);
            }
        }
#Mage::log($totals);

        // return Magento formated shipping carrier result
        $result = Mage::getModel('shipping/rate_result');

        // flat rate customization
        /*
        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier('udropship');
        $method->setCarrierTitle($dropshipCarrier->getConfigData('title'));
        $method->setMethod('flatrate');
        $method->setMethodTitle('Flat Rate');
        $method->setCost(7.5);
        $method->setPrice(7.5);
        $result->append($method);
        */
        if (!empty($totals)) {
            foreach ($totals as $mCode=>$total) {
                $method = Mage::getModel('shipping/rate_result_method');

                $method->setCarrier('udropship');
                $method->setCarrierTitle($this->getConfigData('title'));

                $method->setMethod($mCode);
                $method->setMethodTitle($total['title']);

                $method->setCost($total['cost']);
                if (!empty($total['is_free_shipping'])) {
                    $method->setPrice(0);
                } else {
                $method->setPrice($this->getMethodPrice($total['price'], $mCode));
                }

                $result->append($method);
            }
        } else {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $errorMsg = $this->getConfigData('specificerrmsg');
            $error->setErrorMessage($errorMsg);
            return $error;
        }

        Mage::dispatchEvent('udropship_carrier_collect_after', array('request'=>$request, 'result'=>$result, 'address'=>$address, 'details'=>$details));

        return $result;
    }

    public function getMethodPrice($cost, $method='')
    {
#if ($_SERVER['REMOTE_ADDR']=='24.20.46.76') { echo "<pre>"; print_r($this->_rawRequest->debug()); exit; }
        $freeMethods = explode(',', $this->getConfigData('free_method'));
        $freeShippingSubtotal = $this->getConfigData('free_shipping_subtotal');
        if ($freeShippingSubtotal === null || $freeShippingSubtotal === '') {
            $freeShippingSubtotal = false;
        }
        if (in_array($method, $freeMethods)
            && $this->getConfigData('free_shipping_allowed')
            && $this->getConfigData('free_shipping_enable')
            && $freeShippingSubtotal!==false
            && $freeShippingSubtotal <= $this->_rawRequest->getPackageValueWithDiscount())
        {
            $price = '0.00';
        } else {
            $price = $this->getFinalPriceWithHandlingFee($cost);
        }
        return $price;
    }

    public function getAllowedMethods()
    {
        if (empty($this->_allowedMethods)) {
            $shipping = $this->_getAllMethods();
            $methods = array();
            foreach ($shipping as $m) {
                $methods[$m->getShippingCode()] = $m->getShippingTitle();
            }
            $this->_allowedMethods = $methods;
        }
        return $this->_allowedMethods;
    }

    protected function _getAllMethods()
    {
        if (empty($this->_methods)) {
            $this->_methods = Mage::helper('udropship')->getShippingMethods()
                ->setOrder('days_in_transit', 'desc');
        }
        return $this->_methods;
    }

    public function getUseForAllProducts()
    {
        return true;
    }

    public function isRuleFreeshipping($request)
    {
        $isFreeshipping = true;
        foreach ($request->getAllItems() as $item) {
            if ($item->getFreeShipping()!==true && $item->getTotalQty()>$item->getFreeShipping()) {
                $isFreeshipping = false;
                break;
            }
        }
        $address = Mage::helper('udropship/item')->getAddress($request->getAllItems());
        if ($address instanceof Varien_Object && $address->getFreeShipping() === true) {
            $isFreeshipping = true;
        }
        return $isFreeshipping;
    }

}
