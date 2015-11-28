<?php

/**
 * @method HooshMarketing_Marketo_Model_Personalize_Category_Path setCategoryPath(string $path)
 * @method string getCategoryPath()
 * @method HooshMarketing_Marketo_Model_Personalize_Category_Path setRefererUrl(string $path)
 * Class HooshMarketing_Marketo_Model_Personalize_Category_Path
 */
class HooshMarketing_Marketo_Model_Personalize_Category_Path
    extends HooshMarketing_Marketo_Model_Personalize_Abstract
{
    const CATEGORY_PATH       = "category_path";
    const PRIORITIZE_CATEGORY = "prioritize_category";
    const PATH_DELIMITER      = "/";

    const PATH_NAMES          = "modified_names";
    //Cache variables
    /** @var Mage_Catalog_Model_Resource_Category_Collection */
    protected static $_categoryCollection;

    protected function _construct() {
        $this->_init("hoosh_marketo/personalize_category_path");
    }

    /**
     * @return Mage_Catalog_Helper_Data
     */
    protected function _getCatalogHelper() {
        return Mage::helper("catalog");
    }

    /**
     * Get BreadCrumbs
     * @param Mage_Catalog_Model_Product $_product
     * @return array
     */
    public function getPath($_product = null) {
        $path           = self::PATH_DELIMITER;
        if(empty($_product))
            $_product       = $this->_getCurrentProduct();

        if(!$_product)
            return array($path); //non product page

        $breadCrumbPath = $this->_getCatalogHelper()->getBreadcrumbPath();
        unset($breadCrumbPath["product"]); //remove product from path

        if(empty($breadCrumbPath)) {
            $_path = null;
            $categoryId = $_product->getCategoryIds();
            $categoryId = array_shift($categoryId); //get first product category Id

            if(!empty($categoryId)) { //if product have categories
                $_data = $this->getNamedCategoryPaths($categoryId);

                if(isset($_data[self::PATH_NAMES])) {
                    $_path = $_data[self::PATH_NAMES];
                }
            }
        } else {
            //Working on path compilation
            $_path = array_map(function($category) {
                return isset($category["label"]) ? $category["label"] : null;
            }, $breadCrumbPath);
            $_path = implode(self::PATH_DELIMITER, $_path);
        }

        return array($_product->getId() => $path . $_path);
    }

    /**
     * @return array
     */
    protected function _tryMarketoSplit() {
        /* @var $read Magento_Db_Adapter_Pdo_Mysql*/
        $read    = $this->_getCoreResourceModel()->getConnection('core_read');
        $rootIds = $this->_getHelper()->getRootCategoryIds();
        $query = $read
            ->select()
            ->from(
                array($this->_getCoreResourceModel()->getTableName('catalog_category_entity')),
                array(
                    self::PATH_NAMES => "MARKETOSPLIT(path, '" . implode(',', $rootIds) . "')",
                    'id'             =>'entity_id',
                    'path'
                ))
            ->where("entity_id NOT IN(?)", $rootIds);

        return $read->fetchAll($query);
    }

    /**
     * @param array $_categoryIds
     * @param Mage_Catalog_Model_Resource_Category_Collection $categoryCollection
     * @return array
     */
    protected function _getNames(array $_categoryIds, $categoryCollection = null) {
        if(!empty($categoryCollection)) {
            $_names = array();

            foreach($_categoryIds as $_id) {
                /** @var Mage_Catalog_Model_Category $_item */
                $_item    = $categoryCollection->getItemById($_id);
                if($_item) {
                    $_names[] = $_item->getName();
                }
            }

            return implode(self::PATH_DELIMITER, $_names);
        } else {
            return $this->_getNames($_categoryIds, $this->_preparedCollection($_categoryIds));
        }
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param Mage_Catalog_Model_Resource_Category_Collection $categoryCollection
     * @return array
     */
    protected function _prepareData($category, $categoryCollection = null) {
        return array(
            "id"   => $category->getId(),
            "path" => $category->getData("path"),
            self::PATH_NAMES => $this->_getNames($category->getPathIds(), $categoryCollection)
        );
    }

    /**
     * @param array $entityIds
     * @return Mage_Catalog_Model_Resource_Category_Collection
     */
    protected function _preparedCollection(array $entityIds = array()) {
        if(empty(self::$_categoryCollection)) {
            self::$_categoryCollection = Mage::getSingleton("catalog/category")
                ->getCollection()
                ->addAttributeToSelect("name");

            if(!empty($entityIds)) {
                self::$_categoryCollection->addFieldToFilter("entity_id", $entityIds);

            }
            self::$_categoryCollection
                ->addFieldToFilter("entity_id", array("nin" => $this->_getHelper()->getRootCategoryIds()));
        }

        return self::$_categoryCollection;
    }

    public function getNamedCategoryPaths($_categoryId = null) {
        $_data = array();
        if(!empty($_categoryId)) {
            /** @var Mage_Catalog_Model_Category $_category */
            $_category = Mage::getModel("catalog/category")->load($_categoryId);

            if($_category) {
                $_data = $this->_prepareData($_category);
            }
        } else {
            try {
                $_data = $this->_tryMarketoSplit();
            } catch(Exception $e) {
                foreach($this->_preparedCollection() as $category) {
                    $_data[] = $this->_prepareData($category, $this->_preparedCollection());
                }
            }
        }

        return $_data;
    }
}