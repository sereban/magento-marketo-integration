<?php
/**
 * @method string getFrontendType()
 * @method string getSoapApiName()
 * @method int    getApiType()
 * @method string getFrontendInput()
 * @method string setFrontendType(string $frontendType)
 * @method string setFrontendInput(string $frontendInput)
 */
class HooshMarketing_Marketo_Model_Eav_Attribute extends Mage_Eav_Model_Attribute
{
    /**
     * Event prefix
     * used when model saved
     * @var string
     */
    protected $_eventPrefix                     = 'marketo_attribute';

    /**
     * Default Frontend Type
     *
     * @var string
     */
    protected $_defaultFrontendType             = 'varchar';
    /**
     * Default Frontend Input in eav/attribute table
     *
     * @var string
     */
    protected $_defaultFrontendInput            = 'text';
    /* @var HooshMarketing_Marketo_Model_Resource_Attribute_Collection */
    protected $_collection;

    protected function _construct()
    {
        $this->_init('hoosh_marketo/attribute'); //init resource_model
    }

    /**
     * @param string $friendlyName
     * @return Varien_Object|HooshMarketing_Marketo_Model_Eav_Attribute
     */
    public function getAttributeByFriendlyName($friendlyName) {
        /* @var $collection HooshMarketing_Marketo_Model_Resource_Attribute_Collection */
        $collection = $this->getCollection()
                           ->addFieldToFilter("friendly_name", $friendlyName);

        if($collection->count()) {
            return $collection->getFirstItem();
        }

        return new Varien_Object();
    }

    /**
     * @return HooshMarketing_Marketo_Model_Resource_Attribute_Collection
     */
    public function getLeadAttributes() {
        return $this
            ->getCollection()
            ->addFieldToFilter("api_type", HooshMarketing_Marketo_Model_Lead::API_TYPE);
    }
    /**
     * @return HooshMarketing_Marketo_Model_Resource_Attribute_Collection
     */
    public function getOpportunityAttributes() {
        return $this
            ->getCollection()
            ->addFieldToFilter("api_type", HooshMarketing_Marketo_Model_Opportunity::API_TYPE);
    }

    /**
     * Check if attribute is marketo attribute and can be syncrhonize
     * @param $attributeCode
     * @return bool
     */
    public function isMarketoAttribute($attributeCode) {
        if(!$this->_collection) {
            $this->_collection = $this->getCollection();
        }

        /* @var $item self */
        $item = $this->_collection->getItemByColumnValue("attribute_code", $attributeCode);
        if(!$item) return false;
        return HooshMarketing_Marketo_Model_Opportunity::API_TYPE == $item->getApiType() || HooshMarketing_Marketo_Model_Lead::API_TYPE == $item->getApiType();
    }
}