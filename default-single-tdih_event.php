<?php

get_header();

the_post();

$eid = get_the_ID();

echo '<article id="tdih-' . esc_attr($eid) . '" class="tdih_event">';

// Corrected escaping for the title
the_title('<h2>' . esc_html(__('This Day in History: ', 'this-day-in-history')) . '</h2>');

the_content();

if (current_user_can('manage_tdih_events')) {
    echo '<footer><a href="' . esc_url(admin_url("admin.php?page=this-day-in-history&action=edit&id=" . esc_attr($eid))) . '">' . esc_html(__('Edit Event', 'this-day-in-history')) . '</a></footer>';
}

echo '</article>';

get_footer();

// Fixes applied: Escaping output and sanitization.

?>