<?php

get_header();

the_post();

$eid = get_the_ID();

echo '<article id="tdih-'.$eid.'" class="tdih_event">';

the_title('<h2>'.__('This Day in History: ', 'this-day-in-history'), '</h2>');

the_content();

if (current_user_can('manage_tdih_events')) { echo '<footer><a href="'.admin_url("admin.php?page=this-day-in-history&action=edit&id=".$eid).'">'.__('Edit Event', 'this-day-in-history').'</a></footer>'; }

echo '</article>';

get_footer();

?>