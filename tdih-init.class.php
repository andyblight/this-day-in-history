<?php

class tdih_init
{

	// Prevent direct instantiation; use static methods
	private function __construct()
	{
	}

	// Called by register_activation_hook
	public static function on_activate()
	{
		self::tdih_activate();
	}

	// Called by register_deactivation_hook
	public static function on_deactivate()
	{
		self::tdih_deactivate();
	}

	private static function tdih_activate()
	{
		global $wpdb;

		// Avoid using translation functions during activation (textdomain may not be loaded)
		$defaults = array(
			'date_format' => 'YYYY-MM-DD',
			'era_mark' => 1,
			'no_events' => 'No Events',
			'exclude_search' => 1,
		);

		// Add options only if they don't exist
		if (false === get_option('tdih_options', false)) {
			add_option('tdih_options', $defaults);
		}

		if (defined('TDIH_DB_VERSION')) {
			add_option('tdih_db_version', TDIH_DB_VERSION);
		} else {
			add_option('tdih_db_version', 1);
		}

		// Grant capability to administrator role (if role exists)
		$role = get_role('administrator');
		if ($role && !$role->has_cap('manage_tdih_events')) {
			$role->add_cap('manage_tdih_events');
		}
	}

	private static function tdih_deactivate()
	{
		// Intentionally left blank. Avoid removing capabilities on deactivate to
		// prevent accidental privilege changes on multi-site/admins.
	}
}

?>
