<?php
class HooshMarketing_Marketo_Model_Personalize_Calculator extends HooshMarketing_Marketo_Model_Personalize_Abstract
{
    /* scoring constants */
    const CATEGORY_STEP      = "score_viewed";
    const CHECKOUT_CART_STEP = "score_add_to_cart";
    const ORDER_CART_STEP    = "score_purchased";
    const EXTERNAL_URL_STEP  = "score_external_url";

    const DEFAULT_STEP_VALUE = 1;
    const DEFAULT_THRESHOLD  = 1;

    const TOP_CATEGORY_KEY   = "top_category";
    //MODES OF GENERATED CATEGORY DATA
    const MARKETO_VAR_AS_KEY    = 0;
    const MAGENTO_PATH_AS_KEY   = 1;
    const CATEGORY_ID_AS_KEY    = 2;
    const MARKETO_VAR_AND_SCORE = 3;
    const CATEGORY_ID_AND_SCORE = 4;

    protected $_scoreCategoryIds;
    protected $_scoreCategories;

    /**
     * Marketo Variable Name -> Magento Score Categories as $key => $value
     * @param int $likeKey -> 0 = Marketo Var; 2 = Category Id
     * @return array
     */
    public function getScoreCategoryParams($likeKey = self::MARKETO_VAR_AS_KEY) {
        if(!isset($this->_scoreCategoryIds[$likeKey])) {
            $this->_scoreCategoryIds = array();
            $_scoreCategories        = $this->getScoringCategories(self::MAGENTO_PATH_AS_KEY); //retrieve config data

            $this->_scoreCategoryIds[$likeKey] = array();

            if(is_array($_scoreCategories)) { //additional check if score vairables exists
                foreach($_scoreCategories as $categoryPath => $marketoVar) {
                    if($_categoryId = $this->_getHelper()->getCategoryIdFromPath($categoryPath)) {
                        switch($likeKey) {
                            case self::MARKETO_VAR_AS_KEY:
                                $this->_scoreCategoryIds[$likeKey][$marketoVar]  = $_categoryId;
                                break;
                            case self::CATEGORY_ID_AS_KEY:
                                $this->_scoreCategoryIds[$likeKey][$_categoryId] = $marketoVar;
                                break;
                            case self::MARKETO_VAR_AND_SCORE:
                                $lead = $this->_getLeadModel()->getLoadedByCookie(); //load lead
                                if($lead->hasData($marketoVar))
                                    $this->_scoreCategoryIds[$likeKey][$marketoVar] = $lead->getData($marketoVar);
                                break;
                            case self::CATEGORY_ID_AND_SCORE:
                                $lead = $this->_getLeadModel()->getLoadedByCookie(); //load lead
                                if($lead->hasData($marketoVar))
                                    $this->_scoreCategoryIds[$likeKey][$_categoryId] = $lead->getData($marketoVar);
                        }
                    }
                }
            }
        }

        return $this->_scoreCategoryIds[$likeKey];
    }

    /**
     * @param Mage_Catalog_Model_Category|null $category
     * @param Mage_Catalog_Model_Product|null $product
     * @param int $stepType -> value on which marketoValue should incremented
     */
    public function score(Mage_Catalog_Model_Category $category = null,
                          Mage_Catalog_Model_Product $product = null, $stepType)
    {
        if(!empty($category)) { //if isset current category -> score only for main category, even if we are on product page
            /** @var array $categoryIds */
            $categoryIds = $this->_getHelper()->getAllCategoryIdsFromCategory($category);
        } elseif(!empty($product)) {
            /** @var array $categoryIds */
            $categoryIds = $this->_getHelper()->getAllCategoryIdsFromProduct($product);
        }

        if(isset($categoryIds)) {
            $this->_addScores(
                array_intersect($this->getScoreCategoryParams(), $categoryIds),
                $this->_calculateStepValue($stepType)
            );
        }
    }

    /**
     * @param string | int $stepType
     * @return float|int
     */
    protected function _calculateStepValue($stepType) {
        /* If config exists */
        if($_stepValue = $this->getConfig($stepType)) {
            return $this->_calculateStepValue($this->_stripPlus($_stepValue));
        }

        if(floatval($stepType)) { //if it`s digit with + or another symbols
            return floatval($stepType);
        }

        return self::DEFAULT_STEP_VALUE;
    }

    /**
     * @param array $toIncreaseScore -> array in format: MarketoValue -> MagentoCategoryId (or any other)
     * @param int $step -> value of increasing marketoValue
     */
    protected function _addScores(array $toIncreaseScore, $step) {
        $lead = $this->_getLeadModel()->getLoadedByCookie();
        $_helper = $this->_getHelper();

        foreach($toIncreaseScore as $marketoVar => $categoryData) {
            $_helper->increment($lead, $marketoVar, $step); //increment lead object
        }

        try {
            $lead->save();
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param int $likeKey - set different params; depend on param 0 -> Magento Path; 1 -> Marketo Var
     * @return mixed
     */
    public function getScoringCategories($likeKey = self::MARKETO_VAR_AS_KEY) {
        if(!isset($this->_scoreCategories[$likeKey])) {
            foreach($this->getConfig("scoring_categories", true) as $_field) { //getting config from category mapping
                if(isset($_field["magento_category_path"]) && isset($_field["marketo_lead_attribute"])) {
                    switch($likeKey) {
                        case self::MARKETO_VAR_AS_KEY:
                            $this->_scoreCategories[$likeKey][$_field["marketo_lead_attribute"]] //set Marketo Var as key
                                = $_field["magento_category_path"];
                            break;
                        case self::MAGENTO_PATH_AS_KEY:
                            $this->_scoreCategories[$likeKey][$_field["magento_category_path"]] //set Magento Path as Key
                                = $_field["marketo_lead_attribute"];
                    }
                }
            }
        }

        return $this->_scoreCategories[$likeKey];
    }

    /**
     * Threshold * Nesting Level = access to choose category as leading
     * @param $lead
     * @return array
     */
    public function calculate(HooshMarketing_Marketo_Model_Lead $lead) {
        $maxValue = 0;
        $scoreCategoryIds = $this->getScoreCategoryParams(self::MARKETO_VAR_AND_SCORE);
        $scoreCategories  = $this->getScoringCategories(self::MARKETO_VAR_AS_KEY);

        foreach($scoreCategoryIds as $marketoVar => $score) {
            /* if value is bigger then calculated max value and value is bigger then threshiold for this category */
            if($score >= $maxValue && $score >= $this->_getThreshold() * $this->_getNestingLvl($scoreCategories[$marketoVar])) {
                /* preparing data to next iteration */
                $maxValue = $score; //reload $maxValue increasing to the bigest value we have
                /* setting new data */
                $lead->setData($this->_getTopMarketoVar(), $marketoVar); //add to top marketo var -> marketo variable with top score
            }
        }

        try {
            $lead->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $scoreCategoryIds;
    }

    /* get threshold for making category - top */
    protected function _getThreshold() {
        return ($this->getConfig("threshold") == null) ?
            self::DEFAULT_THRESHOLD : $this->getConfig("threshold");
    }

    /** get count of slashes  - 1
     * @param $categoryPath
     * @return int
     */
    protected function _getNestingLvl($categoryPath) {
        return substr_count($categoryPath, "/") - 1; // /Men = 0; /Men/Shoes = 1
    }

    /**
     * Allows to add score by proceeding: http://marketo.local/slim-fit-dobby-oxford-shirt-572.html?Men=+9
     * Where Men -> is MarketoVar and +9 -> is score
     * @param Zend_Controller_Request_Abstract $request
     */
    public function customScore(Zend_Controller_Request_Abstract $request) {
        $_requestParams = $request->getParams();

        $scoreCategories  = $this->getScoringCategories(self::MARKETO_VAR_AS_KEY);
        $scoreCategoryIds = $this->getScoreCategoryParams(self::CATEGORY_ID_AS_KEY);

        if(is_array($scoreCategories)) { //If score categories exists
            $scoreVariables  = array_intersect_key($_requestParams, $scoreCategories);

            /**
             * @var string $marketoVar -> Marketo Variable of Scoring Category
             * @var int $score -> current score of category
             */
            foreach($scoreVariables as $marketoVar => $score) {
                $score = (!(int)$score) ? self::DEFAULT_THRESHOLD : (int)$score; //check default threshold
                $toIncrease     = array( //init new array for recalculating category scoring
                    $marketoVar => $score
                );

                $_path = $scoreCategories[$marketoVar];
                //add score to all parent categories
                foreach(explode("/", $_path) as $_id) {
                    if(!isset($scoreCategoryIds[$_id]))
                        continue; //if category is not scoring

                    $_var = $scoreCategoryIds[$_id];
                    $toIncrease[$_var] = $_id;
                }

                $this->_addScores($toIncrease, $this->_stripPlus($score));
            }
        }

    }
}