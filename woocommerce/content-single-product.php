<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$alone_sidebar_position = function_exists( 'fw_ext_sidebars_get_current_position' ) ? fw_ext_sidebars_get_current_position() : 'right';
alone_title_bar();
?>
<section class="bt-main-row bt-section-space <?php alone_get_content_class('main', $alone_sidebar_position); ?>" role="main" itemprop="mainEntity" itemscope="itemscope" itemtype="http://schema.org/Blog">
	<div class="container">
		<div class="row">
			<div class="bt-content-area <?php alone_get_content_class( 'content', $alone_sidebar_position ); ?>">
				<div class="bt-col-inner">
          <!-- GEWIJZIGD: Breadcrumb weer tonen -->
					<?php if( function_exists('fw_ext_breadcrumbs') ) fw_ext_breadcrumbs(); ?>
          <div itemscope itemtype="" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php
            	/**
            	 * woocommerce_before_single_product hook.
            	 *
            	 * @hooked wc_print_notices - 10
            	 */
            	 do_action( 'woocommerce_before_single_product' );

            	 if ( post_password_required() ) {
            	 	echo get_the_password_form();
            	 	return;
            	 }
            ?>

          	<?php
          		/**
          		 * woocommerce_before_single_product_summary hook.
          		 *
          		 * @hooked woocommerce_show_product_sale_flash - 10
          		 * @hooked woocommerce_show_product_images - 20
          		 */
          		do_action( 'woocommerce_before_single_product_summary' );
          	?>

          	<div class="summary entry-summary">

          		<?php
          			/**
          			 * woocommerce_single_product_summary hook.
          			 *
          			 * @hooked woocommerce_template_single_title - 5
          			 * @hooked woocommerce_template_single_rating - 10
          			 * @hooked woocommerce_template_single_price - 10
          			 * @hooked woocommerce_template_single_excerpt - 20
          			 * @hooked woocommerce_template_single_add_to_cart - 30
          			 * @hooked woocommerce_template_single_meta - 40
          			 * @hooked woocommerce_template_single_sharing - 50
          			 */
          			do_action( 'woocommerce_single_product_summary' );
          		?>

          	</div><!-- .summary -->

          	<?php
          		/**
          		 * woocommerce_after_single_product_summary hook.
          		 *
          		 * @hooked woocommerce_output_product_data_tabs - 10
          		 * @hooked woocommerce_upsell_display - 15
          		 * @hooked woocommerce_output_related_products - 20
          		 */
          		do_action( 'woocommerce_after_single_product_summary' );
          	?>

          	<meta itemprop="url" content="<?php the_permalink(); ?>" />

          </div><!-- #product-<?php the_ID(); ?> -->
        </div>
      </div><!-- /.fw-content-area-->
      <?php get_sidebar(); ?>
    </div><!-- /.fw-row-->
  </div><!-- /.fw-container-->
</section>
<?php do_action( 'woocommerce_after_single_product' ); ?>
