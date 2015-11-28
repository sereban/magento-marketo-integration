<?php

class HooshMarketing_Marketo_Model_Mapping_Observer extends HooshMarketing_Marketo_Model_Abstract
{
    const LEAD        = "lead";
    const OPPORTUNITY = "opportunity";
    //Opportunities types
    const DYNAMIC   = "opportunity_dynamic";
    const STAT      = "opportunity_static";
    const ALL_TYPES = "both";
    //From System Config
    const MAGENTO_OBJECT = "magento_object";
    const MARKETO_FIELD  = "marketo_attribute";
    const MAGENTO_FIELD  = "magento_attribute";
    const MARKETO_OBJECT = "marketo_object";

    /** @var array  */
    private $_xmlConfig    = array();
    /**
     * Mapping Config
     * @var array
     */
    private $_mappingConfig    = array();
    /**
     * Used to know what fields should be compared -> to know either object was changed (from Marketo point) or not
     * @var array
     */
    private $_dataChangeFields = array();
    /**
     * Prepare mapping config
     * @return array
     */
    protected function _getConfig()
    {
        $_mainConfig = $this->_getHelper()->getMappingConfig("map_field", null, true);
        $_defaults   = $this->_getMappingAttributeSaveModel()->getDefaultFields();

        return array_merge($_defaults, $_mainConfig);
    }

    /**
     * @param string $marketoObject
     * @param string $magentoObject
     * @return array
     */
    public function getDataChangeFields($marketoObject, $magentoObject) {
        $this->prepareFields($marketoObject);

        return isset($this->_dataChangeFields[$marketoObject][$magentoObject])
            ? $this->_dataChangeFields[$marketoObject][$magentoObject] : array();
    }

    /**
     * @param string $key
     * @return array | string
     */
    protected function _getXmlConfig($key) {
        if(!isset($this->_xmlConfig[$key])) {
            $_config = Mage::app()->getConfig()->getNode("mapping/magento_objects/$key");

            if($_config) {
                $this->_xmlConfig[$key] = $_config->asArray();
            } else {
                $this->_xmlConfig[$key] = array();
            }
        }

        return $this->_xmlConfig[$key];
    }

    /**
     * @param $marketoObject = $key (in config.xml)
     * @param string $type -> Type should be defined for opportunity : dynamic or static
     * @return array
     */
    public function prepareFields($marketoObject, $type = self::ALL_TYPES)
    {
        if(!isset($this->_mappingConfig[$marketoObject][$type])) {
            $this->_mappingConfig[$marketoObject][$type] = array();

            foreach ($this->_getConfig() as $toSync)
            {
                if (isset($toSync[self::MARKETO_OBJECT]) && $toSync[self::MARKETO_OBJECT] == $marketoObject) { //Marketo Object Filter leads and opps
                    $_magentoObject   = $toSync[self::MAGENTO_OBJECT];

                    $this->_mappingConfig[$marketoObject][$type][$_magentoObject][]  = $toSync;
                    $this->_dataChangeFields
                            [$marketoObject]
                            [$toSync[self::MAGENTO_OBJECT]]
                            [$toSync[self::MAGENTO_FIELD]] = $toSync[self::MAGENTO_FIELD];
                }
            }
        }

        return $this->_mappingConfig[$marketoObject][$type];
    }

    /**
     * @param $records
     * @param $observer
     * @param $fieldsToSync
     */
    protected function _prepareMarketoFields(&$records, $observer, $fieldsToSync)
    {
        foreach ($fieldsToSync as $key => $fields)
        { //loop through all objects
            $magentoObject = $this->_prepareMagentoObject($key);
            if(!$magentoObject)
                continue;

            $_obj = $magentoObject->prepare($observer);
            /**
             * @var string $magentoFieldName
             * @var HooshMarketing_Marketo_Model_Mapping_Classes_Abstract $magentoObject
             */
            foreach ($fields as $toSync)
            { //loop through all fields
                $magentoFieldName = $toSync[self::MAGENTO_FIELD];
                    //Check if object has data code or object has data label which should be handled by crc32 function first
                if (!empty($_obj) && ($_obj->hasData($magentoFieldName) || $_obj->hasData(crc32($magentoFieldName)))) {
                    if($_obj->hasData($magentoFieldName)) {
                        $magentoFieldValue = $_obj->getData($magentoFieldName); // get additional data from object
                    } else {
                        $magentoFieldValue = $_obj->getData(crc32($magentoFieldName)); // get additional data from object
                    }

                    if (empty($magentoFieldValue)) continue;
                    $records[$toSync[self::MARKETO_FIELD]] = $magentoFieldValue;
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addOpportunityDynamicFields(Varien_Event_Observer &$observer)
    {
        $opportunityData = $observer->getEvent()->getData("opportunity_data");
        $fieldsToSync = $this->prepareFields(self::OPPORTUNITY, self::DYNAMIC);

        $this->_prepareMarketoFields($opportunityData, $observer, $fieldsToSync);

        $observer->getEvent()->setData("opportunity_data", $opportunityData); //setting new data to observer
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addLeadFields(Varien_Event_Observer &$observer)
    {
        $leadRecord = $observer->getEvent()->getData("lead_record");
        $fieldsToSync = $this->prepareFields(self::LEAD);

        $this->_prepareMarketoFields($leadRecord, $observer, $fieldsToSync);

        $observer->getEvent()->setData("lead_record", $leadRecord); //setting new data to observer
    }

    /**
     * @param string $key
     * @return false | HooshMarketing_Marketo_Model_Mapping_Classes_Abstract
     */
    protected function _prepareMagentoObject($key)
    {
        $_objectXmlConfig = $this->_getXmlConfig($key);

        if(!isset($_objectXmlConfig["class"]))
            return false;

        return Mage::getSingleton($_objectXmlConfig["class"]);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addOpportunityStaticFields(Varien_Event_Observer &$observer)
    {
        $opportunityData = $observer->getEvent()->getData("opportunity_data");
        $fieldsToSync = $this->prepareFields(self::OPPORTUNITY, self::STAT);

        $this->_prepareMarketoFields($opportunityData, $observer, $fieldsToSync);

        $observer->getEvent()->setData("opportunity_data", $opportunityData); //setting new data to observer
    }
}