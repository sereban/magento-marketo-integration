<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Render_Magento_Object
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Magento_Object
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
{
    protected $_defaultOptionLabel = "Select Object...";
    protected $_fieldCode       = "magento_object";
    protected $_extraParams     = 'onchange="mapping.hideByObject(this)"  style="width:100px"';

    protected function _getFields() {
        return Mage::getConfig()->getNode("mapping/magento_objects")->asArray();
    }

    public function _toHtml()
    {
        $this->_initParams();

        if (!$this->getOptions()) {
            $this->_addDefaultOption();
            foreach($this->_getFields() as $value => $_data) {
                if(isset($_data["title"]) && isset($_data["category"]))
                    $this->addOption($value, $_data["title"],
                        array(
                                "class"  => $_data["category"],
                                $this->_parentFieldCode => ""
                            ));
            }
        }

        return parent::_toHtml();
    }
}