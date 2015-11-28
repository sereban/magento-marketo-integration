<?php
class HooshMarketing_Marketo_Model_Resource_Opportunity_Attribute_Collection extends Mage_Eav_Model_Resource_Entity_Attribute_Collection
{
    protected function _construct()
    {
        $this->_init('hoosh_marketo/eav_attribute', 'eav/entity_attribute');
    }
}