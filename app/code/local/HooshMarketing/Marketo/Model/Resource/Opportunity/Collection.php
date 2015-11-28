<?php

class HooshMarketing_Marketo_Model_Resource_Opportunity_Collection extends Mage_Eav_Model_Entity_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init("hoosh_marketo/opportunity");
    }

    /**
     * @param int $leadId - can take leadId or take it instead from session
     * @return HooshMarketing_Marketo_Model_Resource_Opportunity_Collection
     */
    public function loadByLead($leadId = null) {
        $leadId = (empty($leadId)) ? $this->_getScopeSession("lead")->getLeadId() : $leadId;
        /* add all opportunities to select */
        $this->addAttributeToSelect("*");

        if(!empty($leadId)) {
            $this->addFieldToFilter("parent_id", $leadId);
        } else {
            $this->setPageSize(0);
        }
        $this->load();
        return $this;
    }

    /**
     * @return HooshMarketing_Marketo_Model_Lead
     */
    protected function _getLeadModel() {
        return Mage::getSingleton("hoosh_marketo/lead");
    }
    /**
     * @param string $scope
     * @return false|Mage_Core_Model_Session_Abstract|HooshMarketing_Marketo_Model_Session_Lead|HooshMarketing_Marketo_Model_Session_Opportunity
     */
    protected function _getScopeSession($scope) {
        return Mage::getModel("hoosh_marketo/session_" . $scope);
    }
}