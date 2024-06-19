<?php

namespace Dynamic_Forms;

class Ajax
{

    private Form $form;

    function ajax_submit()
    {
        $this->check_pre_conditions();

        if ($this->form->readonly) {
            wp_send_json_error(new \WP_Error('readonly_form', __("This is a readonly form", "calendar-booking")));
        }

        // Add and validate the values to the form
        $errors = $this->form->add_value_to_fields($_POST['fields']);
        if ($errors->has_errors()) {
            wp_send_json_error($errors);
        }

        $errors = $this->form->validate_form();
        if ($errors->has_errors()) {
            wp_send_json_error($errors);
        }

        // Initialisierung der Nachrichtenvariablen
        $message = __("Booking request details:", "calendar-booking") . "\n";

        // Schleife durch die Formularfelder und Anhängen an die Nachricht
        foreach ($this->form->fields as $field) {

            if ($field['type'] == 'datepicker') {
                $message .= sprintf(__("Period: %s - %s", "calendar-booking"). "\n", $field['value']['checkin']->format('d.m.Y'), $field['value']['checkout']->format('d.m.Y'));
                continue;
            }

            $message .= $field['label'] . ": " . $field['value'] . "\n";
        }

        // Todo change mail later
        wp_mail(
            "info@ferienwohnungen-iske.de",
            __("New booking request", 'calendar-booking') . " " . get_the_title($this->form->context_post_id),
            $message
        );

        wp_send_json_success([
            'message' => __('Your booking request has been sent successfully.', 'calendar-booking')
        ]);
    }

    // Handle the Ajax request on the server side
    function ajax_calc_price()
    {
        $this->check_pre_conditions();

        // Filter the fields with values
        $fields = array_filter($_POST['fields'], function ($val) {
            return !empty($val);
        });

        // Add and validate the values to the form
        $errors = $this->form->add_value_to_fields($fields);
        if ($errors->has_errors()) {
            wp_send_json_error($errors);
        }

        $costs = $this->form->get_costs();
        if ($costs instanceof \WP_Error && $costs->has_errors()) {
            wp_send_json_error($costs);
        }

        wp_send_json_success($this->form->get_costs());
    }

    /**
     * Check if the request is valid
     *
     * @return void
     */
    private function check_pre_conditions(): void
    {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'dynamic_forms_nonce' ) ) {
            wp_send_json_error([
                'message' => __('Invalid nonce', 'calendar-booking')
            ]);
        }

        if (empty($_POST['form_id'])) {
            wp_send_json_error([
                'message' => __('Missing form_id parameter', 'calendar-booking')
            ]);
        }

        $post_id = intval($_POST['post_id']);
        if (empty($post_id)) {
            wp_send_json_error(new \WP_Error('invalid_post_id', 'Invalid post id'));
        }

        if (empty($_POST['fields'])) {
            wp_send_json_error([
                'message' => __('Missing fields parameter','calendar-booking')
            ]);
        }

        $this->form = new Form($_POST['form_id'], $_POST['post_id']);
    }

}