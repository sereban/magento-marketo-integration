<?php

class HooshMarketing_Marketo_Model_Resource_Attribute_Abstract extends Mage_Eav_Model_Entity_Abstract
{
    protected $_type;

    public function __construct() {
        $this->setType($this->_type);
        $this->setConnection("core_read", "core_write");
        parent::__construct();
    }

    /**
     * @param Varien_Object $object
     * @return $this
     */
    protected function _beforeSave(Varien_Object $object) {
        parent::_beforeSave($object);
        $this->_changeTime($object);
        return $this;
    }
    /* never should have static type */
    public function isAttributeStatic($attribute)
    {
        $attrInstance       = $this->getAttribute($attribute);
        if(!$attrInstance) return false;
        $attrBackendStatic  = $attrInstance->getBackend()->isStatic();
        return $attrInstance && $attrBackendStatic;
    }

    /**
     * @param Mage_Eav_Model_Entity_Type $object
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param mixed $value
     * @return $this
     * @throws Mage_Core_Exception
     */
    protected function _saveAttribute($object, $attribute, $value)
    {
        $table = $attribute->getBackend()->getTable();
        if (!isset($this->_attributeValuesToSave[$table])) {
            $this->_attributeValuesToSave[$table] = array();
        }

        $entityIdField = $attribute->getBackend()->getEntityIdField();

        $data   = array(
            'entity_type_id'    => $object->getEntityTypeId(),
            $entityIdField      => $object->getId(),
            'attribute_id'      => $attribute->getId(),
            'value'             => $this->_prepareValueForSave($value, $attribute)
        );

        $this->_attributeValuesToSave[$table][] = $data;
        $this->_saveEntityData($object);
        return $this;
    }

    /**
     * Set marketo timezone
     * @param Varien_Object $object
     * @return bool
     */
    protected function _changeTime(Varien_Object &$object) {
        /** @var HooshMarketing_Marketo_Helper_DateTime $_dateTimeHelper */
        $_dateTimeHelper = Mage::helper("hoosh_marketo/dateTime");
        $timeStamp = $_dateTimeHelper->toTimeString();
        $object->setData("updated_at", $timeStamp);

        return true;
    }

    /**
     * @param Varien_Object $object
     * @return void
     */
    protected function _saveEntityData(Varien_Object $object)
    {
        $storeId = Mage::app()->getStore()->getId();
        $storeId = (!$storeId) ? Mage_Catalog_Model_Product::DEFAULT_STORE_ID : $storeId;

        $this->_getWriteAdapter()->update(
            $this->getEntityTable(),
            array("store_id" => $storeId, 'updated_at' => $object->getData("updated_at")),
            array("entity_id = ?" => $object->getData("entity_id"))
        );
    }

}