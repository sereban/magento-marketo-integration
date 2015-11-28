<?php
abstract class HooshMarketing_Marketo_Helper_Import_Abstract extends HooshMarketing_Marketo_Helper_Abstract
{
    protected $_type = "csv";

    protected $_source;
    /**
     * @var array|object|Varien_Data_Collection
     */
    protected $_data;

    /**
     * run import
     * @return void
     */
    abstract public function import();

    /**
     * @return void
     */
    public function prepareData() {
        $this->_data = $this->getAdapter()->run($this->_source);
    }

    /**
     * @param  array|object|Varien_Data_Collection $source
     * @return self
     */
    public function setSource($source) {
        $this->_source = $source;
        return $this;
    }

    /**
     * @return HooshMarketing_Marketo_Helper_Csv
     * @throws Exception
     */
    public function getAdapter() {
        if(!$this->_type) throw new Exception("Please specify correct adapter type");
        return Mage::helper("hoosh_marketo/" . $this->_type);
    }
}