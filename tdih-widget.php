<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class this_day_in_history_widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'this_day_in_history_widget',
            __('This Day In History', 'this-day-in-history'),
            array(
                'classname' => 'widget_this_day_in_history',
                'description' => __('Lists the events of a given type and period.', 'this-day-in-history')
            )
        );
    }

    public function widget($args, $instance)
    {
        global $wpdb;

        extract($args, EXTR_SKIP);

        $options = get_option('tdih_options');

        $title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

        $instance['show_link'] = isset($instance['show_link']) ? absint($instance['show_link']) : 0;
        $instance['max_rows'] = isset($instance['max_rows']) ? absint($instance['max_rows']) : 0;
        $instance['excluded'] = isset($instance['excluded']) ? $instance['excluded'] : '';

        $show_age = !empty($instance['show_age']);
        $show_link = $instance['show_link'];
        $show_type = !empty($instance['show_type']);
        $show_year = !empty($instance['show_year']);

        $type = $instance['type'] === ']*[' ? false : sanitize_text_field($instance['type']);
        $max_rows = absint($instance['max_rows']);
        $period = sanitize_text_field($instance['period']);
        $excluded = empty($instance['excluded']) ? '' : $wpdb->prepare(
            " AND COALESCE(t.term_id, '') NOT IN (%s)",
            implode(',', array_map('absint', (array) $instance['excluded']))
        );

        $when = $this->when_clause($period);

        $filter = $type === false ? '' : ($type == '' ? ' AND t.slug IS NULL' : $wpdb->prepare(" AND t.slug = %s", $type));

        $order = $show_type ? ' ORDER BY t.name ASC,' : ' ORDER BY';
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
            $excluded . $order . $limit
        );

        $events = $wpdb->get_results($query);

        if (!empty($events)) {
            $event_type = '.';

            echo $before_widget;

            echo $before_title . esc_html($title) . $after_title;

            echo '<dl class="tdih">';

            foreach ($events as $e => $values) {
                if ($show_type) {
                    if ($events[$e]->event_type != $event_type) {
                        $event_type = $events[$e]->event_type;
                        echo '<dt class="tdih_event_type">' . esc_html($events[$e]->event_type) . '</dt>';
                    }
                }

                echo '<dd>';

                if ($show_year) {
                    $year = $events[$e]->event_year == 0 ? '' : (substr($events[$e]->event_year, 0, 1) == '-' ? substr($events[$e]->event_year, 1) . ($options['era_mark'] == 1 ? __(' BC', 'this-day-in-history') : __(' BCE', 'this-day-in-history')) : $events[$e]->event_year);

                    if (!empty($instance['prefix'])) {
                        echo '<span class="tdih_prefix_text">' . esc_html($instance['prefix']) . '</span> ';
                    }

                    echo '<span class="tdih_event_year">' . esc_html($year) . '</span>';
                }

                $event_title = get_extended($events[$e]->event_name);

                echo ' <span class="tdih_event_name">';

                $event_text = apply_filters('widget_text', $event_title['main'], $instance, $this);

                if ($show_link == 2 || ($show_link == 1 && $event_title['extended'])) {
                    echo '<a href ="' . esc_url(get_post_permalink($events[$e]->ID)) . '">' . esc_html(trim($event_text)) . '</a>';
                } else {
                    echo esc_html($event_text);

                    if ($event_title['extended']) {
                        echo ' <a href ="' . esc_url(get_post_permalink($events[$e]->ID)) . '">' . esc_html($event_title['more_text'] ? $event_title['more_text'] : __('More &#8230;', 'this-day-in-history')) . '</a>';
                    }
                }

                echo '</span>';

                if ($show_age && $events[$e]->event_year != 0) {
                    $age = intval(current_time('Y')) - intval($events[$e]->event_year);
                    echo ' <span class="tdih_event_age">(' . esc_html($age) . ')</span>';
                }

                echo '</dd>';
            }

            echo '</dl>';

            echo $after_widget;
        } else {
            if (!empty($options['no_events'])) {
                echo $before_widget;
                echo $before_title . esc_html($title) . $after_title;
                echo '<p>' . esc_html($options['no_events']) . '</p>';
                echo $after_widget;
            }
        }
    }

    public function form($instance)
    {

        $instance = wp_parse_args((array) $instance, array('title' => __('This Day In History', 'this-day-in-history'), 'show_age' => 0, 'show_link' => 0, 'show_type' => 1, 'show_year' => 1, 'type' => ']*[', 'max_rows' => 0, 'period' => 't', 'excluded' => '', 'prefix' => ''));

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'this-day-in-history'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text"
                value="<?php echo esc_attr($instance['title']) ?>" />
        </p>
        <p>
            <input id="<?php echo $this->get_field_id('show_age'); ?>" name="<?php echo $this->get_field_name('show_age'); ?>"
                type="checkbox" value="1" <?php if ($instance['show_age'])
                    echo 'checked="checked"'; ?> />
            <label
                for="<?php echo $this->get_field_id('show_age'); ?>"><?php _e('Show event age', 'this-day-in-history'); ?></label>
            <br>
            <input id="<?php echo $this->get_field_id('show_year'); ?>" name="<?php echo $this->get_field_name('show_year'); ?>"
                type="checkbox" value="1" <?php if ($instance['show_year'])
                    echo 'checked="checked"'; ?> />
            <label
                for="<?php echo $this->get_field_id('show_year'); ?>"><?php _e('Show year', 'this-day-in-history'); ?></label>
            <br>
            <input id="<?php echo $this->get_field_id('show_type'); ?>" name="<?php echo $this->get_field_name('show_type'); ?>"
                type="checkbox" value="1" <?php if ($instance['show_type'])
                    echo 'checked="checked"'; ?> />
            <label
                for="<?php echo $this->get_field_id('show_type'); ?>"><?php _e('Show event type', 'this-day-in-history'); ?></label>

        </p>
        <p>
            <label
                for="<?php echo $this->get_field_id('show_link'); ?>"><?php _e('Show Links:', 'this-day-in-history'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('show_link'); ?>"
                name="<?php echo $this->get_field_name('show_link'); ?>">
                <?php
                $selected = $instance['show_link'] == 0 ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="0"' . $selected . '>' . __('Show More... link when more tag is used', 'this-day-in-history') . '</option>';

                $selected = $instance['show_link'] == 1 ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="1"' . $selected . '>' . __('Link the Post title when more tag is used', 'this-day-in-history') . '</option>';

                $selected = $instance['show_link'] == 2 ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="2"' . $selected . '>' . __('Always link the post title', 'this-day-in-history') . '</option>';
                ?>
            </select>
        </p>
        <p>
            <label
                for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Filter events by type:', 'this-day-in-history'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('type'); ?>"
                name="<?php echo $this->get_field_name('type'); ?>">
                <?php
                $event_types = get_terms('event_type', 'hide_empty=0');

                $selected = $instance['type'] == ']*[' ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="]*["' . $selected . '>' . __('All event types', 'this-day-in-history') . '</option>';

                $selected = $instance['type'] == '' ? ' selected="selected"' : '';
                echo '<option class="theme-option" value=""' . $selected . '>' . __('No event type', 'this-day-in-history') . '</option>';

                if (count($event_types) > 0) {
                    foreach ($event_types as $event_type) {
                        $selected = $instance['type'] == $event_type->slug ? ' selected="selected"' : '';
                        echo '<option class="theme-option" value="' . $event_type->slug . '"' . $selected . '>' . $event_type->name . '</option>';
                    }
                }
                ?>
            </select>
        </p>
        <p>
            <label
                for="<?php echo $this->get_field_id('max_rows'); ?>"><?php _e('Number of events:', 'this-day-in-history'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('max_rows'); ?>"
                name="<?php echo $this->get_field_name('max_rows'); ?>">
                <?php
                $selected = $instance['max_rows'] == 0 ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="0"' . $selected . '>' . __('Show all events', 'this-day-in-history') . '</option>';

                $selected = $instance['max_rows'] == 1 ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="1"' . $selected . '>' . __('Show only 1 event', 'this-day-in-history') . '</option>';

                for ($p = 2; $p <= 8; $p++) {
                    $selected = $p == $instance['max_rows'] ? ' selected="selected"' : '';
                    echo '<option class="theme-option" value="' . $p . '"' . $selected . '>' . sprintf(__('Show up to %d events', 'this-day-in-history'), $p) . '</option>';
                }
                ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('period'); ?>"><?php _e('Period:', 'this-day-in-history'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('period'); ?>"
                name="<?php echo $this->get_field_name('period'); ?>">
                <?php
                $selected = $instance['period'] == 't' ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="t"' . $selected . '>' . __('Today', 'this-day-in-history') . '</option>';

                $selected = $instance['period'] == 'm' ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="m"' . $selected . '>' . __('Tomorrow', 'this-day-in-history') . '</option>';

                $selected = $instance['period'] == 'y' ? ' selected="selected"' : '';
                echo '<option class="theme-option" value="y"' . $selected . '>' . __('Yesterday', 'this-day-in-history') . '</option>';
                ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('excluded'); ?>">
                <?php _e('Event types to exclude:', 'this-day-in-history') ?></label>
            <select class="widefat" multiple="multiple" id="<?php echo $this->get_field_id('excluded'); ?>"
                name="<?php echo $this->get_field_name('excluded'); ?>[]">
                <?php
                $event_types = get_terms('event_type', 'hide_empty=0');

                $excluded = explode(',', esc_attr($instance['excluded']));

                foreach ($event_types as $type) {
                    $selected = in_array($type->term_id, $excluded) ? ' selected="selected"' : '';
                    echo '<option class="theme-option" value="' . $type->term_id . '"' . $selected . '>' . $type->name . '</option>';
                }
                ?>
            </select>
        </p>
        <p>
            <label
                for="<?php echo $this->get_field_id('prefix'); ?>"><?php _e('Prefix the year with this text:', 'this-day-in-history'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('prefix'); ?>"
                name="<?php echo $this->get_field_name('prefix'); ?>" type="text" placeholder="[nothing]"
                value="<?php echo esc_attr($instance['prefix']) ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {

        $instance = $old_instance;

        $instance['title'] = empty($new_instance['title']) ? __('This Day In History', 'this-day-in-history') : trim(strip_tags($new_instance['title']));

        $instance['show_age'] = isset($new_instance['show_age']) ? (int) $new_instance['show_age'] : 0;
        $instance['show_link'] = isset($new_instance['show_link']) ? (int) $new_instance['show_link'] : 0;
        $instance['show_type'] = isset($new_instance['show_type']) ? (int) $new_instance['show_type'] : 0;
        $instance['show_year'] = isset($new_instance['show_year']) ? (int) $new_instance['show_year'] : 0;
        $instance['max_rows'] = isset($new_instance['max_rows']) ? (int) abs($new_instance['max_rows']) : 0;
        $instance['period'] = isset($new_instance['period']) ? $new_instance['period'] : 't';
        $instance['type'] = isset($new_instance['type']) ? $new_instance['type'] : false;
        $instance['excluded'] = isset($new_instance['excluded']) ? implode(',', (array) $new_instance['excluded']) : '';

        $instance['prefix'] = empty($new_instance['prefix']) ? '' : trim(strip_tags($new_instance['prefix']));

        return $instance;
    }

    private function when_clause($period)
    {
        global $wpdb;

        $start = DateTime::createFromFormat('U', current_time('timestamp'));

        switch ($period) {
            case 'm':
                $start->add(new DateInterval('P1D'));
                break;

            case 'y':
                $start->sub(new DateInterval('P1D'));
                break;

            default:
                // Do nothing
        }

        return $wpdb->prepare(" AND SUBSTR(p.post_title, -5) = %s", $start->format('m-d'));
    }
}

add_action('widgets_init', function () {
    register_widget('this_day_in_history_widget');
});

?>