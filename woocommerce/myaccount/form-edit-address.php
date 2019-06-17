<?php
/**
 * Edit address form
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? __( 'Billing address', 'woocommerce' ) : __( 'Shipping address', 'woocommerce' );

do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>
	<?php _e( 'Het bewerken van adressen via de front-end is niet toegestaan. Contacteer een beheerder die toegang heeft tot de back-end.', 'oftc' ); ?>
<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
