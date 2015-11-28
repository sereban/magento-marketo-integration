<?php

class HooshMarketing_Marketo_Model_Resource_Lead extends HooshMarketing_Marketo_Model_Resource_Attribute_Abstract
{
    public function __construct() {
        $this->_type = HooshMarketing_Marketo_Model_Lead::ENTITY;
        parent::__construct();
    }

    /**
     * Retrieve default entity attributes
     *
     * @return array
     */
    protected function _getDefaultAttributes()
    {
        return array('entity_type_id', 'created_at', 'updated_at');
    }
}