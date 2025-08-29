<?php


/**
 * Normalize year display: remove leading zeros and preserve era marker.
 *
 * @param mixed $raw_year Year part as stored (e.g. "0946" or "-0946" or "946")
 * @return string Escaped display string (e.g. "946" or "946 BCE")
 */
function tdih_format_year( $raw_year ) {
    if ( $raw_year === '' || $raw_year == 0 ) {
        return '';
    }

    $raw = (string) $raw_year;

    if ( substr( $raw, 0, 1 ) === '-' ) {
        $abs_year = abs( intval( $raw ) );
        $options  = get_option( 'tdih_options' );
        $era      = ( isset( $options['era_mark'] ) && (int) $options['era_mark'] === 1 )
            ? esc_html__( ' BC', 'this-day-in-history' )
            : esc_html__( ' BCE', 'this-day-in-history' );
        return esc_html( $abs_year ) . $era;
    }

    return esc_html( intval( $raw ) );
}

/**
 * Format a stored event date for display, removing any leading zero on the year.
 *
 * @param string $stored_date Stored date string (YYYY-MM-DD or -YYYY-MM-DD for BCE)
 * @param string $php_format  PHP date() format (e.g. 'j/n/Y'). If empty, use admin setting.
 * @return string Formatted date (BCE/BC appended if applicable)
 */
function tdih_format_date_for_display( $stored_date, $php_format = '' ) {
    $options = get_option( 'tdih_options' );

    // fallback to admin format mapping if no explicit format provided
    if ( empty( $php_format ) ) {
        $map = array(
            'YYYY-MM-DD' => 'Y-m-d',
            'MM-DD-YYYY' => 'm-d-Y',
            'DD-MM-YYYY' => 'd-m-Y',
        );
        $admin = isset( $options['date_format'] ) ? $options['date_format'] : 'YYYY-MM-DD';
        $php_format = isset( $map[ $admin ] ) ? $map[ $admin ] : 'Y-m-d';
    }

    $era_mark = isset( $options['era_mark'] ) && (int) $options['era_mark'] === 1 ? __( ' BC', 'this-day-in-history' ) : __( ' BCE', 'this-day-in-history' );

    $is_bce = isset( $stored_date[0] ) && $stored_date[0] === '-';
    $plain  = $is_bce ? substr( $stored_date, 1 ) : $stored_date;

    try {
        // Use DateTime to format other tokens safely
        $dt = new DateTime( $plain );
    } catch ( Exception $e ) {
        return esc_html( $stored_date );
    }

    // If format includes full-year token 'Y', format parts around it and insert integer year
    if ( strpos( $php_format, 'Y' ) !== false ) {
        $year_int = intval( $dt->format( 'Y' ) );
        $year_str = $year_int === 0 ? '' : (string) $year_int;

        $parts = explode( 'Y', $php_format );
        $formatted = '';
        $last_index = count( $parts ) - 1;
        foreach ( $parts as $i => $part ) {
            // format each part (may contain other DateTime tokens)
            $formatted .= $dt->format( $part );
            // between parts insert the integer year
            if ( $i < $last_index ) {
                $formatted .= $year_str;
            }
        }
    } else {
        $formatted = $dt->format( $php_format );
        // ensure we have a year string for BCE handling if needed
        $year_str = intval( $dt->format( 'Y' ) ) === 0 ? '' : (string) intval( $dt->format( 'Y' ) );
    }

    if ( $is_bce && $year_str !== '' ) {
        $formatted .= $era_mark;
    }

    return $formatted;
}

/* Add tdih shortcode */

function tdih_shortcode($atts)
{
	global $wpdb;

	$options = get_option('tdih_options', array());

	$defaults = array(
		'show_age' => 0,
		'show_link' => 0,
		'show_type' => 1,
		'show_year' => 1,
		'type' => false,
		'day' => false,
		'month' => false,
		'max_rows' => 0,
		'period' => false,
		'class' => '',
	);

	$attrs = shortcode_atts($defaults, $atts, 'tdih');

	$show_age = intval($attrs['show_age']) !== 0;
	$show_link = intval($attrs['show_link']) === 1 ? 1 : (intval($attrs['show_link']) === 2 ? 2 : 0);
	$show_type = intval($attrs['show_type']) !== 0;
	$show_year = intval($attrs['show_year']) !== 0;

	$type = $attrs['type'] === false ? false : sanitize_text_field($attrs['type']);
	$day = $attrs['day'] === 'c' ? current_time('d') : (intval($attrs['day']) > 0 && intval($attrs['day']) < 32 ? intval($attrs['day']) : false);
	$month = $attrs['month'] === 'c' ? current_time('n') : (intval($attrs['month']) > 0 && intval($attrs['month']) < 13 ? intval($attrs['month']) : false);

	if ($day > 0 && empty($month)) {
		$month = current_time('n');
	}
	if ($month > 0 && empty($day)) {
		$day = current_time('d');
	}

	$max_rows = min(99, absint($attrs['max_rows']));
	$period = sanitize_text_field($attrs['period']);
	$class = $attrs['class'] !== '' ? ' ' . sanitize_html_class($attrs['class']) : '';

	$when = tdih_when_clause($period, false, $day, $month);

	if ($type === false) {
		$filter = '';
	} elseif ($type === '') {
		$filter = ' AND t.slug IS NULL';
	} else {
		// prepare the equality safely
		$filter = $wpdb->prepare(' AND t.slug = %s', $type);
	}

	$order = $show_type ? ' ORDER BY t.name ASC,' : ' ORDER BY';
	if ($max_rows > 0) {
		$order .= ' RAND(),';
	}
	$order .= ' CONVERT(LEFT(p.post_title, LENGTH(p.post_title) - 6), SIGNED INTEGER) ASC';

	$limit = $max_rows > 0 ? ' LIMIT ' . absint($max_rows) : '';

	// $when and $filter are prepared/escaped by their functions above; $order/$limit are safe (no user SQL injection points)
	$sql = "SELECT p.ID, LEFT(p.post_title, LENGTH(p.post_title) - 6) AS event_year, p.post_content AS event_name, t.name AS event_type
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy='event_type'
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'tdih_event' " . $when . $filter . $order . $limit;

	$events = $wpdb->get_results($sql);

	if (empty($events)) {
		$options = get_option('tdih_options', array());
		$no_events = isset($options['no_events']) ? $options['no_events'] : '';
		return $no_events ? '<p>' . esc_html($no_events) . '</p>' : '';
	}

	// Build output list.
	$text = '<dl class="tdih_list' . $class . '">';

	$last_type = '';
	foreach ($events as $e) {

		$current_type = isset($e->event_type) ? $e->event_type : '';
		if ($show_type && $current_type !== $last_type) {
			$last_type = $current_type;
			$text .= '<dt class="tdih_event_type">' . esc_html($current_type) . '</dt>';
		}

		// Build row.
		$text .= '<dd>';

		if ($show_year) {
			$event_year = isset($e->event_year) ? $e->event_year : '';
			$text .= '<span class="tdih_event_year">' . tdih_format_year( $event_year ) . '</span>';
			// Add a separator between the year and the event.
			$text .= ' - ';
		}

		$extended = get_extended(isset($e->event_name) ? $e->event_name : '');
		$main = isset($extended['main']) ? $extended['main'] : '';
		$more = isset($extended['extended']) ? $extended['extended'] : '';

		$text .= '<span class="tdih_event_name">';

		$link = esc_url(get_permalink(absint($e->ID)));
		$main_clean = wp_kses_post(trim($main));

		if ($show_link === 2 || ($show_link === 1 && !empty($more))) {
			$text .= '<a href="' . $link . '">' . $main_clean . '</a>';
		} else {
			$text .= $main_clean;
			if (!empty($more)) {
				$more_text = !empty($extended['more_text']) ? $extended['more_text'] : __('More &#8230;', 'this-day-in-history');
				$text .= ' <a href="' . $link . '">' . esc_html($more_text) . '</a>';
			}
		}

		$text .= '</span>';

		if ($show_age && isset($e->event_year) && $e->event_year != 0) {
			$age = intval(current_time('Y')) - intval($e->event_year);
			$text .= ' <span class="tdih_event_age">(' . esc_html($age) . ')</span>';
		}

		$text .= '</dd>';
	}

	$text .= '</dl>';

	return $text;
}

add_shortcode('tdih', 'tdih_shortcode');


/* Add tdih_tab shortcode */

function tdih_tab_shortcode($atts)
{
	global $wpdb;

	$options = get_option('tdih_options', array());

	$defaults = array(
		'show_age' => 0,
		'show_date' => 1,
		'show_dow' => 0,
		'show_head' => 1,
		'show_link' => 0,
		'show_type' => 1,
		'order_dmy' => 0,
		'type' => false,
		'day' => false,
		'month' => false,
		'year' => false,
		'period' => false,
		'period_days' => false,
		'date_format' => false,
		'class' => '',
	);

	$attrs = shortcode_atts($defaults, $atts, 'tdih_tab');

	$show_age = intval($attrs['show_age']) !== 0;
	$show_date = intval($attrs['show_date']) !== 0;
	$show_dow = intval($attrs['show_dow']) !== 0;
	$show_head = intval($attrs['show_head']) !== 0;
	$show_link = intval($attrs['show_link']) === 1 ? 1 : (intval($attrs['show_link']) === 2 ? 2 : 0);
	$show_type = intval($attrs['show_type']) !== 0;
	$order_dmy = intval($attrs['order_dmy']) !== 0;

	$type = $attrs['type'] === false ? false : sanitize_text_field($attrs['type']);
	$day = $attrs['day'] === 'c' ? current_time('d') : (intval($attrs['day']) > 0 && intval($attrs['day']) < 32 ? intval($attrs['day']) : false);
	$month = $attrs['month'] === 'c' ? current_time('n') : (intval($attrs['month']) > 0 && intval($attrs['month']) < 13 ? intval($attrs['month']) : false);
	$year = $attrs['year'] === 'c' ? current_time('Y') : (intval($attrs['year']) > -10000 && intval($attrs['year']) < 10000 ? intval($attrs['year']) : false);

	$period_days = $attrs['period_days'] !== false ? min(99, absint($attrs['period_days'])) : false;
	$date_format = $attrs['date_format'] === false ? false : sanitize_text_field($attrs['date_format']);

	$format = tdih_date_mask(isset($options['date_format']) ? $options['date_format'] : 'YYYY-MM-DD', $show_dow, $date_format);

	$when = tdih_when_clause($attrs['period'], $period_days, $day, $month, $year);

	if ($type === false) {
		$filter = '';
	} elseif ($type === '') {
		$filter = ' AND t.slug IS NULL';
	} else {
		$filter = $wpdb->prepare(' AND t.slug = %s', $type);
	}

	if ($period_days === false) {
		$order = $order_dmy === false ? ' ORDER BY LENGTH(p.post_title) DESC, p.post_title ASC' : ' ORDER BY SUBSTR(p.post_title, -2) ASC, SUBSTR(p.post_title, -5, 2) ASC, LEFT(p.post_title, LENGTH(p.post_title) - 6) ASC';
	} else {
		$order = ' ORDER BY SUBSTR(p.post_title, -5, 2) ASC, SUBSTR(p.post_title, -2) ASC, LEFT(p.post_title, LENGTH(p.post_title) - 6) ASC';
	}

	$order .= ', p.post_content ASC';

	$sql = "SELECT p.ID, p.post_title AS event_date, p.post_content AS event_name, t.name AS event_type
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy='event_type'
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'tdih_event' " . $when . $filter . $order;

	$events = $wpdb->get_results($sql);

	if (empty($events)) {
		$no_events = isset($options['no_events']) ? $options['no_events'] : '';
		return $no_events ? '<p>' . esc_html($no_events) . '</p>' : '';
	}

	$class = $attrs['class'] !== '' ? ' ' . sanitize_html_class($attrs['class']) : '';
	$text = '<table class="tdih_table' . $class . '">';

	if ($show_head) {
		$text .= '<thead><tr>';
		if ($show_date) {
			$text .= '<th class="tdih_event_date">' . esc_html__('Date', 'this-day-in-history') . '</th>';
		}
		if ($show_age) {
			$text .= '<th class="tdih_event_age">'.esc_html__('Age', 'this-day-in-history').'</th>';
		}
		if ($show_type) {
			$text .= '<th class="tdih_event_type">' . esc_html__('Type', 'this-day-in-history') . '</th>';
		}
		$text .= '<th class="tdih_event_name">' . esc_html__('Event', 'this-day-in-history') . '</th>';
		$text .= '</tr></thead>';
	}

	$text .= '<tbody>';
	foreach ($events as $e) {
		$text .= '<tr>';

		if ($show_date) {
			$text .= '<td class="tdih_event_date">' . tdih_format_date_for_display( $e->event_date, $date_format ) . '</td>';
		}

		if ($show_age) {
			$d = new DateTime($events[$e]->event_date);
			$now = new DateTime();
			$interval = $now->diff($d);
			$age = $interval->y;
			$text .= '<td class="tdih_event_age">'.$age.'</td>';
		}

		if ($show_type) {
			$text .= '<td class="tdih_event_type">' . esc_html($e->event_type) . '</td>';
		}

		$extended = get_extended($e->event_name);
		$main = isset($extended['main']) ? wp_kses_post($extended['main']) : '';
		$more = isset($extended['extended']) ? $extended['extended'] : '';

		$text .= '<td class="tdih_event_name">';
		$link = esc_url(get_permalink(absint($e->ID)));

		if ($show_link === 2 || ($show_link === 1 && !empty($more))) {
			$text .= '<a href="' . $link . '">' . $main . '</a>';
		} else {
			$text .= $main;
			if (!empty($more)) {
				$more_text = !empty($extended['more_text']) ? $extended['more_text'] : __('More &#8230;', 'this-day-in-history');
				$text .= ' <a href="' . $link . '">' . esc_html($more_text) . '</a>';
			}
		}

		$text .= '</td></tr>';
	}
	$text .= '</tbody></table>';

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

// Helper: return a prepared BETWEEN clause comparing post_title month/day.
// Note: percent signs must be doubled (%%) inside the format string for $wpdb->prepare.
function tdih_prepare_between_clause($start, $stop)
{
	global $wpdb;
	return $wpdb->prepare(
		" AND CASE SUBSTR(p.post_title, 1, 1)
            WHEN '-' THEN DATE_FORMAT(SUBSTR(p.post_title, 2), '%%m%%d')
            ELSE DATE_FORMAT(p.post_title, '%%m%%d')
         END BETWEEN %s AND %s",
		$start->format('md'),
		$stop->format('md')
	);
}


function tdih_when_clause($period, $period_days, $day, $month, $year = false)
{
	global $wpdb;

	// Validate and normalise inputs
	$period = is_string($period) ? trim($period) : '';
	$allowed_periods = array('', 'a', 'm', 'c', 'l', 'n', 'w', 'y');
	if (!in_array($period, $allowed_periods, true)) {
		$period = '';
	}

	$period_days = absint($period_days);
	$period_days = $period_days > 0 ? min((int) $period_days, 365) : 0; // cap reasonable range

	$day = is_numeric($day) ? intval($day) : false;
	$day = ($day > 0 && $day < 32) ? $day : false;

	$month = is_numeric($month) ? intval($month) : false;
	$month = ($month > 0 && $month < 13) ? $month : false;

	if ($year !== false) {
		$year = intval($year);
		if ($year < -9999 || $year > 9999) {
			$year = false;
		}
	}

	// Use immutable DateTime to avoid accidental mutation and use WP timestamp
	$ts = (int) current_time('timestamp');
	try {
		$start = (new DateTimeImmutable())->setTimestamp($ts);
	} catch (Exception $e) {
		// fallback to safe server time
		$start = new DateTimeImmutable();
	}
	$stop = $start;

	// Helper to safely extend stop relative to start when period_days requested
	$extend_stop = function (DateTimeImmutable $base, $days) {
		if ($days <= 0) {
			return $base;
		}
		return $base->modify('+' . (int) $days . ' days');
	};

	// Period handling
	if ($period) {
		switch ($period) {
			case 'a': // all
				return '';

			case 'm': // tomorrow
				$start = $start->modify('+1 day');
				if ($period_days) {
					// include additional days starting from tomorrow
					$stop = $extend_stop($start, $period_days - 1);
					return tdih_prepare_between_clause($start, $stop);
				}
				return $wpdb->prepare(" AND SUBSTR(p.post_title, -5) = %s", $start->format('m-d'));

			case 'y': // yesterday
				$start = $start->modify('-1 day');
				if ($period_days) {
					$stop = $extend_stop($start, $period_days - 1);
					return tdih_prepare_between_clause($start, $stop);
				}
				return $wpdb->prepare(" AND SUBSTR(p.post_title, -5) = %s", $start->format('m-d'));

			case 'c': // centred (3 days either side)
				$start = $start->modify('-3 days');
				$stop = $start->modify('+6 days');
				if ($period_days) {
					$stop = $extend_stop($start, $period_days - 1);
				}
				return tdih_prepare_between_clause($start, $stop);

			case 'l': // last week
			case 'n': // next week
			case 'w': // week (this week)
				// Compute start of week (Monday) and adjust
				$weekday = intval($start->format('N')); // 1 (Mon) .. 7 (Sun)
				$week_start = $start->modify('-' . ($weekday - 1) . ' days'); // Monday
				if ($period === 'l') {
					$week_start = $week_start->modify('-7 days');
				} elseif ($period === 'n') {
					$week_start = $week_start->modify('+7 days');
				}
				$week_stop = $week_start->modify('+6 days');

				if ($period_days) {
					$week_stop = $extend_stop($week_start, $period_days - 1);
				}

				return tdih_prepare_between_clause($week_start, $week_stop);

			default:
				// Unknown handled above; fall through
				return '';
		}
	}

	// No period specified: support explicit year/month/day filters
	$clauses = '';

	if ($year || $month || $day) {
		if ($year) {
			// Year stored in left part of post_title; ensure consistent padding for negative years
			$year_str = ($year < 0) ? sprintf("%05d", $year) : sprintf("%04d", $year);
			$clauses .= $wpdb->prepare(" AND LEFT(p.post_title, LENGTH(p.post_title) - 6) = %s", $year_str);
		}

		if ($month && $day) {
			$md = sprintf("%02d-%02d", $month, $day);
			$clauses .= $wpdb->prepare(" AND SUBSTR(p.post_title, -5) = %s", $md);
		} else {
			if ($month) {
				$mstr = sprintf("%02d", $month);
				$clauses .= $wpdb->prepare(" AND SUBSTR(p.post_title, -5, 2) = %s", $mstr);
			}
			if ($day) {
				$dstr = sprintf("%02d", $day);
				$clauses .= $wpdb->prepare(" AND SUBSTR(p.post_title, -2) = %s", $dstr);
			}
		}

		return $clauses;
	}

	// Default: today
	return $wpdb->prepare(" AND SUBSTR(p.post_title, -5) = %s", $start->format('m-d'));
}

?>
