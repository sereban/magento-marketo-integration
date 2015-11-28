<?php

class HooshMarketing_Marketo_Block_Widget_Content
    extends Mage_Core_Block_Abstract
    implements Mage_Widget_Block_Interface
{
    /**
     * @return string
     * @throws Exception
     */
    protected function _toHtml()
    {
        if (Mage::helper("hoosh_marketo")->getModuleStatus()) {
            $lead = Mage::getModel("hoosh_marketo/lead")->getLead();
            $fieldName = $this->getData("field_name");
            $fieldValue = $this->getData("field_value");
            $blockId = $this->getData("block_id");
            $defaultBlockId = $this->getData("default_block_id");
            $content = '';

            if (!empty($fieldValue)) {
                $fieldValues = explode(",", $fieldValue);
                
                if (isset($lead[$fieldName]) && in_array($this->stripSpaces($lead[$fieldName]), $fieldValues)) {
                    if ($blockId) {

                        $block = Mage::getModel('cms/block')
                            ->setStoreId(Mage::app()->getStore()->getId())
                            ->load($blockId);
                        if ($block->getIsActive()) {
                            /* @var $helper Mage_Cms_Helper_Data */
                            $helper = Mage::helper('cms');
                            $processor = $helper->getBlockTemplateProcessor();
                            $content = $processor->filter($block->getContent());
                        }
                    }
                } else if ($defaultBlockId) {

                    $block = Mage::getModel('cms/block')
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->load($defaultBlockId);
                    if ($block->getIsActive()) {
                        /* @var $helper Mage_Cms_Helper_Data */
                        $helper = Mage::helper('cms');
                        $processor = $helper->getBlockTemplateProcessor();
                        $content = $processor->filter($block->getContent());
                    }
                }
            }

            return $content;
        }

        return "";
    }

    /**
     * @param $string
     * @return mixed
     */
    public function stripSpaces($string) {
        return preg_replace("/\s+/", "", $string);
    }
}
