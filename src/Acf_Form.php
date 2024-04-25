<?php

namespace Dynamic_Forms;

class Acf_Form
{

    /**
     * Bulk adds fields to layouts
     *
     * @param array $field
     * @return array
     */
    public function add_field_to_layouts(array $field): array {
        // Skip adding on acf screen
        if (is_admin() && !wp_doing_ajax() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if (empty($screen) || $screen->post_type === "acf-field-group") {
                return $field;
            }
        }

        foreach ($field['layouts'] as &$layout) {

            $name = $layout['name'];

            $field_defaults = [
                'parent'        => $field['key'],
                'parent_layout' => $layout['key'],
                '_valid'        => 1,
                'wrapper'       => array(
                    'width' => '',
                    'class' => '',
                    'id'    => '',
                ),
                'instructions'  => '',
                'class'         => ''
            ];

            // Label field
            $field_label = array_merge([
                'ID'         => 0,
                'key'        => $layout['key'] . '_' . $name,
                'label'      => 'Beschriftung',
                'name'       => 'label',
                'prefix'     => 'acf',
                'type'       => 'text',
                'menu_order' => -1,
                'required'   => 1,
                '_name'      => 'label',
                'prepend'    => '',
                'append'     => '',
                'maxlength'  => '',
            ], $field_defaults);

            // Required field
            $field_required = array_merge([
                'ID'            => 0,
                'key'           => 'field_65b250e94fee3_' . $name,
                'label'         => 'Pflichtfeld',
                'name'          => 'required',
                '_name'         => 'required',
                'aria-label'    => '',
                'type'          => 'true_false',
                'required'      => 0,
                'message'       => '',
                'default_value' => 0,
                'ui_on_text'    => '',
                'ui_off_text'   => '',
                'ui'            => 1,
            ], $field_defaults);

            // Add fields
            if ($name !== "datepicker" && $name !== "page_break") {
                $layout['sub_fields'][] = $field_label;
            }
            if ($name !== "page_break") {
                $layout['sub_fields'][] = $field_required;
            }

        }

        return $field;
    }

}