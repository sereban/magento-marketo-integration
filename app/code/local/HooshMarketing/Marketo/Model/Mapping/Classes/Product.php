<?php
class HooshMarketing_Marketo_Model_Mapping_Classes_Product
    extends HooshMarketing_Marketo_Model_Mapping_Classes_Abstract
{
    const ATTRIBUTE_TYPE_TO_PREPARE = "int";

    protected $_key = "product";
    protected $_fieldIdentifier = array(
        "catalog_product"        => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::ATTRIBUTE_TYPE,
        "catalog_product_entity" => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::TABLE_TYPE
    );
    //Calback should be prepared
    protected $_preparedCallbacks = array(
        "_attributes" => "product"
    );

    /**
     * @param Mage_Catalog_Model_Product $product
     */
    protected function _attributes(Mage_Catalog_Model_Product &$product) {
        $_attributes = $product->getAttributes();
        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        foreach($_attributes as $attribute) {
            if($attribute->getBackendType() == self::ATTRIBUTE_TYPE_TO_PREPARE) {
                $attributeText = $product->getAttributeText($attribute->getAttributeCode()); //getting attribute value
                $product->setData($attribute->getAttributeCode(), $attributeText); //setting this to product  (override existing integer value)
            }
        }
    }
}