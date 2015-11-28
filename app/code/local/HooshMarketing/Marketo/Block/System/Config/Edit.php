<?php

class HooshMarketing_Marketo_Block_System_Config_Edit extends Mage_Adminhtml_Block_System_Config_Edit
{
    public function getSaveUrl()
    {
        return $this->getUrl('hooshmarketing/system_config/save', array('_current' => true));
    }
}