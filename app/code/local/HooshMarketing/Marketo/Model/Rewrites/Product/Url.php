<?php
/**
 * @class HooshMarketing_Marketo_Model_Rewrites_Product_Url_Base is dummy class
 * Define which class should we extend Mage_Catalog either Enterprise_Catalog
 */
switch(Mage::getEdition()) {
    case "Enterprise":
        class HooshMarketing_Marketo_Model_Rewrites_Product_Url_Base extends Enterprise_Catalog_Model_Product_Url {

        }
    break;
    default:
        class HooshMarketing_Marketo_Model_Rewrites_Product_Url_Base extends Mage_Catalog_Model_Product_Url {

        }
}


class HooshMarketing_Marketo_Model_Rewrites_Product_Url extends HooshMarketing_Marketo_Model_Rewrites_Product_Url_Base {
    /**
     * Retrieve Product URL
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  bool $useSid forced SID mode
     * @return string
     */
    public function getProductUrl($product, $useSid = null, $categoryId = null)
    {
        if ($useSid === null) {
            $useSid = Mage::app()->getUseSessionInUrl();
        }

        $params = array();
        if (!$useSid) {
            $params['_nosid'] = true;
        }

        if(!empty($categoryId)) {
            $params["desktop_category_id"] = $categoryId;
        }

        return $this->getUrl($product, $params);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $params
     * @return int|null
     */
    protected function _getCategoryIdForUrl($product, $params)
    {
        if(isset($params["desktop_category_id"]) && $params["desktop_category_id"]) {
            return $params["desktop_category_id"];
        } else {
            return parent::_getCategoryIdForUrl($product, $params);
        }
    }
}