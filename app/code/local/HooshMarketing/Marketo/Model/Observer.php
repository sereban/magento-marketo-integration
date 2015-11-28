<?php

class HooshMarketing_Marketo_Model_Observer extends HooshMarketing_Marketo_Model_Abstract
{
    //FLAGS
    private static $_scored              = false;
    private static $_isCategoryPathAdded = false;

    /**
     * Frontend And Admin
     * Subscribe to newsletter
     * Events: newsletter_subscriber_save_after
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function newsletterSubscribe(Varien_Event_Observer $observer)
    {
        if (!$this->_getHelper()->getModuleStatus()) return false;

        /* send observer to mapping */
        $this
            ->_getLeadModel()
            ->prepareLeadToSync(
                $this->_getHelper()->getCompanyParamToSync(),
                array("subscriber" => $observer->getEvent()->getData("subscriber"))
            );

        return true;
    }
    /**
     * Frontend And Admin
     * Subscribe to newsletter
     * Events: controller_action_predispatch
     * @return bool
     */
    public function preDispatch()
    {
      //  Mage::log(Mage::app()->getRequest()->getRequestUri());
        if (!$this->_getHelper()->getModuleStatus())  return false;
        Varien_Profiler::start("marketo_predispatch"); //starting log profile

        try {
            $this->_getLeadModel()->getApiAdapter()->checkLead(); //set new data to lead from admin and from frontend

            if(!$this->_isAdmin()) { //only for frontend
                $this->_getPesonalizeCalculator()->customScore(Mage::app()->getRequest()); //check for custom score
                $this->updateTheme(); //for all pages instead of catalog pages
            }
        } catch(Exception $e) {
            Mage::logException($e);
        }

        Varien_Profiler::stop("marketo_predispatch");

        return true;
    }

    protected function _calculcateCatalogScore() {
        if(!self::$_scored) { //should
            $calculator = $this->_getPesonalizeCalculator();

            $this->_getPesonalizeCalculator()->score(
                Mage::registry("current_category"),
                Mage::registry("current_product"),
                $calculator::CATEGORY_STEP
            );

            $this->updateTheme();

            self::$_scored = true;
        }
    }

    /**
     * Frontend
     * Load layout before
     * Events: core_block_abstract_prepare_layout_before
     * @return bool
     */
    public function loadLayoutBefore() {
        $this->_calculcateCatalogScore();
        $this->_getCronObserver()->logLeadInactivity();
    }
    /**
     * Frontend
     * After rendering html
     * Events: core_block_abstract_to_html_after
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function toHtmlAfter(Varien_Event_Observer $observer) {
        /** @var Mage_Core_Block_Abstract $_block */
        $_block     = $observer->getEvent()->getBlock();
        /** @var Varien_Object $_transport */
        $_transport = $observer->getEvent()->getData("transport");
        //Add category path params
        $this->_addCategoryPathToProductForm($_block, $_transport);
        $this->_handleWelcomeMessage($_block, $_transport);
    }

    /**
     * @param Mage_Core_Block_Abstract $_block
     * @param Varien_Object $transport
     */
    protected function _addCategoryPathToProductForm(Mage_Core_Block_Abstract $_block, Varien_Object &$transport) {
        if($_block instanceof Mage_Catalog_Block_Product_View && !self::$_isCategoryPathAdded) {
            try {
                $_categoryPath = $this->_getPersonalizeCategoryPathModel()->getPath();
                $_productId    = key($_categoryPath); //product id

                $name          = HooshMarketing_Marketo_Model_Personalize_Category_Path::CATEGORY_PATH . "[$_productId]";

                $this->_getHelper()->addParamsToForm(
                    array($name => $_categoryPath[$_productId]), $transport);

                self::$_isCategoryPathAdded = true; //dispatched
            } catch(Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * @param Mage_Core_Model_Abstract $_block
     * @param Varien_Object $_transport
     */
    protected function _handleWelcomeMessage($_block, $_transport) {
        if($_block instanceof Mage_Page_Block_Html_Welcome) {
            /** @var Mage_Widget_Model_Template_Filter $widget */
            $widget  = Mage::getModel("widget/template_filter");
            $_filter = $this->_getHelper()->getConfig("frontend_settings", "welcome_message");

            if($_filter) {
                $_html = $this
                    ->_getHelper()
                    ->__('Welcome, %s!', $this->_getHelper()->escapeHtml($widget->filter($_filter)));

                $_transport->setData("html", $_html);
            }
        }
    }

    /**
     * @return bool
     */
    public function updateTheme() {
        if (!$this->_getHelper()->getModuleStatus()) return false;

        $this->_getPesonalizeCalculator()->calculate($this->_getLeadModel()); //calculate
        $this->_getPersonalizeStoreModel()->setStoreView();

        return true;
    }
    /**
     * Frontend
     * Before product collection is loaded
     * Events: catalog_product_collection_load_before
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function setProductOrder(Varien_Event_Observer $observer) {
        if (!$this->_getHelper()->getModuleStatus()) return false;
        /** @var Mage_Catalog_Model_Resource_Product_Collection $_productCollection */
        $_productCollection = $observer->getEvent()->getData("collection");
        /* sort collection by category with personalize products */
        $productCollection = $this->_getPersonalizeSortingModel()->sort($_productCollection);

        $observer->getEvent()->setData("collection", $productCollection); // setting sorted collectopn

        return true;
    }
}