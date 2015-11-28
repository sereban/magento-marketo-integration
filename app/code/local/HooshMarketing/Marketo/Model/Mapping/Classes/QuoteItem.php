<?php
class HooshMarketing_Marketo_Model_Mapping_Classes_QuoteItem
    extends HooshMarketing_Marketo_Model_Mapping_Classes_Abstract
{
    protected $_key = "quote_item";
    protected $_fieldIdentifier = array(
        "sales_flat_quote_item" => HooshMarketing_Marketo_Model_Mapping_Classes_Abstract::TABLE_TYPE
    );
    protected $_preparedCallbacks = array(
        "_customOptions" => "quote_item"
    );

    /**
     * Preparing custom options
     * @param Mage_Sales_Model_Quote_Item $item
     */
    protected function _customOptions(Mage_Sales_Model_Quote_Item &$item) {
        $_options = $this->_getConfigurableProductHelper()->getCustomOptions($item);
        /** @var array $_option */
        foreach($_options as $_option) {
            if(isset($_option["label"]) && isset($_option["value"])) {
                $item->setData(crc32($_option["label"]), $_option["value"]);
            }
        }
    }

    /**
     * @return Mage_Catalog_Helper_Product_Configuration
     */
    protected function _getConfigurableProductHelper() {
        return Mage::helper("catalog/product_configuration");
    }
}