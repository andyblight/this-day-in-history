<?php

/* Add tdih shortcode */

function tdih_shortcode($atts)
{
	global $wpdb;

	extract(shortcode_atts(array(
		'show_age' => 0,
		'show_link' => 0,
		'show_type' => 1,
		'show_year' => 1,
		'type' => false,
		'day' => false,
		'month' => false,
		'max_rows' => 0,
		'period' => false,
		'class' => ''
	), $atts));

	$show_age = intval($show_age) == 0 ? false : true;
	$show_link = intval($show_link) == 0 ? 0 : (intval($show_link) == 1 ? 1 : 2);
	$show_type = intval($show_type) == 0 ? false : true;
	$show_year = intval($show_year) == 0 ? false : true;

	$type = $type === false ? false : sanitize_text_field($type);
	$day = $day == 'c' ? current_time('d') : (intval($day) > 0 && intval($day) < 32 ? intval($day) : false);
	$month = $month == 'c' ? current_time('n') : (intval($month) > 0 && intval($month) < 13 ? intval($month) : false);

	if ($day > 0) {
		$month = $month == 0 ? current_time('n') : $month;
	}

	if ($month > 0) {
		$day = $day == 0 ? current_time('d') : $day;
	}

	$max_rows = abs(intval($max_rows)) < 100 ? abs(intval($max_rows)) : false;

	$when = tdih_when_clause($period, false, $day, $month);

	$filter = $type === false ? '' : ($type == '' ? ' AND t.slug IS NULL' : $wpdb->prepare(" AND t.slug = %s", sanitize_text_field($type)));

	$order = $show_type ? ' ORDER BY t.name ASC,' : ' ORDER BY';

	// Avoid RAND() for performance reasons; shuffle in PHP instead
	$order .= ' CONVERT(LEFT(p.post_title, LENGTH(p.post_title) - 6), SIGNED INTEGER) ASC';

	$limit = $max_rows > 0 ? $wpdb->prepare(' LIMIT %d', $max_rows) : '';

	$query = $wpdb->prepare(
		"SELECT p.ID, LEFT(p.post_title, LENGTH(p.post_title) - 6) AS event_year, p.post_content AS event_name, t.name AS event_type
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
        LEFT JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = %s
        LEFT JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
        WHERE p.post_type = %s %s %s %s",
		'event_type',
		'tdih_event',
		$when,
		$filter,
		$order . $limit
	);

	$events = $wpdb->get_results($query);

	if (!empty($events)) {
		$event_type = '.';

		if ($class != '') {
			$class = ' ' . esc_attr($class);
		}

		$text = '<dl class="tdih_list' . $class . '">';

		foreach ($events as $e => $values) {
			if ($show_type) {
				if ($events[$e]->event_type != $event_type) {
					$event_type = $events[$e]->event_type;
					$text .= '<dt class="tdih_event_type">' . esc_html($events[$e]->event_type) . '</dt>';
				}
			}

			$text .= '<dd>';

			if ($show_year) {
				$year = $events[$e]->event_year == 0 ? '' : (substr($events[$e]->event_year, 0, 1) == '-' ? substr($events[$e]->event_year, 1) . ($options['era_mark'] == 1 ? __(' BC', 'this-day-in-history') : __(' BCE', 'this-day-in-history')) : $events[$e]->event_year);

				$text .= '<span class="tdih_event_year">' . esc_html($year) . '</span>  ';
			}

			$title = get_extended($events[$e]->event_name);

			$text .= '<span class="tdih_event_name">';

			if ($show_link == 2 || ($show_link == 1 && $title['extended'])) {
				$text .= '<a href="' . esc_url(get_post_permalink($events[$e]->ID)) . '">' . esc_html(trim($title['main'])) . '</a>';
			} else {
				$text .= esc_html($title['main']);

				if ($title['extended']) {
					$text .= ' <a href="' . esc_url(get_post_permalink($events[$e]->ID)) . '">' . esc_html($title['more_text'] ? $title['more_text'] : __('More &#8230;', 'this-day-in-history')) . '</a>';
				}
			}

			$text .= '</span>';

			if ($show_age && $events[$e]->event_year != 0) {
				$age = intval(current_time('Y')) - intval($events[$e]->event_year);
				$text .= ' <span class="tdih_event_age">(' . esc_html($age) . ')</span>';
			}

			$text .= '</dd>';
		}

		$text .= '</dl>';
	} else {
		$options = get_option('tdih_options');
		$text = empty($options['no_events']) ? '' : '<p>' . esc_html($options['no_events']) . '</p>';
	}

	return $text;
}

add_shortcode('tdih', 'tdih_shortcode');


/* Add tdih_tab shortcode */

function tdih_tab_shortcode($atts)
{
	global $wpdb;

	$options = get_option('tdih_options');

	extract(shortcode_atts(array('show_date' => 1, 'show_dow' => 0, 'show_head' => 1, 'show_link' => 0, 'show_type' => 1, 'order_dmy' => 0, 'type' => false, 'day' => false, 'month' => false, 'year' => false, 'period' => false, 'period_days' => false, 'date_format' => false, 'class' => ''), $atts));

	$show_date = intval($show_date) == 0 ? false : true;
	$show_dow = intval($show_dow) == 0 ? false : true;
	$show_head = intval($show_head) == 0 ? false : true;
	$show_link = intval($show_link) == 0 ? 0 : (intval($show_link) == 1 ? 1 : 2);
	$show_type = intval($show_type) == 0 ? false : true;

	$order_dmy = intval($order_dmy) == 0 ? false : true;

	$type = $type === false ? false : sanitize_text_field($type);
	$day = $day == 'c' ? current_time('d') : (intval($day) > 0 && intval($day) < 32 ? intval($day) : false);
	$month = $month == 'c' ? current_time('n') : (intval($month) > 0 && intval($month) < 13 ? intval($month) : false);
	$year = $year == 'c' ? current_time('Y') : (intval($year) > -10000 && intval($year) < 10000 ? intval($year) : false);

	$period_days = abs(intval($period_days)) < 100 ? abs(intval($period_days)) : false;
	$date_format = $date_format === false ? false : sanitize_text_field($date_format);

	$format = tdih_date_mask($options['date_format'], $show_dow, $date_format);

	$when = tdih_when_clause($period, $period_days, $day, $month, $year);

	$filter = $type === false ? '' : ($type == '' ? ' AND t.slug IS NULL' : " AND t.slug='" . $type . "'");

	if ($period_days === false) {

		$order = $order_dmy === false ? ' ORDER BY LENGTH(p.post_title) DESC, p.post_title ASC' : ' ORDER BY SUBSTR(p.post_title, -2) ASC, SUBSTR(p.post_title, -5, 2) ASC, LEFT(p.post_title, LENGTH(p.post_title) - 6) ASC';

	} else {

		$order = ' ORDER BY SUBSTR(p.post_title, -5, 2) ASC, SUBSTR(p.post_title, -2) ASC, LEFT(p.post_title, LENGTH(p.post_title) - 6) ASC';
	}

	$order .= ', p.post_content ASC';

	$events = $wpdb->get_results("SELECT p.ID, p.post_title AS event_date, p.post_content AS event_name, t.name AS event_type FROM " . $wpdb->prefix . "posts p LEFT JOIN " . $wpdb->prefix . "term_relationships tr ON p.ID = tr.object_id LEFT JOIN " . $wpdb->prefix . "term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id   AND tt.taxonomy='event_type' LEFT JOIN " . $wpdb->prefix . "terms t ON tt.term_id = t.term_id WHERE p.post_type = 'tdih_event'" . $when . $filter . $order);

	if (!empty($events)) {

		if ($class != '') {
			$class = ' ' . esc_attr($class);
		}

		$text = '<table class="tdih_table' . $class . '">';

		if ($show_head) {
			$text .= '<thead>';
			if ($show_date) {
				$text .= '<th class="tdih_event_date">' . __('Date', 'this-day-in-history') . '</th>';
			}
			if ($show_type) {
				$text .= '<th class="tdih_event_type">' . __('Type', 'this-day-in-history') . '</th>';
			}
			$text .= '<th class="tdih_event_name">' . __('Event', 'this-day-in-history') . '</th></thead>';
		}

		foreach ($events as $e => $values) {
			$text .= '<tr>';
			if ($show_date) {
				$d = substr($events[$e]->event_date, 0, 1) == '-' ? new DateTime(substr($events[$e]->event_date, 1)) : new DateTime($events[$e]->event_date);
				$event_date = substr($events[$e]->event_date, 0, 1) == '-' ? $d->format($format) . ($options['era_mark'] == 1 ? __(' BC', 'this-day-in-history') : __(' BCE', 'this-day-in-history')) : $d->format($format);
				$text .= '<td class="tdih_event_date">' . esc_html($event_date) . '</td>';
			}

			if ($show_type) {
				$text .= '<td class="tdih_event_type">' . esc_html($events[$e]->event_type) . '</td>';
			}

			$title = get_extended($events[$e]->event_name);
			$text .= '<td class="tdih_event_name">';

			if ($show_link == 2 || ($show_link == 1 && $title['extended'])) {
				$text .= '<a href ="' . esc_url(get_post_permalink($events[$e]->ID)) . '">' . esc_html(trim($title['main'])) . '</a></td>';
			} else {
				$text .= esc_html($title['main']);
				if ($title['extended']) {
					$text .= ' <a href ="' . esc_url(get_post_permalink($events[$e]->ID)) . '">' . esc_html($title['more_text'] ? $title['more_text'] : __('More &#8230;', 'this-day-in-history')) . '</a>';
				}
				$text .= '</td>';
			}
			$text .= '</tr>';
		}
		$text .= '</table>';
	} else {
		$text = empty($options['no_events']) ? '' : '<p>' . esc_html($options['no_events']) . '</p>';
	}

	return $text;
}

add_shortcode('tdih_tab', 'tdih_tab_shortcode');


function tdih_date_mask($format_desc, $show_dow, $date_format)
{

	if ($date_format === false) {

		switch ($format_desc) {

			case 'MM-DD-YYYY':

				$format = 'm-d-Y';
				break;

			case 'DD-MM-YYYY':

				$format = 'd-m-Y';
				break;

			default:

				$format = 'Y-m-d';
		}

		if ($show_dow) {
			$format = 'D ' . $format;
		}

	} else {

		$format = $date_format;

	}

	return $format;
}

function tdih_when_clause($period, $period_days, $day, $month, $year = false)
{

	$start = DateTime::createFromFormat('U', current_time('timestamp'));

	$stop = DateTime::createFromFormat('U', current_time('timestamp'));

	$days = intval($period_days) - 1;

	if ($period) {

		switch ($period) {

			case 'a':

				return '';
				break;

			case 'm':

				$start->add(new DateInterval('P1D'));

				if ($period_days) {
					$days += 1;
				}

				break;

			case 'c':
			case 'l':
			case 'n':
			case 'w':

				if ($period == 'n') {

					$start->add(new DateInterval('P7D'));

					$stop->add(new DateInterval('P7D'));
				}

				if ($period == 'l') {

					$start->sub(new DateInterval('P7D'));

					$stop->sub(new DateInterval('P7D'));
				}

				if ($period == 'c') {

					$start->sub(new DateInterval('P3D'));

					$stop->add(new DateInterval('P3D'));

				} else {

					$period = $start->format('N') - 1;

					if ($period > 1) {
						$start->sub(new DateInterval('P' . $period . 'D'));
					}

					$until = ($period - 6);

					if ($until > 0) {

						$stop->sub(new DateInterval('P' . $until . 'D'));

					} elseif ($until < 0) {

						$until = 0 - $until;

						$stop->add(new DateInterval('P' . $until . 'D'));
					}
				}

				$when = " AND CASE SUBSTR(p.post_title, 1, 1) WHEN '-' THEN DATE_FORMAT(SUBSTR(p.post_title, 2), '%m%d') ELSE DATE_FORMAT(p.post_title,'%m%d') END BETWEEN '" . $start->format('md') . "' AND '" . $stop->format('md') . "'";

				return $when;

			case 'y':

				$start->sub(new DateInterval('P1D'));

				if ($period_days) {
					$days -= 1;
				}

				break;

			default:
			/* nowt */
		}

		if ($period_days) {

			if ($days > 0) {
				$stop->add(new DateInterval('P' . $days . 'D'));
			}

			$when = " AND CASE SUBSTR(p.post_title, 1, 1) WHEN '-' THEN DATE_FORMAT(SUBSTR(p.post_title, 2), '%m%d') ELSE DATE_FORMAT(p.post_title,'%m%d') END BETWEEN '" . $start->format('md') . "' AND '" . $stop->format('md') . "'";

		} else {

			$when = $wpdb->prepare(" AND SUBSTR(p.post_title, -5) = %s", $start->format('m-d'));

		}

	} else {

		if ($year || $month || $day) {

			$when = '';

		} else {

			$when = $wpdb->prepare(" AND SUBSTR(p.post_title, -5) = %s", $start->format('m-d'));
		}

		if ($year) {

			$when .= " AND LEFT(p.post_title, LENGTH(p.post_title) - 6) = '" . ($year < 0 ? sprintf("%05d", $year) : sprintf("%04d", $year)) . "'";
		}

		if ($month && $day) {

			$when .= " AND SUBSTR(p.post_title, -5) = '" . sprintf("%02d", $month) . "-" . sprintf("%02d", $day) . "'";

		} else {

			if ($month) {
				$when .= " AND SUBSTR(p.post_title, -5, 2) ='" . sprintf("%02d", $month) . "'";
			}

			if ($day) {
				$when .= " AND SUBSTR(p.post_title, -2) = '" . sprintf("%02d", $day) . "'";
			}
		}
	}

	return $when;
}

?>