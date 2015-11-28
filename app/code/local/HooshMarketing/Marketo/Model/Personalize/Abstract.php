<?php
class HooshMarketing_Marketo_Model_Personalize_Abstract extends HooshMarketing_Marketo_Model_Abstract
{
    /**
     * @param $field
     * @param bool|false $unserialize
     * @return mixed
     */
    public function getConfig($field, $unserialize = false) {
        return $this->_getHelper()->getCategoryConfig($field, null, $unserialize); //with current store
    }

    /* Stripping functions */
    protected function _stripPlus($config) {
        return preg_replace("/[\+]/",  "", $config);
    }

    /**
     * @return mixed
     */
    protected function _getTopMarketoVar() {
        return $this->getConfig("map_top_field", false);
    }
}