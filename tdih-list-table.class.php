<?php

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class TDIH_List_Table extends WP_List_Table
{

	private $date_description;

	private $date_format;

	private $era_mark;

	private $per_page;

	public function __construct()
	{

		$options = get_option('tdih_options');

		$this->date_description = $options['date_format'];

		$this->date_format = $this->date_mask();

		$this->era_mark = $options['era_mark'] == 1 ? __(' BC', 'this-day-in-history') : __(' BCE', 'this-day-in-history');

		$this->per_page = $this->get_items_per_page('edit_tdih_event_per_page');

		parent::__construct(array('singular' => 'event', 'plural' => 'events', 'ajax' => true));
	}

	public function column_default($item, $column_name)
	{

		switch ($column_name) {

			case 'event_name':
				return $item->event_name;

			case 'event_type':
				return $item->event_type === NULL ? '--' : '<a href="admin.php?page=this-day-in-history&type=' . $item->event_slug . '">' . $item->event_type . '</a>';

			case 'event_modified':
				return $item->event_modified;

			default:
				return print_r($item, true);
		}
	}

	public function column_event_date($item)
	{
		$actions = array(
			'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'edit', 'id' => $item->ID)))),
			'delete' => sprintf('<a href="%s">Delete</a>', esc_url(add_query_arg(array('page' => $_REQUEST['page'], 'action' => 'delete', 'id' => $item->ID, 'noheader' => true)))),
			'view' => sprintf('<a href="%s">View</a>', esc_url(get_home_url() . '/this-day-in-history/' . $item->slug)),
		);

		return sprintf('%1$s %2$s', esc_html($this->date_add_era($item->event_date)), $this->row_actions($actions));
	}

	public function column_cb($item)
	{

		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->ID);
	}

	public function get_columns()
	{

		$columns = array(
			'cb' => '<input type="checkbox" />',
			'event_date' => __('Event Date', 'this-day-in-history'),
			'event_name' => __('Event Name', 'this-day-in-history'),
			'event_type' => __('Event Type', 'this-day-in-history'),
			'event_modified' => __('Event Modified', 'this-day-in-history')
		);

		return $columns;
	}

	public function get_hidden_columns()
	{

		$columns = (array) get_user_option('manage_tdih_event-menucolumnshidden');

		return $columns;
	}

	public function get_sortable_columns()
	{

		$sortable_columns = array(
			'event_date' => array('event_date', true),
			'event_name' => array('event_name', false),
			'event_type' => array('event_type', false),
			'event_modified' => array('event_modified', false)
		);

		return $sortable_columns;
	}

	public function get_bulk_actions()
	{

		$actions = array('bulk_delete' => 'Delete');

		return $actions;
	}

	public function no_items()
	{

		_e('No historic events have been found.', 'this-day-in-history');
	}

	public function prepare_items()
	{
		global $wpdb;

		$this->_column_headers = $this->get_column_info();

		$this->process_bulk_action();

		$type = empty($_REQUEST['type']) ? '' : $wpdb->prepare("AND t.slug = %s", sanitize_text_field($_REQUEST['type']));
		$filter = empty($_REQUEST['s']) ? '' : $wpdb->prepare(
			"AND (p.post_title LIKE %s OR p.post_content LIKE %s)",
			'%' . $wpdb->esc_like(sanitize_text_field($_REQUEST['s'])) . '%',
			'%' . $wpdb->esc_like(sanitize_text_field($_REQUEST['s'])) . '%'
		);

		$_REQUEST['orderby'] = empty($_REQUEST['orderby']) ? 'event_date' : $_REQUEST['orderby'];

		$order = empty($_REQUEST['order']) ? 'ASC' : $_REQUEST['order'];

		switch ($_REQUEST['orderby']) {

			case 'event_name':

				$orderby = 'ORDER BY p.post_content ';

				break;

			case 'event_date':

				$orderby = "ORDER BY CONVERT(LEFT(p.post_title, LENGTH(p.post_title) - 6), SIGNED INTEGER) " . $order;

				$orderby .= ", CASE SUBSTR(p.post_title, 1, 1) WHEN '-' THEN DATE_FORMAT(SUBSTR(p.post_title, 2), '%m%d') ELSE DATE_FORMAT(p.post_title,'%m%d') END ";

				break;

			case 'event_type':

				$orderby = 'ORDER BY t.name ';

				break;

			case 'event_modified':

				$orderby = 'ORDER BY p.post_modified ';

				break;

			default:

				$orderby = "ORDER BY p.post_title ";
		}

		$events = $wpdb->get_results("SELECT p.ID, p.post_title AS event_date, p.post_name AS slug, p.post_content AS event_name, t.name AS event_type, p.post_modified AS event_modified, t.slug AS event_slug FROM " . $wpdb->prefix . "posts p LEFT JOIN " . $wpdb->prefix . "term_relationships tr ON p.ID = tr.object_id LEFT JOIN " . $wpdb->prefix . "term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id   AND tt.taxonomy='event_type' LEFT JOIN " . $wpdb->prefix . "terms t ON tt.term_id = t.term_id WHERE p.post_type = 'tdih_event' " . $type . $filter . $orderby . $order);

		$total_items = count($events);

		$this->items = array_slice($events, (($this->get_pagenum() - 1) * $this->per_page), $this->per_page);

		$this->set_pagination_args(array('total_items' => $total_items, 'per_page' => $this->per_page, 'total_pages' => ceil($total_items / $this->per_page)));
	}

	public function process_action()
	{
		global $wpdb;

		switch ($this->current_action()) {

			case 'new':
				$this->item_add_edit();
				break;

			case 'add':
				$this->item_add();
				break;

			case 'edit':
				$this->item_add_edit();
				break;

			case 'update':
				$this->item_update();
				break;

			case 'delete':
				$this->item_delete();
				break;

			case 'bulk_delete':
				$this->bulk_delete();
				break;

			default:
				$this->prepare_items();
				$this->item_list();
		}
	}

	private function bulk_delete()
	{
		global $wpdb;

		check_admin_referer('bulk-events');

		$ids = array_map('absint', (array) $_REQUEST['event']);
		foreach ($ids as $id) {
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}posts WHERE ID = %d", $id));
		}

		$url = add_query_arg(array('message' => 4), admin_url('admin.php?page=this-day-in-history'));
		wp_redirect(esc_url_raw($url));
		exit;
	}

	private function date_add_era($date)
	{

		$d = substr($date, 0, 1) == '-' ? new DateTime(substr($date, 1)) : new DateTime($date);

		$date = substr($date, 0, 1) == '-' ? $d->format($this->date_format) . $this->era_mark : $d->format($this->date_format);

		return $date;
	}

	private function date_check($date)
	{

		if (preg_match("/^-?(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)) {

			$year = $matches[1] == '0000' ? '2000' : $matches[1];

			if (checkdate($matches[2], $matches[3], $year)) {
				return true;
			}
		}

		return false;
	}

	private function date_mask()
	{

		switch ($this->date_description) {

			case 'MM-DD-YYYY':
				$format = 'm-d-Y';
				break;

			case 'DD-MM-YYYY':
				$format = 'd-m-Y';
				break;

			default:
				$format = 'Y-m-d';
		}

		return $format;
	}

	private function date_reorder($date)
	{

		switch ($this->date_description) {

			case 'MM-DD-YYYY':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{1,4})(" . $this->era_mark . ")?$/i", $date, $matches)) {
					$date = sprintf('%04d', $matches[3]) . '-' . $matches[1] . '-' . $matches[2];
				}
				break;

			case 'DD-MM-YYYY':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{1,4})(" . $this->era_mark . ")?$/i", $date, $matches)) {
					$date = sprintf('%04d', $matches[3]) . '-' . $matches[2] . '-' . $matches[1];
				}
				break;

			default:
				if (preg_match("/^(\d{1,4})-(\d{2})-(\d{2})(" . $this->era_mark . ")?$/i", $date, $matches)) {
					$date = sprintf('%04d', $matches[1]) . '-' . $matches[2] . '-' . $matches[3];
				}
		}

		if (isset($matches[4])) {
			$date = '-' . $date;
		}

		return $date;
	}

	private function event_types($id)
	{

		$terms = get_the_terms($id, 'event_type');

		$term_list = '';

		if ($terms != '') {
			foreach ($terms as $term) {
				$term_list .= $term->name . ', ';
			}
		}
		$term_list = trim($term_list, ', ');

		return $term_list;
	}

	private function item_add()
	{

		check_admin_referer('this_day_in_history_add_edit');

		$event_date = $this->date_reorder($_POST['event_date_v']);
		$event_slug = sanitize_title_with_dashes($_POST['event_date_v']);
		$event_name = wp_kses_post($_POST['event_name_v']);
		$event_type = $_POST['event_type_v'];

		$error = $this->validate_event($event_date, $event_name);

		if ($error) {

			wp_die($error, 'Error', array("back_link" => true));

		} else {

			$post = array(
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_status' => 'publish',
				'post_title' => $event_date,
				'post_name' => $event_slug,
				'post_content' => $event_name,
				'post_type' => 'tdih_event',
				'tax_input' => $event_type == -1 ? '' : array('event_type' => $event_type)
			);

			$result = wp_insert_post($post);
		}

		$url = add_query_arg(array('message' => 1), admin_url('?page=this-day-in-history'));

		wp_redirect($url);
	}

	private function item_add_edit()
	{
		global $wpdb;

		if ($this->current_action() == 'edit') {

			$id = (int) $_GET['id'];

			$event = $wpdb->get_row("SELECT ID, post_title AS event_date, post_content AS event_name, post_name as event_slug FROM " . $wpdb->prefix . "posts WHERE ID=" . $id);

			$event->event_date = $this->date_add_era($event->event_date);

			$event_type = $this->event_types($id);

		} else {

			$event = (object) array('event_date' => '', 'event_name' => '');

			$event_type = 0;
		}

		?>
		<div id="tdih" class="wrap">
			<h2>
				<?php _e('This Day In History', 'this-day-in-history'); ?>
				<?php if ($this->current_action() == 'edit') {
					echo '<a href="' . admin_url('?page=this-day-in-history&action=new') . '" class="add-new-h2">' . _x('Add New', 'post') . '</a>';
				} ?>
			</h2>
			<div id="ajax-response"></div>
			<div class="form-wrap">
				<h3><?php $this->current_action() == 'edit' ? _e('Edit Historic Event', 'this-day-in-history') : _e('New Historic Event', 'this-day-in-history'); ?>
				</h3>
				<form id="add_edit_event" method="post" class="add_edit_event validate"
					action="<?php echo esc_attr(add_query_arg('noheader', 'true')); ?>">
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
					<input type="hidden" name="action"
						value="<?php echo $this->current_action() == 'edit' ? 'update' : 'add'; ?>" />
					<?php if ($this->current_action() == 'edit') {
						echo '<input type="hidden" name="id" value="' . $id . '" />';
					} ?>
					<?php wp_nonce_field('this_day_in_history_add_edit'); ?>
					<div class="form-field form-required">
						<label for="event_date_v"><?php _e('Date', 'this-day-in-history'); ?></label>
						<input type="text" name="event_date_v" id="event_date_v" value="<?php echo $event->event_date; ?>"
							required="required" />
						<p><?php printf(__('The date the event occured (enter date in %s format).', 'this-day-in-history'), $this->date_description); ?>
						</p>
					</div>
					<div class="form-field form-required">
						<label for="event_name_v"><?php _e('Name', 'this-day-in-history'); ?></label>
						<?php wp_editor($event->event_name, 'event_name_v'); ?>
						<p><?php _e('The name of the event.', 'this-day-in-history'); ?></p>
					</div>
					<div class="form-field">
						<label for="event_type_v"><?php _e('Type', 'this-day-in-history'); ?></label>
						<?php wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'event_type_v', 'taxonomy' => 'event_type', 'selected' => $event_type, 'hierarchical' => 0, 'value_field' => 'name', 'orderby' => 'name', 'show_option_none' => __('none', 'this-day-in-history'))); ?>
						<p><?php _e('The type of event.', 'this-day-in-history'); ?></p>
					</div>
					<p class="submit">
						<input type="submit" name="submit" class="button"
							value="<?php echo $this->current_action() == 'edit' ? __('Save Changes', 'this-day-in-history') : __('Add Event', 'this-day-in-history'); ?>" />
					</p>
				</form>
			</div>
		</div>
		<?php

	}

	private function item_delete()
	{

		$id = (int) $_GET['id'];

		$result = wp_delete_post($id, true);

		$url = add_query_arg(array('message' => 3), admin_url('?page=this-day-in-history'));

		wp_redirect($url);
	}

	private function item_list()
	{

		?>
		<div class="wrap">
			<h2>
				<?php _e('This Day In History', 'this-day-in-history'); ?>
				<a href="<?php echo admin_url('?page=this-day-in-history&action=new'); ?>"
					class="add-new-h2"><?php _ex('Add New', 'post'); ?></a>
			</h2>
			<form id="search-event-list" method="get">
				<input type="hidden" name="page" value="this-day-in-history" />
				<?php $this->search_box(__('Search Historic Events', 'this-day-in-history'), 'tdih'); ?>
			</form>
			<form id="event-list" method="post">
				<input type="hidden" name="noheader" value="true" />
				<?php $this->display() ?>
			</form>
		</div>
		<?php

	}

	private function item_update()
	{

		check_admin_referer('this_day_in_history_add_edit');

		$id = (int) $_POST['id'];
		$event_date = $this->date_reorder($_POST['event_date_v']);
		$event_slug = sanitize_title_with_dashes($_POST['event_date_v']);
		$event_name = wp_kses_post($_POST['event_name_v']);
		$event_type = $_POST['event_type_v'];

		$error = $this->validate_event($event_date, $event_name);

		if ($error) {

			wp_die($error, 'Error', array("back_link" => true));

		} else {

			$post = array(
				'ID' => $id,
				'post_title' => $event_date,
				'post_name' => $event_slug,
				'post_content' => $event_name,
				'tax_input' => $event_type = array('event_type' => $event_type == '-1' ? '' : $event_type)
			);

			$result = wp_update_post($post);
		}

		$url = add_query_arg(array('message' => 2), admin_url('?page=this-day-in-history'));

		wp_redirect($url);
	}

	private function validate_event($event_date, $event_name)
	{

		$error = false;

		if (empty($event_date)) {

			$error = '<h3>' . __('Missing Event Date', 'this-day-in-history') . '</h3><p>' . __('You must enter a date for the event.', 'this-day-in-history') . '</p>';

		} else if (empty($event_name)) {

			$error = '<h3>' . __('Missing Event Name', 'this-day-in-history') . '</h3><p>' . __('You must enter a name for the event.', 'this-day-in-history') . '</p>';

		} else if (!$this->date_check($event_date)) {

			$error = '<h3>' . __('Invalid Event Date', 'this-day-in-history') . '</h3><p>' . sprintf(__('Please enter dates in the format %s.', 'this-day-in-history'), $this->date_description) . '</p>';
		}

		return $error;
	}
}

?>