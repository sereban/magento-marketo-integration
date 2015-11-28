<?php
/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Abstract
 * @method Varien_Data_Form_Element_Abstract getElement()
 * @method $this setName(string $name)
 */
abstract class HooshMarketing_Marketo_Block_System_Config_Mapping_Abstract
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_renderCode;
    //Renderers and columns
    protected $_groupRenderer = array();
    protected $_columns = array();
    protected $_selectRenderedColumns = array();
    private $_arrayRowsCache;
    //Labels and configs
    protected $_addButtonLabel     = "Add New Mapping Field";
    protected $_renderToJsTemplate = true;

    /**
     * @return array
     */
    public function getArrayRows()
    {
        if (null !== $this->_arrayRowsCache) {
            return $this->_arrayRowsCache;
        }
        $result = array();
        $element = $this->getElement();

        if ($element->getData("value") && is_array($element->getData("value"))) {
            foreach ($element->getData("value") as $rowId => $row) {
                if(empty($row)) continue; //Adding check if row is empty or not
                foreach ($row as $key => $value) {
                    $row[$key] = $this->escapeHtml($value);
                }
                $row['_id'] = $rowId;
                $result[$rowId] = new Varien_Object($row);
                $this->_prepareArrayRow($result[$rowId]);
            }
        }

        $this->_arrayRowsCache = $result;
        return $this->_arrayRowsCache;
    }

    protected function _prepareToRender()
    {
        foreach($this->_columns as $fieldCode => $data) {
            $this->addColumn($fieldCode, $data);
        }

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('hoosh_marketo')->__($this->_addButtonLabel);
    }

    /**
     * @param string $code
     * @return HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
     */
    protected function _getRenderer($code) {
        if (!isset($this->_groupRenderer[$code])) {
            $this->_groupRenderer[$code] = $this->getLayout()->createBlock(
                "hoosh_marketo/system_config_mapping_renderer_$code", "" ,
                    array('is_render_to_js_template' => $this->_renderToJsTemplate)
            );
        }

        return $this->_groupRenderer[$code];
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        foreach($this->_selectRenderedColumns as $column) {
            if($renderer = $this->_getRenderer($column)) {
                $parentField = $renderer->getParentFieldCode(); //it it is null use standard mechanism
                if(!empty($parentField))
                    $parentField = $row->getData($parentField);

                $renderer->setData("row", $row);
                $row->setData(
                    'option_extra_attr_' . $renderer->calcOptionHashWithObject(
                        $row->getData($column),
                        $parentField
                    ),
                    'selected="selected"'
                );
            }
        }
    }

}