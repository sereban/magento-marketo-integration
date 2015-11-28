<?php

class HooshMarketing_Marketo_Model_Api_Opportunity extends HooshMarketing_Marketo_Model_Api_Core
{
    /**
     * need to compare external keys in marketo and in magento
     */
    const GET_OPPORTUNITY_COMPARISON = "EQ";
    /* method constants */
    const GET_OPPORTUNITIES_METHOD  = "getMObjects";
    const SYNC_MOBJECT_METHOD       = "syncMObjects";
    const OPERATION_UPSERT          = "UPSERT";
    const OPERATION_UPDATE          = "UPDATE";
    /* additional params */
    const OPPORTUNITY_ROLE          = "buyer";
    const GET_OPPORTUNITIES_PARAMS  = "paramsGetMObjects";
    const OPPORTUNITY               = "Opportunity";
    const OPPORTUNITY_ID            = "oppty_id";
    const OPPORTUNITY_TYPE_ROLE     = "OpportunityPersonRole";

    /**
     * In past - getMObject()
     * $criteria = array(
     *  lead_id =>
     *  attributes => array(name, value)
     *
     * )
     * @param array $criteria
     * @return array
     */
    public function getOpportunities(array $criteria)
    {
        $opportunityRequest = new StdClass();
        $response = new StdClass();

        if (isset($criteria["attributes"]) && $this->_getApiHelper()->isValidArray($criteria["attributes"])) {
            $criteriaAttributes = array();
            $externalKey        = array();
            foreach ($criteria["attributes"] as $attrName => $attrValue) {
                $criteriaAttributes[] = $this->_getApiHelper()->objectFromArray(array(
                    "attrName" => $attrName,
                    "attrValue" => $attrValue,
                    "comparison" => self::GET_OPPORTUNITY_COMPARISON
                ));

                $externalKey = $this->_getApiHelper()->objectFromArray(array( //note that function can have only one external key
                    "name" => $attrName,
                    "value" => $attrValue
                ));
            }
            /* add criteria to request */
            $this->_getApiHelper()->objectFromArray(array("mObjCriteriaList" => $criteriaAttributes), false, $opportunityRequest);
            $this->_getApiHelper()->objectFromArray(array("externalKey" => $externalKey), false, $opportunityRequest); //set external key
        }

        /* prepare lead Ids association part */
        if (isset($criteria["lead_id"])) {
            /* add  lead association to request */
            $association[] = $this->_getApiHelper()->objectFromArray(array("mObjType" => "Lead", "id" => $criteria["lead_id"]));
            $this->_getApiHelper()->objectFromArray(array("mObjAssociationList" => $association), false, $opportunityRequest);
        }

        if (isset($opportunityRequest->mObjCriteriaList) || isset($opportunityRequest->mObjAssociationList)) { //only when some criterias passed to function
            /* add type to request */
            $this->_getApiHelper()->objectFromArray(array("type" => self::OPPORTUNITY), false, $opportunityRequest);
            $response = $this->call(self::GET_OPPORTUNITIES_METHOD, array(self::GET_OPPORTUNITIES_PARAMS => $opportunityRequest));
        }


        return $this->_prepareGetOpportunitiesResponse($response);
    }

    /**
     * @param StdClass $response
     * @return array
     */
    protected function _prepareGetOpportunitiesResponse(StdClass $response)
    {
        $result = array();
        /* preapre list of mobjects */
        $opportunities = $this->_getApiHelper()->parseResponse($response, array("result", "mObjectList", "mObject"),
            HooshMarketing_Marketo_Helper_Api::ARRAY_FORMAT);

        foreach ($opportunities as $index => $opportunity) {
            $result[$index][self::OPPORTUNITY_ID] = $this->_getApiHelper()->parseResponse($opportunity, array("id"));
            $attributes = $this->_getApiHelper()->parseResponse($opportunity, array("attribList", "attrib"),
                HooshMarketing_Marketo_Helper_Api::ARRAY_FORMAT);

            if (!$this->_getApiHelper()->isValidArray($attributes)) continue; //left only mobject id

            foreach ($attributes as $attribute) {
                $name = $this->_getApiHelper()->parseResponse($attribute, array("name"));
                $value = $this->_getApiHelper()->parseResponse($attribute, array("value"));
                if (empty($name)) continue;

                $result[$index][$name] = $value;
            }
        }
        return $result;
    }

    /**
     * Prepare and make syncing
     * @param $operation
     * @param $params
     * @return false | StdClass
     */
    protected function _sync($operation, $params)
    {
        $syncRequest = $this->_getApiHelper()->objectFromArray(array("operation" => $operation, "mObjectList" => $params));
        return $this->call(self::SYNC_MOBJECT_METHOD, array($syncRequest));
    }

    /**
     * @param array $criteria
     * @param array $data - should be the same for all opportunities and should be array
     * @return array
     */
    public function updateOpportunity(array $criteria, array $data)
    {
        $getOpp = $this->getOpportunities($criteria);
        $updateOpportunity = array();
        /* prepare opportunity ids */
        foreach ($getOpp as $opp) {
            $updateOpportunity[] = $this->syncOpportunity($data, $opp[self::OPPORTUNITY_ID]);
        }

        return $updateOpportunity;
    }

    /**
     * @param StdClass $response
     * @return array (Opportunity Ids)
     */
    protected function _prepareSyncOpportunityResponse(StdClass $response)
    {
        /* data that will be written to opportunity personal role */
        $oppIds = array();

        $syncedOpportunities = $this->_getApiHelper()->parseResponse($response,
            array("result", "mObjStatusList", "mObjStatus"),
            HooshMarketing_Marketo_Helper_Api::ARRAY_FORMAT);

        foreach ($syncedOpportunities as $syncOpportunity) {
            $oppIds[] = $this->_getApiHelper()->parseResponse($syncOpportunity, array("id"));
        }

        return $oppIds;
    }

    /**
     * @param array $data
     * @param null $oppId
     * @return false|StdClass
     * @throws Exception
     * create or update Opportunity -> user opertion UPSERT
     */
    public function syncOpportunity(array $data, $oppId = null)
    {
        $type                   = (!empty($oppId)) ? self::OPERATION_UPDATE : self::OPERATION_UPSERT;
        $syncOpportunityRequest = new StdClass();
        $attributeList          = array();
        $leadId                 = null;

        foreach ($data as $attrName => $attrValue) {
            if($this->_getAttributeInstance()->isMarketoAttribute($attrName)) {
                $attributeList[] = $this->_getApiHelper()->objectFromArray(array("name" => $attrName, "value" => $attrValue), true);
            }
        }

        /* Add attributes to request object */
        $this->_getApiHelper()->objectFromArray(array("attribList" => $attributeList), false, $syncOpportunityRequest);
        /* if opportunity id exist -> associate opportunity id with this */

        $mainParams["type"] = self::OPPORTUNITY;
        if (!empty($oppId)) {
            $mainParams["id"] = $oppId;
        }

        $this->_getApiHelper()->objectFromArray($mainParams, false, $syncOpportunityRequest);

        $oppIds = $this->_prepareSyncOpportunityResponse(
            $this->_sync(self::OPERATION_UPSERT, array($syncOpportunityRequest))
        );

        if(isset($data["parent_id"])) {
            /* check if id exist retieve id or thwo exception */
            if (!isset($data["parent_id"])) throw new Exception("Cannot retrieve lead id");
            /* @var $lead HooshMarketing_Marketo_Model_Lead */
            $lead   = $this->_getLeadModel()->load($data["parent_id"]);

            if(!$lead->getLeadId())
                throw new Exception("Lead Id is Incorrect");

            $leadId = $lead->getLeadId();
        } elseif(isset($data["lead_id"])) { //manually adding lead id to opportunity
            $leadId = $data["lead_id"];
        }

        return $this->createOpportunityRole($oppIds, $leadId, $type); // update or create depends on what we need
    }

    /**
     * @return HooshMarketing_Marketo_Model_Lead
     */
    protected function _getLeadModel() {
        return Mage::getSingleton("hoosh_marketo/lead");
    }

    /**
     * @param $oppIds
     * @param $leadId
     * @param $type - by default use upsert
     * Create relations between lead and opportunity
     * @return false|StdClass
     */
    public function createOpportunityRole($oppIds, $leadId, $type = self::OPERATION_UPSERT)
    {
        $opportunityPersonalRoleRequest = new StdClass();
        $mObjects = array();

        foreach ($oppIds as $oppId) {
            $attributes = array();
            $mapKeys = array(
                "OpportunityId" => $oppId,
                "PersonId" => $leadId,
                "Role" => self::OPPORTUNITY_ROLE,
                "IsPrimary" => true
            );

            foreach ($mapKeys as $name => $value) {
                $attributes[] = $this->_getApiHelper()->objectFromArray(array("name" => $name, "value" => $value));
            }

            $attributeList = $this->_getApiHelper()->objectFromArray(array("attrib" => $attributes));
            $mObjects[] = $this->_getApiHelper()->objectFromArray(array("attribList" => $attributeList, "type" => self::OPPORTUNITY_TYPE_ROLE));
        }

        $this->_getApiHelper()->objectFromArray(array("mObject" => $mObjects), false, $opportunityPersonalRoleRequest);

        return $this->_sync($type, $opportunityPersonalRoleRequest);
    }
}