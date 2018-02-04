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
			<div class="post-single-entry-header"> <!-- Start .single-entry-header -->
				<?php echo "{$image_background_elem}"; ?>
				<div class="heading-entry-wrap">
					<!-- Cat & tag -->
				  <div class="cat-meta">
				    <?php echo ! empty( $alone_post_options['category_list'] ) ? '<div class="post-category">' . $alone_post_options['category_list'] . '</div>' : ''; ?>
				  </div>

					<!-- title -->
				  <?php echo "{$alone_post_options['title']}"; ?>

					<div class="extra-meta">
				    <!-- post date -->
				    <div class="post-date" title="<?php _e('Date', 'alone'); ?>">
				      <?php echo "{$alone_post_options['date']}"; ?>
				    </div>

				    <!-- GEWIJZIGD: Andere metadata niet tonen -->

				  </div>
				</div>
			</div> <!-- End .single-entry-header -->
			<div class="row">
				<div class="col-md-2">
					<?php echo alone_share_post(array('facebook' => true, 'twitter' => true, 'google_plus' => true, 'linkedin' => true, 'pinterest' => false));//echo do_shortcode('[x_share title="'. esc_html__(' ', 'alone') .'" facebook="true" twitter="true" google_plus="true" linkedin="true" pinterest="true"]'); ?>
				</div>
				<div class="col-md-10">
					<div class="post-single-content-text">
						<?php
						/* content */
						the_content();

						// GEWIJZIGD: Link naar vorige / volgende post niet tonen

						$skus = explode( ',', get_post_meta( get_the_ID(), 'oft-post-product', true ) );
						if ( count($skus) > 0 ) {
							echo '<div class="woocommerce columns-3">';
							woocommerce_product_loop_start();
							foreach ( $skus as $sku ) {
								// Kan het een geldig artikelnummer zijn?
								if ( intval($sku) > 10000 ) {
									$post_object = get_post( wc_get_product_id_by_sku($sku) );
									if ( $post_object !== NULL ) {
										setup_postdata( $GLOBALS['post'] =& $post_object );
										wc_get_template_part( 'content', 'product' );
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
