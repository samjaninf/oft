<?php
/**
 * Related Products
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $related_products ) : ?>

	<section class="related products">

		<p>&nbsp;</p>

		<h2><?php esc_html_e( 'Related products', 'alone' ); ?></h2>

		<!-- Gebruik de standaard (gemodificeerde) WooCommerce-layout voor consequente stijlgeving -->
		<div class="woocommerce columns-4">

		<?php woocommerce_product_loop_start(); ?>
			
			<?php foreach ( $related_products as $related_product ) : ?>
				<?php
					$post_object = get_post( $related_product->get_id() );

					setup_postdata( $GLOBALS['post'] =& $post_object );

					wc_get_template_part( 'content', 'product' );
				?>
			<?php endforeach; ?>
		<?php woocommerce_product_loop_end(); ?>

		</div>

	</section>

<?php endif;

wp_reset_postdata();
