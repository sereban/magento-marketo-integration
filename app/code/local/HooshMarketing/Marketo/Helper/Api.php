<?php
class HooshMarketing_Marketo_Helper_Api extends HooshMarketing_Marketo_Helper_Abstract
{
    const SOAP_CALL_COLLECTION_NAMESPACE = "soap_call_collection";
    const CONNECTION_TIMEOUT             = 20;
    const TRACE                          = true;
    const ARRAY_FORMAT                   = 1;
    const SIMPLE_FORMAT                  = 2;

    /**
     * @param bool $recursive
     * @param array $data
     * @param StdClass $stdObject
     * @return StdClass
     */
    public function objectFromArray(array $data, $recursive = false, &$stdObject = null) {
        $stdObject = (empty($stdObject)) ? new StdClass() : $stdObject;

        foreach($data as $var => $value) {
            $stdObject->{$var} = ($recursive && is_array($value)) ? $this->objectFromArray($value, $recursive) : $value;
        }

        return $stdObject;
    }

    /**
     * @param string $currentDate
     * @return string hash
     */
    public function getEncryptionKey($currentDate) {
        $encryptString = $currentDate . $this->getUserId();
        return hash_hmac('sha1', $encryptString, $this->getApiConfig("secretKey"));
    }

    /**
     * @return string
     */
    public function getUserId() {
        return $this->getApiConfig("userId");
    }

    /**
     * @return string
     */
    public function getNameSpace() {
        return $this->getApiConfig("namespace");
    }

    public function getEndPoint() {
        return $this->getApiConfig("soapEndpoint");
    }

    /**
     * @return Varien_Data_Collection
     */
    public function getSoapCallsCollection() {
        return (Mage::registry(self::SOAP_CALL_COLLECTION_NAMESPACE)) ? Mage::registry(self::SOAP_CALL_COLLECTION_NAMESPACE) : new Varien_Data_Collection();
    }

    /**
     * @param Varien_Data_Collection $soapCalls
     */
    public function setSoapCallsCollection(Varien_Data_Collection $soapCalls) {
        if(Mage::registry(self::SOAP_CALL_COLLECTION_NAMESPACE)) {
            Mage::unregister(self::SOAP_CALL_COLLECTION_NAMESPACE);
        }

        Mage::register(self::SOAP_CALL_COLLECTION_NAMESPACE, $soapCalls);
    }

    /**
     * @return array
     */
    public function getSoapBodyOptions() {
        return array(
            "connection_timeout" => self::CONNECTION_TIMEOUT,
            "trace"              => true,
            "location"           => $this->getEndPoint() . "?WSDL"
        );
    }

    /**
     * Allow or Not to debug marketo responses and requests
     * @return string
     */
    public function canDebug() {
        return (bool)$this->getApiConfig("debugSoapResponse");
    }

    /**
     * @return array
     */
    public function invalidApiData() {
        $emptyFields = array();
        $_configFields = $this->getApiConfig();

        if(!empty($_configFields)) { //if path to config fields is correct -> check whether fields are empty or not
            foreach($_configFields as $field => $value) {
                if(!strlen($value)) {
                    $emptyFields[] = $field;
                }
            }
        }

        return $emptyFields;
    }

    public function leadtoAssocArray($lead, array $options = array())
    {
        $leadAssocArray = array();
        if(!$lead || !$lead instanceof StdClass) return $leadAssocArray;
        /* @var StdClass | array $leadRecord  */
        $leadRecord = (isset($options["without_leadRecordList"]) && $options["without_leadRecordList"])
            ? $lead->result->leadRecord : $lead->result->leadRecordList->leadRecord;

        if (is_array($leadRecord) && count($leadRecord)) {
            $leadRecord = $leadRecord[count($leadRecord) - 1]; //to write the last most actual lead
        }
        /* check if lead have leadAttributes. Id must be */
        if(empty($leadRecord->leadAttributeList) || !isset($leadRecord->{HooshMarketing_Marketo_Model_Lead::LEAD_ID})) {
            return false;
        }

        $attributes = $leadRecord->leadAttributeList->attribute;

        if (!is_array($attributes)) {
            return false;
        }

        foreach ($attributes as $attrib) {
            $leadAssocArray[$attrib->attrName] = $attrib->attrValue;
        }
        /* Should be there and are not in attributes */
        $leadAssocArray[HooshMarketing_Marketo_Model_Lead::LEAD_EMAIL] = $leadRecord->{HooshMarketing_Marketo_Model_Lead::LEAD_EMAIL};
        $leadAssocArray[HooshMarketing_Marketo_Model_Lead::LEAD_ID] = $leadRecord->{HooshMarketing_Marketo_Model_Lead::LEAD_ID};

        return $leadAssocArray;
    }

    /**
     * @param $response
     * @param array $path
     * @param int $format
     * @return mixed
     */
    public function parseResponse($response, array $path, $format = self::SIMPLE_FORMAT) {
        $parseResponse = ($format == self::ARRAY_FORMAT) ? array() : null;

        if(is_array($path) && count($path)) {
            $node = array_shift($path);

            if(is_array($response) && isset($response[$node])) {
                $parseResponse = $this->parseResponse($response[$node], $path, $format);
            } elseif(is_object($response) && isset($response->{$node})) {
                $parseResponse = $this->parseResponse($response->{$node}, $path, $format);
            }
        } else {
            $response = (empty($response) && $format == self::ARRAY_FORMAT) ? array() : $response;
            return (!is_array($response) && $format == self::ARRAY_FORMAT) ? array($response) : $response;
        }

        return $parseResponse;
    }

    /**
     * @param $type -> type can be "start" or "end"
     * @param null $store
     * @return int|string
     */
    public function getCronInactivityTime($type, $store = null) {
        return ($time = $this->getCronConfig("inactivity_" . $type . "_time", $store)) ? $time * 60 : null;
    }
}