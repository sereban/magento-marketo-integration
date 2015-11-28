<?php
class HooshMarketing_Marketo_Model_Observer_Quote extends HooshMarketing_Marketo_Model_Abstract
{
    const DEFAULT_QUOTE_KEY = "Magento_Sales_Quotes";
    const DEFAULT_ORDER_KEY = "Magento_Sales_Order";

    private static $_quoteItem = array(); //to store quote item before save data

    private static $_dataChanges = array();

    /**
     * Frontend
     * Before quote_item saved in cart
     * Events: sales_quote_item_save_before
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function quoteItemSaveBefore(Varien_Event_Observer $observer)
    {
        if (!$this->_getHelper()->getModuleStatus()) return false;
        $_item = $observer->getEvent()->getData("data_object");
        self::$_quoteItem["item_id"] = $_item->getId();

        return true;
    }


    /**
     * If item has parent -> it don`t pass validation
     * @param Mage_Sales_Model_Quote_Item | Mage_Sales_Model_Order_Item $item
     * @return bool
     */
    protected function _isChildrenItem($item) {
        return (bool)$item->getParentItemId();
    }

    /**
     * @return bool
     */
    protected function _isUpdate() {
        $id = (isset(self::$_quoteItem["item_id"])) ? self::$_quoteItem["item_id"] : null;
        return !empty($id); //if item already has id -> than we should update item
    }

    /**
     * Frontend
     * After quote_item saved in cart
     * Events: sales_quote_item_save_after
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function quoteItemSave(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote_Item $item */
        $item = $observer->getEvent()->getData("data_object"); //getting object from standard variable data_object
        $quoteIds  = $this->_getLeadModel()->getData($this->_getQuoteKey());

        /*** UPDATE ***/
        if ($this->_isUpdate() || $this->_isChildrenItem($item)) {
            $operation = HooshMarketing_Marketo_Model_Opportunity::UPDATE_OPPORTUNITY;
        } else {
            //PERSONALIZING: add score, when adding product to cart
            $_calculator = $this->_getPesonalizeCalculator();
            $_calculator->score(null, $item->getProduct(), $_calculator::CHECKOUT_CART_STEP);
            //Adding quote id to lead
            $quoteIds  = $this->_getHelper()->processMultipleIds(
                $this->_getLeadModel()->getData($this->_getQuoteKey()), //is already loaded in calculator
                $item->getQuoteId()
            );

            $operation = HooshMarketing_Marketo_Model_Opportunity::CREATE_OPPORTUNITY;
        }
        //Sync Quote ids
        $this->_getLeadModel()->prepareLeadToSync(
            array(
                $this->_getQuoteKey() => $quoteIds,
                "last_product_view"   => null //set last product into null
            )
        );
        /* prepared for creating opportunity */
        $this->_getOpportunityModel()->prepareOpportunityToSync(
            array(
                "data"      => array($this->_getOpportunityModel()->prepareQuoteItemData($item, $operation)),
                "operation" => $operation
            )
        );

        return true;
    }


    /**
     * @return string
     */
    protected function _getQuoteKey() {
        return $this->_getHelper()->getSyncKeys("lead_quote", self::DEFAULT_QUOTE_KEY);
    }

    protected function _getOrderKey() {
        return $this->_getHelper()->getSyncKeys("opportunity_sales_order", "Magento_Sales_Order");
    }

    /**
     * Frontend
     * Remove quote item from cart
     * Events: sales_quote_remove_item
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function updateRemovedOpportunity(Varien_Event_Observer $observer) {
        if (!$this->_getHelper()->getModuleStatus()) return false;
        /** @var Mage_Sales_Model_Quote_Item $item */
        $item           = $observer->getEvent()->getData("quote_item");

        $_data = array(
            "data"      => array($this->_getOpportunityModel()->getOpportunityKey() => $item->getId()),
            "operation" => HooshMarketing_Marketo_Model_Opportunity::REMOVE_OPPORTUNTY
        );

        $this->_getOpportunityModel()->prepareOpportunityToSync($_data); //prepare removed data and save it
        return true;
    }
    /**
     * Frontend
     * Set flag -> synced by marketo
     * Before order Item save
     * Events: sales_order_item_save_before
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function saveOrderItemBefore(Varien_Event_Observer $observer) {
        if (!$this->_getHelper()->getModuleStatus()) return false;

        $orderItemBefore = $observer->getEvent()->getData("data_object");
        /* TODO: make it in transaction: setting to 0 if error occured */
        /* @var $orderItemBefore Mage_Sales_Model_Order_Item */
        $orderItemBefore->setData(HooshMarketing_Marketo_Model_Opportunity::HISTORY_UPDATE_SYNCED_COLUMN, 1); //add as dispatched

        return true;
    }

    /**
     * Frontend
     * Synced with marketo
     * After order Item save
     * Events: sales_order_item_save_after
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function saveOrderItemAfter(Varien_Event_Observer $observer) {
        if (!$this->_getHelper()->getModuleStatus()) return false;

        /* @var $orderItem Mage_Sales_Model_Order_Item */
        $orderItem = $observer->getEvent()->getData("data_object");

        if($this->_isChildrenItem($orderItem)) return false;
        $orderIds  = $this->_getLeadModel()
            ->getLoadedByCookie()
            ->getData($this->_getOrderKey());

        $_orderIds = $this->_getHelper()->processMultipleIds($orderIds, $orderItem->getOrderId());

        if(strcmp($orderIds, $_orderIds)) { //if we have difference between 2 string: negative or positive no matter
            /* Settings new order id to lead */
            $this
                ->_getLeadModel()
                ->prepareLeadToSync(
                    array(
                        $this->_getOrderKey() => $_orderIds
                    )
                );
        }
        /* Setting new score by adding score to order item category */
        $calculator = $this->_getPesonalizeCalculator();
        $calculator->score(
            null,
            $this->_getHelper()->getProductFromId($orderItem->getProductId()),
            $calculator::ORDER_CART_STEP
        );
        //UPDATE OPPORTUNITY
        $this->_getOpportunityModel()->prepareOpportunityToSync(
            array(
                "data"      => array($this->_getOpportunityModel()->prepareOrderItemData($orderItem)),
                "operation" => HooshMarketing_Marketo_Model_Opportunity::UPDATE_OPPORTUNITY
            )
        );

        return true;
    }
    /**
     * Frontend
     * Check whether data need to mapping was changed or not
     * Events: sales_quote_address_save_before
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function syncBillingAddressBefore(Varien_Event_Observer $observer) {
        if (!$this->_getHelper()->getModuleStatus()) return false;
        $_quoteAddress = $observer->getEvent()->getData("data_object");
        if(!$_quoteAddress || $_quoteAddress->getAddressType() != "billing") return false;

        $_fields   = $this->_getMappingObserver()->getDataChangeFields("lead", "billing_address");

        self::$_dataChanges["billing_address"] = $this->_getHelper()->hasMarketoDataChanged($_quoteAddress, $_fields);

        return false;
    }
    /**
     * Frontend
     * Choose only billing address and save it
     * Events: sales_quote_address_save_after
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function syncBillingAddress(Varien_Event_Observer $observer) {
        if (!$this->_getHelper()->getModuleStatus()) return false;
        //Check if need to us data has changed
        if (isset(self::$_dataChanges["billing_address"]) && !self::$_dataChanges["billing_address"]) return false;
        /** @var Mage_Sales_Model_Quote_Address $_quoteAddress */
        $_quoteAddress = $observer->getEvent()->getData("data_object");
        if(!$_quoteAddress || $_quoteAddress->getAddressType() != "billing") return false;

        self::$_dataChanges["billing_address"] = false;

        $this->_getLeadModel()->prepareLeadToSync(
            $this->_getHelper()->getCompanyParamToSync(),
            array("billing_address" => $_quoteAddress)
        );

        return true;
    }


}