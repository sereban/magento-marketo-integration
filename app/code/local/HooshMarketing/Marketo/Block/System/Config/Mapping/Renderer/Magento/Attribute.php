<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Render_Magento_Object
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Magento_Attribute
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
{
    protected $_defaultOptionLabel = "Select Attribute...";
    protected $_parentFieldCode    = "magento_object";
    protected $_fieldCode          = "magento_attribute";
    protected $_extraParams        = 'style="width:120px"';

    protected function _getFields() {
        return Mage::getConfig()->getNode("mapping/magento_objects")->asArray();
    }

    public function _toHtml()
    {
        $this->_initParams();

        if (!$this->getOptions()) {
            $this->_addDefaultOption();

            foreach($this->_getFields() as $key => $_data) {
                if(!isset($_data["class"])) //validate fields
                    continue;
                /** @var HooshMarketing_Marketo_Model_Mapping_Classes_Abstract $_instance */
                $_instance = Mage::getSingleton($_data["class"]);

                if($_instance instanceof HooshMarketing_Marketo_Model_Mapping_Classes_Abstract) {
                    $fields = $_instance->getFields();

                    foreach($fields as $code => $field) {
                        $this->addOption($code, $field, array($this->_parentFieldCode => $key));
                    }
                }
            }
        }
        $this->_addCustomOption(); //allows to use input instead of select
        
        return parent::_toHtml() . $this->getCustomInputHtml();
    }


}