<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Lead_Attribute
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Lead_Attribute
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
{
    protected $_defaultOptionLabel = "Select Attribute ...";
    protected $_fieldCode       = "marketo_lead_attribute";
    protected $_extraParams     = 'style="width:120px"';

    /**
     * @return HooshMarketing_Marketo_Model_Resource_Attribute_Collection
     */
    protected function _getFields() {
        /** @var HooshMarketing_Marketo_Model_Eav_Attribute $_hooshEav */
        $_hooshEav =  Mage::getSingleton("hoosh_marketo/eav_attribute");
        return $_hooshEav->getLeadAttributes();
    }

    public function _toHtml()
    {
        $this->_initParams();

        if (!$this->getOptions()) {
            $this->_addDefaultOption();
            /** @var HooshMarketing_Marketo_Model_Eav_Attribute $attribute */
            foreach($this->_getFields() as $attribute) {
                $_attributeCode = $attribute->getAttributeCode();

                $this->addOption(
                    $_attributeCode,
                    $_attributeCode
                );
            }
        }

        return parent::_toHtml();
    }
}