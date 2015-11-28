<?php
class HooshMarketing_Marketo_Model_Session_Opportunity extends Mage_Core_Model_Session
{
    public function __construct($data=array())
    {
        parent::__construct($data);
        $this->init(HooshMarketing_Marketo_Model_Opportunity::SESSION_NAMESPACE);
    }

    /**
     * @return HooshMarketing_Marketo_Model_Resource_Opportunity_Collection
     */
    public function getOpportunities() {
        $opportunities = $this->getData("opportunities");
        if(!$opportunities instanceof HooshMarketing_Marketo_Model_Resource_Opportunity_Collection) $this->_initOpportunities();
        return $opportunities;
    }

    public function addOpportunity(HooshMarketing_Marketo_Model_Opportunity $opportunity) {
        $opportunities = $this->getOpportunities();
        $opportunities->addItem($opportunity);
        $this->_setOpportunities($opportunities);
    }

    protected function _setOpportunities($collection) {
        $this->setData("opportunities", $collection);
    }

    /**
     * add opportunity collection
     * set collection to session
     */
    protected function _initOpportunities() {
         $this->_setOpportunities($this->_getEntityModel()->getCollection());
    }

    /**
     * @return HooshMarketing_Marketo_Model_Opportunity
     */
    protected function _getEntityModel() {
        return Mage::getSingleton("hoosh_marketo/opportunity");
    }
}