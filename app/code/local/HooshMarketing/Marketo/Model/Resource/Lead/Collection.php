<?php

class HooshMarketing_Marketo_Model_Resource_Lead_Collection extends Mage_Eav_Model_Entity_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init("hoosh_marketo/lead");
    }
    /* filter collection by store */
    public function addStoreFilter(Mage_Core_Model_Store $store) {
        /* set alias `e` for store_id */
        $this->getSelect()->where("e.store_id = ?", $store->getId());
        return $this;
    }
}