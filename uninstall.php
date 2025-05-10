<?php

if (!defined('WP_UNINSTALL_PLUGIN') || !WP_UNINSTALL_PLUGIN || dirname(WP_UNINSTALL_PLUGIN) != dirname(plugin_basename(__FILE__))) {

	status_header( 404 );

	exit;

} else {

	global $wpdb;

	// Drop the events table if it exists (not used since version 0.7)
	$result = $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."tdih_events;");

	// Remove the custom taxonomy terms
	$terms = $wpdb->get_results("SELECT term_taxonomy_id, term_id FROM ".$wpdb->prefix."term_taxonomy WHERE taxonomy='event_type'");

	if (count($terms) > 0) {
		foreach ($terms as $term) {
			$result = $wpdb->query("DELETE FROM ".$wpdb->prefix."terms WHERE term_id=".$term->term_id);

			$result = $wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE term_taxonomy_id=".$term->term_taxonomy_id);

			$result = $wpdb->query("DELETE FROM ".$wpdb->prefix."term_taxonomy WHERE term_taxonomy_id=".$term->term_taxonomy_id);
		}
	}

	// Remove the event posts
	$result = $wpdb->query("DELETE FROM ".$wpdb->prefix."posts WHERE post_type='tdih_event'");

	// Delete the db version
	delete_option("tdih_db_version");

	// Delete the options
	delete_option("tdih_options");

	// Remove the capability
	$role = get_role('administrator');

	if($role->has_cap('manage_tdih_events')) { $role->remove_cap('manage_tdih_events'); }

}

?>