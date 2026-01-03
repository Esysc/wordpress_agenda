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
function acsagma_uninstall_cleanup() {
    // Check if user wants to delete all data
    $acsagma_delete_all_data = get_option('acsagma_delete_data_on_uninstall', false);

    // Always remove installed translation files (they're just copies)
    $acsagma_lang_dir = WP_LANG_DIR . '/plugins/';
    $acsagma_translation_files = glob($acsagma_lang_dir . 'acs-agenda-manager-*.mo');

    if ($acsagma_translation_files) {
        foreach ($acsagma_translation_files as $acsagma_file) {
            if (is_file($acsagma_file)) {
                wp_delete_file($acsagma_file);
            }
        }
    }

    // Also remove .po files if they were copied
    $acsagma_po_files = glob($acsagma_lang_dir . 'acs-agenda-manager-*.po');

    if ($acsagma_po_files) {
        foreach ($acsagma_po_files as $acsagma_file) {
            if (is_file($acsagma_file)) {
                wp_delete_file($acsagma_file);
            }
        }
    }

    // If user opted to delete all data
    if ($acsagma_delete_all_data) {
        // Remove the database table
        global $wpdb;
        $acsagma_table_name = $wpdb->prefix . 'acs_agenda_manager';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', $acsagma_table_name));

        // Remove the Agenda page
        $acsagma_page_name = get_option('acsagma_page', 'Agenda');
        $acsagma_page = get_page_by_path(sanitize_title($acsagma_page_name));
        if ($acsagma_page) {
            wp_delete_post($acsagma_page->ID, true);
        }
    }

    // Always remove options (small footprint)
    delete_option('acsagma_page');
    delete_option('acsagma_google_maps_api_key');
    delete_option('acsagma_agenda_manager_plugin_version');
    delete_option('acsagma_delete_data_on_uninstall');

    // Remove transients
    delete_transient('acsagma_agenda_events_cache');
}

// Run the cleanup
acsagma_uninstall_cleanup();
