<?php

/**
 * Class HooshMarketing_Marketo_Block_System_Config_Mapping_Render_Magento_Object
 * @method setName(string $value)
 * @method setExtraParams(string $value)
 */
class HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Magento_Category_Path
    extends HooshMarketing_Marketo_Block_System_Config_Mapping_Renderer_Select
{
    protected $_defaultOptionLabel = "Select Category Path...";
    protected $_fieldCode          = "magento_category_path";
    protected $_extraParams        = 'style="width:120px"';

    protected function _getFields() {
        return $this->_getPersonalizeCategoryModel()->getNamedCategoryPaths();
    }

    public function _toHtml()
    {
        $this->_initParams();

        if (!$this->getOptions()) {
            $this->_addDefaultOption();
            foreach($this->_getFields() as $category) {
                $this->addOption(
                    $category["path"],
                    $category["modified_names"]
                );
            }
        }

        return parent::_toHtml();
    }
}