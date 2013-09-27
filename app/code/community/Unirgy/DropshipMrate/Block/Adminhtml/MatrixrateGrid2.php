<?php

class Unirgy_DropshipMrate_Block_Adminhtml_MatrixrateGrid2 extends Unirgy_DropshipMrate_Block_Adminhtml_MatrixrateGrid
{
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('udmrate/matrixrate2_collection');
        $collection->setConditionFilter($this->getConditionName())
            ->setWebsiteFilter($this->getWebsiteId());

        $this->setCollection($collection);

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }
}