<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

abstract class Ess_M2ePro_Block_Adminhtml_Component_Grid_Container extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    // ########################################

    abstract protected function getEbayNewUrl();

    abstract protected function getAmazonNewUrl();

    abstract protected function getBuyNewUrl();

    // ########################################

    protected function getAddButtonOnClickAction()
    {
        /** @var $helper Ess_M2ePro_Helper_Component */
        $helper = Mage::helper('M2ePro/Component');
        $action = '';

        if (count($helper->getActiveComponents()) == 1) {
            if (Mage::helper('M2ePro/Component_Ebay')->isActive()) {
                $url = $this->getEbayNewUrl();
            } elseif (Mage::helper('M2ePro/Component_Amazon')->isActive()) {
                $url = $this->getAmazonNewUrl();
            } else {
                $url = $this->getBuyNewUrl();
            }

            $action = 'setLocation(\''.$url.'\');';
        }

        return $action;
    }

    // ########################################

    public function _toHtml()
    {
        return $this->getAddButtonJavascript() . parent::_toHtml();
    }

    // ----------------------------------------

    protected function getAddButtonJavascript()
    {
        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) < 2) {
            return '';
        }

        $tempDropDownHtml = Mage::helper('M2ePro')->escapeJs($this->getAddButtonDropDownHtml());

        return <<<JAVASCRIPT
<script type="text/javascript">

    Event.observe(window, 'load', function() {
        $$('.add-button-drop-down')[0].innerHTML += '{$tempDropDownHtml}';
        DropDownObj = new DropDown();
        DropDownObj.prepare($$('.add-button-drop-down')[0]);
    });

</script>
JAVASCRIPT;
    }

    protected function getAddButtonDropDownHtml()
    {
        $activeComponents = Mage::helper('M2ePro/Component')->getActiveComponents();

        $html = '<ul style="display: none;">';
        foreach ($activeComponents as $component) {
            $newUrlMethod = 'get'.ucfirst($component).'NewUrl';
            $componentHelper = 'Ess_M2ePro_Helper_Component_'.ucfirst($component);

            $html .= '<li href="'.$this->$newUrlMethod().'">'.constant($componentHelper.'::TITLE').'</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    // ########################################
}