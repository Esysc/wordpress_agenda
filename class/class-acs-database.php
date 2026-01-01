<?php
/**
 * Database operations for ACS Agenda Manager
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;

/**
 * Handles all database operations with proper caching
 */
class ACSAGMA_Database {

    /**
     * Cache group for this plugin
     */
    private const CACHE_GROUP = 'acsagma_agenda_manager';

    /**
     * Get full table name with prefix
     */
    public static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . ACSAGMA_AGENDA_TABLE_NAME;
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
        return self::create_table();
    }

    /**
     * Clear all caches for this plugin
     */
    public static function clear_cache(): void {
        wp_cache_delete('event_filters', self::CACHE_GROUP);
        wp_cache_flush_group(self::CACHE_GROUP);
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

        // Generate cache key from arguments
        $cache_key = 'events_' . md5(wp_json_encode($args));
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false !== $cached) {
            return $cached;
        }

        $table_name = self::get_table_name();

        $clauses = ['1=1'];
        $params = [];

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $clauses[] = '(categorie LIKE %s OR title LIKE %s OR intro LIKE %s OR date LIKE %s)';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (!empty($args['filter'])) {
            $filter_parts = explode('-', sanitize_text_field($args['filter']));
            if (!empty($filter_parts[0])) {
                $filter_like = '%' . $wpdb->esc_like($filter_parts[0]) . '%';
                $clauses[] = 'title LIKE %s';
                $params[] = $filter_like;
            }
        }

        $params[] = (int) $args['per_page'];
        $params[] = (int) (($args['page'] - 1) * $args['per_page']);

        $clauses_sql = implode(' AND ', $clauses);

        /*
         * Table name is safe - comes from get_table_name() which uses $wpdb->prefix + constant.
         * Clauses are hardcoded SQL fragments with %s placeholders for user input.
         * WordPress doesn't have an identifier placeholder for table names.
         * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
         * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
         */
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE {$clauses_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
                ...$params
            ),
            ARRAY_A
        ) ?: [];
        // phpcs:enable

        wp_cache_set($cache_key, $results, self::CACHE_GROUP, HOUR_IN_SECONDS);

        return $results;
    }

    /**
     * Count total events
     */
    public static function count_events(array $args = []): int {
        global $wpdb;

        // Generate cache key from search argument
        $cache_key = 'count_' . md5(wp_json_encode($args));
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false !== $cached) {
            return (int) $cached;
        }

        $table_name = self::get_table_name();
        $clauses = ['1=1'];
        $params = [];

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $clauses[] = '(categorie LIKE %s OR title LIKE %s OR intro LIKE %s)';
            $params = [$search, $search, $search];
        }

        $where_sql = implode(' AND ', $clauses);

        /*
         * Table name is safe - comes from get_table_name() which uses $wpdb->prefix + constant.
         * Where clauses are hardcoded SQL fragments with %s placeholders.
         * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
         * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
         */
        if (!empty($params)) {
            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}",
                    ...$params
                )
            );
        } else {
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}");
        }
        // phpcs:enable

        wp_cache_set($cache_key, $count, self::CACHE_GROUP, HOUR_IN_SECONDS);

        return $count;
    }

    /**
     * Get a single event by ID
     */
    public static function get_event(int $id): ?array {
        global $wpdb;

        $cache_key = 'event_' . $id;
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false !== $cached) {
            return $cached ?: null;
        }

        $table_name = self::get_table_name();

        /*
         * Table name is safe - comes from get_table_name().
         * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
         * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
         */
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        // phpcs:enable

        // Cache the result (empty array if not found, to cache negative lookups)
        wp_cache_set($cache_key, $result ?: [], self::CACHE_GROUP, HOUR_IN_SECONDS);

        return $result ?: null;
    }

    /**
     * Insert a new event
     */
    public static function insert_event(array $data): int {
        global $wpdb;

        $table_name = self::get_table_name();
        $sanitized = self::sanitize_event_data($data);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom plugin table.
        $wpdb->insert($table_name, $sanitized);

        $insert_id = (int) $wpdb->insert_id;

        if ($insert_id) {
            self::clear_cache();
        }

        return $insert_id;
    }

    /**
     * Update an existing event
     */
    public static function update_event(int $id, array $data): bool {
        global $wpdb;

        $table_name = self::get_table_name();
        $sanitized = self::sanitize_event_data($data);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, cache cleared after.
        $result = $wpdb->update($table_name, $sanitized, ['id' => $id]);

        if (false !== $result) {
            self::clear_cache();
        }

        return $result !== false;
    }

    /**
     * Delete an event
     */
    public static function delete_event(int $id): bool {
        global $wpdb;

        $table_name = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, cache cleared after.
        $result = $wpdb->delete($table_name, ['id' => $id], ['%d']);

        if (false !== $result) {
            self::clear_cache();
        }

        return $result !== false;
    }

    /**
     * Get distinct event titles for filtering
     */
    public static function get_event_filters(): array {
        global $wpdb;

        $cache_key = 'event_filters';
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false !== $cached) {
            return $cached;
        }

        $table_name = self::get_table_name();

        /*
         * Table name is safe - comes from get_table_name().
         * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
         * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
         * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
         */
        $results = $wpdb->get_results(
            "SELECT DISTINCT title, categorie FROM {$table_name} ORDER BY title ASC",
            ARRAY_A
        ) ?: [];
        // phpcs:enable

        wp_cache_set($cache_key, $results, self::CACHE_GROUP, HOUR_IN_SECONDS);

        return $results;
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
