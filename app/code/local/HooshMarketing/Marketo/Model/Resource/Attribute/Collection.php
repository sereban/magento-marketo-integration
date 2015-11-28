<?php
class HooshMarketing_Marketo_Model_Resource_Attribute_Collection extends Mage_Eav_Model_Resource_Entity_Attribute_Collection
{
    protected function _construct()
    {
        $this->_init('hoosh_marketo/eav_attribute', 'eav/entity_attribute');
    }

    protected function _initSelect()
    {
        $entityTypeIdLead        = (int)Mage::getModel('eav/entity')->setType( HooshMarketing_Marketo_Model_Lead::ENTITY)->getTypeId();
        $entityTypeIdOpportunity = (int)Mage::getModel('eav/entity')->setType( HooshMarketing_Marketo_Model_Opportunity::ENTITY)->getTypeId();

        $this->getSelect()
            ->from(array('main_table' => $this->getResource()->getMainTable()))
            ->join(
                array('additional_table' => $this->getTable('hoosh_marketo/eav_attribute_additional_table')),
                'additional_table.attribute_id = main_table.attribute_id'
            )
            ->where('main_table.entity_type_id IN(?,?)', array($entityTypeIdLead, $entityTypeIdOpportunity));
        return $this;
    }
}