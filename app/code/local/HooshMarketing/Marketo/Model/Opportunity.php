<?php

/**
 * Class HooshMarketing_Marketo_Model_Opportunity
 * @method setParentId(int $id)
 * @method setAction(int $action)
 * @method setQuantity(int $action)
 * @method int getAction()
 * @method int getStoreId()
 * @method int getParentId() -> return lead id
 */
class HooshMarketing_Marketo_Model_Opportunity extends HooshMarketing_Marketo_Model_Eav_Abstract
{
    /* entity for detect current typeInstance */
    const ENTITY             = "marketo_opportunity";
    const SESSION_NAMESPACE  = "opportunity";
    const DEFAULT_KEY        = "Magento_Line_Item_Id";
    /* consant methods */
    const UPDATE_OPPORTUNITY = "update";
    const CREATE_OPPORTUNITY = "create";
    const REMOVE_OPPORTUNTY  = "remove";
    /* TODO: move to model and change there update and create codes to the same digits */
    const NEED_TO_UPDATE = 2;
    const NEED_TO_CREATE = 1;
    const EMPTY_OPPORTUNITY = "a:0:{}";
    /** History Updates */
    const HISTORY_UPDATE_SYNCED_COLUMN = "marketo_history_synced"; //used in sales_flat_order_item table to check if order item was synced with marketo or no
    const API_TYPE           = 2;
    const MARKETO_SYNCED     = 1;
    const MARKETO_NON_SYNCED = 0;
    //HARDCODED FIELDS
    const LEAD_SOURCE        = "LeadSource"; //store name + domain
    const LAST_ACTIVITY_DATE = "LastActivityDate";
    const OPPORTUNITY_TYPE   = "Type";
    const IS_CLOSED          = "IsClosed";
    const STAGE              = "Stage";
    const EXPECTED_REVENUE   = "ExpectedRevenue";
    const AMOUNT             = "Amount";
    const CLOSE_DATE         = "CloseDate";
    const IS_WON             = "IsWon";
    const PROBABILITY        = "Probability";

    protected $_bannedProductTypes = array("configurable");

    protected function _construct() {
        $this->_init("hoosh_marketo/opportunity");
    }

    protected function _afterSave() {
        $this->getScopeSession("opportunity")->setData("opportunities", $this);
    }

    /**
     * @return string
     */
    public function getOpportunityKey() {
        return $this->_getHelper()->getSyncKeys("opportunity_line_item_id", self::DEFAULT_KEY) ;
    }

    /**
     * @param string $email
     */
    public function historyUpdate($email) {
        $quoteAddressTable = $this->_getCoreResource()->getTableName("sales_flat_order_address");
        /** @var Mage_Sales_Model_Resource_Quote_Item_Collection $quoteItems */
        $quoteItems = Mage::getModel("sales/order_item")
            ->getCollection()
            ->addFieldToFilter('parent_item_id', array('null' => true))
            ->addFieldToFilter(self::HISTORY_UPDATE_SYNCED_COLUMN, self::MARKETO_NON_SYNCED);

        $quoteItems->getSelect()
            ->joinLeft(
                array("order_address_table"=>$quoteAddressTable),
                "main_table.order_id = order_address_table.parent_id AND address_type='billing'",
                array("email"=>"order_address_table.email")
            )
            ->where("email = ?", $email);

        $dataToSync = array();
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach($quoteItems as $quoteItem) {
            try {
                $quoteItem->setData(self::HISTORY_UPDATE_SYNCED_COLUMN, self::MARKETO_SYNCED);
                $quoteItem->save();
            } catch(Exception $e) {
                Mage::logException($e);
            }

            $dataToSync[] = $this->prepareQuoteItemData($quoteItem,
                self::CREATE_OPPORTUNITY,
                array(
                    "Stage"    => $this->_getHelper()->getHardcodedOpportunityFields("purchase_stage"),
                    "IsClosed" => true,
                    "IsWon"    => true
            ));
        }

        if(!empty($dataToSync)) {
            $this->prepareOpportunityToSync(
                array(
                    "data"      => $dataToSync,
                    "operation" => self::CREATE_OPPORTUNITY
                )
            );
        }

    }

    /**
     * This function also initialize lead modele by cookie
     * @return bool
     */
    protected function _errorOpportunity() {
        return !$this->_getLeadModel()->getLoadedByCookie()->getId();
    }

    /**
     * @param array $_toSync  -> consist operation and data keys
     * @return bool
     */
    public function prepareOpportunityToSync(array $_toSync) {
        if($this->_errorOpportunity()
            || Mage::app()->getStore()->isAdmin()) return false; //check if everything is ok
        $leadId = $this->_getLeadModel()->getId();

        try {
            /* update or create opportunity and log them to database */
            switch($_toSync["operation"]) {
                case self::CREATE_OPPORTUNITY:
                    foreach ($_toSync["data"] as $opportunity) {
                        $this->_create($this, $opportunity, $leadId);
                    }
                    break;
                case self::UPDATE_OPPORTUNITY:
                    foreach ($_toSync["data"] as $opportunity) {
                        if (!isset($opportunity[$this->getOpportunityKey()])) { //we need to update all
                            foreach ($this->getCollection()->loadByLead($leadId) as $loadOpp) {
                                $this->_update($loadOpp, $opportunity);
                            }
                        } else {
                            $this->loadByAttribute($this->getOpportunityKey(),
                                $opportunity[$this->getOpportunityKey()]);

                            if($this->getId()) {
                                $this->_update($this, $opportunity);
                            }
                        }
                    }
                    break;
                case self::REMOVE_OPPORTUNTY:
                    foreach ($_toSync["data"] as $opportunity) { //should be quote_item_ids
                        //Load Opportunity
                        $this->loadByAttribute($this->getOpportunityKey(), $opportunity);
                        //Update it
                        $this->_update($this, $this->prepareRemovedOpportunityData());
                    }
                    break;
                }
            } catch(Exception $e) {
                Mage::logException($e);
            }

        return true;
    }

    /**
     * Update only already existing opportunity
     * @param HooshMarketing_Marketo_Model_Opportunity $opportunity
     * @param array $_data
     * @throws Exception
     */
    protected function _update(HooshMarketing_Marketo_Model_Opportunity $opportunity, array $_data) {
        $opportunity->addData($_data);
        $opportunity->pushAction(self::NEED_TO_UPDATE);
        $opportunity->save();
    }

    /**
     * In every situation will create new opportunity
     * @param HooshMarketing_Marketo_Model_Opportunity $opportunity
     * @param array $_data
     * @param int $leadId
     * @throws Exception
     */
    protected function _create(HooshMarketing_Marketo_Model_Opportunity $opportunity, array $_data, $leadId) {
        $opportunity->setData($_data);
        $opportunity->pushAction(self::NEED_TO_CREATE);
        $opportunity->setParentId($leadId);
        $opportunity->save();
    }

    /**
     * @return HooshMarketing_Marketo_Model_Api_Opportunity
     */
    public function getApiAdapter() {
        return Mage::getSingleton("hoosh_marketo/api_opportunity");
    }

    /**
     * Add new Action to process opportunity
     * @param int $action
     * @return void
     */
    public function pushAction($action) {
        $actions = $this->_getActions(); //get everything from  attribute

        if(!in_array($action, $actions)) {
            array_push($actions, $action);  //push new action to array
            $this->setAction($this->_getHelper()->serialize($actions));
        }
    }

    /**
     * @return int
     */
    public function shiftAction() {
        $actions = $this->_getActions();

        $shift   = array_shift($actions);
        $this->setAction($this->_getHelper()->serialize($actions));

        return $shift;
    }

    /**
     * Remove all already exist actions
     */
    public function discardActions() {
        $this->setAction($this->_getHelper()->serialize(array())); //set zero actions
    }

    /**
     * @return array
     */
    protected function _getActions() {
        $_actions = $this->_getHelper()->unSerialize($this->getAction());
        return (!is_array($_actions)) ? array() : $_actions;
    }

    /**
     * @param Mage_Sales_Model_Quote_Item|null $quoteItem
     * @param $operation
     * @param array $_defaults
     * @return array
     */
    public function prepareQuoteItemData(
        Mage_Sales_Model_Quote_Item $quoteItem = null,
        $operation,
        $_defaults = array())
    {
        $_data = array();
        $eventParams = array(
            "opportunity_data" => &$_data,
        );

        switch($operation) {
            case self::CREATE_OPPORTUNITY:
                $_data        = array( //Default attributes
                    self::IS_CLOSED => false,
                    self::STAGE     => $this->_getHelper()->getHardcodedOpportunityFields("default_stage")
                );

                $_paths    = Mage::app()->getRequest()->getParam( //get path from HTTP GET
                    HooshMarketing_Marketo_Model_Personalize_Category_Path::CATEGORY_PATH
                );
                $_data[self::OPPORTUNITY_TYPE] = isset($_paths[$quoteItem->getProduct()->getId()]) ?
                    $_paths[$quoteItem->getProduct()->getId()] :
                    current($this->_getPersonalizeCategoryPathModel()->getPath($quoteItem->getProduct()));
                break;
            case self::UPDATE_OPPORTUNITY:
                if($quoteItem->getParentItemId()) {
                    $_data[$this->getOpportunityKey()] = $quoteItem->getParentItemId();
                }
                break;
        }

        $eventParams["product"] = $quoteItem->getProduct();
        if(!$quoteItem->getParentItemId()) //ignore children quote items
            $eventParams["quote_item"] = $quoteItem;

        Mage::dispatchEvent("opportunity_dynamic_before_{$operation}", $eventParams);

        if(!empty($_defaults)) {
            $_data = $_defaults + $_data; //merge defaults and data. Data have priority
        }

        if($quoteItem->getParentItemId()) {
            $_data[$this->getOpportunityKey()] = $quoteItem->getParentItemId();
        }

        return $_data;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return array
     */
    public function prepareOrderItemData($orderItem) {
        $data = array(
            $this->getOpportunityKey() => $orderItem->getQuoteItemId(),
            self::IS_CLOSED            => true,
            self::IS_WON               => true,
            self::CLOSE_DATE           => $this->_getDateTimeHelper()->getServerDate(),
            self::STAGE                => $this->_getHelper()->getHardcodedOpportunityFields("purchase_stage"),
            self::PROBABILITY          => $this->_getHelper()->getHardcodedOpportunityFields("purchase_probability"),
        );

        $this->_addCommonData($data);

        Mage::dispatchEvent("opportunity_dynamic_before_update",
                array("opportunity_data" => &$data , "order_item" => $orderItem ));

        return $data;
    }

    /**
     * Prepare opportunity data before removing it from cart
     * @return array
     */
    public function prepareRemovedOpportunityData() {
        return array(
            self::IS_CLOSED        => true,
            self::EXPECTED_REVENUE => $this->_getHelper()->getHardcodedOpportunityFields("remove_amount"),
            self::AMOUNT           => $this->_getHelper()->getHardcodedOpportunityFields("remove_amount"),
            self::CLOSE_DATE       => $this->_getDateTimeHelper()->getServerDate(),
            self::STAGE            => $this->_getHelper()->getHardcodedOpportunityFields("remove_stage")
        );
    }

    /**
     * @param array $data Adding common data to opportunity
     */
    protected function _addCommonData(array &$data) {
        $data[self::LEAD_SOURCE]        = $this->_getHelper()->getHardcodedOpportunityFields("lead_source");
        $data[self::LAST_ACTIVITY_DATE] = $this->_getDateTimeHelper()->getServerDate();
    }

    /**
     * @param $productId
     * @param $store
     * @return array
     */
    public function getInfoFromProductId($productId, $store)
    {
        $product = Mage::getModel("catalog/product")->load((int)$productId); //product with data or empty product
        $data = array(
            self::LEAD_SOURCE      => $this->_getHelper()->getHardcodedOpportunityFields("lead_source", $store),
            self::OPPORTUNITY_TYPE => current($this->_getPersonalizeCategoryPathModel()->getPath($product)),
            self::IS_CLOSED        => false,
            self::IS_WON           => false,
            self::STAGE            => $this->_getHelper()->getHardcodedOpportunityFields("last_viewed_stage", $store),
        );  

        Mage::dispatchEvent("opportunity_dynamic_before_update",
            array("opportunity_data"=> &$data, "product" => $product));

        return $data;
    }
    /**
     * @return array
     */
    public function toOptionArray() {
        $options = array();
        /** @var HooshMarketing_Marketo_Model_Eav_Attribute $_hooshEav */
        $_hooshEav   =  Mage::getSingleton("hoosh_marketo/eav_attribute");
        $_attributes = $_hooshEav->getOpportunityAttributes();
        /** @var HooshMarketing_Marketo_Model_Eav_Attribute  $attribute */
        foreach($_attributes as $attribute) {
            $options[] = array(
                "label" => $attribute->getAttributeCode(),
                "value" => $attribute->getAttributeCode()
            );
        }

        return $options;
    }
}