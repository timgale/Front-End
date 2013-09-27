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

class Unirgy_DropshipVendorProduct_VendorController extends Unirgy_Dropship_VendorController
{
    public function indexAction()
    {
        $this->_forward('products');
    }
    public function productsAction()
    {
        $session = Mage::getSingleton('udropship/session');
        $session->setUdprodLastGridUrl(Mage::getUrl('*/*/*', array('_current'=>true)));
        $this->_renderPage(null, 'udprod');
    }
    protected function _checkProduct($productId=null)
    {
        Mage::helper('udprod')->checkProduct($productId);
        return $this;
    }
    public function productEditAction()
    {
        $session = Mage::getSingleton('udropship/session');
        $oldStoreId = Mage::app()->getStore()->getId();
        try {
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            $this->_checkProduct();
            Mage::app()->setCurrentStore($oldStoreId);
            if (Mage::helper('udropship')->isWysiwygAllowed()) {
                $this->_renderPage(array('default', 'uwysiwyg_editor', 'uwysiwyg_editor_js'), 'udprod');
            } else {
                $this->_renderPage(null, 'udprod');
            }
        } catch (Exception $e) {
            Mage::app()->setCurrentStore($oldStoreId);
            $session->addError($e->getMessage());
            $this->_redirectAfterPost();
        }
    }
    public function productNewAction()
    {
        $session = Mage::getSingleton('udropship/session');
        Mage::app()->getRequest()->setParam('id', null);
        try {
            if (Mage::helper('udropship')->isWysiwygAllowed()) {
                $this->_renderPage(array('default', 'uwysiwyg_editor', 'uwysiwyg_editor_js'), 'udprod');
            } else {
                $this->_renderPage(null, 'udprod');
            }
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            $this->_redirectAfterPost();
        }
    }
    public function productPostAction()
    {
        $session = Mage::getSingleton('udropship/session');
        $v = Mage::getSingleton('udropship/session')->getVendor();
        $hlp = Mage::helper('udropship');
        $prHlp = Mage::helper('udprod');
        $r = $this->getRequest();
        $oldStoreId = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        if ($r->isPost()) {
            try {
                $prod = $this->_initProduct();
                $isNew = !$prod->getId();
                $prHlp->checkUniqueVendorSku($prod, $v);
                $prod->save();
                $prHlp->processAfterSave($prod);
                $prHlp->processUdmultiPost($prod, $v);
                if ($isNew) {
                    $prHlp->processNewConfigurable($prod, $v);
                }
                $prHlp->processQuickCreate($prod, $isNew);
                $prHlp->reindexProduct($prod);
                $session->addSuccess('Product has been saved');
            } catch (Exception $e) {
                $session->setUdprodFormData($r->getPost('product'));
                $session->addError($e->getMessage());
            }
        }
        Mage::app()->setCurrentStore($oldStoreId);
        $this->_redirectAfterPost(@$prod);
    }
    
    protected function _redirectAfterPost($prod=null)
    {
        $session = Mage::getSingleton('udropship/session');
        $hlp = Mage::helper('udropship');
        $r = $this->getRequest();
        if (!$r->getParam('continue_edit')) {
            if ($session->getUdprodLastGridUrl()) {
                $this->_redirectUrl($session->getUdprodLastGridUrl());
            } else {
                $this->_redirect('udprod/vendor/products');
            }
        } else {
            if (isset($prod) && $prod->getId()) {
                $this->_redirect('udprod/vendor/productEdit', array('id'=>$prod->getId()));
            } else {
                $this->_redirect('udprod/vendor/productNew', array('_current'=>true));
            }
        }
    }
    protected function _initProduct()
    {
        $r = $this->getRequest();
        $v = Mage::getSingleton('udropship/session')->getVendor();
        $productId  = (int) $this->getRequest()->getParam('id');
        $productData = $r->getPost('product');
        $product = Mage::helper('udprod')->initProductEdit(array(
            'id'   => $productId,
            'data' => $productData,
            'vendor' => $v
        ));
        if (isset($productData['options'])) {
            $product->setProductOptions($productData['options']);
        }
        $product->setCanSaveCustomOptions(
            (bool)$this->getRequest()->getPost('affect_product_custom_options')
        );
        return $product;
    }
    public function categoriesJsonAction()
    {
        $r = Mage::app()->getRequest();
        $oldStoreId = Mage::app()->getStore()->getId();
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $product = Mage::helper('udprod')->initProductEdit(array(
            'id' => $r->getParam('id'),
            'vendor' => Mage::getSingleton('udropship/session'),
        ));
        Mage::register('current_product', $product);
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('udropship/categories')
                ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
        );
        Mage::app()->setCurrentStore($oldStoreId);
    }
    public function cfgQuickCreateAttributeAction()
    {
        $session = Mage::getSingleton('udropship/session');
        $oldStoreId = Mage::app()->getStore()->getId();
        try {
            $this->_setTheme();
            $prodBlock = Mage::app()->getLayout()->createBlock('udprod/vendor_product', 'udprod.edit', array('skip_add_head_js'=>1));
            $cfgEl = $prodBlock->getForm()->getElement('_cfg_quick_create');
            $cfgEl->setCfgAttributeValue($this->getRequest()->getParam('cfg_attr_value'));
            $this->getResponse()->setBody(
                $cfgEl->toHtml()
            );
        } catch (Exception $e) {
            Mage::app()->setCurrentStore($oldStoreId);
            $this->returnResult(array(
                'error'=>true,
                'message' => $e->getMessage(),
            ));
        }
    }
    public function returnResult($result)
    {
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    protected $_oldStoreId;
    public function preDispatch()
    {
        $useSidXpath = Mage_Core_Model_Session_Abstract::XML_PATH_USE_FRONTEND_SID;
        $oldUseSid = Mage::getStoreConfig($useSidXpath);
        if ($this->getRequest()->getActionName() == 'upload') {
            Mage::app()->getStore()->setConfig($useSidXpath, 1);
        }
        parent::preDispatch();
        if ($this->getRequest()->getActionName() == 'upload') {
            Mage::app()->getStore()->setConfig($useSidXpath, $oldUseSid);
            $this->_oldStoreId = Mage::app()->getStore()->getId();
            Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        }
        return $this;
    }
    public function uploadAction()
    {
        try {
            $uploader = new Mage_Core_Model_File_Uploader($this->getRequest()->getParam('image_field', 'image'));
            $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
            $uploader->addValidateCallback('catalog_product_image',
                Mage::helper('catalog/image'), 'validateUploadFile');
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $result = $uploader->save(
                Mage::getSingleton('catalog/product_media_config')->getBaseTmpMediaPath()
            );
            $result['tmp_name'] = str_replace(DS, "/", $result['tmp_name']);
            $result['path'] = str_replace(DS, "/", $result['path']);

            $result['url'] = Mage::getSingleton('catalog/product_media_config')->getTmpMediaUrl($result['file']);
            //$result['file'] = $result['file'] . '.tmp';
            $result['file'] = $result['file'];
            $result['cookie'] = array(
                'name'     => session_name(),
                'value'    => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path'     => $this->_getSession()->getCookiePath(),
                'domain'   => $this->_getSession()->getCookieDomain()
            );

        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode());
        }
        usleep(10);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        if ($this->_oldStoreId) Mage::app()->setCurrentStore($this->_oldStoreId);
    }
}