<?php
class HooshMarketing_Marketo_Model_Resource_Attribute_Setup extends Mage_Eav_Model_Entity_Setup
{
    protected function _prepareValues($attr)
    {
        $data = array(
            'backend_model'   => $this->_getValue($attr, 'backend'),
            'backend_type'    => $this->_getValue($attr, 'type', 'varchar'),
            'backend_table'   => $this->_getValue($attr, 'table'),
            'frontend_model'  => $this->_getValue($attr, 'frontend'),
            'frontend_input'  => $this->_getValue($attr, 'input', 'text'),
            'frontend_label'  => $this->_getValue($attr, 'label'),
            'frontend_class'  => $this->_getValue($attr, 'frontend_class'),
            'source_model'    => $this->_getValue($attr, 'source'),
            'is_required'     => $this->_getValue($attr, 'required', 1),
            'is_user_defined' => $this->_getValue($attr, 'user_defined', 0),
            'default_value'   => $this->_getValue($attr, 'default'),
            'is_unique'       => $this->_getValue($attr, 'unique', 0),
            'note'            => $this->_getValue($attr, 'note'),
            'is_global'       => $this->_getValue($attr, 'is_global', 1),
            'is_enabled'      => $this->_getValue($attr, 'is_enabled', 1),
            'api_type'        => $this->_getValue($attr, 'api_type', 1),
            'soap_api_name'        => $this->_getValue($attr, 'soap_api_name', ''),
            'rest_api_name'        => $this->_getValue($attr, 'rest_api_name', ''),
            'friendly_name'        => $this->_getValue($attr, 'friendly_name', ''),
        );

        return $data;
    }
}