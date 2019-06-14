<?php
	/*
	Plugin Name: OFT My Account Tabs
	Description: Voeg extra tabbladen toe om orders / facturen / crediteringen te raadplegen die niet via BestelWeb verliepen.
	Version:     1.3.1
	Author:      Full Stack Ahead
	Author URI:  https://www.fullstackahead.be
	Text Domain: oft
	*/

	defined('ABSPATH') or die('Access prohibited!');

	register_activation_hook( __FILE__, array( 'Custom_My_Account_Endpoint', 'install' ) );
	register_deactivation_hook( __FILE__, array( 'Custom_My_Account_Endpoint', 'uninstall' ) );

	// Wijzig de tekst boven de adressenlijst op de profielpagina van de klant (en overschrijf zo ook de WCMCA-plugin)
	add_filter( 'woocommerce_my_account_my_address_description', array( 'Custom_My_Account_Endpoint', 'change_my_addresses_list_description' ), 100, 1 );

	// Verschijnen in omgekeerde volgorde in het menu, zie add_menu_items()
	new My_Account_Endpoint_Invoices( __( 'facturen', 'oft' ) );
	new My_Account_Endpoint_Credits( __( 'crediteringen', 'oft' ) );
	new My_Account_Endpoint_Favourites( __( 'favorieten', 'oft' ) );
	new My_Account_Endpoint_Others( __( 'overige-bestellingen', 'oft' ) );

	// Detailpagina voor individuele facturen
	new My_Account_Endpoint_View_Invoice( __( 'factuur', 'oft' ) );

	class My_Account_Endpoint_Others extends Custom_My_Account_Endpoint {
		function endpoint_title() {
			return __( 'Overige bestellingen', 'oft' );
		}

		function get_endpoint_statuses() {
			return array( 'in behandeling', 'verzonden' );
		}
	}

	class My_Account_Endpoint_Invoices extends Custom_My_Account_Endpoint {
		function endpoint_title() {
			return __( 'Facturen', 'oft' );
		}

		function get_endpoint_statuses() {
			return array( 'gefactureerd' );
		}
	}

	class My_Account_Endpoint_Credits extends Custom_My_Account_Endpoint {
		function endpoint_title() {
			return __( 'Crediteringen', 'oft' );
		}

		function get_endpoint_statuses() {
			return array( 'te crediteren', 'gecrediteerd' );
		}
	}

	class My_Account_Endpoint_Favourites extends Custom_My_Account_Endpoint {
		function endpoint_title() {
			return __( 'Favoriete producten', 'oft' );
		}

		function endpoint_content() {
			if ( false === ( $favourite_skus = get_transient( 'products_purchased_by_frequency_user_'.get_current_user_id() ) ) ) {
				$customer_orders = wc_get_orders(
					array(
						'limit' => -1,
						'customer_id' => get_current_user_id(),
						'type' => 'shop_order',
						'status' => 'completed',
						'date_created' => '>'.( time() - YEAR_IN_SECONDS ),
					)
				);

				$favourite_skus = array();
				foreach ( $customer_orders as $customer_order ) {
					$items = $customer_order->get_items();
					foreach ( $items as $item ) {
						$product = $item->get_product();
						if ( $product !== false and $product->is_visible() ) {
							// Prefix want array_splice() houdt numerieke keys niet in stand
							if ( ! array_key_exists( 'SKU'.$product->get_sku(), $favourite_skus ) ) {
								$favourite_skus['SKU'.$product->get_sku()] = 0;
							}
							$favourite_skus['SKU'.$product->get_sku()] += $item->get_quantity();
						}
					}
				}

				// function is_above_treshold( $value ) {
				// 	return ( $value > 100 );
				// }
				// $favourite_skus = array_filter( $favourite_skus, 'is_above_treshold' );
				arsort($favourite_skus);
				// Of slaan we dit op per klantnummer?
				set_transient( 'products_purchased_by_frequency_user_'.get_current_user_id(), $favourite_skus, DAY_IN_SECONDS );
			}

			// var_dump_pre($favourite_skus);

			// Limiteer tot 30 vaakst gekochte producten
			$favourite_skus_top = array_splice( $favourite_skus, 0, 30 );

			if ( count($favourite_skus_top) > 0 ) {
				echo '<p class="woocommerce-Message woocommerce-Message--info woocommerce-info">'.sprintf( __( 'Dit zijn de %s producten die je de voorbije 12 maanden het vaakst bestelde:', 'oft' ), count($favourite_skus_top) ).'</p>';
				// Kan helaas niet gesorteerd worden op custom parameter ...
				echo do_shortcode('[products skus="'.str_replace( 'SKU', '', implode( ',', array_keys($favourite_skus_top) ) ).'" columns="5"]');
			} else {
				echo __( 'Nog geen producten gekocht.', 'oft' );
			}
		}
	}

	class My_Account_Endpoint_View_Invoice extends Custom_My_Account_Endpoint {
		function add_menu_items( $items ) {
			return $items;
		}

		function endpoint_title() {
			return __( 'Factuur', 'oft' );
		}

		function endpoint_content() {
			$invoice_number = get_query_var( $this->slug );
			$invoice = $this->get_invoice_by_number( $invoice_number );
			// var_dump_pre( $invoice );

			// TO DO: Security deftig toevoegen m.b.v. add_filter( 'user_has_cap', 'wc_customer_has_capability', 10, 3 ); 
			if ( $invoice->OrderHeader->KlantNr != get_user_meta( get_current_user_id(), 'billing_number_oft', true ) ) {
				// echo '<div class="woocommerce-error">' . esc_html__( 'Invalid order.', 'woocommerce' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html__( 'My account', 'woocommerce' ) . '</a></div>';
				// return;
			}

			wc_get_template(
				'myaccount/view-invoice.php',
				array(
					'invoice' => $invoice,
					'invoice_number' => $invoice_number,
				)
			);
		}

		function get_invoice_by_number( $invoice_number ) {
			$order_data = simplexml_load_file( WP_CONTENT_DIR.'/odisy/export/orders.xml' );
			
			if ( $order_data !== false ) {
				foreach ( $order_data->Order as $order ) {
					$header = $order->OrderHeader;
					$lines = $order->OrderLines;
					$parts = explode( ' ', $header->OrderCreditRef->__toString() );
					$order_number = $parts[0];
					if ( $order_number == $invoice_number ) {
						return $order;
					}
				}
			}

			return false;
		}
	}

	class Custom_My_Account_Endpoint {
		protected $slug;

		function __construct( $param ) {
			$this->slug = $param;
			add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ) );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'disable_menu_items' ), 10 );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_items' ), 20 );
			add_filter( 'woocommerce_endpoint_'.$this->slug.'_title', array( $this, 'endpoint_title' ) );
			add_action( 'woocommerce_account_'.$this->slug.'_endpoint', array( $this, 'endpoint_content' ) );
		}

		function add_query_vars( $vars ) {
			$vars[ $this->slug ] = $this->slug;
			return $vars;
		}

		function disable_menu_items( $items ) {
			$new_items = array();
			foreach ( $items as $slug => $title ) {
				// Schakel bepaalde knoppen uit
				if ( $slug !== 'dashboard' and $slug !== 'downloads' ) {
					$new_items[$slug] = $title;
				}
				// Herbenoem bepaalde knoppen
				if ( $slug === 'orders' ) {
					$new_items[$slug] = __( 'Online bestellingen', 'oft' );
				}
			}
			return $new_items;
		}

		function add_menu_items( $items ) {
			$new_items = array();
			foreach ( $items as $slug => $title ) {
				$new_items[$slug] = $title;
				if ( $slug === 'orders' ) {
					// Voeg menuknop toe net na 'Bestellingen'
					$new_items[ $this->slug ] = $this->endpoint_title();
				}
			}
			return $new_items;
		}

		function endpoint_title() {
			return __( 'Facturen', 'oft' );
		}

		function endpoint_content() {
			// Mogelijkheden: 'in behandeling', 'verzonden', 'gefactureerd', 'te crediteren' of 'gecrediteerd'
			return $this->get_endpoint_content( $this->get_endpoint_statuses() );
		}

		function get_endpoint_statuses() {
			return array( 'gefactureerd' );
		}

		function get_endpoint_content( $statuses ) {
			$my_orders_columns = array( 'order-number' => __( 'Odisy', 'oft' ), 'order-reference' => __( 'Referentie', 'oft' ), 'order-date' => __( 'Datum', 'oft' ), 'order-status' => __( 'Status', 'oft' ), 'order-total' => __( 'Totaal', 'oft' ) );
			
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
					// if ( intval( $header->KlantNr ) === intval( get_user_meta( get_current_user_id(), 'billing_number_oft', true ) ) ) {
					if ( intval( $header->KlantNr ) === 2128 ) {
						if ( in_array( $header->OrderCreditStatus->__toString(), $statuses ) ) {
							if ( in_array( 'gefactureerd', $statuses ) and ( $order_type === 'RL' or $order_type === 'B' ) ) {
								// Crediteringen skippen en naar dat tabblad verhuizen?
								// continue;
							}
							
							if ( $header->BestelwebRef->__toString() !== 'N/A' ) {
								// Skip orders die via BestelWeb liepen
								continue;
							}

							$customer_orders[$order_number] = $order;
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

										<?php elseif ( 'order-reference' === $column_id ) : ?>
											<?php echo ucfirst( __( $customer_order->OrderHeader->ExterneReferentie->__toString(), 'oft' ) ); ?>

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

		static function change_my_addresses_list_description( $default_text ) {
			return sprintf( __( 'Deze adressen worden standaard ingevuld bij het afronden. Je kunt ze zelf niet aanpassen. Zie je een foutje staan of wil je een nieuw adres toevoegen? <a href="mailto:%s?subject=Aanvraag correctie adresgegevens">Contacteer onze Klantendienst</a> om de gegevens te corrigeren in al onze systemen.', 'oft' ), 'klantendienst@oft.be' );
		}

		protected static function install() {
			add_rewrite_endpoint( $this->slug, EP_ROOT | EP_PAGES );
			flush_rewrite_rules();
		}

		protected static function uninstall() {
			flush_rewrite_rules();
		}
	}
?>