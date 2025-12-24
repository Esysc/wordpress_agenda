<?php
/**
 * Database operations for ACS Agenda Manager
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;

/**
 * Handles all database operations
 */
class ACS_Database {

    /**
     * Get full table name with prefix
     */
    public static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . ACS_AGENDA_TABLE_NAME;
    }

    /**
     * Create the plugin database table
     */
    public static function create_table(): bool {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            categorie VARCHAR(120) NOT NULL,
            title VARCHAR(120) NOT NULL,
            emplacement VARCHAR(120) NOT NULL,
            image VARCHAR(255) NOT NULL,
            intro TEXT NOT NULL,
            link VARCHAR(255) NOT NULL,
            date VARCHAR(255) NOT NULL,
            price VARCHAR(60) DEFAULT NULL,
            account TINYINT(1) DEFAULT 1,
            candopartial TINYINT(1) DEFAULT 0,
            redirect VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_date (date),
            INDEX idx_categorie (categorie)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        return empty($wpdb->last_error);
    }

    /**
     * Update database schema for plugin updates
     */
    public static function update_schema(): bool {
        global $wpdb;

        $table_name = self::get_table_name();

        // Check if table exists
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
        );

        if (!$table_exists) {
            return self::create_table();
        }

        // Get existing columns
        $existing_columns = $wpdb->get_col("DESC {$table_name}", 0);

        $required_columns = [
            'redirect' => 'VARCHAR(255) DEFAULT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ];

        $alterations = [];

        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns, true)) {
                $alterations[] = "ADD COLUMN {$column} {$definition}";
            }
        }

        if (!empty($alterations)) {
            $sql = "ALTER TABLE {$table_name} " . implode(', ', $alterations);
            $wpdb->query($sql);
        }

        return empty($wpdb->last_error);
    }

    /**
     * Get all events with optional filters
     */
    public static function get_events(array $args = []): array {
        global $wpdb;

        $defaults = [
            'per_page' => 10,
            'page' => 1,
            'orderby' => 'id',
            'order' => 'DESC',
            'search' => '',
            'filter' => '',
        ];

        $args = wp_parse_args($args, $defaults);
        $table_name = self::get_table_name();

        $sql = "SELECT * FROM {$table_name} WHERE 1=1";
        $params = [];

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= " AND (categorie LIKE %s OR title LIKE %s OR intro LIKE %s OR date LIKE %s)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (!empty($args['filter'])) {
            $filter_parts = explode('-', $args['filter']);
            if (!empty($filter_parts[0])) {
                $sql .= " AND title LIKE %s";
                $params[] = '%' . $wpdb->esc_like($filter_parts[0]) . '%';
            }
        }

        $allowed_orderby = ['id', 'categorie', 'title', 'date', 'price', 'created_at', 'updated_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'id';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY {$orderby} {$order}";
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['per_page'], ($args['page'] - 1) * $args['per_page']);

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Count total events
     */
    public static function count_events(array $args = []): int {
        global $wpdb;

        $table_name = self::get_table_name();
        $sql = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1";
        $params = [];

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= " AND (categorie LIKE %s OR title LIKE %s OR intro LIKE %s)";
            $params = [$search, $search, $search];
        }

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get a single event by ID
     */
    public static function get_event(int $id): ?array {
        global $wpdb;

        $table_name = self::get_table_name();
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id),
            ARRAY_A
        );

        return $result ?: null;
    }

    /**
     * Insert a new event
     */
    public static function insert_event(array $data): int {
        global $wpdb;

        $table_name = self::get_table_name();
        $sanitized = self::sanitize_event_data($data);

        $wpdb->insert($table_name, $sanitized);

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing event
     */
    public static function update_event(int $id, array $data): bool {
        global $wpdb;

        $table_name = self::get_table_name();
        $sanitized = self::sanitize_event_data($data);

        $result = $wpdb->update($table_name, $sanitized, ['id' => $id]);

        return $result !== false;
    }

    /**
     * Delete an event
     */
    public static function delete_event(int $id): bool {
        global $wpdb;

        $table_name = self::get_table_name();
        $result = $wpdb->delete($table_name, ['id' => $id], ['%d']);

        return $result !== false;
    }

    /**
     * Get distinct event titles for filtering
     */
    public static function get_event_filters(): array {
        global $wpdb;

        $table_name = self::get_table_name();

        return $wpdb->get_results(
            "SELECT DISTINCT title, categorie FROM {$table_name} ORDER BY title ASC",
            ARRAY_A
        ) ?: [];
    }

    /**
     * Sanitize event data before database operations
     */
    private static function sanitize_event_data(array $data): array {
        $sanitized = [];

        $text_fields = ['categorie', 'title', 'emplacement', 'image', 'link', 'date', 'price', 'redirect'];
        $textarea_fields = ['intro'];
        $boolean_fields = ['account', 'candopartial'];

        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = sanitize_text_field(wp_unslash($data[$field]));
            }
        }

        foreach ($textarea_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = sanitize_textarea_field(wp_unslash($data[$field]));
            }
        }

        foreach ($boolean_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = absint($data[$field]);
            }
        }

        return $sanitized;
    }
}
