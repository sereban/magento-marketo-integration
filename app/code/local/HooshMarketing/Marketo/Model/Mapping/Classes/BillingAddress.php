<?php
class HooshMarketing_Marketo_Model_Mapping_Classes_BillingAddress
    extends HooshMarketing_Marketo_Model_Mapping_Classes_Abstract
{
    protected $_key           = "billing_address";
    protected $_fieldIdentifier = array(
        "country"                  => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::SIMPLE_FIELD,
        "sales_flat_quote_address" => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::TABLE_TYPE
    );

    protected $_preparedCallbacks = array(
        "_country"         => "billing_address"
    );
    /**
     * @param Mage_Sales_Model_Quote_Address $address
     */
    protected function _country(&$address) {
        $_country = Mage::app()->getLocale()->getCountryTranslation($address->getData("country_id"));
        $address->setData("country", $_country);
    }
}