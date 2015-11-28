<?php
class HooshMarketing_Marketo_Model_System_Config_Mapping_Attribute_Save
    extends HooshMarketing_Marketo_Model_System_Config_Mapping_Save
{
    protected function _afterLoad()
    {
        if($this->getValue() == null) {
            $this->setValue(array() + $this->getDefaultFields());
        } else {
            $this->setValue(unserialize($this->getValue()) + $this->getDefaultFields());
        }
    }

    protected function _beforeSave()
    {
        $this->setValue(serialize($this->getValue()));
    }

    /**
     * Prepare default fields from config.xml
     * @return array
     */
    public function getDefaultFields() {
        $fields = array();
        $i = 0;

        $_objects = Mage::app()->getConfig()->getNode("mapping/default_fields");

        if($_objects) {
            $_objects = $_objects->asArray();

            foreach($_objects as $objectValue => $objectMapping) {
                foreach($objectMapping as $magentoObject => $magentoData) {
                    foreach($magentoData as $marketoField => $magentoField) {
                        $counter = $this->_createCounter($i);

                        $fields[$counter]["marketo_object"]    = $objectValue;
                        $fields[$counter]["marketo_attribute"] = $marketoField;
                        $fields[$counter]["magento_object"]    = $magentoObject;
                        $fields[$counter]["magento_attribute"] = $magentoField;
                        $i++;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * @param int $iteratorIndex
     * @return string
     */
    protected function _createCounter($iteratorIndex) {
        return "_{$iteratorIndex}_{$iteratorIndex}";
    }
}