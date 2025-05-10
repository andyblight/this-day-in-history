<?php

if (!defined('WP_UNINSTALL_PLUGIN') || !WP_UNINSTALL_PLUGIN || dirname(WP_UNINSTALL_PLUGIN) != dirname(plugin_basename(__FILE__))) {
    status_header(404);
    exit;
}

global $wpdb;

// Drop the events table if it exists (not used since version 0.7)
$table_name = $wpdb->prefix . "tdih_events";
$result = $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name));
if ($wpdb->last_error) {
    error_log('Error dropping table: ' . $wpdb->last_error);
}

// Remove the custom taxonomy terms
$terms = $wpdb->get_results($wpdb->prepare("SELECT term_taxonomy_id, term_id FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy = %s", 'event_type'));

foreach ($terms as $term) {
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}terms WHERE term_id = %d", $term->term_id));
    if ($wpdb->last_error) {
        error_log('Error deleting term: ' . $wpdb->last_error);
    }

    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = %d", $term->term_taxonomy_id));
    if ($wpdb->last_error) {
        error_log('Error deleting term relationships: ' . $wpdb->last_error);
    }

    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id = %d", $term->term_taxonomy_id));
    if ($wpdb->last_error) {
        error_log('Error deleting term taxonomy: ' . $wpdb->last_error);
    }
}

// Remove the event posts
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}posts WHERE post_type = %s", 'tdih_event'));
if ($wpdb->last_error) {
    error_log('Error deleting posts: ' . $wpdb->last_error);
}

// Delete the database version option
if (!delete_option("tdih_db_version")) {
    error_log('Error deleting option: tdih_db_version');
}

// Delete the plugin options
if (!delete_option("tdih_options")) {
    error_log('Error deleting option: tdih_options');
}

// Remove the capability
$role = get_role('administrator');
if ($role && $role->has_cap('manage_tdih_events')) {
    $role->remove_cap('manage_tdih_events');
} else {
    error_log('Error removing capability: manage_tdih_events');
}

?>