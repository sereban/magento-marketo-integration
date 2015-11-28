<?php
class HooshMarketing_Marketo_Model_Mapping_Classes_Customer
    extends HooshMarketing_Marketo_Model_Mapping_Classes_Abstract
{
    protected $_addressKey = "customer_address";
    protected $_key = "customer";
    //Takes fields to mapping from table or from attribute table
    protected $_fieldIdentifier = array(
        "country"          => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::SIMPLE_FIELD,
        "first_purchase"   => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::SIMPLE_FIELD,
        "last_purchase"    => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::SIMPLE_FIELD,
        "purchases_number" => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::SIMPLE_FIELD,
        "lifetime_sales"   => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::SIMPLE_FIELD,
        "customer"         => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::ATTRIBUTE_TYPE,
        "customer_address" => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::ATTRIBUTE_TYPE,
        "customer_entity"  => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::TABLE_TYPE
    );

    protected $_preparedCallbacks = array(
        "_country"         => "customer_address",
        "_customerAddress" => "customer",
        "_sales"           => "customer"
    );

    /**
     * @param Mage_Customer_Model_Address $address
     */
    protected function _country(&$address) {
        $_country = Mage::app()->getLocale()->getCountryTranslation($address->getData("country_id"));
        $address->setData("country", $_country);
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function _sales(&$customer) {
        $orderTable = $this->_getCoreResource()->getTableName("sales_flat_order");

        $_result = $this->_getAdapter() //Aggregate functions used -> result will have only one string
            ->select()
            ->from(
                $orderTable,
                array(
                    "first_purchase"   => new Zend_Db_Expr("MIN(`created_at`)"),
                    "last_purchase"    => new Zend_Db_Expr("MAX(`created_at`)"),
                    "purchases_number" => new Zend_Db_Expr("COUNT(`created_at`)"),
                    "lifetime_sales"   => new Zend_Db_Expr("SUM(`grand_total`)")
                )
            )
            ->where("customer_id = ?", $customer->getId());

        $customer->addData($this->_getAdapter()->fetchRow($_result)); //setting new data to customer
    }

    protected function _customerAddress(Mage_Customer_Model_Customer &$customer) {
        $_address = $customer->getDefaultBillingAddress(); //use default billing address as address synced in Marketo

        if(!$_address)
            return false;
        //PREPARE ADDITIONAL DATA
        $_address->setData($this->_addressKey, true);
        $this->_prepareData($_address);

        foreach($_address->getData() as $field => $value) {
            if(!$customer->hasData($field)) //replace only when customer
                $customer->setData($field, $value);

        }

        return true;
    }
}