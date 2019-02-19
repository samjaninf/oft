<?php
$TBFW = defined( 'FW' );
$alone_post_options = alone_single_post_options( $post->ID );
$alone_related_articles_type = ! empty( $TBFW ) ? fw_get_db_settings_option( 'posts_settings/related_articles/yes/related_type', 'related-articles-1' ) : 'related-articles-1';
$alone_is_builder = alone_fw_ext_page_builder_is_builder_post($post->ID);
$alone_general_posts_options = alone_general_posts_options();

$image_background_elem = '';
if ( has_post_thumbnail() ) { // check if the post has a Post Thumbnail assigned to it.
  $style_inline = "background: url(". get_the_post_thumbnail_url($post->ID, $alone_post_options['image_size']) .") center center;";
  $image_background_elem = "<div class='post-sing-image-background' style='{$style_inline}' data-stellar-background-ratio='0.8'></div>";
}

$article_classes = array(
	'post',
	'post-details',
	'clearfix',
	'post-single-creative-layout-' . $alone_general_posts_options['blog_type'],
);
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( implode(' ', $article_classes) ); ?> itemscope="itemscope" itemtype="http://schema.org/BlogPosting" itemprop="blogPost">
	<div class="col-inner">
		<div class="entry-content clearfix" itemprop="text">
			<div class="row">
				<div class="col-md-2"></div>
				<div class="col-md-10">
					<div class="post-single-content-text">
						<div class="extra-meta" style="font-size: 80%;">
							<div class="post-date">
								<?php echo "{$alone_post_options['date']}"; ?>
								<?php echo ! empty( $alone_post_options['category_list'] ) ? ' &mdash; '.$alone_post_options['category_list'] : ''; ?>
							</div>
						</div>

						<!-- title -->
						<?php echo "{$alone_post_options['title']}"; ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-2">
					<?php echo alone_share_post( array( 'facebook' => true, 'twitter' => true, 'google_plus' => false, 'linkedin' => true, 'pinterest' => false ) ); ?>
				</div>
				<div class="col-md-10">
					<div class="post-single-content-text">
						<?php
						/* content */
						the_content();

						// GEWIJZIGD: Link naar vorige / volgende post niet tonen

						// GEWIJZIGD: Gelinkte producten toevoegen onder bericht
						$skus = get_post_meta( get_the_ID(), 'oft_post_products', true );
						if ( count($skus) > 0 ) {
							global $sitepress;
							echo '<div class="woocommerce columns-3">';
							woocommerce_product_loop_start();
							foreach ( $skus as $sku ) {
								// Kan het een geldig artikelnummer zijn?
								if ( intval($sku) > 10000 ) {
									$post_object = get_post( apply_filters( 'wpml_object_id', wc_get_product_id_by_sku($sku), 'product', false, $sitepress->get_current_language() ) );
									if ( $post_object !== NULL ) {
										setup_postdata( $GLOBALS['post'] =& $post_object );
										// Voorlopig weer uitgeschakelen, is niet zo mooi
										// wc_get_template_part( 'content', 'product' );
										wp_reset_postdata();
									}
								}
							}
							woocommerce_product_loop_end();
							echo '</div>';
						}

						/* tags */
						if(isset($alone_post_options['tag_list']) && ! empty($alone_post_options['tag_list'])) {
							echo "<div class='single-entry-tag'>". esc_html__('Tags: ', 'alone') . "{$alone_post_options['tag_list']}</div>";
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</article>
<hr />
<?php get_template_part( 'templates/related-articles/'.$alone_related_articles_type ); ?>
