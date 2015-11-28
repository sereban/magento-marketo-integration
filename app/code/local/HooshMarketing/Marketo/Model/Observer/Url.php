<?php
class HooshMarketing_Marketo_Model_Observer_Url extends HooshMarketing_Marketo_Model_Abstract
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function changeUrlKeyInCollection(Varien_Event_Observer $observer) {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = $observer->getEvent()->getData("collection");

        if($collection instanceof Mage_Catalog_Model_Resource_Product_Collection && $collection->count()) {
            foreach($collection as &$product) {
                /** @var Mage_Catalog_Model_Product $product */
                $this->_changeUrlKey($product);
            }
        }
    }
    /**
     * @param Varien_Event_Observer $observer
     */
    public function changeUrlKeyInProduct(Varien_Event_Observer $observer) {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getData("data_object");

        if($product instanceof Mage_Catalog_Model_Product) {
            $this->_changeUrlKey($product);
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return boolean
     */
    protected function _changeUrlKey(Mage_Catalog_Model_Product &$product) {
        if($product->getCategoryId() || !$this->_getHelper()->isUrlRewriteEnable()) return false; //if category already exist or rewrite categories is disabled

        $categoryIds = $product->getCategoryIds();
        $categoryId  = null;
        $product->setData("request_path", null);

        /** Process categories if they exists */
        if(is_array($categoryIds) && count($categoryIds)) {
            $categoryId = $categoryIds[count($categoryIds) - 1]; //get the last element
        }

        return $this->_getUrlModel()->getProductUrl($product, null, $categoryId);
    }

    /**
     * @return HooshMarketing_Marketo_Model_Rewrites_Product_Url
     */
    protected function _getUrlModel() {
        return Mage::getSingleton("hoosh_marketo/rewrites_product_url");
    }
}