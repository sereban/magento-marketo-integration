<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Lead_Attribute
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Lead_Attribute_Template
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Marketo_Lead_Attribute
{
    protected $_fieldCode       = "marketo_lead_attribute_template";
    /**
     * @return HooshMarketing_Marketo_Model_Resource_Attribute_Collection
     */
    protected function _getFields() {
        /** @var HooshMarketing_Marketo_Model_Personalize_Calculator $calculator */
        $calculator = Mage::getSingleton("hoosh_marketo/personalize_calculator");
        return ($_categories = $calculator->getScoringCategories()) ? $_categories : new Varien_Data_Collection();
    }

    public function _toHtml()
    {
        $this->_initParams();

        if (!$this->getOptions()) {
            $this->_addDefaultOption();
            /** @var HooshMarketing_Marketo_Model_Eav_Attribute $attribute */
            foreach($this->_getFields() as $marketoVar => $categoryPath) {
                $this->addOption(
                    $marketoVar,
                    $marketoVar
                );
            }
        }

        return parent::_toHtml();
    }
}