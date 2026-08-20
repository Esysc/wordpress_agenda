<?php
/**
 * Plugin Name: ACS Agenda Manager
 * Plugin URI: https://github.com/Esysc/wordpress_agenda
 * Description: A WordPress plugin for managing and displaying event agendas. Perfect for workshops, courses, and event organizers.
 * Version: 3.6.1
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
define('ACSAGMA_AGENDA_VERSION', '3.6.1');
define('ACSAGMA_AGENDA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACSAGMA_AGENDA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACSAGMA_AGENDA_TABLE_NAME', 'acs_agenda_manager');

/**
 * Main plugin class
 */
final class ACSAGMA_Agenda_Manager {

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
        require_once ACSAGMA_AGENDA_PLUGIN_DIR . 'class/class-acs-database.php';
        require_once ACSAGMA_AGENDA_PLUGIN_DIR . 'class/class-acs-event.php';
        require_once ACSAGMA_AGENDA_PLUGIN_DIR . 'class/class-acs-template.php';
        require_once ACSAGMA_AGENDA_PLUGIN_DIR . 'class/class-acs-admin.php';
        require_once ACSAGMA_AGENDA_PLUGIN_DIR . 'class/class-acs-options.php';
        require_once ACSAGMA_AGENDA_PLUGIN_DIR . 'class/class-acs-help.php';

        // Initialize admin pages
        if (is_admin()) {
            ACSAGMA_Admin::get_instance();
            ACSAGMA_Options::get_instance();
            ACSAGMA_Help::get_instance();
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
        add_action('wp_ajax_acsagma_read_more', [$this, 'ajax_read_more']);
        add_action('wp_ajax_nopriv_acsagma_read_more', [$this, 'ajax_read_more']);
        add_action('wp_ajax_acsagma_contact_form_submit', [$this, 'ajax_contact_form_submit']);
        add_action('wp_ajax_nopriv_acsagma_contact_form_submit', [$this, 'ajax_contact_form_submit']);

        // Shortcode
        add_shortcode('acsagma_agenda', [$this, 'render_agenda_shortcode']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(bool $force = false): void {
        if (!$force && !$this->should_load_frontend_assets()) {
            return;
        }

        $contact_form_enabled = (bool) get_option('acsagma_contact_form_enabled', true);

        // Common styles (variables, buttons, spinner)
        wp_enqueue_style(
            'acs-agenda-common',
            ACSAGMA_AGENDA_PLUGIN_URL . 'css/acs-common.css',
            [],
            ACSAGMA_AGENDA_VERSION
        );

        // Frontend styles
        wp_enqueue_style(
            'acs-agenda-style',
            ACSAGMA_AGENDA_PLUGIN_URL . 'css/acs.css',
            ['acs-agenda-common'],
            ACSAGMA_AGENDA_VERSION
        );

        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-datepicker');

        wp_enqueue_script(
            'acs-agenda-frontend',
            ACSAGMA_AGENDA_PLUGIN_URL . 'js/acs-frontend.js',
            ['jquery', 'jquery-ui-dialog'],
            ACSAGMA_AGENDA_VERSION,
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );

        wp_localize_script('acs-agenda-frontend', 'acsagmaAgenda', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acsagma_agenda_nonce'),
            'contactFormNonce' => $contact_form_enabled ? wp_create_nonce('acsagma_contact_form_nonce') : '',
            'contactFormEnabled' => $contact_form_enabled,
            'fallbackImage' => ACSAGMA_AGENDA_PLUGIN_URL . 'css/images/Accept-icon.png',
            'i18n' => [
                'readMoreError' => __('Unable to load details. Please try again.', 'acs-agenda-manager'),
                'noResults' => __('No events match your filters.', 'acs-agenda-manager'),
                /* translators: %1$d is the first item number, %2$d the last item number, %3$d the total number of events. */
                'resultsLabel' => __('Showing %1$d-%2$d of %3$d events', 'acs-agenda-manager'),
                'compactOn' => __('Compact mode on', 'acs-agenda-manager'),
                'compactOff' => __('Compact mode off', 'acs-agenda-manager'),
                'prev' => __('Previous', 'acs-agenda-manager'),
                'next' => __('Next', 'acs-agenda-manager'),
                'contactFormSuccess' => $contact_form_enabled ? __('Thanks! Your message has been sent.', 'acs-agenda-manager') : '',
                'contactFormError' => $contact_form_enabled ? __('Unable to send your message. Please try again.', 'acs-agenda-manager') : '',
                'contactFormSending' => $contact_form_enabled ? __('Sending...', 'acs-agenda-manager') : '',
                'contactFormRequired' => $contact_form_enabled ? __('Please fill in all required fields.', 'acs-agenda-manager') : '',
                'contactFormNameRequired' => $contact_form_enabled ? __('Please enter your name.', 'acs-agenda-manager') : '',
                'contactFormEmailRequired' => $contact_form_enabled ? __('Please enter your email address.', 'acs-agenda-manager') : '',
                'contactFormInvalidEmail' => $contact_form_enabled ? __('Please enter a valid email address.', 'acs-agenda-manager') : '',
                'contactFormMessageRequired' => $contact_form_enabled ? __('Please enter your message.', 'acs-agenda-manager') : '',
                'contactFormSubmitLabel' => $contact_form_enabled ? __('Send message', 'acs-agenda-manager') : '',
            ],
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(string $hook): void {
        if (strpos($hook, 'agenda') === false) {
            return;
        }

        // Common styles (variables, buttons, spinner)
        wp_enqueue_style(
            'acs-agenda-common',
            ACSAGMA_AGENDA_PLUGIN_URL . 'css/acs-common.css',
            [],
            ACSAGMA_AGENDA_VERSION
        );

        // Admin-specific styles
        wp_enqueue_style(
            'acs-agenda-admin-style',
            ACSAGMA_AGENDA_PLUGIN_URL . 'css/acs-admin.css',
            ['acs-agenda-common', 'wp-jquery-ui-dialog'],
            ACSAGMA_AGENDA_VERSION
        );

        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-datepicker');

        wp_enqueue_media();
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');

        // Load Google Maps API if key is configured (only if supplied via option)
        $google_maps_api_key = get_option('acsagma_google_maps_api_key', '');
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
                ACSAGMA_AGENDA_VERSION,
                [
                    'in_footer' => true,
                    'strategy' => 'defer',
                ]
            );
        }

        wp_enqueue_script(
            'acs-agenda-admin',
            ACSAGMA_AGENDA_PLUGIN_URL . 'js/acs-admin.js',
            ['jquery', 'jquery-ui-dialog', 'jquery-ui-datepicker'],
            ACSAGMA_AGENDA_VERSION,
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );

        wp_localize_script('acs-agenda-admin', 'acsagmaAgendaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acsagma_agenda_admin_nonce'),
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
            if ($post && has_shortcode($post->post_content, 'acsagma_agenda')) {
                $should_load = true;
            }

            $template = get_page_template_slug($post);
            if ($template === 'page-agenda.php') {
                $should_load = true;
            }
        }

        return apply_filters('acsagma_agenda_should_enqueue_assets', $should_load);
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
            'invalidDate' => __('Invalid date format. Use dd/mm/yy', 'acs-agenda-manager'),
            'pastDateUnavailable' => __('Past date unavailable', 'acs-agenda-manager'),
            'noDatesSelected' => __('No dates selected yet', 'acs-agenda-manager'),
            'removeDate' => __('Remove date', 'acs-agenda-manager'),
            'requestFailed' => __('Request failed', 'acs-agenda-manager'),
            'select' => __('Select', 'acs-agenda-manager'),
            'noImageSelected' => __('No image selected', 'acs-agenda-manager'),
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
        $installed_version = get_option('acsagma_agenda_manager_plugin_version', '0');

        if (version_compare($installed_version, ACSAGMA_AGENDA_VERSION, '<')) {
            ACSAGMA_Database::update_schema();
            update_option('acsagma_agenda_manager_plugin_version', ACSAGMA_AGENDA_VERSION);
        }
    }

    /**
     * Render agenda shortcode
     */
    public function render_agenda_shortcode($atts = [], ?string $content = null): string {
        $events = ACSAGMA_Event::get_upcoming_events();

        if (empty($events)) {
            return '<p>' . esc_html__('No upcoming events.', 'acs-agenda-manager') . '</p>';
        }

        return ACSAGMA_Template::render_agenda($events);
    }

    /**
     * AJAX handler for read more dialog
     */
    public function ajax_read_more(): void {
        check_ajax_referer('acsagma_agenda_nonce', 'nonce', true);

        $post_id = isset($_POST['postid']) ? absint($_POST['postid']) : 0;
        $href = isset($_POST['href']) ? esc_url_raw(wp_unslash($_POST['href'])) : '';
        $event_title = isset($_POST['event_title']) ? sanitize_text_field(wp_unslash($_POST['event_title'])) : '';
        $event_dates = isset($_POST['event_dates']) ? sanitize_text_field(wp_unslash($_POST['event_dates'])) : '';
        $event_intro = isset($_POST['event_intro']) ? sanitize_text_field(wp_unslash($_POST['event_intro'])) : '';
        $dialog_mode = isset($_POST['dialog_mode']) ? sanitize_key(wp_unslash($_POST['dialog_mode'])) : 'readmore';

        $post = null;
        if ($post_id > 0) {
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(__('Post not found', 'acs-agenda-manager'));
            }
        }

        $response = ACSAGMA_Template::render_read_more_dialog($post, $href, [
            'title' => $event_title,
            'dates' => $event_dates,
            'intro' => $event_intro,
            'mode' => $dialog_mode,
            'post_id' => $post_id,
            'href' => $href,
        ]);

        echo wp_kses($response, ACSAGMA_Template::get_allowed_read_more_html());
        wp_die();
    }

    /**
     * AJAX handler for contact form submission.
     */
    public function ajax_contact_form_submit(): void {
        check_ajax_referer('acsagma_contact_form_nonce', 'nonce', true);

        $contact_form_enabled = (bool) get_option('acsagma_contact_form_enabled', true);
        if (!$contact_form_enabled) {
            wp_send_json_error(['message' => __('Unable to send your message. Please try again.', 'acs-agenda-manager')]);
        }

        $honeypot = isset($_POST['acsagma_contact_company']) ? sanitize_text_field(wp_unslash($_POST['acsagma_contact_company'])) : '';
        if ($honeypot !== '') {
            wp_send_json_error(['message' => __('Unable to send your message. Please try again.', 'acs-agenda-manager')]);
        }

        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        $event_title = isset($_POST['event_title']) ? sanitize_text_field(wp_unslash($_POST['event_title'])) : '';
        $event_dates = isset($_POST['event_dates']) ? sanitize_text_field(wp_unslash($_POST['event_dates'])) : '';
        $event_href = isset($_POST['event_href']) ? esc_url_raw(wp_unslash($_POST['event_href'])) : '';

        if ($name === '') {
            wp_send_json_error(['message' => __('Please enter your name.', 'acs-agenda-manager')]);
        }

        if ($email === '') {
            wp_send_json_error(['message' => __('Please enter your email address.', 'acs-agenda-manager')]);
        }

        if (!$this->is_valid_contact_email($email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'acs-agenda-manager')]);
        }

        if ($message === '') {
            wp_send_json_error(['message' => __('Please enter your message.', 'acs-agenda-manager')]);
        }

        $recipient_setting = sanitize_text_field((string) get_option('acsagma_contact_form_recipient_email', ''));
        $recipient_candidates = preg_split('/[;,\s]+/', $recipient_setting) ?: [];
        $recipient_emails = array_values(array_filter(array_map('sanitize_email', $recipient_candidates), 'is_email'));
        $default_recipient = sanitize_email((string) get_option('admin_email'));

        if (empty($recipient_emails) && $default_recipient !== '') {
            $recipient_emails = [$default_recipient];
        }

        if (empty($recipient_emails)) {
            wp_send_json_error(['message' => __('Unable to send your message. Please try again.', 'acs-agenda-manager')]);
        }

        $subject_prefix = sanitize_text_field((string) get_option('acsagma_contact_form_subject_prefix', 'ACS Agenda'));
        $include_dates = (bool) get_option('acsagma_contact_form_include_dates', true);
        $show_phone = (bool) get_option('acsagma_contact_form_show_phone', false);

        $subject_parts = [];
        if ($subject_prefix !== '') {
            $subject_parts[] = $subject_prefix;
        }
        if ($event_title !== '') {
            $subject_parts[] = $event_title;
        }
        if ($include_dates && $event_dates !== '') {
            $subject_parts[] = $event_dates;
        }

        $subject = implode(' - ', $subject_parts);
        if ($subject === '') {
            $subject = __('Contact request', 'acs-agenda-manager');
        }

        $body = [];
        $body[] = sprintf('%s: %s', __('Name', 'acs-agenda-manager'), $name);
        $body[] = sprintf('%s: %s', __('Email', 'acs-agenda-manager'), $email);
        if ($show_phone && $phone !== '') {
            $body[] = sprintf('%s: %s', __('Phone', 'acs-agenda-manager'), $phone);
        }
        if ($event_title !== '') {
            $body[] = sprintf('%s: %s', __('Event', 'acs-agenda-manager'), $event_title);
        }
        if ($event_dates !== '') {
            $body[] = sprintf('%s: %s', __('Dates', 'acs-agenda-manager'), $event_dates);
        }
        if ($event_href !== '') {
            $body[] = sprintf('%s: %s', __('Event page', 'acs-agenda-manager'), $event_href);
        }
        $body[] = '';
        $body[] = __('Message', 'acs-agenda-manager') . ':';
        $body[] = $message;

        $headers = [
            sprintf('Reply-To: %s <%s>', $name, $email),
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $sent = wp_mail($recipient_emails, $subject, implode("\n", $body), $headers);

        if (!$sent) {
            wp_send_json_error(['message' => __('Unable to send your message. Please try again.', 'acs-agenda-manager')]);
        }

        wp_send_json_success([
            'message' => __('Thanks! Your message has been sent.', 'acs-agenda-manager'),
        ]);
        wp_die();
    }

    /**
     * Stricter email validation for contact form submissions.
     *
     * Requires a domain with at least one dot and a TLD of at least 2 chars.
     */
    private function is_valid_contact_email(string $email): bool {
        if (!is_email($email)) {
            return false;
        }

        $at_pos = strrpos($email, '@');
        if ($at_pos === false) {
            return false;
        }

        $domain = substr($email, $at_pos + 1);
        if ($domain === '' || strpos($domain, '.') === false) {
            return false;
        }

        if (!preg_match('/^(?:[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?\.)+(?:[A-Z]{2,63}|XN--(?:[A-Z0-9]|[A-Z0-9][A-Z0-9-]{0,57}[A-Z0-9]))$/i', $domain)) {
            return false;
        }

        $tld = (string) strrchr($domain, '.');
        if ($tld === '') {
            return false;
        }

        return strlen(ltrim($tld, '.')) >= 2;
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
        ACSAGMA_Database::create_table();

        // Install bundled translations to WordPress languages directory
        self::install_translations();

        $page_name = get_option('acsagma_page', 'Agenda');
        $existing_page = self::get_page_by_title($page_name);

        if (!$existing_page) {
            wp_insert_post([
                'post_title' => $page_name,
                'post_name' => sanitize_title($page_name),
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[acsagma_agenda]',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }

        update_option('acsagma_page', $page_name);
        update_option('acsagma_agenda_manager_plugin_version', ACSAGMA_AGENDA_VERSION);
    }

    /**
     * Install bundled translations to WordPress languages directory.
     * This allows WordPress to auto-load translations without load_plugin_textdomain().
     */
    private static function install_translations(): void {
        $source_dir = plugin_dir_path(__FILE__) . 'lang/';
        $target_dir = WP_LANG_DIR . '/plugins/';

        // Create target directory if it doesn't exist
        if (!is_dir($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        // Get all .mo files from bundled translations
        $mo_files = glob($source_dir . '*.mo');

        if (!$mo_files) {
            return;
        }

        foreach ($mo_files as $mo_file) {
            $filename = basename($mo_file);
            $target_file = $target_dir . $filename;

            // Only copy if source is newer or target doesn't exist
            if (!file_exists($target_file) || filemtime($mo_file) > filemtime($target_file)) {
                copy($mo_file, $target_file);
            }
        }
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate(): void {
        // Clean up transients if needed
        delete_transient('acsagma_agenda_events_cache');
    }
}

// Activation/deactivation hooks
register_activation_hook(__FILE__, ['ACSAGMA_Agenda_Manager', 'activate']);
register_deactivation_hook(__FILE__, ['ACSAGMA_Agenda_Manager', 'deactivate']);

// Initialize plugin
add_action('plugins_loaded', function() {
    ACSAGMA_Agenda_Manager::get_instance();
});
