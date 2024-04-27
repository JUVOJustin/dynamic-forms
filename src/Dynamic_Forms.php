<?php

namespace Dynamic_Forms;

use Dynamic_Forms\Frontend\Frontend;
use Dynamic_Forms\Admin\Admin;
use Timber\Timber;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Dynamic_Forms
 * @subpackage Dynamic_Forms/includes
 * @author     Justin Vogt <mail@juvo-design.de>
 */
class Dynamic_Forms
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin
     *
     * @var Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct(string $version)
    {

        $this->plugin_name = 'dynamic_forms';
        $this->version = $version;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        $this->loader = new Loader();

        // Timber
        Timber::init();
        add_filter('timber/locations', function($locations) {

            if (file_exists(get_stylesheet_directory() . "/dynamic-forms/frontend/")) {
                $locations[] = get_stylesheet_directory() . "/dynamic-forms/frontend/";
            }

            return array_merge($locations, array(
                DYNAMIC_FORMS_PATH . "resources/frontend/views",
                DYNAMIC_FORMS_PATH . "resources/admin/views",
            ));
        });

        // Save json data if dev env
        add_filter('acf/settings/save_json/key=group_65afe63ae981c', function($path): string {
            return DYNAMIC_FORMS_PATH . 'resources/acf-json';
        });
        add_filter('acf/settings/save_json/key=group_662d49486998b', function($path): string {
            return DYNAMIC_FORMS_PATH . 'resources/acf-json';
        });
        add_filter('acf/settings/save_json/key=post_type_65afc996ee80d', function($path): string {
            return DYNAMIC_FORMS_PATH . 'resources/acf-json';
        });

        //Load Json
        add_filter('acf/settings/load_json', function(array $paths): array {
            $paths[] = DYNAMIC_FORMS_PATH . 'resources/acf-json';
            return $paths;
        });
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks()
    {

        add_action('admin_enqueue_scripts', function() {
            $this->enqueue_bud_entrypoint('dynamic-forms-admin');
        }, 100);

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        add_action('wp_enqueue_scripts', function() {
            $this->enqueue_bud_entrypoint('dynamic-forms-frontend', [
                'ajax_url'       => admin_url('admin-ajax.php'),
                'post_id'        => get_the_ID(),
                'nonce'          => wp_create_nonce('dynamic_forms_nonce'),
                'easepicker_css' => [
                    DYNAMIC_FORMS_URL . 'dist/css/easepicker.css',
                    DYNAMIC_FORMS_URL . 'dist/css/easepicker.custom.css'
                ],
            ]);
        }, 100);

        $ajax = new Ajax();
        $this->loader->add_action('wp_ajax_get_form_data', $ajax, 'ajax_get_data_callback');
        $this->loader->add_action('wp_ajax_nopriv_get_form_data', $ajax, 'ajax_get_data_callback');
        $this->loader->add_action('wp_ajax_calc_price', $ajax, 'ajax_calc_price');
        $this->loader->add_action('wp_ajax_nopriv_calc_price', $ajax, 'ajax_calc_price');
        $this->loader->add_action('wp_ajax_submit', $ajax, 'ajax_submit');
        $this->loader->add_action('wp_ajax_nopriv_submit', $ajax, 'ajax_submit');

        $this->loader->add_shortcode('calendar_form', new Shortcodes(), 'calendar_form');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Enqueue a bud entrypoint
     *
     * @param string $entry
     * @param array $localize_data
     */
    private function enqueue_bud_entrypoint(string $entry, array $localize_data = [])
    {
        $entrypoints_manifest = DYNAMIC_FORMS_PATH . '/dist/entrypoints.json';

        // parse json file
        $entrypoints = json_decode(file_get_contents($entrypoints_manifest));

        // Iterate entrypoint groups
        foreach ($entrypoints as $key => $bundle) {

            // Only process the entrypoint that should be enqueued per call
            if ($key != $entry) {
                continue;
            }

            // Iterate js and css files
            foreach ($bundle as $type => $files) {
                foreach ($files as $file) {
                    if ($type == "js") {
                        wp_enqueue_script(
                            "$this->plugin_name/$file",
                            DYNAMIC_FORMS_URL . 'dist/' . $file,
                            $bundle->dependencies ?? [],
                            $this->get_version(),
                            true,
                        );

                        // Maybe localize js
                        if (!empty($localize_data)) {
                            wp_localize_script("$this->plugin_name/$file", str_replace('-', '_', $this->plugin_name), $localize_data);

                            // Unset after localize since we only need to localize one script per bundle so on next iteration will be skipped
                            unset($localize_data);
                        }
                    }

                    if ($type == "css") {
                        wp_enqueue_style(
                            "$this->plugin_name/$file",
                            DYNAMIC_FORMS_URL . 'dist/' . $file,
                            [],
                            $this->get_version(),
                        );
                    }
                }
            }
        }
    }

}
