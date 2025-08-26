<?php

get_header();

the_post();

$eid = absint( get_the_ID() );

echo '<article id="tdih-' . esc_attr( $eid ) . '" class="tdih_event">';

the_title( '<h2>' . esc_html__( 'This Day in History: ', 'this-day-in-history' ), '</h2>' );

the_content();

if ( current_user_can( 'manage_tdih_events' ) ) {
    $edit_url = add_query_arg(
        array(
            'page'   => 'this-day-in-history',
            'action' => 'edit',
            'id'     => $eid,
        ),
        admin_url( 'admin.php' )
    );
    echo '<footer><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit Event', 'this-day-in-history' ) . '</a></footer>';
}

echo '</article>';

get_footer();

?>
