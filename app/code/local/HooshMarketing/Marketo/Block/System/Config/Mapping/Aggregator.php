<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Aggregator
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Aggregator
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Abstract
{
    /** Labels */
    const MAGENTO_FIELD_NAME = "Magento Field Name";
    const MARKETO_VARIABLE   = "Marketo Variable";
    //Render columns
    protected $_selectRenderedColumns = array(
        "marketo_lead_attribute"
    );

    protected function _prepareToRender()
    {
        $this->_columns = array(
            "magento_field_name" => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MAGENTO_FIELD_NAME)
            ),
            "marketo_lead_attribute"             => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MARKETO_VARIABLE),
                'renderer' => $this->_getRenderer("marketo_lead_attribute"),
            )
        );

        parent::_prepareToRender();
    }
}