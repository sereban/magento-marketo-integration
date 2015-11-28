<?php

class HooshMarketing_Marketo_Adminhtml_Marketo_TestController extends Mage_Adminhtml_Controller_Action
{
    const UNKNOWN_ERROR_STATUS = "ERROR";
    const UNKNOWN_ERROR_MESSAGE = "Problem with creating marketo request";
    const UNKNOWN_ERROR_METHOD = "Can`t define called method";
    const UNKNOWN_ERROR_TIME = 0;
    const EXCEPTION_ERROR_STATUS = "EXCEPTION";
    const EXCEPTION_ERROR_TIME = 0;


    public function connectionAction()
    {
        $this->getRequest()->setParam("type", "lead");
        $this->workAction();
    }

    protected function _prepareLeadSyncParams()
    {
        return array(
            array(
                "FirstName" => "Native_Magento_Test_Name",
                "LastName" => "Native_Magento_Test_LastName",
                "Email" => $this->_getEmail()
            )
        );
    }

    /**
     * @return HooshMarketing_Marketo_Model_Lead
     */
    protected function _getLeadModel()
    {
        return Mage::getSingleton("hoosh_marketo/lead");
    }

    /**
     * @return array
     */
    protected function _getRegisteredMarketoConnection()
    {
        return $this->_getApiHelper()->getSoapCallsCollection();
    }

    /**
     * @return HooshMarketing_Marketo_Model_Opportunity
     */
    private function _getOpportunityModel()
    {
        return Mage::getSingleton("hoosh_marketo/opportunity");
    }

    /**
     * @return HooshMarketing_Marketo_Helper_Api
     */
    protected function _getApiHelper()
    {
        return Mage::helper("hoosh_marketo/api");
    }

    protected function _addExceptionMessage(Exception $e, $type)
    {
        return array(array(
            "status" => self::EXCEPTION_ERROR_STATUS,
            "errMessage" => $e->getMessage(),
            "method" => $type,
            "diffTime" => self::EXCEPTION_ERROR_TIME
        )
        );
    }

    protected function _addUnknownError()
    {
        return array(array(
            "status" => self::UNKNOWN_ERROR_STATUS,
            "errMessage" => self::UNKNOWN_ERROR_MESSAGE,
            "method" => self::UNKNOWN_ERROR_METHOD,
            "diffTime" >= self::UNKNOWN_ERROR_TIME
        ));
    }

    public function workAction()
    {
        $connection = array();
        if ($this->getRequest()->getParam("type") == "lead") {
            $this->_getLeadModel()->getApiAdapter()->syncLead($this->_prepareLeadSyncParams(), 1);
        } else {
            $lead = $this->_getLeadModel()->getLoadedByCookie(); //load by cookie and check whether lead exist
            if (!$lead->getEmail()) {
                $this->_getLeadModel()->getApiAdapter()->syncLead($this->_prepareLeadSyncParams(), 1);
            }

            try {
                $this->_getOpportunityModel()->getApiAdapter()->syncOpportunity($this->_getTestQuoteItemsData());
            } catch (Exception $e) {
                $connection = $this->_addExceptionMessage($e, "Create Opportunity");
            }
        }

        if (empty($connection)) { //turn collection to array
            foreach ($this->_getRegisteredMarketoConnection() as $index => $_connection) {
                /** @var Varien_Object $_connection */
                $connection[$index] = $_connection->getData();
            }
        }

        if (!isset($connection[0]["status"]) || !isset($connection[0]["errMessage"]) || !isset($connection[0]["diffTime"])) {
            $connection = $this->_addUnknownError();
        }

        $this->getResponse()->setBody(Mage::helper("core")->jsonEncode($connection)); //add request to body
    }

    protected function _getEmail()
    {
        return "test" . date("Ymd", time()) . preg_replace("/\s+/", "", Mage::app()->getStore()->getName()) . "@gmail.com";
    }

    protected function _getTestQuoteItemsData()
    {
        $quote = Mage::getModel("sales/quote")
            ->getCollection()
            ->addOrder("items_count", "DESC")
            ->setPageSize(20);
        /** @var HooshMarketing_Marketo_Model_Opportunity $oppModel */
        $oppModel = Mage::getModel("hoosh_marketo/opportunity");
        $quoteData = array();
        /** @var Mage_Sales_Model_Quote $q */
        foreach ($quote as $q) {
            /** @var Mage_Sales_Model_Quote_Item $item */
            foreach ($q->getAllVisibleItems() as $item) {
                $quoteData = $oppModel->prepareQuoteItemData(
                    $item,
                    HooshMarketing_Marketo_Model_Opportunity::CREATE_OPPORTUNITY
                );
            }
            if (!empty($quoteData)) {
                break;
            }
        }

        if (empty($quoteData)) {
            throw new Exception("Prepared Quote Data to Sync is empty. Maybe you havn`t already exist quote items");
        }
        $quoteData["lead_id"] = $this->_getLeadModel()->getLeadId();

        return $quoteData;
    }

}