<?php

class HooshMarketing_Marketo_Helper_Csv
{
    const HEADING_INDEX = 0;
    /* @var $_adapter Varien_File_Csv */
    protected $_adapter;

    protected $_mappedHeaders = array();
    /**
     * Used for enable or disable heading as keys
     * @var bool
     */
    protected $_headingAsKeys = true;
    /** @var  array */
    protected $_data = array();

    protected $_delimiter = ",";

    /**
     * @param $file
     * @return array
     * Parse csv, can parse csv and set header name to each column
     * @throws Exception
     */
    protected function _parseCsv($file) {
        if(!isset($this->_data[$file])) {
            if(!$this->_adapter) {
                $this->_prepareAdapter();
            }

            $_data = $this->_adapter->getData($file);
            $this->_checkHeading($_data);
            $this->_processMappedHeaders($_data[self::HEADING_INDEX]);

            if($this->_headingAsKeys) {
                /** @var int $i */
                for($i=0; $i < count($_data); $i++) {
                    if($i == self::HEADING_INDEX) continue;

                    foreach($_data[self::HEADING_INDEX] as $_columnIndex => $header) {
                        $this->_data[$file][$i][$header] = $_data[$i][$_columnIndex];
                    }
                }
            } else {
                $this->_data[$file] = $_data;
            }
        }

        return $this->_data[$file];
    }

    protected function _prepareAdapter() {
        $this->_adapter = new Varien_File_Csv();
        $this->_adapter->setDelimiter($this->_delimiter);
    }

    /**
     * prepare headers and mapped them
     * @param array $headers
     */
    protected function _processMappedHeaders(array &$headers) {
        foreach($headers as &$header) {
            if(isset($this->_mappedHeaders[$header])) {
                $header = $this->_mappedHeaders[$header];
            }
        }
    }

    /**
     * @param array $mappedHeaders
     * @return $this
     */
    public function setMappedHeaders(array $mappedHeaders) {
        $this->_mappedHeaders = $mappedHeaders;
        return $this;
    }

    /**
     * @param string $delimiter
     * @return $this
     */
    public function setDelimiter($delimiter) {
        $this->_delimiter = $delimiter;
        return $this;
    }

    /**
     * @param array $csvData
     * @throws Exception
     */
    protected function _checkHeading($csvData) {
        if(!isset($csvData[self::HEADING_INDEX]) || !is_array($csvData[self::HEADING_INDEX]) || !count($csvData[self::HEADING_INDEX]))
                throw new Exception("Csv file have no headers");
    }

    /**
     * @param string $currentFile
     * @return array
     * @throws Exception
     */
    public function run($currentFile) {
        if(empty($currentFile)) throw new Exception("File Name is empty");
        return $this->_parseCsv($currentFile);
    }
}