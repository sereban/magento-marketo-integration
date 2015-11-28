<?php
/* TODO: Switch to native magento soap api methods */
abstract class HooshMarketing_Marketo_Model_Api_Core
{
    /* Header constants */
    const AUTH_HEADER_LABEL = 'AuthenticationHeader';
    const EXCEPTION_MESSAGE = "Exception failed: ";
    const SOAP_LOG_FILE     = "marketo_soap.log";

    /**
     * @var array
     * All Soap Calls called all over the time script running
     */
    public $soapCalls = array();
    /* @var string $_entityModel - use this value to initialize lead model */
    protected $_entityModel;
    /**
     * @return SoapHeader
     */
    protected function _getHeader() {
        $currentDate = $this->_getTimeHelper()->getW3CTime();

        /* signature attributes to authorize hash code + current date to check hash code */
        $attributes = $this->_getApiHelper()->objectFromArray(array(
            "mktowsUserId"     => $this->_getApiHelper()->getUserId(),
            "requestSignature" => $this->_getApiHelper()->getEncryptionKey($currentDate),
            "requestTimestamp" => $currentDate
        ));

        return new SoapHeader($this->_getApiHelper()->getNameSpace(), self::AUTH_HEADER_LABEL, $attributes);
    }

    /**
     * @return HooshMarketing_Marketo_Helper_Api
     */
    protected function _getApiHelper() {
        return Mage::helper("hoosh_marketo/api");
    }

    /**
     * @return HooshMarketing_Marketo_Helper_DateTime
     */
    protected function _getTimeHelper() {
        return Mage::helper("hoosh_marketo/dateTime");
    }

    /**
     * @return SoapClient
     */
    protected function _getSoapClient() {
        return new SoapClient($this->_getApiHelper()->getEndPoint() . "?WSDL", array('trace' => 1));
    }

    /**
     * @param bool $response
     * @param bool $request
     * @param SoapClient $soapClient
     * @return string
     */
    public function getDebug($response, $request, SoapClient $soapClient)
    {
        if ($response && $request) {
            return
                "RAW request:\n" . $soapClient->__getLastRequest() . "\n" .
                "RAW response:\n" . $soapClient->__getLastResponse() . "\n";
        }
        if ($response) {
            return
                "RAW response:\n" . $soapClient->__getLastResponse() . "\n";
        }
        if ($request) {
            return
                "RAW request:\n" . $soapClient->__getLastRequest() . "\n";
        }

        return "";
    }

    /**
     * @param int $diffTime
     * @param string $method
     * @param bool $status
     * @param null $errMessage
     * @throws Exception
     */
    protected function _profileSoapCall($diffTime, $method, $status, $errMessage = null) {
        $this->soapCalls = $this->_getApiHelper()->getSoapCallsCollection();
        /* prepare item*/
        $item = new Varien_Object();
        $item->setData(array(
            "diffTime"=>$diffTime,
            "method"=>$method,
            "status"=>$status,
            "errMessage"=>$errMessage
        ));
        /* add item */
        $this->soapCalls->addItem($item);

        $this->_getApiHelper()->setSoapCallsCollection($this->soapCalls);
    }

    protected function _getDebugDetails($responseXml, $method) {
        if(preg_match("/^get(.*)$/", $method)) {
            return array("status" => "SUCCESS", "error" => "");
        } else {
            $dom = new DOMDocument();
            $dom->loadXML($responseXml);

            $statusItem = $dom->getElementsByTagName("status")->item(0);
            $errItem = $dom->getElementsByTagName("error")->item(0);
            return array(
                "status"=>(!empty($statusItem)) ? $statusItem->textContent : "FAILED",
                "error" => (!empty($errItem)) ? $errItem->textContent : ""
            );
        }
    }

    /**
     * @param $method
     * @param array $params
     * @return false|StdClass
     */
    public function call($method, array $params)
    {
        $startTime = microtime(true);
        $soapClient = $this->_getSoapClient();
        $soapHeader = $this->_getHeader();

        try {
            $response = $soapClient->__soapCall($method, $params, $this->_getApiHelper()->getSoapBodyOptions(), $soapHeader);
        } catch (Exception $e) {
            $this->_profileSoapCall(microtime(true) - $startTime, $method, self::EXCEPTION_MESSAGE, $e->getMessage());
            Mage::logException($e);
            return false;
        }

        $_debug = $this->_getDebugDetails($soapClient->__getLastResponse(), $method);
        $this->_profileSoapCall(microtime(true) - $startTime, $method, $_debug["status"], $_debug["error"]);

        if ($this->_getApiHelper()->canDebug()) {
            Mage::log($this->getDebug(true, true, $soapClient), null, self::SOAP_LOG_FILE);
        }

        return $response;
    }

    /**
     * @return HooshMarketing_Marketo_Model_Session_Lead
     */
    protected function getLeadSession() {
        return Mage::getSingleton("hoosh_marketo/session_lead");
    }

    /**
     * @return HooshMarketing_Marketo_Model_Lead | HooshMarketing_Marketo_Model_Opportunity
     * @throws Exception
     */
    protected function _getEntityModel() {
        if(!$this->_entityModel) throw new Exception("Entity Model in Api Class didn`t specified");
        return Mage::getSingleton("hoosh_marketo/" . $this->_entityModel);
    }
    /**
     * @return HooshMarketing_Marketo_Model_Eav_Attribute
     */
    protected function _getAttributeInstance() {
        return Mage::getSingleton("hoosh_marketo/eav_attribute");
    }

}