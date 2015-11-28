<?php
/* @var $installer  Mage_Eav_Model_Entity_Setup */
$installer = $this;
/** @var Varien_Db_Adapter_Pdo_Mysql $adapter */
$adapter = $installer->getConnection();

//Installing default attributes
$installer->startSetup();

$opportunityEavAttributes = Mage::app()->getConfig()->getNode("marketo/eav_attributes/opportunity")->asArray();
/* parse all attributes from config.xml, tag = attribute_code */
foreach($opportunityEavAttributes as $attributeCode => $attribute) {
    $installer->addAttribute("marketo_opportunity", $attributeCode, $attribute);
}

$leadEavAttributes = Mage::app()->getConfig()->getNode("marketo/eav_attributes/lead")->asArray();

foreach($leadEavAttributes as $attributeCode => $attribute) {
    $installer->addAttribute("marketo_lead", $attributeCode, $attribute);
}

$installer->endSetup();
