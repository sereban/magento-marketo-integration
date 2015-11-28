<?php
class HooshMarketing_Marketo_Block_System_Config_Mapping_Category
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Abstract
{
    const MAGENTO_CATEGORY    = "Magento Product Category";
    const MARKETO_SCORE_FIELD = "Marketo Score Attribute";

    //Render columns
    protected $_selectRenderedColumns = array(
        "magento_category_path", "marketo_lead_attribute"
    );

    protected function _prepareToRender()
    {
        $this->addColumn('magento_category_path', array(
            'label' => Mage::helper('hoosh_marketo')->__(self::MAGENTO_CATEGORY),
            'renderer'=> $this->_getRenderer("magento_category_path"),
            'style' => 'width:150px'
        ));
        $this->addColumn('marketo_lead_attribute', array(
            'label' => Mage::helper('hoosh_marketo')->__(self::MARKETO_SCORE_FIELD),
            'renderer'=> $this->_getRenderer("marketo_lead_attribute"),
            'style' => 'width:150px',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('hoosh_marketo')->__($this->_addButtonLabel);
    }
}