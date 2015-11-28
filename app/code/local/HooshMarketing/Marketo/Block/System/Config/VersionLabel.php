<?php
class HooshMarketing_Marketo_Block_System_Config_VersionLabel extends Mage_Adminhtml_Block_System_Config_Form_Field_Heading
{
    public function render(Varien_Data_Form_Element_Abstract $element) {
        $html =sprintf('<tr class="system-fieldset-additional" id="row_%s_add">
                <td colspan="1">Version <span style="margin-left: 5px; color: red; font-weight: bold;  font-size: 16px;">%s</span></td>
            </tr>',
            $element->getHtmlId(), Mage::helper("hoosh_marketo")->getMarketoVersion());
        return $html;
    }
}