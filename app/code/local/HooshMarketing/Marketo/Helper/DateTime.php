<?php
class HooshMarketing_Marketo_Helper_DateTime extends HooshMarketing_Marketo_Helper_Abstract
{
    /**
     * @param null $timeOffset
     * @param  int|null $storeId
     * return timeobject depending on store time zone
     * @return DateTime
     */
    public function getTimeObject($timeOffset = null, $storeId = null)
    {
        if (!empty($timeOffset)) {
            $date = new DateTime('now', $this->getTimeZone($storeId));
            $timeStamp = $date->getTimeStamp();
            $timeStamp += $timeOffset;
            $date->setTimestamp($timeStamp);
            return $date;
        }
        return new DateTime('now', $this->getTimeZone());
    }

    /**
     * @param  int|null $storeId
     * @return DateTimeZone
     */
    public function getTimeZone($storeId = null)
    {
        return new DateTimeZone($this->getApiConfig("timeZone", $storeId));
    }

    public function getStoreTimeStamp()
    {
        $storeId = $this->getStore()->getId();
        $timestamp = Mage::app()->getLocale()->storeTimeStamp($storeId);
        return $timestamp;
    }

    public function getServerDate() {
        return $this->_dateFormat(time());
    }

    public function _dateFormat($timestamp) {
        return date('Y-m-d H:i:s', $timestamp);
    }

    public function getStoreDate()
    {
        return $this->_dateFormat($this->getStoreTimeStamp());
    }

    /**
     * TODO: add configuration time string type
     * @param DateTime|null $timeObject
     * @param string $format
     * @return string
     */
    public function toTimeString(DateTime $timeObject = null, $format = "Y-m-d H:i:s") {
        return (empty($timeObject)) ? $this->getTimeObject()->format($format) : $timeObject->format($format);
    }

    /**
     * @param int $storeId
     * @param int|null $offset
     * @return string
     */
    public function toStoreTimeString($offset = null , $storeId) {
        return $this->toTimeString($this->getTimeObject($offset, $storeId));
    }

    /**
     * @param null|int $offset
     * @return string
     * @throws Exception
     */
    public function getW3CTime($offset = null) {
        $timeObject = $this->getTimeObject($offset);
        if(!$timeObject instanceof DateTime) throw new Exception("Cannot create Time Object with offset: $offset");

        return $timeObject->format(DATE_W3C);
    }

    /**
     * @param string $timeString
     * @param string $timeFormat
     * @throws Exception
     * @return int
     */
    public function timeFromString($timeString, $timeFormat = "Y-m-d H:i:s") {
        $timeObject = $this->timeObjectFromString($timeString, $timeFormat);
        if(!$timeObject) return 0;
        return $timeObject->getTimestamp();
    }

    /**
     * @param $timeString
     * @param string $timeFormat
     * @return DateTime|int
     * @throws Exception
     */
    public function timeObjectFromString($timeString, $timeFormat = "Y-m-d H:i:s") {
        if(empty($timeString)) return 0; //if updated time didn`t exist
        $timeObject = DateTime::createFromFormat($timeFormat, $timeString, $this->getTimeZone());
        if(!$timeObject) throw new Exception("Cannot create time object from request time : $timeString");

        return $timeObject;
    }
}