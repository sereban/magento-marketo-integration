<?php
abstract class HooshMarketing_Marketo_Model_Mapping_Classes_Abstract
    extends HooshMarketing_Marketo_Model_Abstract
{
    const CONNECTION_TYPE = "core_read"; //should be only read

    //Key For Object
    protected $_key;
    protected $_preparedObjects = array();  //cached objects
    /** @var array
     * $fieldId => $type (attribute or table)
     */
    protected $_fieldIdentifier = array();
    //Calbacks which should be triggered when data prepared
    protected $_preparedCallbacks = array();

    const ATTRIBUTE_TYPE = 1;
    const TABLE_TYPE     = 2;
    const SIMPLE_FIELD   = 3;

    /**
     * @return string
     */
    public function getKey() {
        return $this->_key;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return null|Varien_Object
     */
    public function prepare(Varien_Event_Observer $observer) {
        /** @var Varien_Object $_object */
        $_object = $observer->getEvent()->getData($this->getKey());

        if($_object instanceof Varien_Object && $_object->getId()) {
            if(!isset($this->_preparedObjects[$this->getKey()][$_object->getId()])) {
                $_object->setData($this->getKey(), true); //
                $this->_prepareData($_object);
                $this->_preparedObjects[$this->getKey()][$_object->getId()] = $_object;
            }

            return $this->_preparedObjects[$this->getKey()][$_object->getId()];
        } else {
            return new Varien_Object();
        }
    }

    /**
     * Prepare non standard fields like attributes with type integer
     * @param Varien_Object $_object
     */
    protected function _prepareData(Varien_Object $_object) {
        foreach($this->_preparedCallbacks as $callback => $key) {
            if($_object->hasData($key)) //only for this object
                call_user_func_array(array($this, $callback), array(&$_object));
        }
    }

    /**
     * @return array
     */
    public function getFields() {
        $_fields = array();
        foreach($this->_fieldIdentifier as $fieldId => $type) {
            switch($type) {
                case self::ATTRIBUTE_TYPE:
                    $_fields += $this->_getHelper()->getAttributes($fieldId);
                    break;
                case self::TABLE_TYPE:
                    $_fields += $this->_getHelper()->getTableFields($fieldId);
                    break;
                case self::SIMPLE_FIELD:
                    $_fields += $this->_getHelper()->getSimpleColumnForMapping($fieldId);
            }
        }

        return $_fields;
    }

    /**
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getAdapter() {
        return $this->_getCoreResource()->getConnection(self::CONNECTION_TYPE);
    }
}