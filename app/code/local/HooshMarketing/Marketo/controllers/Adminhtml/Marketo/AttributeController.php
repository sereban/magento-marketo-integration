<?php
class HooshMarketing_Marketo_Adminhtml_Marketo_AttributeController extends Mage_Adminhtml_Controller_Action
{
    /* @var array $_attributeCodeFieldsMapping
     * The value of this array will be used in attribute code
     */
    protected $_attributeCodeFieldsMapping = array(
        "marketo_lead"        => "soap_api_name",
        "marketo_opportunity" => "soap_api_name"
    );

    public function importAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function importPostAction() {
        /** @var HooshMarketing_Marketo_Helper_Import_Attribute  $import */
        $import = Mage::helper("hoosh_marketo/import_attribute");
        $fileName = $_FILES["csv_data"]["tmp_name"];
        $entityTypeCode = $this->getRequest()->getPost("entity_type_code");
        $attributeCodeField = isset($this->_attributeCodeFieldsMapping[$entityTypeCode]) ? $this->_attributeCodeFieldsMapping[$entityTypeCode] : "soap_api_name";

        /* prepare import model*/
        $import->setSource($fileName)
               ->setEntityTypeCode($this->getRequest()->getPost("entity_type_code"))
               ->setAttributeCodeField($attributeCodeField);
        /* prepare csv adapter */
        $import
            ->getAdapter()
            ->setDelimiter(",")
            ->setMappedHeaders(array(
                "REST API Name" => "rest_api_name",
                "SOAP API Name" => "soap_api_name",
                "Friendly Label" => "friendly_name"
            ));

        try {
            $import->import();
            $this->_getSession()->addSuccess("Attributes imported successfully");
        } catch(Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
        }

        $this->_redirect("*/*/import");
    }
    /** @return Mage_Adminhtml_Model_Session */
    protected function _getSession() {
        return Mage::getSingleton("adminhtml/session");
    }
}