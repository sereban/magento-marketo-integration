<?php
class HooshMarketing_Marketo_Model_Personalize_Category_Sorting
    extends HooshMarketing_Marketo_Model_Personalize_Abstract
{
    //FLAGS
    const MARKETO_SORTED = "marketo_sorted";

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     * @return mixed
     */
    public function sort(Mage_Catalog_Model_Resource_Product_Collection $productCollection) {
        //Pass a small validation
        if(!$this->_getHelper()->isCategorySortingEnable() || $productCollection->getFlag(self::MARKETO_SORTED))
            return $productCollection;

        $categoriesList = $this
            ->_getPesonalizeCalculator()
            ->getScoreCategoryParams(
                HooshMarketing_Marketo_Model_Personalize_Calculator::CATEGORY_ID_AND_SCORE
            ); //get List of categories with key -> Category_id and value - Score

        if(!count($categoriesList)) //if we havn`t score categories do nothing
            return $productCollection;

        uasort($categoriesList, function($f, $s) {
            return $f > $s ? 1 : -1; //sort by descending
        }); //Sort categories in order to show top scored categories first

        $categoriesList = array_keys($categoriesList); // get only category ids

        try {
            $productCategoryTable = $this->_getCoreResource()->getTableName("catalog_category_product");

            $productCollection
                ->getSelect()
                ->joinLeft(array("marketo_category_table" => $productCategoryTable), //in any way join table with categories
                    "marketo_category_table.product_id = e.entity_id",
                    array("top_category_id" => "marketo_category_table.category_id")) //Join category attribute
                ->group("entity_id")
                ->order("FIELD(top_category_id, " . implode(',', $categoriesList) . ") DESC"); //order by top

            $productCollection->setFlag(self::MARKETO_SORTED, true); //to set order only one time
        } catch(Exception $e) {
            Mage::logException($e);
        }

        return $productCollection;
    }
}