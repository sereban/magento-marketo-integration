<?php
class HooshMarketing_Marketo_Model_System_Config_Mapping_Save extends  Mage_Core_Model_Config_Data
{
    protected function _afterLoad() {
        $this->setValue(unserialize($this->getValue()));
    }

    protected function _beforeSave() {
        $this->setValue(serialize($this->getValue()));
    }
}