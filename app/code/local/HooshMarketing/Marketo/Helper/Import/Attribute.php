<?php
class HooshMarketing_Marketo_Helper_Import_Attribute extends HooshMarketing_Marketo_Helper_Import_Abstract
{
    protected $_backendType = "varchar";
    protected $_frontendInput = "text";
    /* required, need for detect entity_type_instance */
    protected $_entityTypeCode;
    protected $_attributeCodeField;

    public function import() {
        $this->prepareData();
        if(empty($this->_entityTypeCode)) $this->_throwEmptyTypeCodeException();

        $succes = false;
        $entityTypeId = $this->_getAttributeHelper()->getEntityTypeIdByCode($this->_entityTypeCode);

        foreach($this->_data as $column) {
            $eav = $this->_getAttributeModel();
            try {
                /* set system params */
                $this->setAttributeCode($eav, $column);
                if(strlen($column[$this->_attributeCodeField]) >
                    Mage_Eav_Model_Entity_Attribute::ATTRIBUTE_CODE_MAX_LENGTH)
                    continue; //if length of attribute_code is more than allowed

                $eav->addData(
                    array(
                        "entity_type_id" => $entityTypeId,
                        "backend_type"   => $this->_backendType,
                        "frontend_input" => $this->_frontendInput,
                        "api_type"       => ($this->_entityTypeCode == HooshMarketing_Marketo_Model_Opportunity::ENTITY)
                            ? HooshMarketing_Marketo_Model_Opportunity::API_TYPE :
                            HooshMarketing_Marketo_Model_Lead::API_TYPE
                    )
                );
                /*  check if attribute exists */
                if($eav->getAttributeCode()) {
                    $eav->loadByCode($eav->getEntityTypeId(), $eav->getAttributeCode());
                }

                /** @var array $columns should consist columns which presents in eav_attribute table */
                foreach($column as $key => $value) {
                    $eav->setData($key, $value);
                }

                /* save data to attribute tables */
                $eav->save();
                $succes = true; //if at least one attribute was imported successfully
            } catch(Exception $e) {
                $this->_getAdminhtmlSession()->addException($e, $e->getMessage() .
                    ". Attribute '" . $eav->getAttributeCode(). "' was skipped");
                Mage::logException($e);
            }
        }

        if(!$succes)
            throw new Exception("No Attribute was imported");
    }

    /**
     * @param HooshMarketing_Marketo_Model_Eav_Attribute $eav
     * @param array $column
     * @throws Exception
     * run attribute code setter like lambda
     */
    public function setAttributeCode(HooshMarketing_Marketo_Model_Eav_Attribute &$eav, array $column) {
        if(!$this->_attributeCodeField ||
            !isset($column[$this->_attributeCodeField]) ||
            empty($column[$this->_attributeCodeField])
          )
            throw new Exception("Attribute code field (Soap Field) not specified");

            $eav->setData("attribute_code", $column[$this->_attributeCodeField]);
    }

    /**
     * @param string $attributeCodeFIeld
     * @return $this
     */
    public function setAttributeCodeField($attributeCodeFIeld) {
        $this->_attributeCodeField = $attributeCodeFIeld;
        return $this;
    }

    /**
     * @throws Exception
     */
    protected function _throwEmptyTypeCodeException() {
        throw new Exception("Attribute Type Cannot Be Empty");
    }

    /**
     * @param $typeCode
     * @return $this
     * @throws Exception
     */
    public function setEntityTypeCode($typeCode) {
        if(empty($typeCode)) $this->_throwEmptyTypeCodeException();
        $this->_entityTypeCode = $typeCode;
        return $this;
    }

    /**
     * @return false|HooshMarketing_Marketo_Model_Eav_Attribute
     */
    protected function _getAttributeModel() {
        return Mage::getModel("hoosh_marketo/eav_attribute");
    }

    /**
     * @return HooshMarketing_Marketo_Helper_Attribute
     */
    protected function _getAttributeHelper() {
        return Mage::helper("hoosh_marketo/attribute");
    }

}