<?php
class HooshMarketing_Marketo_Model_Mapping_Classes_Quote
    extends HooshMarketing_Marketo_Model_Mapping_Classes_Abstract
{
    protected $_key = "quote";
    protected $_fieldIdentifier = array(
        "sales_flat_quote" => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::TABLE_TYPE
    );

    /**
     * @return Mage_Checkout_Model_Session
     */
    public function getSession() {
        return Mage::getSingleton("checkout/session");
    }

    public function prepare(Varien_Event_Observer $observer) {
        $quote = null;

        if($this->getSession()) {
            $quote = $this->getSession()->getQuote();
        }

        return $quote;
    }
}