<?php
class HooshMarketing_Marketo_Block_Template extends Mage_Core_Block_Template
{
    const AGGREGATOR_FILE = "aggregator.xml";

    protected $_multiLinesAttributes = array("Address");
    /**
     * @return string | null
     */
    public function getMunchkinCode() {
        return $this->_getHelper()->getMunckinCode();
    }

    /**
     * @return HooshMarketing_Marketo_Model_Lead
     */
    protected function _getLeadModel() {
        return Mage::getSingleton("hoosh_marketo/lead");
    }

    /**
     * @return HooshMarketing_Marketo_Helper_Data
     */
    protected function _getHelper() {
        return Mage::helper("hoosh_marketo");
    }
    /**
     * @return HooshMarketing_Marketo_Helper_Api
     */
    protected function _getApiHelper() {
        return Mage::helper("hoosh_marketo/api");
    }

    /**
     * Json items -> get from aggregator.xml
     * @return string
     */
    public function getAggregatorItems() {
        $_items  = array();
        $_config = $this->_getHelper()->loadXmlAsConfig(self::AGGREGATOR_FILE);
        $lead    = $this->_getLeadModel()->getLoadedByCookie();
        //Parse default aggregator fields
        if($_config instanceof Varien_Simplexml_Config && $_root = $_config->getNode()) {
            foreach($_root->asArray() as $marketoKey => $node) {
                if($lead->hasData($marketoKey)) {
                    foreach(explode(",", $node) as $magentoKey) { //explode defailt fields from aggregator.xml
                        $this->_processAggragatedField($_items, $marketoKey, $magentoKey);
                    }
                }
            }
        }
        //Add non-default fields
        $_mappingConfig = $this->_getHelper()->getConfig("marketo_aggregator", "mapping", null, true);

        foreach($_mappingConfig as $field) { //loop fields in aggregator mapping
            if(isset($field["magento_field_name"]) && isset($field["marketo_lead_attribute"])) {
                $this->_processAggragatedField($_items, $field["marketo_lead_attribute"], $field["magento_field_name"]);
            }
        }

        return $this->_getHelper()->jsonEncode($_items);
    }

    /**
     * @param $marketoKey
     * @param $magentoKey
     */
    protected function _processAggragatedField(&$_items, $marketoKey, $magentoKey) {
        $lead = $this->_getLeadModel();

        if(in_array($marketoKey, $this->_multiLinesAttributes)) {
            $this->_processFewLines($lead->getData($marketoKey), $_items, $magentoKey);
        } else {
            $_items[$magentoKey] = $lead->getData($marketoKey);
        }
    }

    /**
     * @param string $linesData
     * @param $_item
     * @param $magentoKey
     */
    protected function _processFewLines($linesData, &$_item, $magentoKey) {

        $lines = explode("\n", $linesData);

        foreach($lines as $index => $line) {
            $_key = $magentoKey . "[{$index}]";

            $_item[$_key] = $line;
        }
    }

    /**
     * Check what fields are not available to Show
     * @return bool|string
     */
    public function getIncorrectCredentialFields() {
        $_fields = array();
        $fields  = $this->_getApiHelper()->invalidApiData();
        if(!empty($fields) && is_array($fields)) {
            /** @var Mage_Adminhtml_Model_Config $configModel */
            $configModel = Mage::getSingleton("adminhtml/config");
            $configModel->getSections();

            foreach($fields as $field) {
                $label = $configModel->getSystemConfigNodeLabel(
                                    HooshMarketing_Marketo_Helper_Abstract::MARKETO_ROOT,
                                    HooshMarketing_Marketo_Helper_Abstract::API_CONFIG,
                                    $field
                                );
                if(empty($label)) continue;
                $_fields[] = $label;
            }
        }
        if(!empty($_fields)) {
            return implode(", ", $_fields);
        }

        return false;
    }
}