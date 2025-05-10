<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class tdih_init
{

    public function __construct($case = false)
    {
        switch ($case) {
            case 'activate':
                $this->tdih_activate();
                break;

            case 'deactivate':
                $this->tdih_deactivate();
                break;

            default:
                wp_die(esc_html__('Invalid Access', 'this-day-in-history'));
                break;
        }
    }

    public static function on_activate()
    {
        if (!current_user_can('activate_plugins')) {
            wp_die(esc_html__('You do not have sufficient permissions to activate plugins.', 'this-day-in-history'));
        }

        new tdih_init('activate');
    }

    public static function on_deactivate()
    {
        if (!current_user_can('activate_plugins')) {
            wp_die(esc_html__('You do not have sufficient permissions to deactivate plugins.', 'this-day-in-history'));
        }

        new tdih_init('deactivate');
    }

    private function tdih_activate()
    {
        global $wpdb;

        // Add default options
        add_option('tdih_options', array(
            'date_format' => 'YYYY-MM-DD',
            'era_mark' => 1,
            'no_events' => esc_html__('No Events', 'this-day-in-history'),
            'exclude_search' => 1
        ));

        // Set the database version
        add_option('tdih_db_version', TDIH_DB_VERSION);

        // Add custom capability to the administrator role
        $role = get_role('administrator');
        if ($role) {
            if (!$role->has_cap('manage_tdih_events')) {
                $role->add_cap('manage_tdih_events');
            }
        } else {
            error_log('Administrator role not found. Could not add capability "manage_tdih_events".');
        }
    }

    private function tdih_deactivate()
    {
        // Perform any necessary cleanup on deactivation
        // Currently, no actions are required
    }
}

?>