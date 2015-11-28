<?php
class HooshMarketing_Marketo_Model_Observer_Customer extends HooshMarketing_Marketo_Model_Abstract
{
    //KEYS
    const CUSTOMER         = "customer";
    const CUSTOMER_ADDRESS = "customer_address";
    /**
     * Place info -> whether origData and data have differences
     * @var array
     */
    private static $_dataChanges = array();
    /**
     * Frontend and Admin
     * When customer or customer address saved
     * Events: customer_address_save_after & customer_save_after
     * @param Varien_Event_Observer $observer
     * @param string $key
     * @return bool
     */
    public function customerSaveBefore(Varien_Event_Observer $observer, $key = self::CUSTOMER) {
        if (!$this->_getHelper()->getModuleStatus()) return false;
        $_object = $observer->getEvent()->getData("data_object");

        if(empty($_object)) {
            $key     = self::CUSTOMER_ADDRESS;
            $_object = $observer->getEvent()->getData($key);
        }

        $_fields   = $this->_getMappingObserver()->getDataChangeFields("lead", $key);

        self::$_dataChanges[$key] = $this->_getHelper()->hasMarketoDataChanged($_object, $_fields);

        return true;
    }
    /**
     * Frontend and Admin
     * When customer or customer address saved
     * Events: customer_address_save_after & customer_save_after
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function customerSave(Varien_Event_Observer $observer) {
        if (!$this->_getHelper()->getModuleStatus()) return false;

        $_customer = $observer->getEvent()->getData(self::CUSTOMER); //getting saved customer
        //Validation on chaning fields
        if($_customer && isset(self::$_dataChanges[self::CUSTOMER]) && !self::$_dataChanges[self::CUSTOMER])
            return false;
        /** @var Mage_Customer_Model_Address $_address */
        $_address  = $observer->getEvent()->getData(self::CUSTOMER_ADDRESS);
        if($_address && isset(self::$_dataChanges[self::CUSTOMER_ADDRESS]) && !self::$_dataChanges[self::CUSTOMER_ADDRESS])
            return false;

        //If we have customer_address
        if(empty($_customer))
            $_customer = $_address->getCustomer();

        $this->_prepareLead($_customer);

        return true;
    }

    /**
     * @param Mage_Customer_Model_Customer $_customer
     */
    protected function _prepareLead($_customer) {
        $this
            ->_getLeadModel()
            ->prepareLeadToSync( //prepare data to sync state
                $this->_getHelper()->getCompanyParamToSync(), //Add hardcode Company_Id params to data
                array(self::CUSTOMER => $_customer)
            );
    }

    /**
     * Frontend
     * When customer login on checkout or on customer login
     * Event: customer_login
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function customerLogin(Varien_Event_Observer $observer)
    {
        $helper = $this->_getHelper();
        if (!$helper->getModuleStatus()) return false;

        $_customer = $observer->getEvent()->getData(self::CUSTOMER);
        $lead  = $this->_getLeadModel()->loadByAttribute("email", $_customer->getEmail()); //load authorized customer

        if($lead && $lead->isValid()) { //checking id and cookie
            /** Set new cookie by email */
            Mage::app()->getCookie()->set(
                HooshMarketing_Marketo_Model_Session_Lead::COOKIE_CODE, $lead->getCookie()
            );
        }

        $this->_prepareLead($_customer);
        // HISTORY SYNCING -> Syncing of before placed order items to lead
        if(!$this->_getHelper()->getEnableConfig("history_sync_enabling")) {
            $this->_getOpportunityModel()->historyUpdate($_customer->getEmail());
        }

        return true;
    }
}