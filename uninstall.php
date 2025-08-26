<?php
// Uninstall routine for This Day In History plugin.
// This file is executed only when WP_UNINSTALL_PLUGIN is defined by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop legacy table if it exists (legacy; safe to attempt)
$legacy_table = esc_sql( $wpdb->prefix . 'tdih_events' );
$wpdb->query( "DROP TABLE IF EXISTS {$legacy_table}" );

// Remove all terms in the event_type taxonomy (use WP API to keep integrity)
if ( taxonomy_exists( 'event_type' ) ) {
    $terms = get_terms( array(
        'taxonomy'   => 'event_type',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term_id ) {
            // wp_delete_term will clean up term_taxonomy and relationships
            wp_delete_term( absint( $term_id ), 'event_type' );
        }
    }
}

// Remove all tdih_event posts (use WP API to ensure metadata deletion)
$posts = get_posts( array(
    'post_type'   => 'tdih_event',
    'numberposts' => -1,
    'fields'      => 'ids',
) );

if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
    foreach ( $posts as $post_id ) {
        wp_delete_post( absint( $post_id ), true ); // force delete
    }
}

// Delete plugin options
delete_option( 'tdih_db_version' );
delete_option( 'tdih_options' );

// Remove capability from administrator role if present
$role = get_role( 'administrator' );
if ( $role && $role->has_cap( 'manage_tdih_events' ) ) {
    $role->remove_cap( 'manage_tdih_events' );
}

?>