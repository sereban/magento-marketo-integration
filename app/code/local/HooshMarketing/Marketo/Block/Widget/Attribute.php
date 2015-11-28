<?php

class HooshMarketing_Marketo_Block_Widget_Attribute extends Mage_Core_Block_Abstract implements Mage_Widget_Block_Interface
{
    /**
     * @return mixed
     */
    protected function _toHtml()
    {
        if (Mage::helper("hoosh_marketo")->getModuleStatus()) {
            /** @var HooshMarketing_Marketo_Model_Lead $lead */
            $lead = Mage::getSingleton("hoosh_marketo/lead")->getLoadedByCookie();
            $fieldName = $this->getData("field_name"); // retrieve

            $default = $this->getData("default");

            if ($lead->hasData($fieldName)) {
                $name = $lead->getData($fieldName);
            }

            if (empty($name)) {
                return $default;
            }

            return $name;
        }

        return "";
    }
}