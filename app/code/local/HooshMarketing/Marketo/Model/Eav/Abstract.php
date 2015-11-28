<?php
class HooshMarketing_Marketo_Model_Eav_Abstract extends HooshMarketing_Marketo_Model_Abstract
{
    /**
     * Load entity by attribute
     *
     * @param Mage_Eav_Model_Entity_Attribute_Interface|integer|string|array $attribute
     * @param null|string|array $value
     * @param string $additionalAttributes
     * @return bool|HooshMarketing_Marketo_Model_Lead
     */
    public function loadByAttribute($attribute, $value, $additionalAttributes = '*')
    {
        $collection = $this->getResourceCollection()
            ->addAttributeToSelect($additionalAttributes)
            ->addAttributeToFilter($attribute, $value)
            ->setPage(1,1);

        foreach ($collection as $object) {
            /** @var $object self */
            $this->setData($object->getData()); //set data
            $this->setOrigData(); // and set orig data - to use as already exist object
            return $object;
        }
        return false;
    }

}