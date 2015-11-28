<?php

class HooshMarketing_Marketo_Model_Resource_Opportunity extends HooshMarketing_Marketo_Model_Resource_Attribute_Abstract
{
    public function __construct() {
        $this->_type = HooshMarketing_Marketo_Model_Opportunity::ENTITY;
        parent::__construct();
    }
}