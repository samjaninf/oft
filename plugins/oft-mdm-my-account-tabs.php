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
	register_deactivation_hook( __FILE__, array( 'Custom_My_Account_Endpoint', 'uninstall' ) );

	new My_Account_Endpoint_Invoices( __( 'facturen', 'oft' ) );
	new My_Account_Endpoint_Credits( __( 'crediteringen', 'oft' ) );
	new My_Account_Endpoint_Others( __( 'overige', 'oft' ) );
	
	class My_Account_Endpoint_Others extends Custom_My_Account_Endpoint {
		public function add_menu_items( $items ) {
			$new_items = array();
			foreach ( $items as $slug => $title ) {
				$new_items[$slug] = $title;
				if ( $slug === 'orders' ) {
					// Voeg menuknop toe net na 'Bestellingen'
					$new_items[ $this->$endpoint ] = $this->endpoint_title();
				}
			}
			return $new_items;
		}

		public function endpoint_title() {
			return __( 'Overige bestellingen', 'oft' );
		}

		public function get_endpoint_statuses() {
			return array( 'in behandeling', 'verzonden' );
		}
	}

	class My_Account_Endpoint_Invoices extends Custom_My_Account_Endpoint {
		public function add_menu_items( $items ) {
			$new_items = array();
			foreach ( $items as $slug => $title ) {
				$new_items[$slug] = $title;
				if ( $slug === 'orders' ) {
					// Voeg menuknop toe net na 'Bestellingen'
					$new_items[ $this->$endpoint ] = $this->endpoint_title();
				}
			}
			return $new_items;
		}

		public function endpoint_title() {
			return __( 'Facturen', 'oft' );
		}

		public function get_endpoint_statuses() {
			return array( 'gefactureerd' );
		}
	}

	class My_Account_Endpoint_Credits extends Custom_My_Account_Endpoint {
		public function add_menu_items( $items ) {
			$new_items = array();
			foreach ( $items as $slug => $title ) {
				$new_items[$slug] = $title;
				if ( $slug === 'orders' ) {
					// Voeg menuknop toe net na 'Bestellingen'
					$new_items[ $this->$endpoint ] = $this->endpoint_title();
				}
			}
			return $new_items;
		}

		public function endpoint_title() {
			return __( 'Crediteringen', 'oft' );
		}

		public function get_endpoint_statuses() {
			return array( 'te crediteren', 'gecrediteerd' );
		}
	}

	class Custom_My_Account_Endpoint {
		protected $endpoint;

		public function __construct( $param ) {
			$this->$endpoint = $param;
			add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ) );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'disable_menu_items' ), 10 );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_items' ), 20 );
			add_filter( 'woocommerce_endpoint_'.$this->$endpoint.'_title', array( $this, 'endpoint_title' ) );
			add_action( 'woocommerce_account_'.$this->$endpoint.'_endpoint', array( $this, 'endpoint_content' ) );
		}

		public function add_query_vars( $vars ) {
			$vars[ $this->$endpoint ] = $this->$endpoint;
			return $vars;
		}

		public function disable_menu_items( $items ) {
			$new_items = array();
			foreach ( $items as $slug => $title ) {
				// Schakel bepaalde knoppen uit
				if ( $slug !== 'dashboard' and $slug !== 'downloads' ) {
					$new_items[$slug] = $title;
				}
			}
			return $new_items;
		}

		public function add_menu_items( $items ) {
			return $items;
		}

		public function endpoint_title() {
			return __( 'Facturen', 'oft' );
		}

		public function endpoint_content() {
			// Mogelijkheden: 'in behandeling', 'verzonden', 'gefactureerd', 'te crediteren' of 'gecrediteerd'
			return $this->get_endpoint_content( $this->get_endpoint_statuses() );
		}

		public function get_endpoint_statuses() {
			return array( 'gefactureerd' );
		}

		public function get_endpoint_content( $statuses ) {
			$my_orders_columns = array( 'order-number' => __( 'Odisy-referentie', 'oft' ), 'order-date' => __( 'Datum', 'oft' ), 'order-status' => __( 'Status', 'oft' ), 'order-total' => __( 'Totaal', 'oft' ) );
			
			$customer_orders = array();
			$order_data = simplexml_load_file( WP_CONTENT_DIR.'/odisy/export/orders.xml' );
			
			if ( $order_data !== false ) {
				
				foreach ( $order_data->Order as $order ) {
					$header = $order->OrderHeader;
					$lines = $order->OrderLines;
					$parts = explode( ' ', $header->OrderCreditRef->__toString() );
					$order_number = $parts[0];
					if ( count($parts) > 1 ) {
						$order_type = $parts[1];
					} else {
						$order_type = 'UNKNOWN';
					}
					
					// Te vervangen door de parameters van de ingelogde klant!
					if ( intval( $header->KlantNr ) === 2128 ) {
						if ( in_array( $header->OrderCreditStatus->__toString(), $statuses ) ) {
							// if ( ( $order_type === 'N' or $order_type === 'CH' ) )
							// Haal enkel orders op die niet via BestelWeb liepen
							if ( $header->BestelwebRef->__toString() === 'N/A' ) {
								$customer_orders[$order_number] = $order;
							}
						}
					}
				}

				// Volgens aflopend ordernummer sorteren
				krsort($customer_orders);

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
					<?php if ( count( $customer_orders ) > 0 ) : ?>
						<?php foreach ( $customer_orders as $customer_order_number => $customer_order ) : ?>
							<?php
								// TO DO: Datumformaat corrigeren in XML
								$date = wc_string_to_datetime( str_replace( '/', '-', $customer_order->OrderHeader->OrderCreditDatum->__toString() ) );
								$item_count = $customer_order->OrderLines->OrderLine->count();
							?>
							<tr class="order">
								<?php foreach ( $my_orders_columns as $column_id => $column_name ) : ?>
									<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
										<?php if ( 'order-number' === $column_id ) : ?>
											<!-- TO DO: Detailpagina creëren -->
											<a href="<?php echo esc_url( wc_get_account_endpoint_url( __( 'factuur', 'oft' ) ).$customer_order_number.'/' ) ; ?>">
												<?php echo $customer_order_number; ?>
											</a>

										<?php elseif ( 'order-date' === $column_id ) : ?>
											<time datetime="<?php echo esc_attr( $date->date('c') ); ?>"><?php echo esc_html( wc_format_datetime( $date ) ); ?></time>

										<?php elseif ( 'order-status' === $column_id ) : ?>
											<!-- TO DO: Vertaalbare placeholders voorzien voor de mogelijke statussen -->
											<?php echo ucfirst( __( $customer_order->OrderHeader->OrderCreditStatus->__toString(), 'oft' ) ); ?>

										<?php elseif ( 'order-total' === $column_id ) : ?>
											<?php
												$total = 0;
												foreach ( $customer_order->OrderLines->OrderLine as $line ) {
													$total += floatval( $line->BestelCreditBedrag->__toString() );
												}
												printf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'woocommerce' ), wc_price( $total ), $item_count );
											?>
										<?php endif; ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr><td><?php _e( 'Geen gegevens beschikbaar.', 'oft' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			<?php
		}

		public static function install() {
			add_rewrite_endpoint( $this->$endpoint, EP_ROOT | EP_PAGES );
			flush_rewrite_rules();
		}

		public static function uninstall() {
			flush_rewrite_rules();
		}
	}
?>