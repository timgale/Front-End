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

class Unirgy_Dropship_Helper_Catalog extends Mage_Core_Helper_Abstract
{
    public function isQty($product)
    {
        return Mage::helper('cataloginventory')->isQty($product->getTypeId());
    }
    protected $_topCats;
    public function getTopCategories()
    {
        if (null === $this->_topCats) {
            $this->_topCats = $this->getCategoryChildren(
                $this->getStoreRootCategory($this->getStore())
            );
        }
        return $this->_topCats;
    }
    public function getStore()
    {
        return Mage::app()->getDefaultStoreView();
    }
    protected $_storeRootCategory = array();
    public function getStoreRootCategory($store=null)
    {
        $store = $store ? Mage::app()->getStore($store) : Mage::app()->getDefaultStoreView();
        $rootId = $store->getRootCategoryId();
        if (!isset($this->_storeRootCategory[$rootId])) {

            $this->_storeRootCategory[$rootId] = Mage::getModel('catalog/category')->load($rootId);
        }
        return $this->_storeRootCategory[$rootId];
    }
    public function getPathInStore($cat)
    {
        $result = array();
        $path = array_reverse($cat->getPathIds());
        foreach ($path as $itemId) {
            if ($itemId == $this->getStore()->getRootCategoryId()) {
                break;
            }
            $result[] = $itemId;
        }
        return implode(',', $result);
    }
    public function getCategoryChildren($cId, $active=true, $recursive=false)
    {
        return $this->_getCategoryChildren($cId, $active, $recursive);
    }
    protected function _getCategoryChildren($cId, $active=true, $recursive=false, $orderBy='level,position')
    {
        if ($cId instanceof Mage_Catalog_Model_Category) {
            $cat = $cId;
        } else {
            $cat = Mage::getModel('catalog/category')->load($cId);
        }
        $collection = $cat->getCollection()
            ->addAttributeToSelect('url_key')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('all_children')
            ->addAttributeToSelect('is_anchor');
        $orderBy = explode(',', $orderBy);
        foreach ($orderBy as $ob) {
            $ob = explode(':', $ob);
            $ob[1] = !empty($ob[1]) ? $ob[1] : 'asc';
            $collection->setOrder($ob[0], $ob[1]);
        }
        if (Mage::helper('catalog/category_flat')->isEnabled()) {
            $collection->addUrlRewriteToResult();
        } else {
            $collection->joinUrlRewrite();
        }
        if ($active) {
            $collection->addAttributeToFilter('is_active', 1);
        }
        $collection->getSelect()->where('path LIKE ?', "{$cat->getPath()}/%");
        if (!$recursive) {
            $collection->getSelect()->where('level <= ?', $cat->getLevel() + 1);
        }
        return $collection;
    }
    public function getCategoriesCollection($cIds, $active=true, $orderBy='level,position')
    {
        $collection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('url_key')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('all_children')
            ->addAttributeToSelect('is_anchor');
        $orderBy = explode(',', $orderBy);
        foreach ($orderBy as $ob) {
            $ob = explode(':', $ob);
            $ob[1] = !empty($ob[1]) ? $ob[1] : 'asc';
            $collection->setOrder($ob[0], $ob[1]);
        }
        if (Mage::helper('catalog/category_flat')->isEnabled()) {
            $collection->addUrlRewriteToResult();
        } else {
            $collection->joinUrlRewrite();
        }
        if ($active) {
            $collection->addAttributeToFilter('is_active', 1);
        }
        $collection->addIdFilter($cIds);
        return $collection;
    }
    public function processCategoriesData(&$fCatIds, $returnArray=true)
    {
        if (!is_array($fCatIds)) {
            if (strpos($fCatIds, 'a:')===0) {
                $fCatIds = @unserialize($fCatIds);
            } elseif (strpos($fCatIds, '{')===0) {
                $fCatIds = Zend_Json::decode($fCatIds);
            }
        }
        if (is_array($fCatIds) && !$returnArray) {
            $fCatIds = implode(',', $fCatIds);
        } elseif (!is_array($fCatIds) && $returnArray) {
            $fCatIds = explode(',', $fCatIds);
        }
        $fCatIds = $fCatIds === null ? '' : $fCatIds;
        return $this;
    }
    protected $_store;
    protected $_oldStore;
    protected $_oldArea;
    protected $_oldDesign;
    protected $_oldTheme;

    public function setDesignStore($store=null, $area=null)
    {
        if (!is_null($store)) {
            if ($this->_store) {
                return $this;
            }
            $this->_oldStore = Mage::app()->getStore();
            $this->_oldArea = Mage::getDesign()->getArea();
            $this->_store = Mage::app()->getStore($store);

            $theme = array();
            $store = $this->_store;
            $area = $area ? $area : 'frontend';
            if ($area == 'adminhtml') {
                $package = (string)Mage::getConfig()->getNode('stores/admin/design/package/name');
                $design = array('package'=>$package, 'store'=>$store->getId());
                $theme['default'] = (string)Mage::getConfig()->getNode('stores/admin/design/theme/default');
                foreach (array('layout', 'template', 'skin', 'locale') as $type) {
                    $theme[$type] = (string)Mage::getConfig()->getNode("stores/admin/design/theme/{$type}");
                }
            } else {
                $package = Mage::getStoreConfig('design/package/name', $store);
                $design = array('package'=>$package, 'store'=>$store->getId());
                $theme['default'] = (string)$store->getConfig("design/theme/default");
                foreach (array('layout', 'template', 'skin', 'locale') as $type) {
                    $theme[$type] = (string)$store->getConfig("design/theme/{$type}");
                }
            }
            $inline = false;
        } else {
            if (!$this->_store) {
                return $this;
            }
            $this->_store = null;
            $store = $this->_oldStore;
            $area = $this->_oldArea;
            $design = $this->_oldDesign;
            $theme = $this->_oldTheme;
            $inline = true;
        }

        Mage::app()->setCurrentStore($store);
        $oldDesign = Mage::getDesign()->setArea($area)->setAllGetOld($design);
        foreach (array('default', 'layout', 'template', 'skin', 'locale') as $type) {
            $oldTheme[$type] = Mage::getDesign()->getTheme($type);
            Mage::getDesign()->setTheme($type, @$theme[$type]);
        }
        Mage::app()->getLayout()->setArea($area);
        Mage::app()->getTranslator()->init($area, true);
        Mage::getSingleton('core/translate')->setTranslateInline($inline);

        if ($this->_store) {
            $this->_oldDesign = $oldDesign;
            $this->_oldTheme = $oldTheme;
        } else {
            $this->_oldStore = null;
            $this->_oldArea = null;
            $this->_oldDesign = null;
            $this->_oldTheme = null;
        }

        return $this;
    }
    public function getPidBySku($sku, $excludePids=null)
    {
        $res = Mage::getSingleton('core/resource');
        $read = $res->getConnection('catalog_read');
        $table = $res->getTableName('catalog/product');
        $select = $read->select()
            ->from($table, 'entity_id')
            ->where('sku = :sku');
        $bind = array(':sku' => (string)trim($sku));
        if (!empty($excludePids)) {
            if (!is_array($excludePids)) {
                $excludePids = array($excludePids);
            }
            $select->where('entity_id not in (?)', $excludePids);
        }
        return $read->fetchOne($select, $bind);
    }
    public function getPidByVendorSku($vSku, $vId, $excludePids=null)
    {
        $pId = null;
        if (Mage::helper('udropship')->isUdmultiActive()) {
            $res = Mage::getSingleton('core/resource');
            $read = $res->getConnection('udropship_read');
            $table = $res->getTableName('udropship_vendor_product');
            $select = $read->select()
                ->from($table, 'product_id')
                ->where('vendor_sku = :vendor_sku and vendor_id = :vendor_id');
            $bind = array(':vendor_sku' => (string)trim($vSku), ':vendor_id' => $vId);
            if (!empty($excludePids)) {
                if (!is_array($excludePids)) {
                    $excludePids = array($excludePids);
                }
                $select->where('product_id not in (?)', $excludePids);
            }
            $pId = $read->fetchOne($select, $bind);
        } else {
            $vSkuAttr = Mage::getStoreConfig('udropship/vendor/vendor_sku_attribute');
            if ($vSkuAttr && $vSkuAttr!='sku') {
                $attrFilters = array(array(
                    'attribute' => $vSkuAttr,
                    'in' => array($vSku)
                ));
                if (!empty($excludePids)) {
                    if (!is_array($excludePids)) {
                        $excludePids = array($excludePids);
                    }
                    $attrFilters[] = array(
                        'attribute' => 'entity_id',
                        'nin' => $excludePids
                    );
                }
                $prodCol = Mage::getModel('catalog/product')->getCollection()->setPage(1,1)
                    ->addAttributeToSelect($vSkuAttr)
                    ->addAttributeToFilter($attrFilters);
                $pId = $prodCol->getFirstItem()->getId();
            }
        }
        return $pId;
    }
    public function getVendorSkuByPid($pId, $vId)
    {
        $vSku = null;
        if (Mage::helper('udropship')->isUdmultiActive()) {
            $res = Mage::getSingleton('core/resource');
            $read = $res->getConnection('udropship_read');
            $table = $res->getTableName('udropship_vendor_product');
            $select = $read->select()
                ->from($table, 'vendor_sku')
                ->where('product_id = :product_id and vendor_id = :vendor_id');
            $bind = array(':product_id' => (string)trim($pId), ':vendor_id' => $vId);
            $vSku = $read->fetchOne($select, $bind);
        } else {
            $vSkuAttr = Mage::getStoreConfig('udropship/vendor/vendor_sku_attribute');
            if ($vSkuAttr && $vSkuAttr!='sku') {
                $attrFilters = array(array(
                    'attribute' => 'entity_id',
                    'in' => array($pId)
                ));
                $prodCol = Mage::getModel('catalog/product')->getCollection()->setPage(1,1)
                    ->addAttributeToSelect($vSkuAttr)
                    ->addAttributeToFilter($attrFilters);
                if ($prodCol->getFirstItem()->getId()) {
                    $vSku = $prodCol->getFirstItem()->getData($vSkuAttr);
                }
            }
        }
        return $vSku;
    }

    public function reindexPids($pIds)
    {
        $indexer = Mage::getSingleton('index/indexer');
        $pAction = Mage::getModel('catalog/product_action');
        $idxEvent = Mage::getModel('index/event')
            ->setEntity(Mage_Catalog_Model_Product::ENTITY)
            ->setType(Mage_Index_Model_Event::TYPE_MASS_ACTION)
            ->setDataObject($pAction);
        /* hook to cheat index process to be executed */
        $pAction->setWebsiteIds(array(0));
        $pAction->setProductIds($pIds);
        foreach (array(
            'cataloginventory_stock','catalog_product_attribute','catalog_product_price',
            'tag_summary','catalog_category_product'
        ) as $idxKey
        ) {
            $indexer->getProcessByCode($idxKey)->register($idxEvent)->processEvent($idxEvent);
        }
        Mage::getSingleton('catalogsearch/fulltext')->rebuildIndex(null, $pIds);
        foreach ($pIds as $pId) {
            Mage::getSingleton('catalog/product_flat_indexer')->updateProduct($pId);
            Mage::getSingleton('catalog/url')->refreshProductRewrite($pId);
        }
    }

    public function getWebsiteValues($hash=false, $selector=true)
    {
        $values = array();
        if ($selector) {
            if ($hash) {
                $values[''] = Mage::helper('udropship')->__('* Select category');
            } else {
                $values[] = array('label'=>Mage::helper('udropship')->__('* Select category'), 'value'=>'');
            }
        }
        foreach (Mage::app()->getWebsites() as $website) {
            if ($hash) {
                $values[$website->getId()] = $website->getName();
            } else {
                $values[] = array('label'=>$website->getName(), 'value'=>$website->getId());
            }
        }
        return $values;
    }
    public function getCategoryValues($hash=false, $selector=true)
    {
        $values = array();
        if ($selector) {
            if ($hash) {
                $values[''] = Mage::helper('udropship')->__('* Select category');
            } else {
                $values[] = array('label'=>Mage::helper('udropship')->__('* Select category'), 'value'=>'');
            }
        }
        $cat = Mage::helper('udropship/catalog')->getStoreRootCategory();
        $this->_attachCategoryValues($cat, $values, 0, $hash);
        return $values;
    }
    protected function _attachCategoryValues($cat, &$values, $level, $hash=false)
    {
        $children = $cat->getChildrenCategories();
        if (count($children)>0) {
            if ($hash) {
                $values[$cat->getId()] = $cat->getName();
            } else {
                $values[] = array('label'=>$cat->getName(), 'value'=>$cat->getId(), 'level'=>$level, 'disabled'=>true);
            }
            $level+=1;
            foreach ($children as $child) {
                $this->_attachCategoryValues($child, $values, $level, $hash);
            }
        } else {
            if ($hash) {
                $values[$cat->getId()] = $cat->getName();
            } else {
                $values[] = array('label'=>$cat->getName(), 'value'=>$cat->getId(), 'level'=>$level);
            }
        }
        return $this;
    }

    public function createCfgAttr($cfgProd, $cfgAttrId, $pos)
    {
        $cfgPid = $cfgProd;
        if ($cfgProd instanceof Mage_Catalog_Model_Product) {
            $cfgPid = $cfgProd->getId();
        }
        $res = Mage::getSingleton('core/resource');
        $write = $res->getConnection('catalog_write');
        $superAttrTable = $res->getTableName('catalog/product_super_attribute');
        $superLabelTable = $res->getTableName('catalog/product_super_attribute_label');

        $exists = $write->fetchRow("select sa.*, sal.value_id, sal.value label from {$superAttrTable} sa
            inner join {$superLabelTable} sal on sal.product_super_attribute_id=sa.product_super_attribute_id
            where sa.product_id={$cfgPid} and sa.attribute_id={$cfgAttrId} and sal.store_id=0");
        if (!$exists) {
            $write->insert($superAttrTable, array(
                'product_id' => $cfgPid,
                'attribute_id' => $cfgAttrId,
                'position' => $pos,
            ));
            $saId = $write->lastInsertId($superAttrTable);
            $write->insert($superLabelTable, array(
                'product_super_attribute_id' => $saId,
                'store_id' => 0,
                'use_default' => 1,
                'value' => '',
            ));
        }

        return $this;
    }

    public function getCfgSimpleSkus($cfgPid)
    {
        $res = Mage::getSingleton('core/resource');
        $write = $res->getConnection('catalog_write');
        $t = $res->getTableName('catalog/product_super_link');
        $t2 = $res->getTableName('catalog/product');
        return $write->fetchCol("select {$t2}.sku from {$t} inner join {$t2} on {$t2}.entity_id={$t}.product_id
            where parent_id='{$cfgPid}'");
    }

    public function getCfgSimplePids($cfgPid)
    {
        $res = Mage::getSingleton('core/resource');
        $write = $res->getConnection('catalog_write');
        $t = $res->getTableName('catalog/product_super_link');
        $t2 = $res->getTableName('catalog/product');
        return $write->fetchCol("select {$t2}.entity_id from {$t} inner join {$t2} on {$t2}.entity_id={$t}.product_id
            where parent_id='{$cfgPid}'");
    }

    public function unlinkCfgSimple($cfgPid, $simpleSku, $byPid=false)
    {
        $res = Mage::getSingleton('core/resource');
        $write = $res->getConnection('catalog_write');
        $t = $res->getTableName('catalog/product_super_link');
        $t2 = $res->getTableName('catalog/product_relation');

        $p2 = $byPid ? $simpleSku : Mage::helper('udropship/catalog')->getPidBySku($simpleSku);

        $linkId = $write->fetchCol("select link_id from {$t}
            where parent_id='{$cfgPid}' and product_id='{$p2}'");
        if ($linkId) {
            $write->delete($t,$write->quoteInto("link_id in (?)", $linkId));
            $write->delete($t2, "parent_id={$cfgPid} and child_id={$p2}");
        }
        return $this;
    }

    public function linkCfgSimple($cfgPid, $simpleSku, $byPid=false)
    {
        $res = Mage::getSingleton('core/resource');
        $write = $res->getConnection('catalog_write');
        $t = $res->getTableName('catalog/product_super_link');

        $p2 = $byPid ? $simpleSku : Mage::helper('udropship/catalog')->getPidBySku($simpleSku);

        $linkId = $write->fetchOne("select link_id from {$t} where parent_id='{$cfgPid}' and product_id='{$p2}'");
        if (!$linkId && $p2) {
            $write->insert($t, array('parent_id'=>$cfgPid, 'product_id'=>$p2));
            $relTable = $res->getTableName('catalog/product_relation');
            if (!$write->fetchOne("select parent_id from {$relTable} where parent_id={$cfgPid} and child_id={$p2}")) {
                $write->insert($relTable, array('parent_id'=>$cfgPid, 'child_id'=>$p2));
            }
        }
        return $this;
    }
    public function getSortedCategoryChildren($cId, $orderBy, $active=true, $recursive=false)
    {
        return $this->_getCategoryChildren($cId, $active, $recursive, $orderBy);
    }
}