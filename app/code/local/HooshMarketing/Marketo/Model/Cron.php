<?php

class HooshMarketing_Marketo_Model_Cron extends HooshMarketing_Marketo_Model_Abstract
{
    const INACTIIVTY_DEFAULT_FROM_TIME = 800;
    const INACTIIVTY_DEFAULT_TO_TIME   = 60;
    const START                        = "start";
    const END                          = "end";
    const MAGENTO_INACTIVITY_TIME      = "Magento_Inactivity_Time";
    const SYNC_LEAD_LAG                = 86400;
    const CRON_JOB_ALL_DATA            = "hoosh_marketo_send_all_data";
    const DO_NOT_UPDATE_TIME           = "do_not_update_time";

    /**
     * log lead activity -> by updated last_activity_time attribute
     * and last_product_id attribute
     * @return bool
     * @throws Exception
     */
    public function logLeadInactivity() {
        if (!$this->_getHelper()->getModuleStatus())  return false;

        $this->_getLeadModel()->getLoadedByCookie();
        /* check if lead loaded by cookie exists in database */

        if($this->_getLeadModel()->getId()) {
            /* set product id to databse if we are on  product page */
            if($this->_getCurrentProduct())
                $this->_getLeadModel()->setLastProductId($this->_getCurrentProduct()->getId());

            /* setting last activity time */
            $this->_getLeadModel()->setLastActivityTime($this->_getDateTimeHelper()->toTimeString()); //set time string
            $this->_getLeadModel()->save();
        }

        return true;
    }



    /* only prepared to sync inactiivty leads */

    public function syncInactivityLeads() {
        $syncedLeads = array();
        $leadQty     = 0; //uses as counter
        $leadIds     = array();

        foreach(Mage::getSingleton("core/store")->getCollection() as $store) {
            /* @var $store Mage_Core_Model_Store */
            /* @var $storeLeadCollection HooshMarketing_Marketo_Model_Resource_Lead_Collection
            filtered with start and end time set in each store in cron tab config of marketo
             */

            $storeLeadCollection = $this->_getLeadModel()
                ->getCollection()
                ->addAttributeToSelect("*")
                ->addStoreFilter($store)
                ->addAttributeToFilter("last_activity_time", array(
                    "from"  => $this->_getInactivityFromTime($store),
                    "to"    => $this->_getInactivityToTime($store)
                ));

            foreach($storeLeadCollection as $lead) {
                /* @var $lead HooshMarketing_Marketo_Model_Lead */
                /* handle product with status viewed */
                if($_id = $lead->getLastProductId()) { //check if it`s not null
                    /**
                     * prepare opportunity new model -> only one opportunity can be compared with lead
                     */
                    $opportunity = $this->_getOpportunityModel(true);
                    /* now we will create opportunity which will synced with next cron request */
                    $opportunity->setData(
                        $this->_getOpportunityModel()->getInfoFromProductId($_id, $store->getId())
                    );
                    /* TODO: marke param Quantity configurable */
                    $opportunity->setData("Quantity", 1); // must be compatible with your marketo account
                    $opportunity->pushAction(HooshMarketing_Marketo_Model_Opportunity::NEED_TO_CREATE); // set creator action
                    $opportunity->setParentId($lead->getId());
                    $lead->setLastProductId(0); //remove last viewed product id
                }

                try {
                    if(!isset($leadIds[$lead->getId()])) {
                        //Dissalow to update update_at field
                        $this->_doNotUpdateLead(true);
                        $lead->save();
                        $this->_doNotUpdateLead(false);

                        $_now = $this->_getDateTimeHelper()->getTimeObject(null, $store->getId())->getTimestamp();

                        $_lag = $this->_getDateTimeHelper()->timeObjectFromString($lead->getLastActivityTime())->getTimestamp();

                        /** @var $syncedLeads array -> use as box to all leads */
                        $syncedLeads[] = array(
                            HooshMarketing_Marketo_Model_Lead::LEAD_ID => $lead->getLeadId(),
                            $this->_getInactivitySyncKey() => ($_now - $_lag) / 60

                        );
                        $leadIds[$lead->getId()] = $lead->getId();
                        $leadQty++; //add

                        if(isset($opportunity)) {
                            $opportunity->save();
                        }
                    }
                } catch(Exception $e) {
                    Mage::logException($e);
                }
            }
        }

        if($leadQty) { //only if we have leads to sync
            $this->_getLeadModel()->getApiAdapter()->syncLead($syncedLeads, $leadQty);
        }
    }

    /**
     * @param $bool
     */
    protected function _doNotUpdateLead($bool) {
        if(!Mage::registry(self::DO_NOT_UPDATE_TIME)) {
            Mage::register(self::DO_NOT_UPDATE_TIME, $bool);
        }
    }

    /**
     * @return string -> key to marketo inactivity time param
     */

    protected function _getInactivitySyncKey() {
        return $this->_getHelper()->getSyncKeys("lead_inactivity_time", self::MAGENTO_INACTIVITY_TIME);
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return string
     */

    protected function _getInactivityFromTime(Mage_Core_Model_Store $store) {
        $startTime = ($this->_getApiHelper()->getCronInactivityTime(self::END, $store->getId()))
            ? $this->_getApiHelper()->getCronInactivityTime(self::END, $store->getId()) : self::INACTIIVTY_DEFAULT_FROM_TIME;
        return $this->_getDateTimeHelper()->toStoreTimeString(- $startTime, $store->getId());
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    protected function _getInactivityToTime(Mage_Core_Model_Store $store) {
        $startTime = ($this->_getApiHelper()->getCronInactivityTime(self::START, $store->getId()))
            ? $this->_getApiHelper()->getCronInactivityTime(self::START, $store->getId()) : self::INACTIIVTY_DEFAULT_TO_TIME;

        return $this->_getDateTimeHelper()->toStoreTimeString(- $startTime, $store->getId());

    }
    /**
     * @return int|string
     */
    protected function _getLastCronJobTime() {
        $_adapter   = $this->_getCoreResource()->getConnection("core_read"); //read connection
        $cronSelect = $_adapter
            ->select()
            ->from(
                $this->_getCoreResource()->getTableName("cron_schedule"),
                "finished_at"
            )
            ->where("job_code = ?", self::CRON_JOB_ALL_DATA)
            ->where("status = ?", "success")
            ->order("schedule_id DESC")
            ->limit(1);

        $_finshed = $_adapter->fetchOne($cronSelect);

        return $_finshed ? $_finshed : $this->_getDateTimeHelper()->_dateFormat(time() - self::SYNC_LEAD_LAG);
    }

    /**
     * Cron job which sync lead and Opportunity data
     */

    public function syncLeadAndOpportunityData() {
        list($leadResult, $opportunityResult) = array(array(), array());
        /* prepared lead */
        $_leads   = array();
        $lastDate = $this->_getLastCronJobTime();

        $leads         = $this->_getLeadModel()
            ->getCollection()
            ->addAttributeToSelect("*")
            ->addFieldToFilter("updated_at", array("from" => $lastDate));

        foreach($leads as $lead) {
            /* @var $lead HooshMarketing_Marketo_Model_Lead */
            if(! $this->_getApiHelper()->getModuleStatus($lead->getStoreId()) || !$lead->hasData("Email"))
                continue;

            $_leads[$lead->getId()] = $lead->getData();
        }
        var_dump(array_keys($_leads));
        if(!empty($_leads)) {
            /* prepare opportunity */
            $opportunities = $this
                ->_getOpportunitiesCollection()
                ->addFieldToFilter("parent_id", array_keys($_leads))
                ->addAttributeToFilter("action", array("neq" => $this->_getHelper()->serialize(array()) ));

            //Syncing Leads
            $leadResult         = $this->_getLeadModel()->getApiAdapter()->syncLead($_leads, count($_leads));
            $createdOpportunity = array();

            foreach($opportunities as $opportunity) {
                /* @var $opportunity HooshMarketing_Marketo_Model_Opportunity */
                $leadData = $_leads[$opportunity->getParentId()];

                if(!isset($leadData["Email"]))
                    continue; //check if lead exist and if lead is authorized

                try {
                    while(($action = $opportunity->shiftAction()) != NULL) { //check for not null action
                        switch($action) {
                            case HooshMarketing_Marketo_Model_Opportunity::NEED_TO_UPDATE:
                                /* add line item id criteria for quote_item */
                                if($this->_getOpportunityModel()->getOpportunityKey() && $opportunity->getData($this->_getOpportunityModel()->getOpportunityKey())) {
                                    $criteria["attributes"] = array(
                                        $this->_getOpportunityModel()->getOpportunityKey() => $opportunity->getData($this->_getOpportunityModel()->getOpportunityKey())
                                    );
                                }

                                $criteria["lead_id"] = $opportunity->getParentId();
                                $opportunityResult[] = $opportunity->getApiAdapter()->updateOpportunity($criteria, $opportunity->getData());
                                break;
                            case HooshMarketing_Marketo_Model_Opportunity::NEED_TO_CREATE:
                                if(!in_array($opportunity->getId(), $createdOpportunity)) {
                                    $opportunityResult[] = $opportunity->getApiAdapter()->syncOpportunity($opportunity->getData());
                                    $createdOpportunity  = true;
                                    $createdOpportunity[$opportunity->getId()] = $opportunity->getId();
                                }
                                break;
                        }
                    }

                    $opportunity->setData("action", HooshMarketing_Marketo_Model_Opportunity::EMPTY_OPPORTUNITY); //remove all actions
                    $opportunity->save(); //save after all actions applied

                } catch(Exception $e) {
                    Mage::logException($e);
                }
            }
            var_dump($leadResult, $opportunityResult);
        }
    }

}