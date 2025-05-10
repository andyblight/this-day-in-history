<?php

class tdih_init {

	public function __construct($case = false) {

		switch($case) {
			case 'activate' :
				$this->tdih_activate();
			break;

			case 'deactivate' :
				$this->tdih_deactivate();
			break;

			default:
				wp_die('Invalid Access');
			break;
		}
	}

	public static function on_activate() {
		new tdih_init('activate');
	}

	public static function on_deactivate() {
		new tdih_init('deactivate');
	}

	private static function tdih_activate() {
		global $wpdb;

		add_option('tdih_options', array('date_format' => 'YYYY-MM-DD', 'era_mark' => 1, 'no_events' =>__('No Events', 'this-day-in-history'), 'exclude_search' => 1));

		add_option('tdih_db_version', TDIH_DB_VERSION);

		$role = get_role('administrator');

		if(!$role->has_cap('manage_tdih_events')) { $role->add_cap('manage_tdih_events'); }
	}

	private static function tdih_deactivate() {
		// do nowt
	}
}

?>