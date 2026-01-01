<?php
/**
 * Help/Guide page for ACS Agenda Manager
 *
 * Provides integrated user documentation within the WordPress admin.
 *
 * @package ACSAgendaManager
 * @since 3.0.0
 */

defined('ABSPATH') || exit;

/**
 * Handles the help/guide admin page
 *
 * @since 3.0.0
 */
class ACSAGMA_Help {

    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return self
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
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_help_styles']);
    }

    /**
     * Enqueue help page styles
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_help_styles(string $hook): void {
        if ('acsagma-agenda_page_acsagma-help' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'acs-agenda-help',
            ACSAGMA_AGENDA_PLUGIN_URL . 'css/acs-help.css',
            [],
            ACSAGMA_AGENDA_VERSION
        );
    }

    /**
     * Add the help submenu page
     *
     * @return void
     */
    public function add_submenu_page(): void {
        add_submenu_page(
            'acsagma-agenda',
            __('User Guide', 'acs-agenda-manager'),
            __('User Guide', 'acs-agenda-manager'),
            'manage_options',
            'acsagma-help',
            [$this, 'render_help_page']
        );
    }

    /**
     * Render the help page
     *
     * @return void
     */
    public function render_help_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied', 'acs-agenda-manager'));
        }

        include ACSAGMA_AGENDA_PLUGIN_DIR . 'templates/help-page.php';
    }

    /**
     * Get table of contents
     *
     * @return array
     */
    public static function get_toc(): array {
        return [
            'getting-started' => __('Getting Started', 'acs-agenda-manager'),
            'adding-events' => __('Adding Events', 'acs-agenda-manager'),
            'managing-events' => __('Managing Events', 'acs-agenda-manager'),
            'shortcode' => __('Using the Shortcode', 'acs-agenda-manager'),
            'partial-attendance' => __('Partial Attendance', 'acs-agenda-manager'),
            'customization' => __('Customization', 'acs-agenda-manager'),
            'troubleshooting' => __('Troubleshooting', 'acs-agenda-manager'),
        ];
    }
}
