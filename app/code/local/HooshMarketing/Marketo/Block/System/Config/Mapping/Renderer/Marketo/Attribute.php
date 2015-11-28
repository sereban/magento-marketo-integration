<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Attribute
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Attribute
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
{
    private $__codes = array(
        HooshMarketing_Marketo_Model_Lead::API_TYPE        => "lead",
        HooshMarketing_Marketo_Model_Opportunity::API_TYPE => "opportunity",
    );

    protected $_defaultOptionLabel = "Select Attribute...";
    protected $_parentFieldCode = "marketo_object";
    protected $_fieldCode       = "marketo_attribute";
    protected $_extraParams     = 'style="width:120px"';

    /**
     * @return HooshMarketing_Marketo_Model_Resource_Attribute_Collection
     */
    protected function _getFields() {
        return Mage::getSingleton("hoosh_marketo/eav_attribute")->getCollection();
    }

    public function _toHtml()
    {
        $this->_initParams();

        if (!$this->getOptions()) {
            $this->_addDefaultOption();
            /** @var HooshMarketing_Marketo_Model_Eav_Attribute $attribute */
            foreach($this->_getFields() as $attribute) {
                $_code = (isset($this->__codes[$attribute->getApiType()])) ?
                    $this->__codes[$attribute->getApiType()] : "none";

                $_attributeCode = $attribute->getAttributeCode();

                $this->addOption(
                    $_attributeCode,
                    $_attributeCode,
                    array(
                        $this->_parentFieldCode => $_code
                    )
                );
            }
        }

        return parent::_toHtml();
    }
}