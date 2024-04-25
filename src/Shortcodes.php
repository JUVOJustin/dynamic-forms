<?php

namespace Dynamic_Forms;

use Timber\Timber;

class Shortcodes
{

    /**
     * Shortcode callback
     *
     * @param array $args
     * @return bool|string
     */
    public function calendar_form($args)
    {
        $args = shortcode_atts([
            'id' => '',
        ], $args);

        $id = intval($args['id']);
        if (empty($id)) {
            return "<p>You have to set a Form ID</p>";
        }

        $form = new Form($id);

        return Timber::compile('form.twig', [
            'form_id' => $id,
            'form'    => $form,
        ]);
    }

}