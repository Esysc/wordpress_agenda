<?php
/**
 * Plugin Name: ACS Agenda Manager
 * Plugin URI: https://github.com/Esysc/wordpress_agenda
 * Description: A WordPress plugin for managing and displaying event agendas. Perfect for workshops, courses, and event organizers.
 * Version: 3.1.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Andrea Cristalli
 * Author URI: https://github.com/Esysc
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acs-agenda-manager
 * Domain Path: /lang
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;

// Plugin constants
define('ACS_AGENDA_VERSION', '3.1.0');
define('ACS_AGENDA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACS_AGENDA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACS_AGENDA_TABLE_NAME', 'acs_agenda_manager');

/**
 * Main plugin class
 */
final class ACS_Agenda_Manager {

    /** @var self|null Singleton instance */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies(): void {
        require_once ACS_AGENDA_PLUGIN_DIR . 'class/class-acs-database.php';
        require_once ACS_AGENDA_PLUGIN_DIR . 'class/class-acs-event.php';
        require_once ACS_AGENDA_PLUGIN_DIR . 'class/class-acs-template.php';
        require_once ACS_AGENDA_PLUGIN_DIR . 'class/class-acs-admin.php';
        require_once ACS_AGENDA_PLUGIN_DIR . 'class/class-acs-options.php';
        require_once ACS_AGENDA_PLUGIN_DIR . 'class/class-acs-help.php';

        // Initialize admin pages
        if (is_admin()) {
            ACS_Admin::get_instance();
            ACS_Options::get_instance();
            ACS_Help::get_instance();
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('plugins_loaded', [$this, 'check_version']);

        // AJAX handlers
        add_action('wp_ajax_read_more', [$this, 'ajax_read_more']);
        add_action('wp_ajax_nopriv_read_more', [$this, 'ajax_read_more']);

        // Shortcode
        add_shortcode('agenda', [$this, 'render_agenda_shortcode']);

        // Locale filter
        add_filter('locale', [$this, 'set_locale_from_browser']);
    }

    /**
     * Set locale based on browser language
     */
    public function set_locale_from_browser(string $locale): string {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $locale;
        }

        $browser_lang = substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE'])), 0, 2);

        $locale_map = [
            'fr' => 'fr_FR.UTF-8',
            'en' => 'en_US.UTF-8',
        ];

        if (isset($locale_map[$browser_lang])) {
            setlocale(LC_TIME, $locale_map[$browser_lang]);
        }

        return $locale;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(bool $force = false): void {
        if (!$force && !$this->should_load_frontend_assets()) {
            return;
        }

        wp_enqueue_style(
            'acs-agenda-style',
            ACS_AGENDA_PLUGIN_URL . 'css/acs.css',
            [],
            ACS_AGENDA_VERSION
        );

        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-datepicker');

        wp_enqueue_script(
            'acs-multidatespicker',
            ACS_AGENDA_PLUGIN_URL . 'js/jquery-ui-multidatespicker.min.js',
            ['jquery-ui-datepicker'],
            '1.6.6',
            true
        );

        wp_enqueue_script(
            'acs-agenda-frontend',
            ACS_AGENDA_PLUGIN_URL . 'js/acs-frontend.js',
            ['jquery', 'jquery-ui-dialog'],
            ACS_AGENDA_VERSION,
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );

        wp_localize_script('acs-agenda-frontend', 'acsAgenda', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acs_agenda_nonce'),
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(string $hook): void {
        if (strpos($hook, 'agenda') === false) {
            return;
        }

        $this->enqueue_frontend_assets(true);

        wp_enqueue_media();
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');

        // Load Google Maps API if key is configured (only if supplied via option)
        $google_maps_api_key = get_option('acs_google_maps_api_key', '');
        if (!empty($google_maps_api_key)) {
            $maps_url = add_query_arg(
                [
                    'key' => rawurlencode($google_maps_api_key),
                    'libraries' => 'places',
                    'loading' => 'async',
                ],
                'https://maps.googleapis.com/maps/api/js'
            );
            wp_enqueue_script(
                'google-maps-places',
                $maps_url,
                [],
                ACS_AGENDA_VERSION,
                [
                    'in_footer' => true,
                    'strategy' => 'defer',
                ]
            );
        }

        wp_enqueue_script(
            'acs-agenda-admin',
            ACS_AGENDA_PLUGIN_URL . 'js/acs-admin.js',
            ['jquery', 'jquery-ui-dialog', 'acs-multidatespicker'],
            ACS_AGENDA_VERSION,
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );

        wp_localize_script('acs-agenda-admin', 'acsAgendaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acs_agenda_admin_nonce'),
            'hasGoogleMaps' => !empty($google_maps_api_key),
            'i18n' => $this->get_admin_translations(),
        ]);
    }

    /**
     * Decide if frontend assets should load to avoid enqueuing everywhere.
     */
    private function should_load_frontend_assets(): bool {
        if (is_admin()) {
            return false;
        }

        $should_load = false;

        if (is_singular()) {
            $post = get_post();
            if ($post && has_shortcode($post->post_content, 'agenda')) {
                $should_load = true;
            }

            $template = get_page_template_slug($post);
            if ($template === 'page-agenda.php') {
                $should_load = true;
            }
        }

        return apply_filters('acs_agenda_should_enqueue_assets', $should_load);
    }

    /**
     * Get admin translations for JavaScript
     */
    private function get_admin_translations(): array {
        return [
            'confirm' => __('Confirm', 'acs-agenda-manager'),
            'cancel' => __('Cancel', 'acs-agenda-manager'),
            'update' => __('Update', 'acs-agenda-manager'),
            'add' => __('Add', 'acs-agenda-manager'),
            'close' => __('Close', 'acs-agenda-manager'),
            'calendar' => __('Calendar', 'acs-agenda-manager'),
            'copied' => __('Copied', 'acs-agenda-manager'),
            'fieldEmpty' => __('The field is empty', 'acs-agenda-manager'),
            'selectImage' => __('Select or upload an image', 'acs-agenda-manager'),
            'filteredEvents' => __('Filtered events', 'acs-agenda-manager'),
            'confirmDelete' => __('Do you really want to delete this event?', 'acs-agenda-manager'),
            'editor' => __('Editor', 'acs-agenda-manager'),
            'addEvent' => __('Add an event', 'acs-agenda-manager'),
        ];
    }

    /**
     * Check and update plugin version
     */
    public function check_version(): void {
        $installed_version = get_option('acs_agenda_manager_plugin_version', '0');

        if (version_compare($installed_version, ACS_AGENDA_VERSION, '<')) {
            ACS_Database::update_schema();
            update_option('acs_agenda_manager_plugin_version', ACS_AGENDA_VERSION);
        }
    }

    /**
     * Render agenda shortcode
     */
    public function render_agenda_shortcode($atts = [], ?string $content = null): string {
        $events = ACS_Event::get_upcoming_events();

        if (empty($events)) {
            return '<p>' . esc_html__('No upcoming events.', 'acs-agenda-manager') . '</p>';
        }

        return ACS_Template::render_agenda($events);
    }

    /**
     * AJAX handler for read more dialog
     */
    public function ajax_read_more(): void {
        check_ajax_referer('acs_agenda_nonce', 'nonce', true);

        $post_id = isset($_POST['postid']) ? absint($_POST['postid']) : 0;
        $href = isset($_POST['href']) ? esc_url_raw(wp_unslash($_POST['href'])) : '';

        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'acs-agenda-manager'));
        }

        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(__('Post not found', 'acs-agenda-manager'));
        }

        echo wp_kses_post(ACS_Template::render_read_more_dialog($post, $href));
        wp_die();
    }

    /**
     * Get a page by its title using WP_Query (replaces deprecated get_page_by_title)
     *
     * @param string $title Page title
     * @return WP_Post|null
     */
    public static function get_page_by_title(string $title): ?WP_Post {
        $query = new WP_Query([
            'post_type' => 'page',
            'title' => $title,
            'post_status' => 'all',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ]);

        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Plugin activation
     */
    public static function activate(): void {
        require_once plugin_dir_path(__FILE__) . 'class/class-acs-database.php';
        ACS_Database::create_table();

        $page_name = get_option('acsagendapage', 'Agenda');
        $existing_page = self::get_page_by_title($page_name);

        if (!$existing_page) {
            wp_insert_post([
                'post_title' => $page_name,
                'post_name' => sanitize_title($page_name),
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[agenda]',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }

        update_option('acsagendapage', $page_name);
        update_option('acs_agenda_manager_plugin_version', ACS_AGENDA_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate(): void {
        // Clean up transients if needed
        delete_transient('acs_agenda_events_cache');
    }
}

// Activation/deactivation hooks
register_activation_hook(__FILE__, ['ACS_Agenda_Manager', 'activate']);
register_deactivation_hook(__FILE__, ['ACS_Agenda_Manager', 'deactivate']);

// Initialize plugin
add_action('plugins_loaded', function() {
    ACS_Agenda_Manager::get_instance();
});
