<?php
// $alone_general_posts_options = alone_general_posts_options();
$alone_enable_related_articles  = defined( 'FW' ) ? fw_get_db_customizer_option( 'post_settings/posts_single/related_articles', '' ) : array('selected' => 'yes');

if( $alone_enable_related_articles['selected'] != 'yes') return;

// Parameter voor aantal posts wordt niet uitgelezen, zie fix in theme-inclues/helpers.php
$alone_related_articles = alone_related_articles(4);

if ( ! empty( $alone_related_articles ) ) : ?>
	<div class="bt-wrap-related-article bt-<?php echo basename(__FILE__, '.php'); ?>">
		<div class="row">
			<div class="col-md-12">
				<h4><?php esc_html_e( 'Related Articles', 'alone' ); ?></h4>
			</div>
			<div class="related-article-list">
				<?php foreach ( $alone_related_articles as $item ) :
					$post_settings = alone_get_settings_by_post_id($item->ID);
					$wrap_title	= isset($posts_general_settings['blog_title']['selected']) ? $posts_general_settings['blog_title']['selected'] : 'h4';
					$image_size	= 'medium';

					// Levert een array op met 'main' (voor) en 'extended' (na de 'more'-tag)
					$fix_post_content = get_extended( $item->post_content );

					$post_data = array(
						'title_link' => "<a href='". get_permalink($item->ID) ."' class='post-title-link'><{$wrap_title} class='post-title oft-grid-post-title' style='font-weight: bold; letter-spacing: 1px; padding: 0 1em;'>". $item->post_title ."</{$wrap_title}></a>",
						'featured_image' => "<a href='". get_permalink($item->ID) ."' title='". $item->post_title ."'>" . alone_get_image(get_post_thumbnail_id($item->ID), array('size' => $image_size)) . "</a>",
						// Strip shortcodes (o.a. WPBakery) maar bewaar de tekstinhoud (geen strip_shortcodes() gebruiken!)
						'trim_content' => preg_replace( "~(?:\[/?)[^/\]]+/?\]~s", '', wp_trim_words( ! empty($item->post_excerpt) ? $item->post_excerpt : $fix_post_content['main'], $num_words = 50, $more = '...' ) ),
					);
				?>
					<div class="col-md-3 col-sm-3" style="padding: 0 1em;">
						<div class="related-article-item" style="background-color: #f4f4f4; padding: 0; border: 0;">
							<?php echo "{$post_data['featured_image']}"; ?>
							<?php echo "{$post_data['title_link']}"; ?>
							<?php echo "<p class='oft-grid-post-excerpt' style='padding: 0 1em 2em 1em;'>{$post_data['trim_content']}</p>"; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php wp_reset_query(); ?>
			</div>
		</div>
	</div>
<?php endif; ?>
