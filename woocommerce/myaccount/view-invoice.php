<?php
/**
 * View Invoice
 *
 * Shows the details of a particular invoice on the account page.
 *
 * @author  Full Stack Ahead
 * @package WooCommerce/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $invoice ) {
	return;
}

// $show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
// TO DO: Datumformaat corrigeren in XML
$date = wc_string_to_datetime( str_replace( '/', '-', $invoice->OrderHeader->OrderCreditDatum->__toString() ) );

?>
<p><?php
	/* translators: 1: order number 2: order date 3: order status */
	printf(
		__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'woocommerce' ),
		'<mark class="order-number">' . $invoice->OrderHeader->OrderCreditRef->__toString() . '</mark>',
		'<mark class="order-date">' . wc_format_datetime( $date ) . '</mark>',
		'<mark class="order-status">' . wc_get_order_status_name('completed') . '</mark>'
	);
?></p>

<section class="woocommerce-order-details">
	<h2 class="woocommerce-order-details__title"><?php _e( 'Order details', 'woocommerce' ); ?></h2>

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
		<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php _e( 'Product', 'woocommerce' ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php _e( 'Total', 'woocommerce' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
				foreach ( $invoice->OrderLines->OrderLine as $line ) {
					?>
					<td><?php $line->Artikel->__toString(); ?></td>
					<td><?php $line->BestelCreditBedrag->__toString(); ?></td>
					<?php
				}
			?>
		</tbody>

		<tfoot>
			<tr>
				<th scope="row"><?php _e( 'Klantnummer', 'oft' ); ?></th>
				<td><?php echo $invoice->OrderHeader->KlantNr->__toString() ?></td>
			</tr>
		</tfoot>
	</table>
</section>
