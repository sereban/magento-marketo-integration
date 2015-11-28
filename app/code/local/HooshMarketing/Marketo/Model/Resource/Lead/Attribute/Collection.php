<?php
class HooshMarketing_Marketo_Model_Resource_Lead_Attribute_Collection extends Mage_Eav_Model_Resource_Entity_Attribute_Collection
{
    protected $_entityTypeCode   = 'marketo_lead';

    /**
     * Default attribute entity type code
     *
     * @return string
     */
    protected function _getEntityTypeCode()
    {
        return $this->_entityTypeCode;
    }

    protected function _construct()
    {
        $this->_init('hoosh_marketo/eav_attribute', 'eav/entity_attribute');
    }
}