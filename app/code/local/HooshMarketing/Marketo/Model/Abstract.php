<?php
class HooshMarketing_Marketo_Model_Abstract extends Mage_Core_Model_Abstract
{
    /* @return HooshMarketing_Marketo_Model_Lead */
    protected function _getLeadModel() {
        return Mage::getSingleton("hoosh_marketo/lead");
    }
    /* @return HooshMarketing_Marketo_Helper_Data */
    protected function _getHelper() {
        return Mage::helper("hoosh_marketo");
    }
    /**
     * @param Mage_Core_Model_Abstract $model
     * @return HooshMarketing_Marketo_Model_Opportunity
     */
    protected function _getOpportunityModel($model = null) {
        return (!empty($model)) ? Mage::getModel("hoosh_marketo/opportunity") : Mage::getSingleton("hoosh_marketo/opportunity");
    }
    /* @return HooshMarketing_Marketo_Model_Personalize_Calculator */
    protected function _getPesonalizeCalculator() {
        return Mage::getSingleton("hoosh_marketo/personalize_calculator");
    }
    /* @return HooshMarketing_Marketo_Model_Personalize_Category_Path*/
    protected function _getPersonalizeCategoryPathModel() {
        return Mage::getSingleton("hoosh_marketo/personalize_category_path");
    }
    /* @return HooshMarketing_Marketo_Model_Personalize_Store */
    protected function _getPersonalizeStoreModel() {
        return Mage::getSingleton("hoosh_marketo/personalize_store");
    }
    /**
     * @return HooshMarketing_Marketo_Model_Resource_Opportunity_Collection
     */
    protected function _getOpportunitiesCollection() {
        return Mage::getSingleton("hoosh_marketo/opportunity")->getCollection()->addAttributeToSelect("*");
    }

    /**
     * @return bool|Mage_Catalog_Model_Product
     */
    protected function _getCurrentProduct() {
        $product = Mage::registry("current_product");
        
        if(!$product || !$product->getId()) return false;
        return $product;
    }

    /**
     * @param string $scope
     * @return false|Mage_Core_Model_Session_Abstract|HooshMarketing_Marketo_Model_Session_Lead|HooshMarketing_Marketo_Model_Session_Opportunity
     */
    public function getScopeSession($scope) {
        return Mage::getModel("hoosh_marketo/session_" . $scope);
    }

    /**
     * @param string $scope
     * @return HooshMarketing_Marketo_Model_Api_Core|HooshMarketing_Marketo_Model_Api_Lead|HooshMarketing_Marketo_Model_Api_Opportunity
     */
    protected function _getApiInstance($scope) {
        return Mage::getSingleton("hoosh_marketo/api_" . $scope);
    }

    /**
     * @return HooshMarketing_Marketo_Helper_DateTime
     */
    protected function _getDateTimeHelper() {
        return Mage::helper("hoosh_marketo/dateTime");
    }
    /**
     * @return HooshMarketing_Marketo_Helper_Api
     */
    protected function _getApiHelper() {
        return Mage::helper("hoosh_marketo/api");
    }

    /**
     * @return false|Mage_Eav_Model_Entity_Type
     */
    public function _getTypeInstance() {
        return Mage::getModel("eav/entity_type");
    }

    /**
     * @return HooshMarketing_Marketo_Model_Cron
     */
    protected function _getCronObserver() {
        return Mage::getSingleton("hoosh_marketo/cron");
    }

    /**
     * @return HooshMarketing_Marketo_Model_Mapping_Observer
     */
    protected function _getMappingObserver() {
        return Mage::getSingleton("hoosh_marketo/mapping_observer");
    }

    /**
     * @return HooshMarketing_Marketo_Model_Personalize_Category_Sorting
     */
    protected function _getPersonalizeSortingModel() {
        return Mage::getSingleton("hoosh_marketo/personalize_category_sorting");
    }

    /**
     * @return HooshMarketing_Marketo_Model_Eav_Attribute
     */
    public function getAttributeModel() {
        return Mage::getSingleton("hoosh_marketo/eav_attribute");
    }

    /**
     * @return HooshMarketing_Marketo_Model_System_Config_Mapping_Attribute_Save
     */
    protected function _getMappingAttributeSaveModel() {
        return Mage::getModel("hoosh_marketo/system_config_mapping_attribute_save");
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getCoreResource() {
        return Mage::getSingleton("core/resource");
    }

    /**
     * @return bool
     */
    protected function _isAdmin() {
        return Mage::app()->getStore()->isAdmin();
    }
}