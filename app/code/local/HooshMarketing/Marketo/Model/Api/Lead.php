<?php
class HooshMarketing_Marketo_Model_Api_Lead extends HooshMarketing_Marketo_Model_Api_Core
{
    /* marketo api methods */
    const GET_LEAD_METHOD               = "getLead";
    const GET_LEAD_ACTIVITY_METHOD      = "getLeadActivity";
    const SYNC_LEAD_METHOD              = "syncLead";
    const SYNC_MULTIPLE_LEADS_METHOD    = "syncMultipleLeads";
    const DEDUP_ENABLED                 = true;
    const RETURN_LEAD                   = true;
    const GET_LEAD_ACTIVITY_VALUE_INDEX = 3;
    const GET_LEAD_ACTIVITY_NAME_INDEX  = 1;

    /* used to initialze entity model hoosh_marketo/lead */
    protected $_entityModel          = "lead";

    /**
     * @return HooshMarketing_Marketo_Model_Lead
     * @throws Exception
     */
    protected function _getLoadEntityModel() {
        return $this->_getEntityModel()->getLoadedByCookie();
    }

    public function checkLead() {
        $cacheLifeTime = $this->_getApiHelper()->getConfig("cache_life_time", "life_time");
        $updatedAt     = $this->_getTimeHelper()->timeFromString($this->_getLoadEntityModel()->getUpdatedAt());
        $now           = $this->_getTimeHelper()->getTimeObject()->getTimestamp();

        /* prepare conditions */
        $emptyCacheTime = empty($updatedAt);
        $emptyId        = is_null($this->getLeadSession()->getLead()->getLeadId());
        $cacheDeprecate = $cacheLifeTime + $updatedAt <= $now;
        $cookieExists   = (bool)$this->getLeadSession()->getMarketoCookie();

        /* make request for creating new lead */
        if($emptyId && $cookieExists) {
            $this->createLead();
            return true;
        }
        /* make request for updating exist lead */
        if(($emptyCacheTime || $cacheDeprecate) && $cookieExists) $this->updateLead();

        return true;
    }

    /**
     * Create Cache -> Set cache in hoosh_marketo/lead_session and into the entity model if lead id exists in marketo
     * @return void
     * @throws Exception
     */
    public function createLead() {
        /* prepare entity model (If we cannot load by cookie entity model - use empty )*/
        $leadEntityModel = $this->_getLoadEntityModel();
        /* prepare entity model data*/
        $_data    = $leadEntityModel->getData();

        if(!$leadEntityModel->getLeadId()) { // if there is no lead id in model
            /* @var $leadResponse StdClass */
            $_response = $this->_getApiHelper()->leadtoAssocArray(
                                                            $this->call(self::GET_LEAD_METHOD, $this->_prepareLeadKey())
            );

            $_data = $this->_getApiHelper()->merge($_data, $_response);
        }

        /* working witn data to merge */
        $_data = $this->_getApiHelper()->merge($this->getLeadSession()->getMergeData(), $_data);
        /* update data */
        if(isset($_data[HooshMarketing_Marketo_Model_Lead::LEAD_ID])) {
            $this->_updateLead($_data);
        }
    }

    public function updateLead() {
        $activity = $this->_getUpdateActivity();

        $activityRecords = $this->_getApiHelper()->parseResponse(
            $activity,
            array("leadActivityList", "activityRecordList", "activityRecord"),
            HooshMarketing_Marketo_Helper_Api::ARRAY_FORMAT
        );

        $_data = array();

        foreach($activityRecords as $activityRecord) {
            $name  = $this->_getApiHelper()->parseResponse($activityRecord, array("activityAttributes", "attribute", self::GET_LEAD_ACTIVITY_NAME_INDEX, "attrValue"));
            $value = $this->_getApiHelper()->parseResponse($activityRecord, array("activityAttributes", "attribute", self::GET_LEAD_ACTIVITY_VALUE_INDEX, "attrValue"));
            $code  = $this->_getAttributeInstance()->getAttributeByFriendlyName($name)->getAttributeCode();
            $_data[$code] = $value;
        }

        /* update all info only when we have some new info */
        if($this->_getApiHelper()->isValidArray($activityRecords)) $this->_updateLead($_data);
    }

    protected function _updateLead(array $data) {
        /* working with entity_model */
        $this->_getLoadEntityModel()->addData($data);
        try {
            $this->_getLoadEntityModel()->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        /* update session */
        $this->getLeadSession()->setLead($this->_getLoadEntityModel());
        /* set authorize status*/
        $this->getLeadSession()->setAuthorized((bool)$this->_getLoadEntityModel()->getEmail());
    }

    /**
     * @param array $leadCase
     * @param $includeType
     * @param null $dateType
     * @return false|StdClass
     * @throws Exception
     */
    public function getLeadActivity(array $leadCase, $includeType, $dateType = null) {
        if(!$this->getLeadSession()->getLeadEntityId()) return false; //check if lead already exist

        if(!isset($leadCase["type"]) || !isset($leadCase["value"])) $this->createLead(); // if something wrong with current lead - lets create new one
        /* prepare required parameters */
        $leadActivityRequest = array(
            "leadKey" => array(
                "keyType"  => $leadCase["type"],
                "keyValue" => $leadCase["value"]
            ),
            "activityFilter" => array(
                "includeAttributes" => array(
                    "activityType" => $includeType
                )
            )
        );
        /* prepare additional  (time) parameter */
        if(!empty($dateType) && $this->_getLoadEntityModel()->getUpdatedAt()) {
            /* get time of last update */
            $diff = $this->_getTimeHelper()->getTimeObject()->getTimestamp()
                - $this->_getTimeHelper()->timeFromString($this->_getLoadEntityModel()->getUpdatedAt())
                + $this->_getApiHelper()->getConfig("cache_life_time", "life_time");

            $leadActivityRequest["startPosition"] = array(
                $dateType => $this->_getTimeHelper()->getW3CTime(-$diff)
            );
        }

        return $this->call(self::GET_LEAD_ACTIVITY_METHOD, array($leadActivityRequest));
    }

    /**
     * keyType -> Email or Cookie
     * keyValue -> value of email or cookie field
     * @return array
     */
    protected function _prepareLeadKey() {
        $leadKey = array(
            "keyType"  => "COOKIE",
            "keyValue" => $this->getLeadSession()->getMarketoCookie()
        );

        return array("paramsGetLead" => array("leadKey" => $leadKey));
    }

    /**
     * @return false|StdClass. Return result of marketo request
     * @throws Exception
     */
    protected function _getUpdateActivity() {
        return $this->getLeadActivity(array(
            "type" => "IDNUM",
            "value" => $this->_getLoadEntityModel()->getLeadId()
        ), "ChangeDataValue", "oldestCreatedAt");
    }

    /**
     * @param array $leadRecordArray - array with data we need to sync. Scope with few or single lead. Always should be an array
     * @return array|StdClass
     */
    protected function _prepareLeadRecordObject(array $leadRecordArray)
    {
        $leadAttributeList = array();
        $leadRecord = new StdClass();

        foreach ($leadRecordArray as $name => $record) {
            if (empty($record)) continue;

            switch($name) {
                case HooshMarketing_Marketo_Model_Lead::LEAD_ID:
                    $leadRecord->{HooshMarketing_Marketo_Model_Lead::LEAD_ID} = $record;
                    break;
                case HooshMarketing_Marketo_Model_Lead::LEAD_EMAIL:
                    $leadRecord->{HooshMarketing_Marketo_Model_Lead::LEAD_EMAIL} = $record;
                    break;
                default:
                    /* preapre attribute list for custom attributes */
                    if($this->_getAttributeInstance()->isMarketoAttribute($name)) {
                        $leadAttributeList[] = $this->_getApiHelper()->objectFromArray(array("attrName" => $name, "attrValue" => $record));
                    }
            }
        }

        if (empty($leadRecord->{HooshMarketing_Marketo_Model_Lead::LEAD_ID})) {
            $leadRecord->{HooshMarketing_Marketo_Model_Lead::LEAD_ID} = $this->getLeadSession()->getLeadId();
        }

        $leadRecord->leadAttributeList = $leadAttributeList;

        return $leadRecord;
    }

    /**
     * @param array $leadRecordArray should have int indexes: 1,2,3 etc..
     * @param int $leadQty
     * @return mixed
     */
    public function syncLead(array $leadRecordArray, $leadQty = 1)
    {
        if(empty($leadRecordArray))
            return true;

        $leadRecordList = array();
        if ($leadQty != 1) {
            /* walk throw all leads and prepare request for each */
            foreach($leadRecordArray as &$leadRecord) {
                $leadRecord       = $this->_dispatchLeadEvent($leadRecord); // dispatch events for cron leads
                $leadRecordList[] = $this->_prepareLeadRecordObject($leadRecord);
            }

            $params = $this->_getApiHelper()->objectFromArray(array(
                "leadRecordList" => $leadRecordList,
                "dedupEnabled"   => self::DEDUP_ENABLED,
            ));
            $method = self::SYNC_MULTIPLE_LEADS_METHOD;
            $objKey = "paramsSyncMultipleLeads";
        } else {
            $params = $this->_getApiHelper()->objectFromArray(array(
                "leadRecord"    => $this->_prepareLeadRecordObject(current($leadRecordArray)),
                "returnLead"    => self::RETURN_LEAD,
                "marketoCookie" => (isset($leadRecordArray["cookie"])) ? $leadRecordArray["cookie"] : $this->getLeadSession()->getMarketoCookie()
            ));

            $method = self::SYNC_LEAD_METHOD;
            $objKey = "paramsSyncLead";
        }

        return $this->call($method, array($objKey => $params));
    }

    /**
     * Dispatch lead event
     * @param array $leadRecordArray
     * @param array $dispatched
     * @return array
     */
    protected function _dispatchLeadEvent(array $leadRecordArray, $dispatched = array()) {
        $toSend = array("lead_record" => &$leadRecordArray);
        $toSend = $dispatched + $toSend;

        Mage::dispatchEvent("sync_lead_before", $toSend);
        return $leadRecordArray;
    }

}