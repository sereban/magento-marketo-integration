<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Template
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Template
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Abstract
{
    /** Labels */
    const MAGENTO_STORE                = "Magento Store";
    const MARKETO_TEMPLATE_ATTRIBUTE   = "Marketo Template Attribute";

    protected $_selectRenderedColumns = array(
        "marketo_lead_attribute_template", "magento_store"
    );

    protected function _prepareToRender()
    {
        $this->_columns = array(
            "marketo_lead_attribute_template" => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MARKETO_TEMPLATE_ATTRIBUTE),
                'renderer' => $this->_getRenderer("marketo_lead_attribute_template"),
            ),
            "magento_store"             => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MAGENTO_STORE),
                'renderer' => $this->_getRenderer("magento_store"),
            )
        );

        parent::_prepareToRender();
    }
}