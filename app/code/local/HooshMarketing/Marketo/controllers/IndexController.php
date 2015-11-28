<?php

class HooshMarketing_Marketo_indexController extends Mage_Core_Controller_Front_Action
{
    const PRODUCT_IMAGE_PARAM = "product_id";

    /**
     * This function generate image of product on custom page
     */
    public function getImageAction()
    {
        $productId = $this->getRequest()->getParam(self::PRODUCT_IMAGE_PARAM);
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel("catalog/product")->load($productId);

        if (is_integer((int)$productId) && !empty($product) && $product->getId() != null) {
            header("Content-Type: image");
            echo file_get_contents($product->getImageUrl());
        } else {
            echo "This product doesn`t exist!";
        }

    }
    /* @return HooshMarketing_Marketo_Helper_Data */
    protected function _getHelper() {
        return Mage::helper("hoosh_marketo");
    }

    /**
     * Syncing Billing Address Without Saving it
     * @return bool
     */
    public function indexAction() {
        /** @var Mage_Checkout_Model_Session $_checkoutSession */
        $_checkoutSession = Mage::getSingleton("checkout/session");
        $_billingAddress  = $_checkoutSession->getQuote()->getBillingAddress();
        /** @var array $params -> Post from checkout onepage */
        $params = $this->getRequest()->getPost();

        $_billingAddress->addData($params);
        //Trying to synchronize Data
        $this->_getLeadModel()->prepareLeadToSync(
            $this->_getHelper()->getCompanyParamToSync(),
            array("billing_address" => $_billingAddress)
        );

        return true;
    }

    /* @return HooshMarketing_Marketo_Model_Lead */
    protected function _getLeadModel() {
        return Mage::getSingleton("hoosh_marketo/lead");
    }
    /**
     * @param Mage_Core_Model_Abstract $model
     * @return HooshMarketing_Marketo_Model_Opportunity
     */
    protected function _getOpportunityModel($model = null) {
        return (!empty($model)) ? Mage::getModel("hoosh_marketo/opportunity") : Mage::getSingleton("hoosh_marketo/opportunity");
    }

    public function getLeadAction()
    {
        $lead = $this->_getLeadModel()->getLoadedByCookie();
        var_dump($lead->getData());
    }

    public function getOpportunityAction() {
        $lead = $this->_getLeadModel()->getLoadedByCookie();
        $collection = $this
            ->_getOpportunityModel()
            ->getCollection()
            ->addAttributeToSelect("*")
            ->addFieldToFilter("parent_id", $lead->getId());

        /** @var HooshMarketing_Marketo_Model_Opportunity $opportunity */
        foreach($collection as $opportunity) {
            var_dump($opportunity->getData());
        }
    }

    /* synchronize cron opportunity and lead */
    public function syncAction() {
        /** @var HooshMarketing_Marketo_Model_Cron $cron */
        $cron = Mage::getSingleton("hoosh_marketo/cron");
        $cron->syncLeadAndOpportunityData();
    }

    public function syncInactivityAction() {
        /** @var HooshMarketing_Marketo_Model_Cron $cron */
        $cron = Mage::getSingleton("hoosh_marketo/cron");
        $cron->syncInactivityLeads();
    }
}