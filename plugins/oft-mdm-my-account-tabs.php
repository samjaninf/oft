<?php
	/*
	Plugin Name: OFT/OMDM My Account Tabs
	Description: Voeg extra tabbladen toe om orders / facturen / crediteringen te raadplegen die niet via BestelWeb verliepen.
	Version:     1.2.0
	Author:      Full Stack Ahead
	Author URI:  https://www.fullstackahead.be
	Text Domain: oft
	*/

	defined('ABSPATH') or die('Access prohibited!');

	register_activation_hook( __FILE__, array( 'Custom_My_Account_Endpoint', 'install' ) );
	register_deactivation_hook( __FILE__, array( 'Custom_My_Account_Endpoint', 'install' ) );

	new Custom_My_Account_Endpoint();

	class Custom_My_Account_Endpoint {
		public static $endpoint;

		public function __construct() {
			self::$endpoint = __( 'facturen', 'oft' );
			// Niet in install() definiëren zodat we de slug dynamisch kunnen laten variëren volgens taal?
			add_action( 'init', array( $this, 'add_endpoints' ) );
			add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ), 0 );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'new_menu_items' ) );
			add_filter( 'woocommerce_endpoint_'.self::$endpoint.'_title', array( $this, 'endpoint_title' ) );
			add_action( 'woocommerce_account_'.self::$endpoint.'_endpoint', array( $this, 'endpoint_content' ) );
		}

		public function add_endpoints() {
			add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
		}

		public function add_query_vars( $vars ) {
			$vars[ self::$endpoint ] = self::$endpoint;
			return $vars;
		}

		public function new_menu_items( $items ) {
			$new_items = array();
			foreach ( $items as $slug => $title ) {
				// Schakel bepaalde knoppen uit
				if ( $slug !== 'dashboard' and $slug !== 'downloads' ) {
					$new_items[$slug] = $title;
				}
				if ( $slug === 'orders' ) {
					// Voeg menuknop toe net na 'Bestellingen'
					$new_items[ self::$endpoint ] = __( 'Facturen', 'oft' );
				}
			}
			return $new_items;
		}

		public function endpoint_title( $title ) {
			return __( 'Facturen', 'oft' );
		}

		public function endpoint_content() {
			$my_orders_columns = array( 'order-number' => __( 'Odisy-referentie', 'oft' ), 'order-status' => __( 'Status', 'oft' ), 'order-total' => __( 'Totaal', 'oft' ) );
			$customer_orders = array();

			// Lees het lokale XML-bestand
			$order_data = simplexml_load_file( WP_CONTENT_DIR.'/odisy/export/orders.xml' );
			
			if ( $order_data !== false ) {
				
				foreach ( $order_data->Order as $order ) {
					$cnt++;
					$header = $order->OrderHeader;
					$lines = $order->OrderLines;
					$parts = explode( ' ', $header->OrderCreditRef->__toString() );
					$order_number = $parts[0];
					$order_type = $parts[1];
					
					// Te vervangen door de parameters van de ingelogde klant!
					if ( intval( $header->KlantNr ) === 2128 and intval( $header->LeverNr ) === 0 ) {
						// Haal enkel gefactureerde normale orders op
						if ( $header->OrderCreditStatus->__toString() === 'gefactureerd' and ( $order_type === 'N' or $order_type === 'CH' ) ) {
							$customer_orders[$order_number] = $order;
						}
					}
				}

			} else {
				echo "ERROR LOADING XML<br/>";
			}

			?>
			<table class="shop_table shop_table_responsive my_account_orders">
				<thead>
					<tr>
						<?php foreach ( $my_orders_columns as $column_id => $column_name ) : ?>
							<th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $customer_orders as $customer_order_number => $customer_order ) :
						// Methode schrijven?
						$item_count = $customer_order->OrderLines->count();
						$date = wc_string_to_datetime( $customer_order_data->OrderHeader->OrderCreditDatum->__toString() );
						?>
						<tr class="order">
							<?php foreach ( $my_orders_columns as $column_id => $column_name ) : ?>
								<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
									<?php if ( 'order-number' === $column_id ) : ?>
										<a href="<?php echo esc_url(''); ?>">
											<?php echo $customer_order_number; ?>
										</a>

									<?php elseif ( 'order-date' === $column_id ) : ?>
										<time datetime="<?php echo esc_attr( $date->date('c') ); ?>"><?php echo esc_html( wc_format_datetime( $date ) ); ?></time>

									<?php elseif ( 'order-status' === $column_id ) : ?>
										<?php echo esc_html( $customer_order->OrderHeader->OrderCreditStatus->__toString() ); ?>

									<?php elseif ( 'order-total' === $column_id ) : ?>
										<?php
										$total = 0;
										foreach ( $customer_order->OrderLines as $line ) {
											$total += floatval( $line->BestelCreditBedrag->__toString() );
										}
										printf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce' ), $total, $item_count );
										?>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		public static function install() {
			flush_rewrite_rules();
		}
	}
?>