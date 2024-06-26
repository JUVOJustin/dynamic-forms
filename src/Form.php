<?php

namespace Dynamic_Forms;

use DateTime;
use WP_Error;
use WP_Post;

class Form
{

    /**
     * WP_Post Representation of the form post
     *
     * @var WP_Post
     */
    public WP_Post $form_post;

    public int $form_id;
    public array $mirroring_forms;

    /**
     * Post ID of the content where the form is embedded
     *
     * @var int|false
     */
    public int|bool $context_post_id;

    public array $fields;

    public array $buttons;

    public bool $readonly;

    public function __construct(int $id, ?int $post_id = null)
    {
        $this->form_id = $id;
        $this->form_post = get_post($this->form_id );
        $this->context_post_id = $post_id ?: get_the_ID();
        $this->fields = $this->parse_fields(get_field('form_fields', $this->form_post) ?: []);
        $this->buttons = get_field('buttons_group', $this->form_post) ?: [];
        $this->readonly =  get_field('readonly_form', $this->form_post) ?: false;
        $this->mirroring_forms =  get_field('mirroring_form', $this->form_post) ?: [];
    }

    /**
     * Get paginated fields.
     *
     * This method returns the fields grouped by their page number.
     *
     * @return array The paginated fields
     */
    public function get_fields_paginated(): array
    {
        $arr = array();
        foreach ($this->fields as $item) {
            $arr[$item['page']][] = $item;
        }
        ksort($arr, SORT_NUMERIC);
        return $arr;
    }

    /**
     * Parse form fields.
     *
     * This method takes an array of form fields and parses them into a more usable format.
     *
     * @param array $data The form fields to parse. Is is most likely ACF data
     * @return array The parsed form fields
     */
    public function parse_fields(array $data): array
    {
        $page_index = 0;
        $fields = [];
        $current_page = [];

        $i = 0;
        $len = count($data);

        foreach ($data as $field) {
            $field_general = [
                "type" => $field['acf_fc_layout'],
                "page" => $page_index
            ];
            unset($field['acf_fc_layout']);
            $field = array_merge($field_general, $field);

            // Some special fields do not allow setting a name. Use type instead
            if (empty($field['name'])) {
                $field['name'] = $field['type'];
            }

            // Handle fields custom settings
            switch ($field['type']) {
                case "datepicker":
                    $field['bookedDates'] = [];

                    // @link https://easepick.com/guide/
                    $field['config'] = [
                        'calendars' => $field['no_calendars'] ?: 1,
                        'lang'      => str_replace('_', '-', get_locale())
                    ];

                    // Maybe set dropdown
                    if (!empty($field['dropdown'])) {
                        if ($field['dropdown'] === 'years' || $field['dropdown'] === 'both') {
                            $field['config']['AmpPlugin']['dropdown']['years'] = true;
                        }
                        if ($field['dropdown'] === 'months' || $field['dropdown'] === 'both') {
                            $field['config']['AmpPlugin']['dropdown']['months'] = true;
                        }
                    }
                    break;
            }

            // Increment the index
            $i++;

            // If the field is a page break empty the current page and continue
            if (
                $field['type'] === 'page_break'
            ) {
                $fields = array_merge($fields, $current_page);
                $current_page = [];
                $page_index++;
                continue;
            }

            $field = apply_filters("dynamic_forms/parse/field/name={$field['name']}", $field, $this->context_post_id);
            if (empty($field)) {
                continue;
            }

            $current_page[$field['name']] = $field;

            // If the field is the last one, sum fields
            if ($i === $len) {
                $fields = array_merge($fields, $current_page);
            }

        }

        return apply_filters("dynamic_forms/parse/fields", $fields, $this->context_post_id);
    }

    /**
     * Calculate the total price.
     *
     * @return mixed|null
     */
    public function get_costs()
    {
        return apply_filters('dynamic_forms_calculate_total', [
            'total' => 0,
        ], $this, $this->context_post_id);
    }

    /**
     * Add values to form fields.
     *
     * This method takes an array of form data and adds the corresponding values to the form fields.
     *
     * @param array $form_data The form data to add to the fields
     */
    public function add_value_to_fields(array $form_data): WP_Error
    {

        $error = new WP_Error();

        foreach ($form_data as $name => $value) {

            if (!in_array($name, array_keys($this->fields))) {
                continue;
            }

            // Check if generic types are in form data by comparing "name"
            $valid = $this->validate_field_value($name, $value ?? "");
            if ($valid->has_errors()) {
                $error->merge_from($valid);
            }

        }

        return $error;
    }

    /**
     * Validate the form.
     *
     * @return WP_Error
     */
    public function validate_form(): WP_Error
    {
        $error = new WP_Error();

        foreach ($this->fields as $name => $field) {
            $valid = $this->validate_field_value($name, $field['value'] ?? "");
            if ($valid->has_errors()) {
                $error->merge_from($valid);
            }
        }

        return $error;
    }

    /**
     * Validates, sanitizes and sets the value of a field
     *
     * @param string $name
     * @param $val
     * @return WP_Error
     */
    private function validate_field_value(string $name, $val): WP_Error
    {

        $error = new WP_Error();
        $field = &$this->fields[$name];

        // Ignore page breaks (they are not fields)
        if ($field['type'] === 'page_break') {
            return new WP_Error();
        }

        // Allow modifying field value before validation rules
        $val = apply_filters("dynamic_forms/validation/before/val/name={$field['name']}", $val, $field, $this->context_post_id);

        // Check required
        if ($field['required'] && empty($val)) {

            $label = $field['label'] ?: $field['name'];

            // Datepickers do not have one specific label
            if ($field['type'] === 'datepicker') {
                $label = __('Datepicker', 'calendar-booking');
            }

            $error->add('field_empty', sprintf(__('"%s" must be filled', 'calendar-booking'), $label));
            return $error;
        }

        switch ($field['type']) {
            case "number":
                if (!is_numeric($val)) {
                    $error->add('invalid_number', __('You need to enter a valid number', 'calendar-booking'));
                    break;
                }

                if (!empty($field['min']) && $val < $field['min']) {
                    $error->add('number_too_small', __(sprintf('You need to enter a number greater than %d', $field['min']), 'calendar-booking'));
                }

                if (!empty($field['max']) && $val > $field['max']) {
                    $error->add('number_too_big', __(sprintf('You need to enter a number smaller than %d', $field['max']), 'calendar-booking'));
                }

                $val = floatval($val);
                break;
            case "datepicker":

                // Check technical format
                if (!is_array($val)) {
                    $error->add('invalid_date', __('You need to select a valid date', 'calendar-booking'));
                    break;
                }

                // Check if dates are empty
                if (empty($val['checkin']) || empty($val['checkout'])) {
                    $error->add('missing_dates', __('You need to select a start and end date', 'calendar-booking'));
                    break;
                }

                $checkin = $val['checkin'];
                $checkout = $val['checkout'];

                // Check if dates are either already DateTimes or can be parsed
                if (!$checkin instanceof DateTime || !$checkout instanceof DateTime) {
                    $checkin = DateTime::createFromFormat('Y-m-d', $checkin);
                    $checkout = DateTime::createFromFormat('Y-m-d', $checkout);
                    if (!$checkin || !$checkout) {
                        $error->add('invalid_dates', __('You need to select a valid date', 'calendar-booking'));
                        break;
                    }
                }

                // Check if checkout is after checkin
                if ($checkin->getTimestamp() > $checkout->getTimestamp()) {
                    $error->add('invalid_dates', __('End date needs to be after start date', 'calendar-booking'));
                    break;
                }

                $val = [
                    'checkin'     => $checkin,
                    'checkout'    => $checkout,
                ];

                $val['span'] = $checkin->diff($checkout)->d;

                // If an offset is given recalculate the span
                if (!empty($field['day_offset'])){
                    $offset = intval($field['day_offset']);
                    $val['span'] = $val['span'] - $offset;
                }

                break;
            case "email":
                $var = sanitize_email($val);
                if ($var !== $val) {
                    $error->add('invalid_email', __('You need to enter a valid email', 'calendar-booking'));
                    break;
                }
                break;
            case "phone":
                $match = preg_match("/^\+?\d{10,15}$/", $val);
                if (!$match) {
                    $error->add('invalid_phone', __('You need to enter a valid phone number', 'calendar-booking'));
                    break;
                }
                $val = sanitize_text_field($val);
                break;
            case "text":
                if ($val !== sanitize_text_field($val)) {
                    $error->add('invalid_text', __('You need to enter a valid text', 'calendar-booking'));
                }
                break;
        }

        // Maybe add more errors with custom integration
        $error = apply_filters("dynamic_forms/validation/name={$field['name']}", $error, $field, $this->context_post_id);

        // Set val to field by reference
        $field['value'] = $val;

        // Allow changing the filed after the whole validation process
        $field = apply_filters("dynamic_forms/validation/after/field/name={$field['name']}", $field, $error, $this->context_post_id);

        return $error;

    }

    /**
     * Get the value of a field
     *
     * @param string $name
     * @return mixed
     */
    public function field_val(string $name)
    {
        return $this->fields[$name]['value'];
    }

}