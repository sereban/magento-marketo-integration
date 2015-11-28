<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Render_Magento_Object
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Magento_Store
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
{
    protected $_defaultOptionLabel = "Select Store...";
    protected $_fieldCode       = "magento_store";
    protected $_extraParams     = 'style="width:100px"';

    protected function _getFields() {
        return Mage::app()->getStores();
    }

    public function _toHtml()
    {
        $this->_initParams();

        if (!$this->getOptions()) {
            $this->_addDefaultOption();
            /** @var Mage_Core_Model_Store $store */
            foreach($this->_getFields() as $store) {
                $this->addOption($store->getCode(), $store->getName());
            }
        }

        return parent::_toHtml();
    }
}