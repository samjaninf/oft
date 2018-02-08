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

		<h2><?php esc_html_e( 'Related products', 'alone' ); ?></h2>

		<?php woocommerce_product_loop_start(); ?>
			<div class="bt-row">
			<?php foreach ( $related_products as $related_product ) : ?>
				<!-- GEWIJZIGD: Lay-out met 4 kolommen toepassen -->
				<div class="bt-col-4 product-item"><?php
					$post_object = get_post( $related_product->get_id() );

					setup_postdata( $GLOBALS['post'] =& $post_object );

					wc_get_template_part( 'content', 'product' ); ?>
				</div>
			<?php endforeach; ?>
			</div>
		<?php woocommerce_product_loop_end(); ?>

	</section>

<?php endif;

wp_reset_postdata();
