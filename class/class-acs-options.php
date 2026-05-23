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

        register_setting('acsagma_agenda_settings', 'acsagma_delete_data_on_uninstall', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false,
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
        $delete_data_on_uninstall = get_option('acsagma_delete_data_on_uninstall', false);

        include ACSAGMA_AGENDA_PLUGIN_DIR . 'templates/settings-page.php';
    }

    private function save_settings(): void {
        check_admin_referer('acsagma_agenda_settings_nonce');

        $old_page_name = get_option('acsagma_page', 'Agenda');
        $new_page_name = sanitize_text_field(wp_unslash($_POST['acsagma_page'] ?? 'Agenda'));

        // Save Google Maps API key
        $api_key = sanitize_text_field(wp_unslash($_POST['acsagma_google_maps_api_key'] ?? ''));
        update_option('acsagma_google_maps_api_key', $api_key);

        // Save delete data on uninstall option
        $delete_data = isset($_POST['acsagma_delete_data_on_uninstall']) ? true : false;
        update_option('acsagma_delete_data_on_uninstall', $delete_data);

        if ($old_page_name !== $new_page_name) {
            // Prefer updating the existing page to avoid data loss and broken links.
            $old_page = ACSAGMA_Agenda_Manager::get_page_by_title($old_page_name);
            $existing_page = ACSAGMA_Agenda_Manager::get_page_by_title($new_page_name);

            if ($old_page && (!$existing_page || (int) $existing_page->ID === (int) $old_page->ID)) {
                wp_update_post([
                    'ID' => $old_page->ID,
                    'post_title' => $new_page_name,
                    'post_name' => sanitize_title($new_page_name),
                ]);
            } elseif (!$existing_page) {
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
