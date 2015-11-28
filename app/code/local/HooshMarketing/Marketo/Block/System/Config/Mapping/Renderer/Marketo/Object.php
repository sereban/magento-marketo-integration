<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Render_Magento_Object
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Object
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
{
    protected $_defaultOptionLabel = "Select Object...";
    protected $_fieldCode   = "marketo_object";
    protected $_extraParams = 'onchange="mapping.hideByObject(this)" style="width:100px"';

    public function _toHtml()
    {
        $this->_initParams();
        if (!$this->getOptions()) {
            $this->_addDefaultOption();

            $this->addOption("lead", $this->__("Lead"), array($this->_parentFieldCode => null));
            $this->addOption("opportunity", $this->__("Opportunity"), array($this->_parentFieldCode => null));
        }
        return parent::_toHtml();
    }
}