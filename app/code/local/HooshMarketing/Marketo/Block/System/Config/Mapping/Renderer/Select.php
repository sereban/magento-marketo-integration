<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
 * @method bool getIsRenderToJsTemplate()
 * @method $this setName(string $value)
 * @method $this setExtraParams(string $params)
 * @method string getColumnName()
 * @method string getName()
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
        extends Mage_Core_Block_Html_Select
{
    //Default values
    const DEFAULT_OBJECT_ID   = 0;
    const CUSTOM_OPTION_VALUE = 4096;

    protected $_defaultOptionLabel = "Select...";
    protected $_customOptionLabel  = "Custom Attribute ...";
    protected $_parentFieldCode = null; //specify this only when you need depends between 2 fields
    protected $_fieldCode;
    protected $_extraParams; //styles, js functions, etc

    protected function _addDefaultOption() {
        $this->addOption(self::DEFAULT_OBJECT_ID, $this->_defaultOptionLabel);
    }

    protected function _addCustomOption() {
        $this->addOption(self::CUSTOM_OPTION_VALUE, $this->_customOptionLabel);
    }

    /**
     * Add input with display="none" after select in order to use input, if there is no value in select
     * @return string
     */
    public function getCustomInputHtml() {
        $columnName = $this->getColumnName();

        $input  = '<input name="' . $this->getName() .'" ';
        $input .= ' id="custom_option_'. $this->getId() . '"';
        $input .= ' value="#{' . $columnName . '}" style="display:none" disabled="true" />';

        return $input;
    }
    /**
     * @return string
     */
    public function getParentFieldCode() {
        return $this->_parentFieldCode;
    }

    /**
     * @param $value
     * @return HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @param array $option
     * @param bool|false $selected
     * @return string
     */
    protected function _optionToHtml($option, $selected = false)
    {
        $selectedHtml = $selected ? ' selected="selected"' : '';
        $parentParam  = isset($option["params"][$this->_parentFieldCode]) ?
                $option["params"][$this->_parentFieldCode] : null; //if this will be null it will use old hashing mechanism

        if ($this->getIsRenderToJsTemplate() === true) {
            $selectedHtml .= ' #{option_extra_attr_' .
                self::calcOptionHashWithObject($option['value'], $parentParam) . '}';
        }

        $params = '';
        if (!empty($option['params']) && is_array($option['params'])) {
            foreach ($option['params'] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $keyMulti => $valueMulti) {
                        $params .= sprintf(' %s="%s" ', $keyMulti, $valueMulti);
                    }
                } else {
                    $params .= sprintf(' %s="%s" ', $key, $value);
                }
            }
        }

        return sprintf('<option value="%s"%s %s>%s</option>',
            $this->escapeHtml($option['value']),
            $selectedHtml,
            $params,
            $this->escapeHtml($option['label']));
    }

    protected function _initParams() {
        $this->setId($this->_fieldCode . "#{_id}");
        $this->setClass($this->_fieldCode);
        $this->setExtraParams($this->_extraParams);
    }

    /**
     * Calculate CRC32 hash for option value
     *
     * @param string $attributeValue -> Value of main option; $objectValue ->
     * @return string
     */
    public function calcOptionHashWithObject($attributeValue, $objectValue)
    {
        if(empty($objectValue)) {
            return parent::calcOptionHash($attributeValue);
        } else {
            return sprintf('%u', crc32($this->getName() . $this->getId() . $attributeValue . $objectValue));
        }
    }

    /**
     * @return HooshMarketing_Marketo_Helper_Data
     */
    protected function _getHelper() {
        return Mage::helper("hoosh_marketo");
    }

    /**
     * @return HooshMarketing_Marketo_Model_Personalize_Category_Path
     */
    protected function _getPersonalizeCategoryModel() {
        return Mage::getModel("hoosh_marketo/personalize_category_path");
    }
}