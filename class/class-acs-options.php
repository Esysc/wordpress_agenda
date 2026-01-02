<?php
/**
 * Options page for ACS Agenda Manager
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;

/**
 * Handles plugin settings
 */
class ACSAGMA_Options {

    /** @var self|null */
    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_submenu_page(): void {
        add_submenu_page(
            'acsagma-agenda',
            __('Settings', 'acs-agenda-manager'),
            __('Settings', 'acs-agenda-manager'),
            'manage_options',
            'acsagma-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting('acsagma_agenda_settings', 'acsagma_page', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Agenda',
        ]);

        register_setting('acsagma_agenda_settings', 'acsagma_google_maps_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
    }

    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied', 'acs-agenda-manager'));
        }

        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('acsagma_agenda_settings_nonce')) {
            $this->save_settings();
        }

        $agenda_page = get_option('acsagma_page', 'Agenda');
        $google_maps_api_key = get_option('acsagma_google_maps_api_key', '');

        include ACSAGMA_AGENDA_PLUGIN_DIR . 'templates/settings-page.php';
    }

    private function save_settings(): void {
        if (!check_admin_referer('acsagma_agenda_settings_nonce')) {
            wp_die(esc_html__('Security check failed', 'acs-agenda-manager'));
        }

        $old_page_name = get_option('acsagma_page', 'Agenda');
        $new_page_name = sanitize_text_field(wp_unslash($_POST['acsagma_page'] ?? 'Agenda'));

        // Save Google Maps API key
        $api_key = sanitize_text_field(wp_unslash($_POST['acsagma_google_maps_api_key'] ?? ''));
        update_option('acsagma_google_maps_api_key', $api_key);

        if ($old_page_name !== $new_page_name) {
            // Delete old page
            $old_page = ACSAGMA_Agenda_Manager::get_page_by_title($old_page_name);
            if ($old_page) {
                wp_delete_post($old_page->ID, true);
            }

            // Create new page
            $existing_page = ACSAGMA_Agenda_Manager::get_page_by_title($new_page_name);
            if (!$existing_page) {
                wp_insert_post([
                    'post_title' => $new_page_name,
                    'post_name' => sanitize_title($new_page_name),
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_content' => '[acsagma_agenda]',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                ]);
            }

            update_option('acsagma_page', $new_page_name);
        }

        add_settings_error(
            'acsagma_agenda_settings',
            'settings_updated',
            __('Settings saved successfully.', 'acs-agenda-manager'),
            'updated'
        );
    }
}
