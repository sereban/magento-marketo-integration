<?php
class HooshMarketing_Marketo_Model_Personalize_Store extends HooshMarketing_Marketo_Model_Personalize_Abstract
{
    /** @var bool -> Current Store View is already updated */
    private $_isUpdated = false;
    /**
     * Setting new Store Code depends on top marketo variable
     * @return bool
     */
    public function setStoreView() {
        if($this->_isUpdated)
            return false;

        $this->_getLeadModel()->getLoadedByCookie(); //init lead data
        $nonMarketoStore = Mage::app()->getStore();

        if(!$this->_getLeadModel()->hasData($this->_getTopMarketoVar())) //if lead havn`t top marketo field
            return false;

        $_storeCode = $this->_getStoreCode($this->_getLeadModel()->getData($this->_getTopMarketoVar()));

        $_store = Mage::getSingleton("core/store")->load($_storeCode, "code");

        try {
            if($_store->getId()) {
                Mage::app()->setCurrentStore($_storeCode);
                $nonMarketoStore->setId(Mage::app()->getStore()->getId());
                $this->_isUpdated = true; 
            }
        } catch(Exception $e) {
            Mage::logException($e);
        }

        return true;
    }

    /**
     * @param string $topField
     * @return string -> new store name
     */
    protected function _getStoreCode($topField) {
        $templates = $this->_getHelper()->getTemplateSwitcherConfig("stores");

        $_template = array_filter($templates, function($_t) use($topField) {
            return isset($_t["marketo_lead_attribute_template"]) && isset($_t["magento_store"])
                && $_t["marketo_lead_attribute_template"] == $topField;
        });
        $_template = current($_template);

        return $_template["magento_store"];
    }

}