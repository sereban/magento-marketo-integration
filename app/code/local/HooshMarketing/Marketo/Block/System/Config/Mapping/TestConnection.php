<?php
class HooshMarketing_Marketo_Block_System_Config_Mapping_TestConnection extends  Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_template = "marketo/core/config/testconnection.phtml";

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $div = '<div id="canvas"></div>';
        $testConnection = '<button id="test_connection"> <span><span> Test Connection </span></span></button>&nbsp;';
        $leadTest = '<button id="lead_test"> <span><span> Lead Test </span></span></button>&nbsp;';
        $oppTest = '<button id="opp_test"> <span><span> Opportunity Test </span></span></button>';

        return $div.$testConnection.$leadTest.$oppTest.$this->_toHtml();
    }

    public function getTestConnectionUrl() {
        return $this->getUrl("*/marketo_test/connection"); // test in backend
    }

    public function getWorkTestUrl() {
        return $this->getUrl("*/marketo_test/work"); // test in frontend
    }
}