<?php
abstract class HooshMarketing_Marketo_Helper_Abstract extends Mage_Core_Helper_Data
{
    const MODULE_NAME     = "HooshMarketing_Marketo";
    //System Config Section Names
    const API_CONFIG      = "marketo_credentials";
    const MARKETO_ROOT    = "marketo_config";
    const CRON_SETTINGS   = "cron_settings";
    const TEMPLATE_CONFIG = "template_switching";
    const CATEGORY_CONFIG = "category_settings";
    const ENABLE_CONFIG   = "hooshmarketo_enabling_status";
    const OPP_HARD_CONFIG = "marketo_opportunity_hardcode_fields";
    const MAPPING_CONFIG  = "marketo_field_mapping";

    /**
     * @param $group
     * @param $field
     * @param null $store
     * @return string | int | array
     */
    public function getConfig($group, $field = null, $store = null, $unserialize = false) {
        if(!empty($field)) {
            return ($unserialize) ?
                $this->unSerialize(Mage::getStoreConfig(self::MARKETO_ROOT . "/" . $group . "/" . $field, $store)) :
                Mage::getStoreConfig(self::MARKETO_ROOT . "/" . $group . "/" . $field, $store);
        } else {
            return Mage::getStoreConfig(self::MARKETO_ROOT . "/" . $group, $store);
        }
    }

    public function getMappingConfig($field, $store = null, $unserialize = false) {
        $_config = $this->getConfig(self::MAPPING_CONFIG, $field, $store);
        return ($unserialize) ? $this->unSerialize($_config) : $_config;
    }
    /**
     * @param $field
     * @param $store
     * @return array|int|string
     */
    public function getHardcodedOpportunityFields($field, $store = null) {
        return $this->getConfig(self::OPP_HARD_CONFIG, $field, $store);
    }

    /**
     * Takes Enable/Disable data of module
     * @param $field
     * @param $store
     * @return array|int|string
     */
    public function getEnableConfig($field, $store = null) {
        return $this->getConfig(self::ENABLE_CONFIG, $field, $store);
    }

    /**
     * @param $field
     * @param null $store
     * @return string | int | array
     */
    public function getApiConfig($field = null, $store = null) {
        return $this->getConfig(self::API_CONFIG, $field, $store);
    }

    /**
     * @param null $field
     * @param null $store
     * @param bool|true $unserialize -> Used when we have mapping
     * @return array|int|string
     */
    public function getTemplateSwitcherConfig($field = null, $store = null, $unserialize = true) {
        $_config = $this->getConfig(self::TEMPLATE_CONFIG, $field, $store);

        return ($unserialize) ? $this->unSerialize($_config) : $_config;
    }

    public function getCategoryConfig($field = null, $store = null, $unserialize = true) {
        $_config = $this->getConfig(self::CATEGORY_CONFIG, $field, $store);

        return ($unserialize) ? $this->unSerialize($_config) : $_config;
    }

    public function getModuleStatus($storeId = null)
    {
        return $this->getConfig("hooshmarketo_enabling_status", "enabling", $storeId);
    }

    /**
     * @return Mage_Core_Model_Store
     */
    public function getStore() {
        return Mage::app()->getStore();
    }

    /**
     * @param array $first
     * @param array $second
     * The data from
     * @return array
     */
    public function merge(array $first, array $second) {
        return array_merge($first, $second);
    }

    /**
     * @param mixed $array
     * Check if array has values or not
     * @return bool
     */
    public function isValidArray($array) {
        return is_array($array) && count($array);
    }

    /**
     * @param $field
     * @param null $store
     * @return string
     */
    public function getCronConfig($field, $store = null) {
        return $this->getConfig(self::CRON_SETTINGS, $field, $store);
    }
    /* remove object and array values from array (making array simple) */
    public function sanitizeArray($array) {
        foreach($array as $key=>$value) {
            if(is_object($value) || is_array($value)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @return HooshMarketing_Marketo_Model_Abstract
     */
    public function getMarketoAbstractInstance() {
        return Mage::getSingleton("hoosh_marketo/abstract");
    }

    /**
     * If error occured while serializing data -> serialize empty array
     * @param array $data
     * @return string
     */
    public function serialize(array $data) {
        return  (!($serialized = serialize($data))) ? serialize(array()) : $serialized;
    }

    /**
     * @param $serialized
     * @return array
     */
    public function unSerialize($serialized) {
        return (!($unSerialized = @unserialize($serialized))) ? array() : $unSerialized;
    }

    /**
     * Recursevily go throught array and pop file
     * @param $data
     * @return array|mixed
     */
    public function pop(&$data) {
        if(is_array($data)) {
            $poped = array_pop($data);
            return (is_array($poped)) ? $this->pop($poped) : $poped;
        }

        return $data;
    }

    /**
     * Increment object value for 1 or more points
     * @param Varien_Object $object
     * @param $key
     * @param int $value
     */
    public function increment(Varien_Object &$object, $key, $value = 1) {
        if($old = (int)$object->getData($key)) {
            $object->setData($key, $value + $old);
        } else {
            $object->setData($key, $value);
        }
    }

    /**
     * Makes from attribute_code -> Attribute Code
     * @param string $field
     * @return string
     */
    public function beatifyField($field) {
        return preg_replace("/\_/", " ", uc_words($field));
    }

    /**
     * @return Mage_Core_Model_Store
     */
    protected function getSectionStore() {
        $_currentStore = Mage::app()->getStore();

        if($_currentStore->isAdmin()) {
            $currentStore  = Mage::app()->getRequest()->getParam('store');

            if($currentStore) {
                try {
                    $_currentStore = Mage::app()->getStore($currentStore);
                } catch(Exception $e) {
                    Mage::logException($e);
                }
            } else {
                $_currentStore = Mage::app()->getStore("default");
            }
        }

        return $_currentStore;
    }

    /**
     * @return Mage_Core_Model_Session
     */
    protected function _getCoreSession() {
        return Mage::getSingleton("core/session");
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getAdminhtmlSession() {
        return Mage::getSingleton("adminhtml/session");
    }

    /**
     * @return Mage_AdminNotification_Model_Inbox
     */
    protected function _getNotificationModel() {
        return Mage::getSingleton("adminnotification/inbox");
    }

    /**
     * @param Varien_Object $_transport
     * @param $html
     */
    public function appendHtml(Varien_Object $_transport, $html) {
        $_html  = $_transport->getData("html");
        $_html .= $html;
        $_transport->setData("html", $_html);
    }

    /**
     * @param $file
     * @return false | Mage_Core_Model_Config
     */
    public function loadXmlAsConfig($file) {
        $etcDir  = Mage::getModuleDir("etc", self::MODULE_NAME);
        $_config = new Mage_Core_Model_Config();

        $_config->loadFile($etcDir . DS . $file);
        return $_config;
    }

}
