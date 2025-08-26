<?php
/*
Plugin Name: This Day In History
Description: This "On This Day" plugin allows you to enter historic or future events and display them via the included widget and shortcodes.
Author: Andy Blight
Version: 3.9.0
Author URI: https://github.com/andyblight/this-day-in-history-2
Last Updated: 26 August 2025
License: GPLv2 or later
Text Domain: this-day-in-history
*/

/*
	Copyright 2025 Andy Blight.
	Copyright 2011-20 BrokenCrust

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/* Set the current database version */

if (!defined('TDIH_DB_VERSION')) { define('TDIH_DB_VERSION', 3); }


/* Activation, Deactivation and Uninstall */

require_once(plugin_dir_path(__FILE__).'tdih-init.class.php');

register_activation_hook(__FILE__, array('tdih_init', 'on_activate'));

register_deactivation_hook(__FILE__, array('tdih_init', 'on_deactivate'));


/* Database updates */

function tdih_db_updates() {
    global $wpdb;

    // run only in admin to avoid unexpected front-end work and require capability
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( intval( get_option( 'tdih_db_version' ) ) !== TDIH_DB_VERSION ) {

        $options = get_option( 'tdih_options' );

        // add leading 0 to month and day (for version 3)
        $result = $wpdb->query( "UPDATE {$wpdb->posts} SET post_title = DATE_FORMAT(post_title,'%Y-%m-%d') WHERE post_type = 'tdih_event'" );

        // update the options (for version 3)
        $new_options = array(
            'date_format'    => $options['date_format'] == '%Y-%m-%d' ? 'YYYY-MM-DD' : ( $options['date_format'] == '%d-%m-%Y' ? 'DD-MM-YYYY' : 'MM-DD-YYYY' ),
            'era_mark'       => 1,
            'no_events'      => $options['no_events'],
            'exclude_search' => 1,
        );

        update_option( 'tdih_options', $new_options );

        // rebuild the slugs to reflect the chosen date format (for version 3)
        $format = $new_options['date_format'] === 'YYYY-MM-DD' ? '%Y-%m-%d' : ( $options['date_format'] === 'DD-MM-YYYY' ? '%d-%m-%Y' : '%m-%d-%Y' );

        $result = $wpdb->query( "UPDATE {$wpdb->posts} SET post_name = DATE_FORMAT(post_title, '" . esc_sql( $format ) . "') WHERE post_type = 'tdih_event'" );

        // update the database version
        update_option( 'tdih_db_version', TDIH_DB_VERSION );
    }
}

// Replace plugins_loaded hook (runs on every request) with admin_init
remove_action( 'plugins_loaded', 'tdih_db_updates' ); // safe noop if not hooked
add_action( 'admin_init', 'tdih_db_updates' );


/* Include the widget */

require_once(plugin_dir_path(__FILE__).'/tdih-widget.php');


/* Include the shortcodes */

require_once(plugin_dir_path(__FILE__).'/tdih-shortcodes.php');


/* Include the admin list table class */

require_once(plugin_dir_path(__FILE__).'/tdih-list-table.class.php');


/* Add plugin CSS for tdih admin pages */

function load_tdih_styles(){
	wp_register_style('this-day-in-history', plugin_dir_url(__FILE__).'tdih.css');
	wp_enqueue_style('this-day-in-history');
}

add_action('admin_enqueue_scripts', 'load_tdih_styles');


/* Add historic event item to the Admin Bar "New" drop down */

function tdih_add_event_to_menu() {
    global $wp_admin_bar;

    if ( ! current_user_can( 'manage_tdih_events' ) || ! is_admin_bar_showing() ) { return; }

    $href = add_query_arg(
        array( 'page' => 'this-day-in-history', 'action' => 'new' ),
        admin_url( 'admin.php' )
    );

    $wp_admin_bar->add_node( array(
        'id'     => 'add-tdih-event',
        'parent' => 'new-content',
        'title'  => __( 'Historic Event', 'this-day-in-history' ),
        'href'   => esc_url( $href ),
        'meta'   => false,
    ) );
}

add_action('admin_bar_menu', 'tdih_add_event_to_menu', 999);


/* Add event and event type counts to the "At a Glance" dashboard widget */

function tdih_glance_items() {

    $info   = get_post_type_object( 'tdih_event' );
    $events = wp_count_posts( 'tdih_event' );

    // use esc_url / esc_html / number_format_i18n
    printf(
        '</ul><ul><li class="tdih_event-count"><a href="%s">%s %s</a></li>',
        esc_url( admin_url( 'admin.php?page=this-day-in-history' ) ),
        esc_html( number_format_i18n( $events->publish ) ),
        esc_html( _n( $info->labels->singular_name, $info->labels->name, intval( $events->publish ) ) )
    );

    $label = get_taxonomy_labels( get_taxonomy( 'event_type' ) );
    $types = number_format_i18n( wp_count_terms( 'event_type' ) );

    printf(
        '<li class="tdih_event_type-count"><a href="%s">%s %s</a></li></ul>',
        esc_url( admin_url( 'edit-tags.php?taxonomy=event_type' ) ),
        esc_html( $types ),
        esc_html( _n( $label->singular_name, $label->name, intval( $types ) ) )
    );
}

add_filter('dashboard_glance_items', 'tdih_glance_items', 10, 1);


/* Add historic events menu to the main admin menu */

function tdih_add_menu() {
	global $tdih_screen;

	$tdih_screen = add_menu_page(__('This Day In History', 'this-day-in-history'), __('This Day In History', 'this-day-in-history'), 'manage_tdih_events', 'this-day-in-history', 'tdih_display_list', 'dashicons-backup', 21);
	add_submenu_page('this-day-in-history', __('This Day In History', 'this-day-in-history'), __('All Events', 'this-day-in-history'), 'manage_tdih_events', 'this-day-in-history');
	add_submenu_page('this-day-in-history', __('This Day In History', 'this-day-in-history'), __('Add New', 'this-day-in-history'), 'manage_tdih_events', '?page=this-day-in-history&action=new');
	add_submenu_page('this-day-in-history', __('This Day In History', 'this-day-in-history'), __('Event Types', 'this-day-in-history'), 'manage_tdih_events', 'edit-tags.php?taxonomy=event_type');
	add_action('load-'.$tdih_screen, 'tdih_add_help_tab');
}

add_action('admin_menu', 'tdih_add_menu');


/* Highlight the correct top level menu */

function tdih_menu_correction($parent_file) {
	global $current_screen;

	$taxonomy = $current_screen->taxonomy;
	if ($taxonomy == 'event_type') { $parent_file = 'this-day-in-history'; }
	return $parent_file;
}

add_action('parent_file', 'tdih_menu_correction');


/* Add plugin settings */

function tdih_options_menu() {
	add_options_page('This Day In History Options', 'This Day In History', 'manage_options', 'tdih-settings', 'tdih_options');
}

add_action('admin_menu', 'tdih_options_menu');

function tdih_options() {
	if (!current_user_can('manage_options')) { wp_die(__('You do not have sufficient permissions to access this page.', 'this-day-in-history')); }
	?>
		<div class="wrap">
			<h2><?php _e('This Day In History Options', 'this-day-in-history'); ?></h2>
			<form action="options.php" method="post">
				<?php settings_fields('tdih_options'); ?>
				<?php do_settings_sections('tdih'); ?>
				<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'this-day-in-history'); ?>" /></p>
			</form>
		</div>
	<?php
}

function tdih_admin_init(){
	register_setting('tdih_options', 'tdih_options', 'tdih_options_validate');
	add_settings_section('tdih_display', __('Display Settings', 'this-day-in-history'), 'tdih_display_section_text', 'tdih');
	add_settings_field('date_format', __('Event Date Display Format', 'this-day-in-history'), 'tdih_date_format', 'tdih', 'tdih_display');
	add_settings_field('era_mark', __('Date Marker for Last Era', 'this-day-in-history'), 'tdih_era_mark', 'tdih', 'tdih_display');
	add_settings_field('exclude_search', __('Exclude Search Results', 'this-day-in-history'), 'tdih_exclude_search', 'tdih', 'tdih_display');
	add_settings_field('no_events', __('Message for No Events', 'this-day-in-history'), 'tdih_no_events', 'tdih', 'tdih_display');

}

add_action('admin_init', 'tdih_admin_init');

function tdih_display_section_text() {

	echo '<p>'.__('Display settings for the administration screens, widget and shortcodes.', 'this-day-in-history').'</p>';
}

function tdih_date_format() {

    $options = get_option( 'tdih_options' );

    $formats = array( 1 => 'YYYY-MM-DD', 2 => 'MM-DD-YYYY', 3 => 'DD-MM-YYYY' );

    $labels = array(
        1 => __( 'Year First (YYYY-MM-DD)', 'this-day-in-history' ),
        2 => __( 'Month First (MM-DD-YYYY)', 'this-day-in-history' ),
        3 => __( 'Day First (DD-MM-YYYY)', 'this-day-in-history' ),
    );

    echo '<select id="tdih_date_format" name="tdih_options[date_format]">';

    for ( $p = 1; $p < 4; $p++ ) {

        $val   = esc_attr( $formats[ $p ] );
        $label = esc_html( $labels[ $p ] );

        if ( isset( $options['date_format'] ) && $formats[ $p ] === $options['date_format'] ) {
            echo '<option selected="selected" value="' . $val . '">' . $label . '</option>';
        } else {
            echo '<option value="' . $val . '">' . $label . '</option>';
        }
    }

    echo '</select>';
    echo '<p class="description">' . esc_html__( 'Defines the date format for displaying and entering dates.', 'this-day-in-history' ) . '</p>';
}

function tdih_era_mark() {
    $options = get_option( 'tdih_options' );

    $era = isset( $options['era_mark'] ) ? (int) $options['era_mark'] : 1;
    ?>
        <select id="tdih_era_mark" name="tdih_options[era_mark]">
            <option <?php echo $era === 1 ? 'selected="selected"' : ''; ?> value="1"><?php _e('Use BC', 'this-day-in-history'); ?></option>
            <option <?php echo $era === 2 ? 'selected="selected"' : ''; ?> value="2"><?php _e('Use BCE', 'this-day-in-history'); ?></option>
        </select>
        <p class="description"><?php _e('Defines how to show dates with a negative year.', 'this-day-in-history'); ?></p>
    <?php
}

function tdih_exclude_search() {
    $options = get_option( 'tdih_options' );
    $exclude = isset( $options['exclude_search'] ) ? (int) $options['exclude_search'] : 1;
    ?>
        <select id="tdih_exclude_search" name="tdih_options[exclude_search]">
            <option <?php echo $exclude === 1 ? 'selected="selected"' : ''; ?> value="1"><?php _e('Yes', 'this-day-in-history'); ?></option>
            <option <?php echo $exclude === 0 ? 'selected="selected"' : ''; ?> value="0"><?php _e('No', 'this-day-in-history'); ?></option>
        </select>
        <p class="description"><?php _e('Exclude Historic Events from search results?', 'this-day-in-history'); ?></p>
    <?php
}

function tdih_no_events() {
    $options = get_option( 'tdih_options' );
    $value   = isset( $options['no_events'] ) ? $options['no_events'] : '';
    ?>
        <input name='tdih_options[no_events]' type='text' value='<?php echo esc_attr( $value ); ?>' />
        <p class="description"><?php _e('If you prefer the widget and shortcode not to be displayed when no events match, then leave this field empty.', 'this-day-in-history'); ?></p>
    <?php
}

function tdih_options_validate($input) {
	//nowt
	return $input;
}


/* Add help and screen options tabs */

function tdih_add_help_tab() {
	global $tdih_screen, $tdih_table;

	$options = get_option('tdih_options');

	$screen = get_current_screen();

	if ($screen->id != $tdih_screen) { return; }

	$content  = '<p>'.__('This page provides the ability for you to add, edit and remove historic (or for that matter, future) events.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('If you wish you can display these via the included widget or shortcodes.', 'this-day-in-history').'</p>';

	$screen->add_help_tab(array('id' => 'tdih_overview', 'title' => __('Overview'), 'content' => $content));

	$content  = '<p>'.sprintf(__('You must enter a full date in the format %s, for example:', 'this-day-in-history'), $options['date_format']).' ';
	$content .= sprintf(__('the 20<sup>th</sup> November 1497 should be entered as <code>%s', 'this-day-in-history'), tdih_date_example($options['date_format'])).'</code>. Leading zeros in the year are optional.</p>';
	$content .= '<p>'.__('This format is used for administration screens and also as the default date format in the <code>[tdih_tab]</code> shortcode.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('The format can be changed in the This Day In History settings screen.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('Dates from 31<sup>st</sup> December 9999 BC to 31<sup>st</sup> December 9999 AD are supported. Add '.($options['era_mark'] == 1 ? __(' BC', 'this-day-in-history') : __(' BCE', 'this-day-in-history')).' after the date if required e.g. <code>'.tdih_date_example($options['date_format'], true), 'this-day-in-history').'</code>.</p>';
	$content .= '<p>'.__('Enter 0 for the year and it will be listed without a year.', 'this-day-in-history').'</p>';

	$screen->add_help_tab(array('id' => 'tdih_date_format', 'title' => __('Event Dates'), 'content' => $content));

	$content  = '<p>'.__('You must enter name for the event - for example', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('WordPress is über cool!', 'this-day-in-history').'</p>';

	$screen->add_help_tab(array('id' => 'tdih_names', 'title' => __('Event Names'), 'content' => $content));

	$content  = '<p>'.__('You can choose an event type for each event from a list of custom event types which you can enter on the event types screen.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('An event type is optional.', 'this-day-in-history').'</p>';

	$screen->add_help_tab(array('id' => 'tdih_event_types', 'title' => __('Event Types'), 'content' => $content));

	$content  = '<p>'.__('You can add a <code>[tdih]</code> shortcode to any post or page to display a list of events for today as per the widget.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('There are eleven optional attributes for this shortcode:', 'this-day-in-history').'</p>';
	$content .= '<ul>';
	$content .= '<li>'.__('show_age (0, 1) - 1 shows the age in years of the event in brackets after the title and 0 does not (default).', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('show_link (0-2) - 0 shows a more link if there is more to show, 1 links the title if there is more to show and 2 always links the title.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('show_type (0, 1) - 1 shows event types (default) and 0 does not.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('show_year (0, 1) - 1 shows the year of the event (default) and 0 does not.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('type - enter a type to show only events of that type. Shows all types by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('day (1-31) - enter a day to show only events on that day. Shows all days by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('month (1-12, c) - enter a month to show only events in that month. Shows all months by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('year (-9999 to 9999, 0) - enter a year to show only events in that year. Shows all years by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('period (t, m, y) - show events for today, tomorrow and yesterday. Shows today\'s events by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('classes - enter one or more space separated classes which will be added to the table tag.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('max_rows (1-99) - enter a maximum number of events to show. Shows all events by default.', 'this-day-in-history').'</li>';
	$content .= '</ul>';
	$content .= '<p>'.__('Example use:', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('<code>[tdih]</code> - This shows year and event types for all event types for todays events.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('<code>[tdih show_type=0 type=\'birth\']</code> - This shows year and event but not type for the event type (slug) of birth.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('<code>[tdih year=1066 max_rows=5]</code> - This shows year and event types for up to five events that happened on this day in 1066.', 'this-day-in-history').'</p>';

	$screen->add_help_tab(array('id' => 'tdih_shortcode', 'title' => __('tdih Shortcode'), 'content' => $content));

	$content  = '<p>'.__('You can add a <code>[tdih_tab]</code> shortcode to any post or page to display a table of events.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('There are fourteen optional attributes for this shortcode:', 'this-day-in-history').'</p>';
	$content .= '<ul>';
	$content .= '<li>'.__('show_date (0, 1) - 1 shows the date (default) and 0 does not.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('show_dow (0, 1) - 1 shows the day of the week and 0 does not (default).', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('show_head (0, 1) - 1 shows a header row (default) and 0 does not.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('show_link (0, 1, 2) - 0 shows a more link if there is more to show, 1 links the title if there is more to show and 2 always links the title.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('show_type (0, 1) - 1 shows event types (default) and 0 does not.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('order_dmy (0, 1) - 0 sorts chronologically by year-month-day (default) and 1 sorts by day-month-year.').'</li>';
	$content .= '<li>'.__('type - enter a type to show only events of that type. Shows all types by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('day (1-31) - enter a day to show only events on that day. Shows all days by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('month (1-12, c) - enter a month to show only events in that month. Shows all months by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('year (-9999 to 9999, 0) - enter a year to show only events in that year. Shows all years by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('period (a, c, l, m, n, t, w, y) - t, m, y show events for today, tomorrow and yesterday. c, l, n, w show events for current, last, next and ISO week. a shows all events. Shows today\'s events by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('period_days (1-99) - enter the number of days to show for t, m, y periods only. Shows only one day by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('date_format - enter a custom <a href="https://www.php.net/manual/en/function.date.php">php date format</a> to display the date. Uses the tdih admin setting by default.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('classes - enter one or more space separated classes which will be added to the table tag.', 'this-day-in-history').'</li>';
	$content .= '</ul>';
	$content .= '<p>'.__('NB:', 'this-day-in-history').'</p>';
	$content .= '<ul>';
	$content .= '<li>'.__('day of the week will never be shown if the date is not shown.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('Setting date_format will override the tdih admin format and the day of the week setting.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('Setting period will override any values for day, month and year.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('day, month and year can be combined.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('year=0 will display events with no year.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('month=c will display the current month.', 'this-day-in-history').'</li>';
	$content .= '<li>'.__('period=c, l or n show a seven day period with the current day as the middle, last or first day.', 'this-day-in-history').'</li>';
	$content .= '</ul>';
	$content .= '<p>'.__('Example use:', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('<code>[tdih_tab period=\'a\']</code> - This shows a full list of events in date order and includes the event type.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('<code>[tdih_tab show_types=0 type=\'birth\' classes=\'content dark\']</code> - This shows events but not type for the event type (slug) of birth. " content dark" will be added to the table\'s class.', 'this-day-in-history').'</p>';
	$content .= '<p>'.__('<code>[tdih_tab day=20 month=8 date_format=\'Y\']</code> - This shows events on 20<sup>th</sup> August in any year.  Format the date to only show the four digit year.', 'this-day-in-history').'</p>';

	$screen->add_help_tab(array('id' => 'tdih_tab_shortcode', 'title' => __('tdih_tab Shortcode'), 'content' => $content));

	$screen->set_help_sidebar('<p><b>'.__('This Day in History', 'this-day-in-history').'</b></p><p><a href="https://brokencrust.com">'.__('Plugin Information', 'this-day-in-history').'</a></p><p><a href="https://wordpress.org/support/plugin/this-day-in-history">'.__('Support Forum', 'this-day-in-history').'</a></p><p><a href="https://wordpress.org/support/view/plugin-reviews/this-day-in-history">'.__('Rate and Review', 'this-day-in-history').'</a></p><p><a href="'.admin_url('admin.php?page=tdih-settings').'">'.__('Settings', 'this-day-in-history').'</a></p>');

	$screen->add_option('per_page', array('label' => 'Historic Events', 'default' => 20, 'option' => 'edit_tdih_event_per_page'));

	$tdih_table = new TDIH_List_Table();
}


/* Set the screen options */

function tdih_set_option($status, $option, $value) {

	if ('events_per_page' == $option) { return $value; }

	return $status;
}

add_filter('set-screen-option', 'tdih_set_option', 10, 3);


/* Display main admin screen */

function tdih_display_list() {
	global $tdih_table;

	$tdih_table->process_action();

}

/* Display an example date in the chosen order */

function tdih_date_example($format, $bc=false) {

	$options = get_option('tdih_options');

	switch ($format) {
		case 'MM-DD-YYYY':
			$example = '11-20-1497';
			break;

		case 'DD-MM-YYYY':
			$example = '20-11-1497';
			break;

		default:
			$example = '1497-11-20';
	}

	if ($bc) { $example .= ($options['era_mark'] == 1 ? __(' BC', 'this-day-in-history') : __(' BCE', 'this-day-in-history')); }

	return $example;
}


/* Register event_type taxonomy */

function tdih_build_taxonomies() {

	$labels = array(
		'name'                       => _x('Event Types', 'taxonomy general name', 'this-day-in-history'),
		'singular_name'              => _x('Event Type', 'taxonomy singular name', 'this-day-in-history'),
		'menu_name'                  => __('Event Types', 'this-day-in-history'),
		'all_items'                  => __('All Event Types', 'this-day-in-history'),
		'edit_item'                  => __('Edit Event Type', 'this-day-in-history'),
		'view_item'                  => __('View Event Type', 'this-day-in-history'),
		'update_item'                => __('Update Event Type', 'this-day-in-history'),
		'add_new_item'               => __('Add New Event Type', 'this-day-in-history'),
		'new_item_name'              => __('New Event Type Name', 'this-day-in-history'),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'search_items'               => __('Search Event Types', 'this-day-in-history'),
		'popular_items'              => __('Popular Event Types', 'this-day-in-history'),
		'separate_items_with_commas' => __('Separate event types with commas', 'this-day-in-history'),
		'add_or_remove_items'        => __('Add or remove event types', 'this-day-in-history'),
		'choose_from_most_used'      => __('Choose from the most used event types', 'this-day-in-history'),
		'not_found'                  => __('No event types found.', 'this-day-in-history')
	);

	register_taxonomy('event_type', 'tdih_event', array('labels' => $labels, 'public' => true));
}

add_action('init', 'tdih_build_taxonomies', 0);


/* Change event_type taxonomy screen column title */

function tdih_manage_event_type_event_column($columns) {

	unset($columns['posts']);

	$columns['events'] = __('Events', 'this-day-in-history');

	return $columns;
}

add_filter('manage_edit-event_type_columns', 'tdih_manage_event_type_event_column');


/* Change event_type taxonomy screen count and link */

function tdih_manage_event_type_column( $display, $column, $term_id ) {

    if ( 'events' === $column ) {

        $term = get_term( $term_id, 'event_type' );

        $url = add_query_arg(
            array(
                'page' => 'this-day-in-history',
                'type' => isset( $term->slug ) ? $term->slug : '',
            ),
            admin_url( 'admin.php' )
        );

        printf(
            '<a href="%s">%s</a>',
            esc_url( $url ),
            esc_html( number_format_i18n( isset( $term->count ) ? $term->count : 0 ) )
        );
    }
}

add_action('manage_event_type_custom_column', 'tdih_manage_event_type_column', 10, 3);


/* Register tdih_event post type */

function tdih_register_post_types() {

	$options = get_option('tdih_options');

	$labels = array(
		'name'                  => _x('Historic Events', 'post type general name', 'this-day-in-history'),
		'singular_name'         => _x('Historic Event', 'post type singular name', 'this-day-in-history'),
		'add_new'               => _x('Add New', 'event', 'this-day-in-history'),
		'add_new_item'          => __('Add New Historic Event', 'this-day-in-history'),
		'edit_item'             => __('Edit Historic Event', 'this-day-in-history'),
		'new_item'              => __('New Historic Event', 'this-day-in-history'),
		'view_item'             => __('View Historic Event', 'this-day-in-history'),
		'view_items'            => __('View Historic Events', 'this-day-in-history'),
		'search_items'          => __('Search Historic Events', 'this-day-in-history'),
		'not_found'             => __('No historic events found', 'this-day-in-history'),
		'not_found_in_trash'    => __('No events found in Trash', 'this-day-in-history'),
		'parent_item_colon'     => null,
		'all_items'             => __('All Historic Events', 'this-day-in-history'),
		'archives'              => __('Historic Event Archives', 'this-day-in-history'),
		'attributes'            => __('Historic Event Attributes', 'this-day-in-history'),
		'insert_into_item'      => __('Insert into historic event', 'this-day-in-history'),
		'uploaded_to_this_item' => __('Uploaded to this historic event', 'this-day-in-history'),
		'menu_name'             => __('Historic Events', 'this-day-in-history')
	);

	$ex_search = $options['exclude_search'] == 1 ? true : false;

	register_post_type('tdih_event', array('labels' => $labels, 'public' => true, 'show_ui' => false, 'menu_icon' => 'dashicons-backup', 'rewrite' => array('slug' => 'this-day-in-history'), 'exclude_from_search' => $ex_search));
}

add_action('init', 'tdih_register_post_types');


/* Add settings to plugin page */

function tdih_plugin_action_links($links, $file) {
	static $this_plugin;

	if (!$this_plugin) { $this_plugin = plugin_basename(__FILE__); }

	if ($file == $this_plugin) {

		$settings_link = '<a href="'.admin_url('admin.php?page=tdih-settings').'">'.__('Settings', 'this-day-in-history').'</a>';

		array_unshift($links, $settings_link);
	}

	return $links;
}

add_filter('plugin_action_links', 'tdih_plugin_action_links', 10, 2);


/* Display a default template for events if none present in theme */

function load_tdih_event_template($template) {
		global $post;

		if ($post->post_type == "tdih_event" && $template !== locate_template(array("single-tdih_event.php"))) { return plugin_dir_path( __FILE__ ) . "default-single-tdih_event.php"; }

		return $template;
}

add_filter('single_template', 'load_tdih_event_template');


/* Add custom admin notices */

function tdih_admin_notices(){
    if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'this-day-in-history' ) {

        $m = isset( $_GET['message'] ) ? (int) $_GET['message'] : 0;

        $messages = array(
            1 => __( 'Event <strong>created</strong>', 'this-day-in-history' ),
            2 => __( 'Event <strong>updated</strong>', 'this-day-in-history' ),
            3 => __( 'Event <strong>deleted</strong>', 'this-day-in-history' ),
            4 => __( 'Events <strong>deleted</strong>', 'this-day-in-history' ),
        );
        if ( isset( $messages[ $m ] ) ) {
            printf( '<div class="updated"><p>%s</p></div>', wp_kses_post( $messages[ $m ] ) );
        }
    }
}


add_action('admin_notices', 'tdih_admin_notices');

/* Add text domain */
function tdih_load_textdomain() {
    load_plugin_textdomain(
        'this-day-in-history', // adjust if your textdomain differs
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

add_action('init', 'tdih_load_textdomain');
?>