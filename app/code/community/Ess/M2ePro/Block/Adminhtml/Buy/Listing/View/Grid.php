<?php

/*
 * @copyright  Copyright (c) 2012 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Buy_Listing_View_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /** @var $sellingFormatTemplate Ess_M2ePro_Model_Buy_Template_SellingFormat */
    private $sellingFormatTemplate = NULL;

    private $lockedDataCache = array();

    // ####################################

    public function __construct()
    {
        parent::__construct();

        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        // Initialization block
        //------------------------------
        $this->setId('buyListingViewGrid'.$listingData['id']);
        //------------------------------

        // Set default values
        //------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //------------------------------

        $this->sellingFormatTemplate = Mage::helper('M2ePro/Component_Buy')
            ->getObject('Template_SellingFormat',
            $listingData['template_selling_format_id']);
    }

    // ####################################

    protected function _prepareCollection()
    {
        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        // Get collection products in listing
        //--------------------------------
        $collection = Mage::helper('M2ePro/Component_Buy')->getCollection('Listing_Product');
        $collection->getSelect()->distinct();
        $collection->getSelect()->where("`main_table`.`listing_id` = ?",(int)$listingData['id']);
        //--------------------------------

        // Communicate with magento product table
        //--------------------------------
        $dbSelect = Mage::getResourceModel('core/config')->getReadConnection()
            ->select()
            ->from(Mage::getSingleton('core/resource')
                ->getTableName('catalog_product_entity_varchar'),
            new Zend_Db_Expr('MAX(`store_id`)'))
            ->where("`entity_id` = `main_table`.`product_id`")
            ->where("`attribute_id` = `ea`.`attribute_id`")
            ->where("`store_id` = 0 OR `store_id` = ?",(int)$listingData['store_id']);

        $collection->getSelect()
        //->join(array('csi'=>Mage::getSingleton('core/resource')
//                                                ->getTableName('cataloginventory_stock_item')),
//                                '(csi.product_id = `main_table`.product_id)',array('qty'))
            ->join(array('cpe'=>Mage::getSingleton('core/resource')->getTableName('catalog_product_entity')),
            '(cpe.entity_id = `main_table`.product_id)',array('magento_sku'=>'sku'))
            ->join(array('cisi'=>Mage::getSingleton('core/resource')
                ->getTableName('cataloginventory_stock_item')),
            '(cisi.product_id = `main_table`.product_id AND cisi.stock_id = 1)',
            array('is_in_stock'))
            ->join(array('cpev'=>Mage::getSingleton('core/resource')
                ->getTableName('catalog_product_entity_varchar')),
            "( `cpev`.`entity_id` = `main_table`.product_id AND cpev.store_id = ("
                .$dbSelect->__toString()
                ."))",
            array('value'))
            ->join(array('ea'=>Mage::getSingleton('core/resource')->getTableName('eav_attribute')),
            '(`cpev`.`attribute_id` = `ea`.`attribute_id` AND `ea`.`attribute_code` = \'name\')',
            array());

        //--------------------------------

        //exit($collection->getSelect()->__toString());

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => Mage::helper('M2ePro')->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'product_id',
            'filter_index' => 'main_table.product_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('M2ePro')->__('Product Title / SKU'),
            'align'     => 'left',
            //'width'     => '300px',
            'type'      => 'text',
            'index'     => 'value',
            'filter_index' => 'cpev.value',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('stock_availability',
            array(
                'header'=> Mage::helper('M2ePro')->__('Stock Availability'),
                'width' => '100px',
                'index' => 'is_in_stock',
                'filter_index' => 'cisi.is_in_stock',
                'type'  => 'options',
                'sortable'  => false,
                'options' => array(
                    1 => Mage::helper('M2ePro')->__('In Stock'),
                    0 => Mage::helper('M2ePro')->__('Out of Stock')
                ),
                'frame_callback' => array($this, 'callbackColumnStockAvailability')
            ));

        $this->addColumn('sku', array(
            'header' => Mage::helper('M2ePro')->__('Reference ID'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'sku',
            'filter_index' => 'second_table.sku',
            'frame_callback' => array($this, 'callbackColumnSku')
        ));

        $this->addColumn('general_id', array(
            'header' => Mage::helper('M2ePro')->__('Rakuten.com SKU'),
            'align' => 'left',
            'width' => '80px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'second_table.general_id',
            'frame_callback' => array($this, 'callbackColumnGeneralId')
        ));

        $this->addColumn('online_qty', array(
            'header' => Mage::helper('M2ePro')->__('Rakuten.com QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'second_table.online_qty',
            'frame_callback' => array($this, 'callbackColumnAvailableQty')
        ));

        $this->addColumn('online_price', array(
            'header' => Mage::helper('M2ePro')->__('Rakuten.com Price'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'second_table.online_price',
            'frame_callback' => array($this, 'callbackColumnPrice')
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('M2ePro')->__('Status'),
            'width' => '125px',
            'index' => 'status',
            'filter_index' => 'main_table.status',
            'type' => 'options',
            'sortable' => false,
            'options' => array(
                Ess_M2ePro_Model_Listing_Product::STATUS_UNKNOWN => Mage::helper('M2ePro')->__('Unknown'),
                Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED => Mage::helper('M2ePro')->__('Not Listed'),
                Ess_M2ePro_Model_Listing_Product::STATUS_LISTED => Mage::helper('M2ePro')->__('Active'),
                Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED => Mage::helper('M2ePro')->__('Inactive'),
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
        ));

        $this->addColumn('start_date', array(
            'header' => Mage::helper('M2ePro')->__('Start Date'),
            'align' => 'right',
            'width' => '130px',
            'type' => 'datetime',
            'format' => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'index' => 'start_date',
            'filter_index' => 'second_table.start_date',
            'frame_callback' => array($this, 'callbackColumnStartDate')
        ));

        $this->addColumn('end_date', array(
            'header' => Mage::helper('M2ePro')->__('End Date'),
            'align' => 'right',
            'width' => '130px',
            'type' => 'datetime',
            'format' => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'index' => 'end_date',
            'filter_index' => 'second_table.end_date',
            'frame_callback' => array($this, 'callbackColumnEndDate')
        ));

        if (Mage::helper('M2ePro/Server')->isDeveloper()) {
            $this->addColumn('developer_action', array(
                'header'    => Mage::helper('M2ePro')->__('Actions'),
                'align'     => 'left',
                'width'     => '100px',
                'type'      => 'text',
                'index'     => 'value',
                'filter'    => false,
                'sortable'  => false,
                'filter_index' => 'cpev.value',
                'frame_callback' => array($this, 'callbackColumnDeveloperAction')
            ));
        }

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        //--------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        //--------------------------------

        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('list', array(
            'label'    => Mage::helper('M2ePro')->__('List Item(s)'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('revise', array(
            'label'    => Mage::helper('M2ePro')->__('Revise Item(s)'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('relist', array(
            'label'    => Mage::helper('M2ePro')->__('Relist Item(s)'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('stop', array(
            'label'    => Mage::helper('M2ePro')->__('Stop Item(s)'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('stop_and_remove', array(
            'label'    => Mage::helper('M2ePro')->__('Stop on Channel / Remove from Listing'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('move_to_listing', array(
            'label'    => Mage::helper('M2ePro')->__('Move Item(s) to Another Listing'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('map_to_general_id', array(
            'label'    => Mage::helper('M2ePro')->__('Assign Rakuten.com SKU to Item(s)'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('unmap_general_id', array(
            'label'    => Mage::helper('M2ePro')->__('Unassign Rakuten.com SKU'),
            'url'      => '',
            'confirm'  => Mage::helper('M2ePro')->__('Are you sure?')
        ));
        //--------------------------------

        return parent::_prepareMassaction();
    }

    // ####################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        $listingData = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        $productId = (int)$row->getData('product_id');
        $storeId = (int)$listingData['store_id'];

        $withoutImageHtml = '<a href="'
            .$this->getUrl('adminhtml/catalog_product/edit',
                array('id' => $productId))
            .'" target="_blank">'.$productId.'</a>';

        $showProductsThumbnails = (bool)(int)Mage::helper('M2ePro/Module')->getConfig()
            ->getGroupValue('/products/settings/',
            'show_thumbnails');
        if (!$showProductsThumbnails) {
            return $withoutImageHtml;
        }

        /** @var $magentoProduct Ess_M2ePro_Model_Magento_Product */
        $magentoProduct = Mage::getModel('M2ePro/Magento_Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $imageUrlResized = $magentoProduct->getThumbnailImageLink();
        if (is_null($imageUrlResized)) {
            return $withoutImageHtml;
        }

        $imageHtml = $productId.'<hr style="border: 1px solid silver; border-bottom: none;"><img src="'.
            $imageUrlResized.'" />';
        $withImageHtml = str_replace('>'.$productId.'<','>'.$imageHtml.'<',$withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        if (strlen($value) > 60) {
            $value = substr($value, 0, 60) . '...';
        }

        $value = '<span>'.Mage::helper('M2ePro')->escapeHtml($value).'</span>';

        $tempSku = $row->getData('magento_sku');
        is_null($tempSku)
            && $tempSku = Mage::getModel('M2ePro/Magento_Product')->setProductId($row->getData('product_id'))->getSku();

        $value .= '<br/><strong>'.Mage::helper('M2ePro')->__('SKU')
            .':</strong> '.Mage::helper('M2ePro')->escapeHtml($tempSku);

        return $value;
    }

    public function callbackColumnStockAvailability($value, $row, $column, $isExport)
    {
        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">'.$value.'</span>';
        }

        return $value;
    }

    public function callbackColumnSku($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return Mage::helper('M2ePro')->__('N/A');
        }
        return $value;
    }

    public function callbackColumnGeneralId($generalId, $row, $column, $isExport)
    {
        if (empty($generalId)) {
            return $this->getGeneralIdColumnValueEmptyGeneralId($row);
        }

        return $this->getGeneralIdColumnValueNotEmptyGeneralId($row);
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if ((int)$row->getData('status') == Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED) {
            if (is_null($value) || $value === '') {
                return Mage::helper('M2ePro')->__('N/A');
            }
        } else {
            if (is_null($value) || $value === '') {
                return '<i style="color:gray;">receiving...</i>';
            }
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ((int)$row->getData('status') == Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED) {
            if (is_null($value) || $value === '') {
                return Mage::helper('M2ePro')->__('N/A');
            }
        } else {
            if (is_null($value) || $value === '') {
                return '<i style="color:gray;">receiving...</i>';
            }
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        return Mage::app()->getLocale()->currency('USD')->toCurrency($value);
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('status')) {

            case Ess_M2ePro_Model_Listing_Product::STATUS_UNKNOWN:
            case Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED:
                $value = '<span style="color: gray;">' . $value . '</span>';
                break;

            case Ess_M2ePro_Model_Listing_Product::STATUS_LISTED:
                $value = '<span style="color: green;">' . $value . '</span>';
                break;

            case Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED:
                $value = '<span style="color: red;">'.$value.'</span>';
                break;

            default:
                break;
        }

        $value .= $this->getViewLogIconHtml($row->getId());

        $tempLocks = $this->getLockedData($row);
        $tempLocks = $tempLocks['object_locks'];

        foreach ($tempLocks as $lock) {

            switch ($lock->getTag()) {

                case 'new_sku_action':
                    $value .= '<br><span style="color: #605fff">[New SKU In Progress...]</span>';
                    break;

                case 'list_action':
                    $value .= '<br><span style="color: #605fff">[List In Progress...]</span>';
                    break;

                case 'relist_action':
                    $value .= '<br><span style="color: #605fff">[Relist In Progress...]</span>';
                    break;

                case 'revise_action':
                    $value .= '<br><span style="color: #605fff">[Revise In Progress...]</span>';
                    break;

                case 'stop_action':
                    $value .= '<br><span style="color: #605fff">[Stop In Progress...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $value .= '<br><span style="color: #605fff">[Stop And Remove In Progress...]</span>';
                    break;

                default:
                    break;
            }
        }

        return $value;
    }

    public function callbackColumnStartDate($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return Mage::helper('M2ePro')->__('N/A');
        }

        return $value;
    }

    public function callbackColumnEndDate($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return Mage::helper('M2ePro')->__('N/A');
        }

        return $value;
    }

    public function callbackColumnDeveloperAction($value, $row, $column, $isExport)
    {
        $value = '';

        if ($row->getData('status') == Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED) {
            $value != '' && $value .= '<br/>';
            $value .= '<a href="javascript:void(0);" onclick="ListingItemGridHandlerObj.selectByRowId('
                .$row->getData('id').'); ListingActionHandlerObj.runListProducts();">List</a>';
        }

        if ($row->getData('status') == Ess_M2ePro_Model_Listing_Product::STATUS_LISTED) {
            $value != '' && $value .= '<br/>';
            $value .= '<a href="javascript:void(0);" onclick="ListingItemGridHandlerObj.selectByRowId('
                .$row->getData('id').'); ListingActionHandlerObj.runReviseProducts();">Revise</a>';
        }

        if ($row->getData('status') != Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED &&
            $row->getData('status') != Ess_M2ePro_Model_Listing_Product::STATUS_LISTED) {
            $value != '' && $value .= '<br/>';
            $value .= '<a href="javascript:void(0);" onclick="ListingItemGridHandlerObj.selectByRowId('
                .$row->getData('id').'); ListingActionHandlerObj.runRelistProducts();">Relist</a>';
        }

        if ($row->getData('status') == Ess_M2ePro_Model_Listing_Product::STATUS_LISTED) {
            $value != '' && $value .= '<br/>';
            $value .= '<a href="javascript:void(0);" onclick="ListingItemGridHandlerObj.selectByRowId('
                .$row->getData('id').'); ListingActionHandlerObj.runStopProducts();">Stop</a>';
        }

        $value != '' && $value .= '<br/>';
        $value .= '<a href="javascript:void(0);" onclick="ListingItemGridHandlerObj.selectByRowId('
            .$row->getData('id').'); ListingActionHandlerObj.runStopAndRemoveProducts();">Stop And Remove</a>';

        $value .= '<br>' . $row->getId();

        return Mage::helper('M2ePro')->__($value);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('cpev.value LIKE ? OR cpe.sku LIKE ?', '%'.$value.'%');
    }

    // ############################################

    private function getGeneralIdColumnValueEmptyGeneralId($row)
    {
        // ---------------------------------
        if ((int)$row->getData('status') != Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED) {
            return <<<HTML
<i style="color:gray;">receiving...</i>
HTML;
        }
        // ---------------------------------

        // ---------------------------------
        $iconPath = $this->getSkinUrl('M2ePro').'/images/search_statuses/';
        // ---------------------------------

        // ---------------------------------
        $lpId = $row->getData('listing_product_id');

        $productTitle = Mage::helper('M2ePro')->escapeHtml($row->getData('value'));
        if (strlen($productTitle) > 60) {
            $productTitle = substr($productTitle, 0, 60) . '...';
        }
        $productTitle = Mage::helper('M2ePro')->escapeJs($productTitle);
        // ---------------------------------

        // ---------------------------------
        $generalIdSearchSuggestData = $row->getData('general_id_search_suggest_data');

        if (!is_null($generalIdSearchSuggestData)) {
            $generalIdSearchSuggestData = @json_decode($generalIdSearchSuggestData,true);
        }
        // ---------------------------------

        if (isset($generalIdSearchSuggestData['message'])) {

            $tip = Mage::helper('M2ePro')->escapeHtml($generalIdSearchSuggestData['message']);
            $tip = Mage::helper('M2ePro')->escapeJs($tip);

            $iconSrc = $iconPath.'error.png';

            return <<<HTML
N/A &nbsp;
<a href="javascript:;" title="{$tip}"
    onclick="BuyListingProductSearchHandlerObj.openPopUp(0,'{$productTitle}',{$lpId},'{$tip}');">
    <img src="{$iconSrc}" alt="" width="16" height="16">
</a>
HTML;
        }

        if (!empty($generalIdSearchSuggestData)) {

            $tip = Mage::helper('M2ePro')->__('Choose Rakuten.com SKU from the list');
            $iconSrc = $iconPath.'list.png';

            return <<<HTML
N/A &nbsp;
<a href="javascript:;" title="{$tip}"
   onclick="BuyListingProductSearchHandlerObj.openPopUp(1,'{$productTitle}',{$lpId})">
    <img src="{$iconSrc}" alt="" width="16" height="16">
</a>
HTML;
        }

        $tip = Mage::helper('M2ePro')->__('Search for Rakuten.com SKU');
        $iconSrc = $iconPath.'search.png';

        return <<<HTML
N/A &nbsp;
<a href="javascript:;" title="{$tip}"
   onclick="BuyListingProductSearchHandlerObj.openPopUp(0,'{$productTitle}',{$lpId});">
    <img src="{$iconSrc}" alt="" width="16" height="16">
</a>
HTML;
    }

    private function getGeneralIdColumnValueNotEmptyGeneralId($row)
    {
        $generalId = $row->getData('general_id');

        $url = Mage::helper('M2ePro/Component_Buy')->getItemUrl($generalId);

        if ((int)$row->getData('status') != Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED) {
            $conditionTitle = '';
            switch ((int)$row->getData('condition')) {
                case Ess_M2ePro_Model_Buy_Template_General::CONDITION_NEW:
                    $conditionTitle = Mage::helper('M2ePro')->__('New');
                    break;
                case Ess_M2ePro_Model_Buy_Template_General::CONDITION_USED_LIKE_NEW:
                    $conditionTitle = Mage::helper('M2ePro')->__('Used - Like New');
                    break;
                case Ess_M2ePro_Model_Buy_Template_General::CONDITION_USED_VERY_GOOD:
                    $conditionTitle = Mage::helper('M2ePro')->__('Used - Very Good');
                    break;
                case Ess_M2ePro_Model_Buy_Template_General::CONDITION_USED_GOOD:
                    $conditionTitle = Mage::helper('M2ePro')->__('Used - Good');
                    break;
                case Ess_M2ePro_Model_Buy_Template_General::CONDITION_USED_ACCEPTABLE:
                    $conditionTitle = Mage::helper('M2ePro')->__('Used - Acceptable');
                    break;
                case Ess_M2ePro_Model_Buy_Template_General::CONDITION_REFURBISHED:
                    $conditionTitle = Mage::helper('M2ePro')->__('Refurbished');
                    break;
            }
            return <<<HTML
<a href="{$url}" target="_blank">{$generalId}</a><br/>
<span style="color:gray;">{$conditionTitle}</span>
HTML;
        }

        $iconPath = $this->getSkinUrl('M2ePro').'/images/search_statuses/';

        $generalIdSearchStatus = $row->getData('general_id_search_status');

        if (Ess_M2ePro_Model_Buy_Listing_Product::GENERAL_ID_SEARCH_STATUS_SET_AUTOMATIC == $generalIdSearchStatus) {

            $tip = Mage::helper('M2ePro')->__('Rakuten.com was found automatically');

            $text = <<<HTML
<a href="{$url}" target="_blank" title="{$tip}" style="color:#40AADB;">{$generalId}</a>
HTML;

        } else {

            $text = <<<HTML
<a href="{$url}" target="_blank">{$generalId}</a>
HTML;

        }

        // ---------------------------------
        $hasInActionLock = $this->getLockedData($row);
        $hasInActionLock = $hasInActionLock['in_action'];
        // ---------------------------------

        if ($hasInActionLock) {
            return $text;
        }

        $tip = Mage::helper('M2ePro')->__('Unassign Rakuten.com SKU');
        $iconSrc = $iconPath.'unassign.png';

        $id = $row->getData('listing_product_id');

        $text .= <<<HTML
<a href="javascript:;" onclick="BuyListingProductSearchHandlerObj.showUnmapFromGeneralIdPrompt({$id});" title="{$tip}">
    <img src="{$iconSrc}" width="16" height="16"/>
</a>
HTML;

        return $text;
    }

    // ############################################

    public function getViewLogIconHtml($listingProductId)
    {
        // Get last messages
        //--------------------------
        /** @var $connRead Varien_Db_Adapter_Pdo_Mysql */
        $connRead = Mage::getSingleton('core/resource')->getConnection('core_read');

        $dbSelect = $connRead->select()
            ->from(Mage::getResourceModel('M2ePro/Listing_Log')->getMainTable(),
            array('action_id','action','type','description','create_date','initiator'))
            ->where('`listing_product_id` = ?',(int)$listingProductId)
            ->where('`action_id` IS NOT NULL')
            ->order(array('id DESC'))
            ->limit(30);

        $logRows = $connRead->fetchAll($dbSelect);
        //--------------------------

        // Get grouped messages by action_id
        //--------------------------
        $actionsRows = array();
        $tempActionRows = array();
        $lastActionId = false;

        foreach ($logRows as $row) {

            $row['description'] = Mage::helper('M2ePro')->escapeHtml($row['description']);
            $row['description'] = Mage::getModel('M2ePro/Log_Abstract')->decodeDescription($row['description']);

            if ($row['action_id'] !== $lastActionId) {
                if (count($tempActionRows) > 0) {
                    $actionsRows[] = array(
                        'type' => $this->getMainTypeForActionId($tempActionRows),
                        'date' => $this->getMainDateForActionId($tempActionRows),
                        'action' => $this->getActionForAction($tempActionRows[0]),
                        'initiator' => $this->getInitiatorForAction($tempActionRows[0]),
                        'items' => $tempActionRows
                    );
                    $tempActionRows = array();
                }
                $lastActionId = $row['action_id'];
            }
            $tempActionRows[] = $row;
        }

        if (count($tempActionRows) > 0) {
            $actionsRows[] = array(
                'type' => $this->getMainTypeForActionId($tempActionRows),
                'date' => $this->getMainDateForActionId($tempActionRows),
                'action' => $this->getActionForAction($tempActionRows[0]),
                'initiator' => $this->getInitiatorForAction($tempActionRows[0]),
                'items' => $tempActionRows
            );
        }

        if (count($actionsRows) <= 0) {
            return '';
        }

        $actionsRows = array_slice($actionsRows,0,3);
        $lastActionRow = $actionsRows[0];
        //--------------------------

        // Get log icon
        //--------------------------
        $icon = 'normal';
        $iconTip = Mage::helper('M2ePro')->__('Last action was completed successfully.');

        if ($lastActionRow['type'] == Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR) {
            $icon = 'error';
            $iconTip = Mage::helper('M2ePro')->__('Last action was completed with error(s).');
        }
        if ($lastActionRow['type'] == Ess_M2ePro_Model_Log_Abstract::TYPE_WARNING) {
            $icon = 'warning';
            $iconTip = Mage::helper('M2ePro')->__('Last action was completed with warning(s).');
        }

        $iconSrc = $this->getSkinUrl('M2ePro').'/images/log_statuses/'.$icon.'.png';
        //--------------------------

        $html = '<span style="float:right;">';
        $html .= '<a title="'
            .$iconTip
            .'" id="lpv_grid_help_icon_open_'
            .(int)$listingProductId
            .'" href="javascript:void(0);" onclick="ListingItemGridHandlerObj.viewItemHelp('
            .(int)$listingProductId.',\''
            .base64_encode(json_encode($actionsRows))
            .'\');"><img src="'.$iconSrc.'" /></a>';
        $html .= '<a title="'.$iconTip.'" id="lpv_grid_help_icon_close_'
            .(int)$listingProductId
            .'" style="display:none;" href="javascript:void(0);" onclick="ListingItemGridHandlerObj.hideItemHelp('
            .(int)$listingProductId.');"><img src="'.$iconSrc.'" /></a>';
        $html .= '</span>';

        return $html;
    }

    public function getActionForAction($actionRows)
    {
        $string = '';

        switch ($actionRows['action']) {
            case Ess_M2ePro_Model_Listing_Log::ACTION_NEW_SKU_PRODUCT_ON_COMPONENT:
                $string = Mage::helper('M2ePro')->__('New SKU');
                break;
            case Ess_M2ePro_Model_Listing_Log::ACTION_LIST_PRODUCT_ON_COMPONENT:
                $string = Mage::helper('M2ePro')->__('List');
                break;
            case Ess_M2ePro_Model_Listing_Log::ACTION_RELIST_PRODUCT_ON_COMPONENT:
                $string = Mage::helper('M2ePro')->__('Relist');
                break;
            case Ess_M2ePro_Model_Listing_Log::ACTION_REVISE_PRODUCT_ON_COMPONENT:
                $string = Mage::helper('M2ePro')->__('Revise');
                break;
            case Ess_M2ePro_Model_Listing_Log::ACTION_STOP_PRODUCT_ON_COMPONENT:
                $string = Mage::helper('M2ePro')->__('Stop');
                break;
            case Ess_M2ePro_Model_Listing_Log::ACTION_STOP_AND_REMOVE_PRODUCT:
                $string = Mage::helper('M2ePro')->__('Stop on Channel / Remove from Listing');
                break;
            case Ess_M2ePro_Model_Listing_Log::ACTION_CHANGE_STATUS_ON_CHANNEL:
                $string = Mage::helper('M2ePro')->__('Status Change');
                break;
            case Ess_M2ePro_Model_Listing_Log::ACTION_INVENTORY_SYNCHRONIZATION:
                $string = Mage::helper('M2ePro')->__('Inventory Synchronization');
                break;
        }

        return $string;
    }

    public function getInitiatorForAction($actionRows)
    {
        $string = '';

        switch ((int)$actionRows['initiator']) {
            case Ess_M2ePro_Model_Log_Abstract::INITIATOR_UNKNOWN:
                $string = '';
                break;
            case Ess_M2ePro_Model_Log_Abstract::INITIATOR_USER:
                $string = Mage::helper('M2ePro')->__('Manual');
                break;
            case Ess_M2ePro_Model_Log_Abstract::INITIATOR_EXTENSION:
                $string = Mage::helper('M2ePro')->__('Automatic');
                break;
        }

        return $string;
    }

    public function getMainTypeForActionId($actionRows)
    {
        $type = Ess_M2ePro_Model_Log_Abstract::TYPE_SUCCESS;

        foreach ($actionRows as $row) {
            if ($row['type'] == Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR) {
                $type = Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR;
                break;
            }
            if ($row['type'] == Ess_M2ePro_Model_Log_Abstract::TYPE_WARNING) {
                $type = Ess_M2ePro_Model_Log_Abstract::TYPE_WARNING;
            }
        }

        return $type;
    }

    public function getMainDateForActionId($actionRows)
    {
        $format = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
        return Mage::app()->getLocale()->date(strtotime($actionRows[0]['create_date']))->toString($format);
    }

    // ####################################

    public function getGridUrl()
    {
        return $this->getUrl('*/adminhtml_buy_listing/viewGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    // ####################################

    public function _toHtml()
    {
        $javascriptsMain = <<<JAVASCRIPT
<script type="text/javascript">

    if (typeof ListingItemGridHandlerObj != 'undefined') {
        ListingItemGridHandlerObj.afterInitPage();
    }

    Event.observe(window, 'load', function() {
        setTimeout(function() {
            ListingItemGridHandlerObj.afterInitPage();
        }, 350);
    });

</script>
JAVASCRIPT;

        return parent::_toHtml().$javascriptsMain;
    }

    // ####################################

    private function getLockedData($row)
    {
        $listingProductId = $row->getData('listing_product_id');
        if (!isset($this->lockedDataCache[$listingProductId])) {
            $objectLocks = $row->getObjectLocks();
            $tempArray = array(
                'object_locks' => $objectLocks,
                'in_action' => !empty($objectLocks),
            );
            $this->lockedDataCache[$listingProductId] = $tempArray;
        }

        return $this->lockedDataCache[$listingProductId];
    }

    // ####################################
}
