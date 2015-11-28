<?php
/**
 * Marketo Lead Model
 * @method string getStoreId()
 * @method string getLastProductId()
 * @method string getLastActivityTime()
 * @method string getUpdatedAt()
 * @method string getCookie()
 * @method string setCookie(string $cookie)
 * @method void   setLastProductId(int $id)
 * @method void   setLastActivityTime(string $dateTime)
 *
 * @category HooshMarketing
 * @package HooshMarketing_Marketo
 * @author Konstantin Zima
 *
 */
class HooshMarketing_Marketo_Model_Lead extends HooshMarketing_Marketo_Model_Eav_Abstract
{
    const ENTITY              = 'marketo_lead';
    const SESSION_NAMESPACE   = "lead";
    const LEAD_ID             = "Id";
    const LEAD_EMAIL          = "Email";
    const API_TYPE            = 1;
    const LAST_VIEWED_PRODUCT = "last_product_id";

    public $id;
    protected $_resourceCollectionName;

    protected function _construct()
    {
        $this->_init("hoosh_marketo/lead");
    }

    /** Should be synchronize with session */
    protected function _afterSave() {
        $this->getScopeSession("lead")->setData("lead", $this);
    }

    /**
     * @return null|string (Email address)
     */
    public function getEmail() {
        return $this->getData("Email");
    }

    /**
     * @return int (Lead Id in marketo)
     */
    public function getLeadId() {
        return $this->getData("Id");
    }

    /**
     * Load data by cookie
     * @return $this
     */
    public function getLoadedByCookie() {
        if(!$this->getId()) {
            $this->loadByAttribute("cookie", $this->getScopeSession("lead")->getMarketoCookie());
        }

        return $this;
    }

    /**
     * @return HooshMarketing_Marketo_Model_Api_Lead
     */
    public function getApiAdapter() {
        return Mage::getSingleton("hoosh_marketo/api_lead");
    }

    /**
     * @param array $out
     * @param array $dispatched - return to dispatch event object from which additional fields got
     */
    public function prepareLeadToSync(array $out, $dispatched = array()) {
        Varien_Profiler::start("marketo_lead_syncing");

        $leadRecordArray = $this->_dispatchLeadEvent($out, $dispatched);
        $this->getLoadedByCookie(); //load model

        try {
            $this->addData($leadRecordArray);
            $this->save();
        } catch(Exception $e) {
            Mage::logException($e);
        }

        Varien_Profiler::stop("marketo_lead_syncing");
    }

    /**
     * @param array $leadRecordArray
     * @param array $dispatched
     * @method getLeadRecord() -> call to get leadRecordArray
     * @return array
     */
    protected function _dispatchLeadEvent(array $leadRecordArray, $dispatched = array()) {
        $toSend = array("lead_record" => &$leadRecordArray);
        $toSend = $dispatched + $toSend;

        Mage::dispatchEvent("sync_lead_before", $toSend);
        return $leadRecordArray;
    }

    /**
     * @Override
     * @return Mage_Core_Model_Abstract|void
     */
    public function save() {
        if(!(bool)$this->getData("cookie")) return false;

        return parent::save();
    }

    /**
     * @return HooshMarketing_Marketo_Model_Resource_Lead_Collection
     * @throws Mage_Core_Exception
     */
    public function getResourceCollection()
    {
        if (empty($this->_resourceCollectionName)) {
            Mage::throwException(Mage::helper('hoosh_marketo')->__('The model collection resource name is not defined.'));
        }

        $collection = Mage::getResourceModel($this->_resourceCollectionName);
        return $collection;
    }

    /**
     * Return status of lead -> true : authorize; false : anonymous
     * @return bool
     */
    public function checkLeadAuth() {
        $this->getLoadedByCookie(); //prepare lead if his data exists
        return (bool)$this->getEmail();
    }

    public function checkLastViewedProduct(Mage_Sales_Model_Quote_Item $quoteItem) {
        $this->getLoadedByCookie(); //prepare lead if his data exists
        $lastViewed = $this->getData(self::LAST_VIEWED_PRODUCT);

        if($lastViewed == $quoteItem) //remove last viewed product
            $this->setData(self::LAST_VIEWED_PRODUCT, null);
    }

    /**
     * @return array
     */
    public function toOptionArray() {
        $options = array();
        /** @var HooshMarketing_Marketo_Model_Eav_Attribute $_hooshEav */
        $_hooshEav   =  Mage::getSingleton("hoosh_marketo/eav_attribute");
        $_attributes = $_hooshEav->getLeadAttributes();
        /** @var HooshMarketing_Marketo_Model_Eav_Attribute  $attribute */
        foreach($_attributes as $attribute) {
            $options[] = array(
                "label" => $attribute->getAttributeCode(),
                "value" => $attribute->getAttributeCode()
            );
        }

        return $options;
    }

    public function isValid() {
        return $this->getId() && $this->getCookie();
    }
}