<?php
/**
 * ACS Agenda Manager Uninstall
 *
 * Fired when the plugin is uninstalled.
 *
 * @package ACSAgendaManager
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin data on uninstall.
 */

// Check if user wants to delete all data
$delete_all_data = get_option('acsagma_delete_data_on_uninstall', false);

// Always remove installed translation files (they're just copies)
$lang_dir = WP_LANG_DIR . '/plugins/';
$translation_files = glob($lang_dir . 'acs-agenda-manager-*.mo');

if ($translation_files) {
    foreach ($translation_files as $file) {
        if (is_file($file)) {
            wp_delete_file($file);
        }
    }
}

// Also remove .po files if they were copied
$po_files = glob($lang_dir . 'acs-agenda-manager-*.po');

if ($po_files) {
    foreach ($po_files as $file) {
        if (is_file($file)) {
            wp_delete_file($file);
        }
    }
}

// If user opted to delete all data
if ($delete_all_data) {
    // Remove the database table
    global $wpdb;
    $table_name = $wpdb->prefix . 'acs_agenda_manager';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', $table_name));

    // Remove the Agenda page
    $page_name = get_option('acsagma_page', 'Agenda');
    $page = get_page_by_path(sanitize_title($page_name));
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}

// Always remove options (small footprint)
delete_option('acsagma_page');
delete_option('acsagma_google_maps_api_key');
delete_option('acsagma_agenda_manager_plugin_version');
delete_option('acsagma_delete_data_on_uninstall');

// Remove transients
delete_transient('acsagma_agenda_events_cache');
