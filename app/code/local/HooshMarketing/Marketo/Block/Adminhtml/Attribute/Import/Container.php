<?php
class HooshMarketing_Marketo_Block_Adminhtml_Attribute_Import_Container extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected $_blockGroup = "hoosh_marketo";
    protected $_controller = "adminhtml_attribute";
    protected $_mode       = "import";

    protected $_headerText = 'Attribute Importer';

    public function __construct() {
        parent::__construct();
        $this->_removeButton("reset");
        $this->_removeButton("save");

        $this->_addButton("import", array(
            "label"   => "Import",
            "onclick" => "editForm.submit()",
            "class"   => "save"
        ));
    }
}