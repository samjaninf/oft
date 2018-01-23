<?php
// $alone_general_posts_options = alone_general_posts_options();
$alone_enable_related_articles  = defined( 'FW' ) ? fw_get_db_customizer_option( 'post_settings/posts_single/related_articles', '' ) : array('selected' => 'yes');

if( $alone_enable_related_articles['selected'] != 'yes') return;

$alone_related_articles = alone_related_articles();

if ( ! empty( $alone_related_articles ) ) {
	echo '[vc_section css=".vc_custom_1497422831700{padding-top: 65px !important;padding-bottom: 65px !important;}"][vc_row][vc_column][vc_masonry_grid post_type="post" max_items="8" show_filter="yes" element_width="3" gap="20" filter_size="lg" item="3508" initial_loading_animation="none" grid_id="vc_gid:1516716843419-4115c77b-d6a2-10" filter_source="category"][/vc_column][/vc_row][/vc_section]';
}
