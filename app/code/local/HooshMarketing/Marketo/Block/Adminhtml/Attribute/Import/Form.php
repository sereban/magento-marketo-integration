<?php

class HooshMarketing_Marketo_Block_Adminhtml_Attribute_Import_Form extends Mage_Adminhtml_Block_Widget_Form
{
    const FORM_LABEL = "Choose csv File with marketo attributes";
    const LEGEND     = "Import Attributes From Csv";

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        /* Create Fieldset*/
        $fieldset = $form->addFieldset("import", array(
            "legend" => Mage::helper("hoosh_marketo")->__(self::LEGEND)));
        /* prepare fields */
        $fieldset->addField("csv", "file", array(
            "name"     => "csv_data",
            "label"    => Mage::helper("hoosh_marketo")->__(self::FORM_LABEL),
            "title"    => Mage::helper("hoosh_marketo")->__(self::FORM_LABEL),
            "required" => true
        ));

        $fieldset->addField("attribute_type", "select", array(
            "name"     => "entity_type_code",
            "label"    => Mage::helper("hoosh_marketo")->__("Attribute Type"),
            "title"    => Mage::helper("hoosh_marketo")->__("Attribute Type"),
            "values"   => array(
                HooshMarketing_Marketo_Model_Lead::ENTITY        => "Lead",
                HooshMarketing_Marketo_Model_Opportunity::ENTITY => "Opportunity"
            ),
            "required" => true
        ));

        /* prepare form */
        $form->setUseContainer(true);
        $form->setEnctype("multipart/form-data");
        $form->setMethod("post");
        $form->setId('edit_form');
        $form->setAction($this->getUrl('*/*/importPost'));
        $this->setForm($form);
    }
}