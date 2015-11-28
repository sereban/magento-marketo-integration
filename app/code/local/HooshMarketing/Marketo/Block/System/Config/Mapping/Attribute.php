<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Attribute
 * @method Varien_Data_Form_Element_Abstract getElement()
 * @method $this setName($name)
 */

class HooshMarketing_Marketo_Block_System_Config_Mapping_Attribute
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Abstract
{
    /** Labels */
    const MARKETO_OBJECT_LABEL  = "Marketo Object";
    const MARKETO_FIELD_LABEL   = "Marketo Attribute";
    const MAGENTO_OBJECT_LABEL  = "Magento Object";
    const MAGENTO_FIELD_LABEL   = "Magento Attribute";

    public function __construct() {
        $this->setTemplate("marketo/core/config/array.phtml");
        $this->setName("hoosh_marketo.mapping.main_block");
        parent::__construct();
    }

    //Render columns
    protected $_selectRenderedColumns = array(
        "marketo_object", "magento_object", "magento_attribute", "marketo_attribute"
    );

    protected function _prepareToRender()
    {
        $this->_columns = array(
            "marketo_object" => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MARKETO_OBJECT_LABEL),
                'renderer' => $this->_getRenderer("marketo_object"),
                'style' => 'width:40px'
            ),
            "marketo_attribute" => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MARKETO_FIELD_LABEL),
                'renderer' => $this->_getRenderer("marketo_attribute"),
                'style' => 'width:60px'
            ),
            "magento_object" => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MAGENTO_OBJECT_LABEL),
                'renderer' => $this->_getRenderer("magento_object"),
                'style' => 'width:40px'
            ),
            "magento_attribute" => array(
                'label' => Mage::helper('hoosh_marketo')->__(self::MAGENTO_FIELD_LABEL),
                'renderer' => $this->_getRenderer("magento_attribute"),
                'style' => 'width:60px'
            )
        );

        parent::_prepareToRender();
    }
}