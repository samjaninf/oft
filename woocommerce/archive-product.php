<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alone_customizer_shop_option = function_exists('fw_get_db_customizer_option') ? fw_get_db_customizer_option('shop_settings') : array('products_in_row' => 4);

get_header( 'shop' );
alone_title_bar();
$alone_sidebar_position = function_exists( 'fw_ext_sidebars_get_current_position' ) ? fw_ext_sidebars_get_current_position() : 'right';
?>
<section class="bt-main-row bt-section-space <?php alone_get_content_class('main', $alone_sidebar_position); ?>" role="main" itemprop="mainEntity" itemscope="itemscope" itemtype="http://schema.org/Blog">
	<div class="container">
		<div class="row">
			<div class="bt-content-area <?php alone_get_content_class( 'content', $alone_sidebar_position ); ?>">
				<div class="bt-col-inner">
					<!-- GEWIJZIGD: Breadcrumb weer tonen -->
					<?php if ( function_exists('woocommerce_breadcrumb') ) woocommerce_breadcrumb(); ?>
					<?php
						/**
						 * Hook: woocommerce_before_main_content.
						 *
						 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
						 * @hooked woocommerce_breadcrumb - 20
						 * @hooked WC_Structured_Data::generate_website_data() - 30
						 */
						do_action( 'woocommerce_before_main_content' );
					?>
					
					<header class="woocommerce-products-header">
						<?php if ( apply_filters( 'woocommerce_show_page_title', false ) ) : ?>
							<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
						<?php endif; ?>

						<?php
							/**
							 * Hook: woocommerce_archive_description.
							 *
							 * @hooked woocommerce_taxonomy_archive_description - 10
							 * @hooked woocommerce_product_archive_description - 10
							 */
							do_action( 'woocommerce_archive_description' );
						?>
					</header>
					
						<?php if ( have_posts() ) : ?>

							<?php
								/**
								 * Hook: woocommerce_before_shop_loop.
								 *
								 * @hooked wc_print_notices - 10
								 * @hooked woocommerce_result_count - 20
								 * @hooked woocommerce_catalog_ordering - 30
								 */
								do_action( 'woocommerce_before_shop_loop' );
							?>

							<?php woocommerce_product_loop_start(); ?>
							
							<div class="woocommerce-product-subcategories-wrap">
								<?php woocommerce_product_subcategories(); ?>
							</div>

							<?php if ( wc_get_loop_prop( 'total' ) ) : ?>
								<div class="bt-row">
									<?php while ( have_posts() ) : the_post(); ?>
										<?php
											/**
											 * Hook: woocommerce_shop_loop.
											 *
											 * @hooked WC_Structured_Data::generate_product_data() - 10
											 */
											do_action( 'woocommerce_shop_loop' );
										?>
										<div class="bt-col-<?php echo esc_attr( (int) $alone_customizer_shop_option['products_in_row']); ?> product-item">
											<?php wc_get_template_part( 'content', 'product' ); ?>
										</div>
									<?php endwhile; ?>
								</div>
							<?php endif; ?>
							
							<?php woocommerce_product_loop_end(); ?>

							<?php
								/**
								 * Hook: woocommerce_after_shop_loop.
								 *
								 * @hooked woocommerce_pagination - 10
								 */
								do_action( 'woocommerce_after_shop_loop' );
							?>

						<?php else : ?>

							<?php
								/**
								 * Hook: woocommerce_no_products_found.
								 *
								 * @hooked wc_no_products_found - 10
								 */
								do_action( 'woocommerce_no_products_found' );
							?>

						<?php endif; ?>

					<?php
						/**
						 * Hook: woocommerce_after_main_content.
						 *
						 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
						 */
						do_action( 'woocommerce_after_main_content' );
					?>

					<?php
						/**
						 * Hook: woocommerce_sidebar.
						 *
						 * @hooked woocommerce_get_sidebar - 10
						 */
						do_action( 'woocommerce_sidebar' );
					?>
				</div>
			</div><!-- /.bt-content-area-->
			<?php get_sidebar('shop'); ?>
		</div><!-- /.row-->
	</div><!-- /.container-->
</section>
<?php get_footer( 'shop' ); ?>