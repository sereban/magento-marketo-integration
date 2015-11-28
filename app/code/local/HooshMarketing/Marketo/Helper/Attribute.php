<?php
class HooshMarketing_Marketo_Helper_Attribute extends HooshMarketing_Marketo_Helper_Abstract
{
    /**
     * @param string $code
     * @return int|mixed
     * @throws Exception
     */
    public function getEntityTypeIdByCode($code) {
        $typeInstance = $this->getMarketoAbstractInstance()->_getTypeInstance()->loadByCode($code);
        if(!$typeInstance) throw new Exception("Cannot find entity type code");

        return $typeInstance->getId();
    }

    /**
     * prepare all attribute codes for marketo attributes
     * @return array
     */
    public function getAttributeCodes() {
        $attributeCodes = array();

        foreach($this->getAttributes() as $attribute) {
            /** @var HooshMarketing_Marketo_Model_Eav_Attribute $attribute  */
            $attributeCodes[$attribute->getId()] = $attribute->getAttributeCode();
        }

        return $attributeCodes;
    }

    /**
     * @return HooshMarketing_Marketo_Model_Resource_Attribute_Collection
     */
    public function getAttributes() {
        /* prepare attribute collection */
        return $this
            ->getMarketoAbstractInstance()
            ->getAttributeModel()
            ->getCollection()
            ->addFieldToFilter("api_type", array(
                HooshMarketing_Marketo_Model_Opportunity::API_TYPE,
                HooshMarketing_Marketo_Model_Lead::API_TYPE
            ));
    }
}