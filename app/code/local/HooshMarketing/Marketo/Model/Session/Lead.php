<?php

/**
 * Class HooshMarketing_Marketo_Model_Session_Lead
 * @method setLead(HooshMarketing_Marketo_Model_Lead $lead)
 * @method setCacheTime(int $time)
 * @method setAuthorized(boolean $autorize)
 * @method int getCacheTime()
 * @method int getPrioritizeCategory()
 * @method bool getAutorized()
 */
class HooshMarketing_Marketo_Model_Session_Lead extends Mage_Core_Model_Session_Abstract
{
    const COOKIE_CODE = "_mkto_trk";

    public function __construct($data=array())
    {
        parent::__construct($data); 
        $this->init(HooshMarketing_Marketo_Model_Lead::SESSION_NAMESPACE);
    }

    /**
     * @return null|string
     */
    public function getMarketoCookie() {
        /** @var $lead HooshMarketing_Marketo_Model_Lead */
        $lead = $this->getLead();
        if(!$lead->getCookie()) {
            $lead->setCookie($this->_getCookie());
        }

        return $lead->getCookie();
    }

    /**
     * @return null|string
     */
    protected function _getCookie() {
        return isset($_COOKIE[self::COOKIE_CODE]) ? $_COOKIE[self::COOKIE_CODE] : null;
    }

    /**
     * @return null|int
     */
    public function getLeadEntityId() {
        $lead = $this->getData("lead");

        if(!empty($lead)) {
            return $lead->getEntityId();
        }

        return null;
    }

    /**
     * Can update or create lead
     * @return int|null
     */
    public function getLeadId() {
        $id = null;
        if($this->getLead() instanceof Varien_Object) {
            $id = $this->getLead()->getLeadId();
        }

        return $id;
    }

    /**
     * @return HooshMarketing_Marketo_Model_Lead
     */
    public function getLead() {
        if(!$this->getData("lead")) {
            $this->setData("lead", Mage::getSingleton("hoosh_marketo/lead"));
        }

        return $this->getData("lead");
    }

    public function getMergeData() {
        return array(
            "cookie" => $this->getMarketoCookie()
        );
    }
}