<?php
/**
 * Admin functionality for ACS Agenda Manager
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Custom list table for agenda events
 */
class ACS_Agenda_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Event', 'acs-agenda-manager'),
            'plural' => __('Events', 'acs-agenda-manager'),
            'ajax' => false,
        ]);
    }

    public function get_columns(): array {
        return [
            'cb' => '<input type="checkbox" />',
            'categorie' => __('Category', 'acs-agenda-manager'),
            'title' => __('Title', 'acs-agenda-manager'),
            'emplacement' => __('Location', 'acs-agenda-manager'),
            'image' => __('Image', 'acs-agenda-manager'),
            'intro' => __('Description', 'acs-agenda-manager'),
            'link' => __('Page Link', 'acs-agenda-manager'),
            'date' => __('Schedule', 'acs-agenda-manager'),
            'price' => __('Price', 'acs-agenda-manager'),
            'account' => __('Advance Payment', 'acs-agenda-manager'),
            'candopartial' => __('Partial Attendance', 'acs-agenda-manager'),
            'redirect' => __('External URL', 'acs-agenda-manager'),
            'created_at' => __('Created', 'acs-agenda-manager'),
        ];
    }

    public function get_sortable_columns(): array {
        return [
            'categorie' => ['categorie', true],
            'title' => ['title', false],
            'date' => ['date', false],
            'price' => ['price', false],
            'created_at' => ['created_at', false],
        ];
    }

    public function get_bulk_actions(): array {
        return ['bulk-delete' => __('Delete', 'acs-agenda-manager')];
    }

    public function column_cb($item): string {
        return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']);
    }

    public function column_default($item, $column_name): string {
        $partial_options = [
            __('No', 'acs-agenda-manager'),
            __('Yes', 'acs-agenda-manager'),
            __('Keep until end', 'acs-agenda-manager'),
        ];

        $yes_no = [
            __('No', 'acs-agenda-manager'),
            __('Yes', 'acs-agenda-manager'),
        ];

        $name_attr = sprintf('data-name="%s"', esc_attr($column_name));
        $item_class = sprintf('origItem_%d', $item['id']);

        switch ($column_name) {
            case 'candopartial':
                $value = isset($partial_options[$item[$column_name]]) ? $partial_options[$item[$column_name]] : '';
                return sprintf('<span class="%s" %s>%s</span>', esc_attr($item_class), $name_attr, esc_html($value));

            case 'account':
                $value = isset($yes_no[$item[$column_name]]) ? $yes_no[$item[$column_name]] : '';
                return sprintf('<span class="%s" %s>%s</span>', esc_attr($item_class), $name_attr, esc_html($value));

            case 'intro':
                return sprintf(
                    '<a class="read_more button4 info">%s</a>
                    <a class="button4 info hide_more" style="display:none">%s</a>
                    <br><span class="fullcontent %s" style="display:none" %s>%s</span>',
                    esc_html__('Read more', 'acs-agenda-manager'),
                    esc_html__('Hide', 'acs-agenda-manager'),
                    esc_attr($item_class),
                    $name_attr,
                    esc_html($item[$column_name])
                );

            case 'image':
                return sprintf(
                    '<span style="display:none;" class="%s" %s>%s</span>
                    <img src="%s" style="width: 100%%; height: auto;" alt="" />',
                    esc_attr($item_class),
                    $name_attr,
                    esc_attr($item[$column_name]),
                    esc_url($item[$column_name])
                );

            case 'link':
                return sprintf(
                    '<span style="max-width:100%%" class="%s" %s>%s</span>
                    <a href="%s" target="_blank" class="button4 info">%s</a>',
                    esc_attr($item_class),
                    $name_attr,
                    esc_html($item[$column_name]),
                    esc_url($item[$column_name]),
                    esc_html__('Open page', 'acs-agenda-manager')
                );

            default:
                return sprintf(
                    '<span style="max-width:100%%" class="%s" %s>%s</span>',
                    esc_attr($item_class),
                    $name_attr,
                    esc_html($item[$column_name] ?? '')
                );
        }
    }

    public function column_categorie($item): string {
        $nonce = wp_create_nonce('acs_delete_event');
        $item_class = sprintf('origItem_%d', $item['id']);

        $name = sprintf(
            '<strong id="categorie%d" class="%s" data-name="categorie">%s</strong>',
            $item['id'],
            esc_attr($item_class),
            esc_html($item['categorie'])
        );

        $delete_url = wp_nonce_url(
            add_query_arg(
                [
                    'page' => 'agenda',
                    'action' => 'delete',
                    'id' => $item['id'],
                ],
                admin_url('admin.php')
            ),
            'acs_delete_event'
        );

        $actions = [
            'edit' => sprintf(
                '<a href="#" data-id="%d" class="editItems button4 info">%s</a>',
                $item['id'],
                esc_html__('Edit', 'acs-agenda-manager')
            ),
            'delete' => sprintf(
                '<a class="ACSdelete button4 danger" href="%s">%s</a>',
                esc_url($delete_url),
                esc_html__('Delete', 'acs-agenda-manager')
            ),
        ];

        // Add shortcode button if contact form exists
        if (shortcode_exists('ACScontactform')) {
            $actions['shortcode'] = $this->render_shortcode_button($item);
        }

        return $name . str_replace('|', '', $this->row_actions($actions));
    }

    private function render_shortcode_button(array $item): string {
        $shortcode = sprintf(
            '[ACScontactform dates="%s" subject="%s" price="%s" account="%s" candopartial="%s" redirect="%s"]',
            esc_attr($item['date']),
            esc_attr($item['title']),
            esc_attr($item['price']),
            esc_attr($item['account']),
            esc_attr($item['candopartial']),
            esc_attr($item['redirect'] ?? '')
        );

        $dialog_id = 'shortcode' . $item['id'];
        $textarea_id = 'shortcodeText' . $item['id'];

        $dialog = sprintf(
            '<div id="%s" style="display:none">
                <h2>%s</h2>
                <div class="alert alert-success" id="MSGWrapper%s" style="display:none">
                    <p id="ACSmessage%s"></p>
                </div>
                <p><textarea id="%s" disabled>%s</textarea></p>
                <p><button class="button4 info" onclick="copyShortcode(\'%s\')">%s</button></p>
            </div>',
            esc_attr($dialog_id),
            esc_html__('Contact Form Shortcode', 'acs-agenda-manager'),
            esc_attr($textarea_id),
            esc_attr($textarea_id),
            esc_attr($textarea_id),
            esc_attr($shortcode),
            esc_attr($textarea_id),
            esc_html__('Copy!', 'acs-agenda-manager')
        );

        return $dialog . sprintf(
            '<a href="#TB_inline?width=400&height=250&inlineId=%s" class="thickbox button4 info">%s</a>',
            esc_attr($dialog_id),
            esc_html__('Form shortcode', 'acs-agenda-manager')
        );
    }

    public function prepare_items(): void {
        $this->_column_headers = $this->get_column_info();
        $this->process_bulk_action();

        $per_page = $this->get_items_per_page('events_per_page', 10);
        $current_page = $this->get_pagenum();

        // List table filtering/sorting/pagination uses WP's built-in list table nonce handling.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $args = [
            'per_page' => $per_page,
            'page' => $current_page,
            'orderby' => sanitize_text_field(wp_unslash($_REQUEST['orderby'] ?? 'id')),
            'order' => sanitize_text_field(wp_unslash($_REQUEST['order'] ?? 'DESC')),
            'search' => sanitize_text_field(wp_unslash($_REQUEST['s'] ?? '')),
            'filter' => sanitize_text_field(wp_unslash($_REQUEST['event-filter'] ?? '')),
        ];
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $this->items = ACS_Database::get_events($args);
        $total_items = ACS_Database::count_events($args);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);
    }

    public function process_bulk_action(): void {
        // Delete and bulk actions are now handled in handle_actions()
        // which runs on admin_init before any output is sent.
    }

    public function no_items(): void {
        esc_html_e('No events found.', 'acs-agenda-manager');
    }

    public function extra_tablenav($which): void {
        if ($which !== 'top') {
            return;
        }

        $filters = ACS_Database::get_event_filters();

        if (empty($filters)) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filter dropdown uses WP's built-in list table form with nonce.
        $current_filter = sanitize_text_field(wp_unslash($_REQUEST['event-filter'] ?? ''));

        echo '<div class="alignleft actions">';
        echo '<select name="event-filter" class="ewc-filter-event">';
        echo '<option value="">' . esc_html__('All Events', 'acs-agenda-manager') . '</option>';

        foreach ($filters as $filter) {
            $value = $filter['title'] . '-' . $filter['categorie'];
            $selected_attr = selected($current_filter, $value, false);
            echo '<option value="' . esc_attr($value) . '"' . esc_attr($selected_attr) . '>' . esc_html($filter['title']) . ' - ' . esc_html($filter['categorie']) . '</option>';
        }

        echo '</select>';
        echo '<input type="submit" class="button" value="' . esc_attr__('Filter', 'acs-agenda-manager') . '" />';
        echo '</div>';
    }
}

/**
 * Admin page controller
 */
class ACS_Admin {

    /** @var self|null */
    private static $instance = null;

    /** @var ACS_Agenda_List_Table */
    private $list_table;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_filter('set-screen-option', [$this, 'set_screen_option'], 10, 3);
        add_action('admin_init', [$this, 'handle_actions']);

        // AJAX handlers
        add_action('wp_ajax_update_agenda', [$this, 'ajax_update_event']);
        add_action('wp_ajax_add_item_agenda', [$this, 'ajax_add_event']);
    }

    /**
     * Handle delete and bulk actions early, before any output is sent.
     */
    public function handle_actions(): void {
        // Only process on our admin page
        $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($current_page !== 'agenda') {
            return;
        }

        $redirect_url = admin_url('admin.php?page=agenda');

        if ((isset($_POST['action']) && $_POST['action'] !== '') || (isset($_POST['action2']) && $_POST['action2'] !== '')) {
            check_admin_referer('acs_agenda_admin_form', 'acs_agenda_admin_form_nonce');
        }

        // Handle single delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete') {
            $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';

            if (!wp_verify_nonce($nonce, 'acs_delete_event')) {
                wp_die(esc_html__('Security check failed', 'acs-agenda-manager'));
            }

            $id = absint(isset($_GET['id']) ? wp_unslash($_GET['id']) : 0);
            if ($id) {
                ACS_Database::delete_event($id);
                wp_safe_redirect($redirect_url . '&deleted=1');
                exit;
            }
        }

        // Handle bulk delete action
        if ((isset($_POST['action']) && $_POST['action'] === 'bulk-delete') ||
            (isset($_POST['action2']) && $_POST['action2'] === 'bulk-delete')) {

            if (!check_admin_referer('acs_agenda_admin_form', 'acs_agenda_admin_form_nonce')) {
                wp_die(esc_html__('Security check failed', 'acs-agenda-manager'));
            }

            $ids = array_map('absint', wp_unslash($_POST['bulk-delete'] ?? []));

            foreach ($ids as $id) {
                ACS_Database::delete_event($id);
            }

            wp_safe_redirect($redirect_url . '&deleted=' . count($ids));
            exit;
        }
    }

    public function add_menu_page(): void {
        $hook = add_menu_page(
            __('Agenda', 'acs-agenda-manager'),
            __('Agenda', 'acs-agenda-manager'),
            'manage_options',
            'agenda',
            [$this, 'render_admin_page'],
            'dashicons-calendar-alt'
        );

        add_action("load-$hook", [$this, 'screen_options']);
    }

    public function screen_options(): void {
        add_screen_option('per_page', [
            'label' => __('Events per page', 'acs-agenda-manager'),
            'default' => 10,
            'option' => 'events_per_page',
        ]);

        $this->list_table = new ACS_Agenda_List_Table();
    }

    public function set_screen_option($status, $option, $value) {
        return $value;
    }

    public function render_admin_page(): void {
        $agenda_page = ACS_Agenda_Manager::get_page_by_title(get_option('acsagendapage', 'Agenda'));
        $agenda_url = $agenda_page ? get_permalink($agenda_page) : '#';

        include ACS_AGENDA_PLUGIN_DIR . 'templates/admin-page.php';
    }

    public function ajax_update_event(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'acs_agenda_admin_nonce')) {
            wp_send_json_error(esc_html__('Security check failed', 'acs-agenda-manager'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Permission denied', 'acs-agenda-manager'));
        }

        $id = absint(wp_unslash($_POST['id'] ?? 0));

        if (!$id) {
            wp_send_json_error(esc_html__('Invalid event ID', 'acs-agenda-manager'));
        }

        $data = $this->get_event_data_from_post();
        $success = ACS_Database::update_event($id, $data);

        if ($success) {
            wp_send_json_success(esc_html__('Event updated successfully', 'acs-agenda-manager'));
        } else {
            wp_send_json_error(esc_html__('Failed to update event', 'acs-agenda-manager'));
        }
    }

    public function ajax_add_event(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'acs_agenda_admin_nonce')) {
            wp_send_json_error(esc_html__('Security check failed', 'acs-agenda-manager'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Permission denied', 'acs-agenda-manager'));
        }

        $data = $this->get_event_data_from_post();
        $id = ACS_Database::insert_event($data);

        if ($id) {
            wp_send_json_success(esc_html__('Event added successfully', 'acs-agenda-manager'));
        } else {
            wp_send_json_error(esc_html__('Failed to add event', 'acs-agenda-manager'));
        }
    }

    private function get_event_data_from_post(): array {
        // Nonce is verified in calling methods (handle_ajax_add_event, handle_ajax_edit_event).
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified before this method is called.
        $post_data = wp_unslash($_POST);

        return [
            'categorie' => sanitize_text_field($post_data['categorie'] ?? ''),
            'title' => sanitize_text_field($post_data['title'] ?? ''),
            'emplacement' => sanitize_text_field($post_data['emplacement'] ?? ''),
            'image' => sanitize_text_field($post_data['image'] ?? ''),
            'intro' => sanitize_textarea_field($post_data['intro'] ?? ''),
            'link' => esc_url_raw($post_data['link'] ?? ''),
            'date' => sanitize_text_field($post_data['date'] ?? ''),
            'price' => sanitize_text_field($post_data['price'] ?? ''),
            'account' => absint($post_data['account'] ?? 0),
            'candopartial' => absint($post_data['candopartial'] ?? 0),
            'redirect' => esc_url_raw($post_data['redirect'] ?? ''),
        ];
    }
}

// Initialize admin
add_action('plugins_loaded', function() {
    if (is_admin()) {
        ACS_Admin::get_instance();
    }
});
