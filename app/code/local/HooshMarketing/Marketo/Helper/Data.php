<?php

class HooshMarketing_Marketo_Helper_Data extends HooshMarketing_Marketo_Helper_Abstract
{
    /* Log Files Constants */
    const LOG_FILE = "marketo/system.log";
    const EXCEPTION_FILE = "marketo/exception.log";
    const SKU_PREFIX = "quote_item_update_";
    const LEAD_IDS_DELIMITER = ",";
    private static $_defaultCategoryIds = array(0 ,1);

    protected static $_rootIds;

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getSyncKeys($key, $default = null) {
        return (Mage::getStoreConfig("marketo_config/sync_keys/".$key."_key") == null) ? $default : Mage::getStoreConfig("marketo_config/sync_keys/".$key."_key");
    }

    /**
     * @return HooshMarketing_Marketo_Model_Lead
     */
    protected function _getLeadModel() {
        return Mage::getSingleton("hoosh_marketo/lead");
    }

    /**
     * @return array
     */
    public function getCompanyParamToSync() {
        return array(
            "Company_Id" => $this->_getLeadModel()->getLeadId()
        );
    }
    /**
     * @param string $keysString
     * @param string $newKey
     * @return string
     */
    public function processMultipleIds($keysString, $newKey) {
        if(!empty($keysString)) {
            $_ids = explode(self::LEAD_IDS_DELIMITER, $keysString);
        } else {
            $_ids = array();
        }
        if(!in_array($newKey, $_ids))
            $_ids[] = $newKey;

        return  implode(self::LEAD_IDS_DELIMITER, $_ids);
    }

    public function generateQuoteItemUpdateKey(Mage_Sales_Model_Quote_Item $quoteItem) {
        return self::SKU_PREFIX . $quoteItem->getSku();
    }

    public function log($message, $level = null) {
        if(empty($message)) return false;

        Mage::log($message, $level, self::LOG_FILE, true);
        return true;
    }

    public function logException(Exception $e) {
        Mage::log("\n" . $e->__toString(), Zend_Log::ERR, self::EXCEPTION_FILE);
    }

    public function getMarketoVersion() {
        $_v = (array)Mage::app()->getConfig()->getNode("modules/HooshMarketing_Marketo/version");
        if(isset($_v)) {
            return $_v[0];
        } else {
            return "0.0.1";
        }
    }

    /**
     * current url of store domain + REQUEST_URI
     * @return string
     */
    public function getCurrentUrlWithoutPort()
    {
        $request = Mage::app()->getRequest();
        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getServer('REQUEST_URI');
        return $url;
    }


    /* max priority - 9, min priority - 1 */
    public function setPrivilegeObject($objectName, $priority = 1, $postFix) {
        if($pObjects = $this->getPrivilegeObjects($postFix)) {
            $pObjects[$objectName] = $priority;
        } else {
            $pObjects = array();
            $pObjects[$objectName] = $priority;
        }

        Mage::getSingleton("hoosh_marketo/session_lead")->setData("privilege_object_$postFix", $pObjects);
    }

    /* is_lead or is_opportunity */
    public function getPrivilegeObjects($postFix) {
        return Mage::getSingleton("hoosh_marketo/session_lead")->getData("privilege_object_$postFix");
    }

    /**
     * @return bool
     */
    public function isUrlRewriteEnable() {
        return (boolean)Mage::getStoreConfig("marketo_config/hooshmarketo_enabling_status/enable_url_rewrites");
    }

    /**
     * @param $_tableName
     * @return array - Return all fields from table
     */
    public function getTableFields($_tableName) {
        $fields = array();
        /** @var Mage_Core_Model_Resource $adapter */
        $adapter    = Mage::getSingleton("core/resource");
        /** @var Varien_Db_Adapter_Pdo_Mysql $connection */
        $connection = $adapter->getConnection("core_read");

        $tableData = $connection->describeTable($connection->getTableName($_tableName));

        foreach($tableData as $_row) {
            if(isset($_row["COLUMN_NAME"])) {
                $fields[$_row["COLUMN_NAME"]] = $this->beatifyField($_row["COLUMN_NAME"]);
            }
        }

        return $fields;
    }

    /**
     * @param $entityTypeCode
     * @return array - Return all attributes with specific code
     */
    public function getAttributes($entityTypeCode) {
        $attributes = array();
        $entityType = Mage::getSingleton("eav/entity_type")->load($entityTypeCode, "entity_type_code");

        if($entityType && $entityType->getId()) {
            $_attributes = Mage::getSingleton("eav/entity_attribute")
                ->getCollection()
                ->addFieldToFilter("entity_type_id", $entityType->getId());

            /** @var Mage_Eav_Model_Entity_Attribute $_attribute */
            foreach($_attributes as $_attribute) {
                $attributes[$_attribute->getAttributeCode()] = $this->beatifyField($_attribute->getAttributeCode());
            }
        }

        return $attributes;
    }

    /**
     * Takes string, beatify it and wrap into array
     * @param string $field
     * @return array
     */
    public function getSimpleColumnForMapping($field) {
        return array(
            $field => $this->beatifyField($field)
        );
    }

    /**
     * @return array -> Return all root categories for store
     */
    public function getRootCategoryIds() {
        if(empty(self::$_rootIds)) {
            self::$_rootIds = array();
            /** @var Mage_Catalog_Model_Category $rootOfTheRoot */
            $rootOfTheRoot = Mage::getSingleton("catalog/category")
                ->getCollection()
                ->addFieldToFilter("parent_id", self::$_defaultCategoryIds);

            /** @var Mage_Catalog_Model_Category $category */
            foreach($rootOfTheRoot as $category) {
                self::$_rootIds[] = $category->getId();
            }

        }

        return self::$_rootIds;
    }

    /**
     * Return all categories = product category + parent categories
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getAllCategoryIdsFromProduct(Mage_Catalog_Model_Product $product) {
        $_ids = array();
        $categoryCollection = $product->getCategoryCollection();

        if(!empty($categoryCollection)) {
            /** @var Mage_Catalog_Model_Category $category */
            foreach($categoryCollection as $category) {
                foreach($this->getAllCategoryIdsFromCategory($category) as $_id) {
                    $_ids[$_id] = $_id;
                }
            }
        }

        return $_ids;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return array
     */
    public function getAllCategoryIdsFromCategory(Mage_Catalog_Model_Category $category = null) {
        if(empty($category))
            return array();

        $_pathIds = $category->getData("path");
        return explode("/", $_pathIds);
    }

    /**
     * @param $path
     * @return int | false
     */
    public function getCategoryIdFromPath($path) {
        $_ids = explode("/", $path);

        return count($_ids) ? $_ids[count($_ids) - 1] : false;
    }

    /**
     * @return string
     */
    public function getMunckinCode() {
        return $this->getApiConfig("munckin_code");
    }

    /**
     * @return Mage_Adminhtml_Helper_Data
     */
    protected function _getAdminhtmlHelper() {
        return Mage::helper("adminhtml");
    }

    public function getSystemConfigUrl() {
        return $this->_getAdminhtmlHelper()->getUrl('adminhtml/system_config/edit', array(
            "section" => HooshMarketing_Marketo_Helper_Abstract::MARKETO_ROOT
        ));
    }


    /**
     * Add params to form
     * @param array $params
     * @param Varien_Object $_transport
     */
    public function addParamsToForm(array $params, Varien_Object &$_transport) {
        $_form = new Varien_Data_Form();

        foreach($params as $name => $value) {
            $_element = new Varien_Data_Form_Element_Text(); //init element
            $_element->setType("hidden");
            $_element->setForm($_form);//Add Element to empty form
            //Handle dynamic params
            $_element->addData(
                array(
                    "name" =>  $name,
                    "value" => $value,
                )
            );

            $this->appendHtml($_transport, $_element->getElementHtml());
        }
    }

    /**
     * @return array|int|string
     */
    public function isCategorySortingEnable() {
        return $this->getConfig("hooshmarketo_enabling_status", "enable_personalize_sorting");
    }

    /**
     * @param $_id
     * @return Mage_Catalog_Model_Product
     */
    public function getProductFromId($_id) {
        /** @var Mage_Catalog_Model_Product $_product */
        $_product = Mage::getModel("catalog/product");
        $_product->load($_id);
        return $_product;
    }

    /**
     * @param Varien_Object $object
     * @param array $magentoMarketoFields
     * @return bool
     */
    public function hasMarketoDataChanged(Varien_Object $object, array $magentoMarketoFields) {
        $_origData = $object->getOrigData();
        $_data     = $object->getData();
        $data      = array();

        foreach($_data as $key => $value) {
            if(isset($magentoMarketoFields[$key]) && $_origData[$key] != $value) {
                $data[$key] = $value;
            }
        }

        return !empty($_data);
    }
}
