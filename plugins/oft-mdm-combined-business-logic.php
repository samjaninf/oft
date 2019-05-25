<?php
	/*
	Plugin Name: OFT/OMDM Combined Business Logic
	Description: Deze plug-in groepeert alle functies die gedeeld kunnen worden tussen fairtradecrafts.be en oxfamfairtrade.be.
	Version:     0.2.0
	Author:      Full Stack Ahead
	Author URI:  https://www.fullstackahead.be
	Text Domain: oft
	*/

	defined('ABSPATH') or die('Access prohibited!');

	class Custom_Business_Logic {
		public static $company, $endpoint;

		public function __construct( $param = 'oft' ) {
			self::$company = $param;
			
			// Sommige WP-functies (o.a. is_user_logged_in) zijn pas beschikbaar na de 'init'-actie!
			add_action( 'init', array( $this, 'delay_actions_and_filters_till_load_completed' ) );

			// Creëer een custom taxonomie op producten om klantengroepen in op te slaan
			add_action( 'init', array( $this, 'register_client_type_taxonomy' ) );

			// Definieer een extra profielveld op gebruikers dat de klantengroep bevat
			add_action( 'show_user_profile', array( $this, 'show_extra_user_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'show_extra_user_fields' ) );
			add_action( 'personal_options_update', array( $this, 'save_extra_user_fields' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_extra_user_fields' ) );

			// Voeg verkoopseenheid én hoeveelheidsspinner toe aan template voor cataloguspagina's (die we ook laden op productdetailpagina's) */
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_quantity_inputs_to_add_to_cart_link' ), 10, 2 );

			// Toon/verberg bepaalde tabbladen onder 'Mijn account'
			add_filter( 'woocommerce_account_menu_items', array( $this, 'modify_my_account_menu_items' ), 10, 1 );

			// Voeg favorieten toe aan dashboard
			add_action( 'woocommerce_account_dashboard', array( $this, 'show_favourite_products' ) );
			
			// Verhinder het permanent verwijderen van producten (maar na 1 jaar wel automatische clean-up door Wordpress, zie wp-config.php!)
			add_action( 'before_delete_post', array( $this, 'disable_manual_product_removal' ), 10, 1 );

			// Maak de adresvelden voor klanten onbewerkbaar en verduidelijk de labels en layout
			add_filter( 'woocommerce_default_address_fields', array( $this, 'make_addresses_readonly' ), 10, 1 );

			// Label, orden en layout de factuurgegevens
			add_filter( 'woocommerce_billing_fields', array( $this, 'format_checkout_billing' ), 10, 1 );

			// Label, orden en layout de verzendgegevens (maar filter wordt niet doorlopen via AJAX of indien je van winkelmandje naar afrekenen gaat met een verborgen adres!)
			add_filter( 'woocommerce_shipping_fields', array( $this, 'format_checkout_shipping' ), 10, 1 );

			// Zorg ervoor dat het 'shipping_state'-veld ook in onze Belgische shop verwerkt wordt (maar wel verborgen door 'hidden'-klasse!)
			add_filter( 'woocommerce_states', array( $this, 'define_woocommerce_routecodes' ) );

			// Bereken belastingen ook bij afhalingen steeds volgens het factuuradres van de klant!
			add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );

			// Limiteer productaanbod naar gelang klantentype (in cataloguspagina's, shortcodes en detailpagina's)
			add_action( 'woocommerce_product_query', array( $this, 'limit_assortment_for_client_type_archives' ), 10, 1 );
			add_filter( 'woocommerce_shortcode_products_query', array( $this, 'limit_assortment_for_client_type_shortcodes' ), 10, 1 );
			
			// Definieer een eigen filter met de assortimentsvoorwaarden, zodat we alles slechts één keer hoeven in te geven
			add_filter( 'oxfam_product_is_available', array( $this, 'check_product_availability' ), 10, 3 );

			// Verhinder het toevoegen van verboden producten én laat de koopknop verdwijnen + zwier reeds toegevoegde producten uit het winkelmandje
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'disallow_products_not_in_assortment' ), 10, 2 );
			add_filter( 'woocommerce_is_purchasable', array( $this, 'disable_products_not_in_assortment' ), 10, 2 );

			// Maak de detailpagina van verboden producten volledig onbereikbaar
			add_action( 'template_redirect', array( $this, 'prevent_access_to_product_page' ) );
			
			// Synchroniseer de publicatiestatus vanuit de hoofdtaal naar anderstalige producten (zoals bij trashen reeds automatisch door WPML gebeurt)
			// Neem een hoge prioriteit, zodat de functie pas doorlopen wordt na de 1ste 'save_post' die de zichtbaarheid regelt
			add_action( 'draft_to_publish', array( $this, 'sync_product_status' ), 100, 1 );
			add_action( 'draft_to_private', array( $this, 'sync_product_status' ), 100, 1 );
			add_action( 'publish_to_draft', array( $this, 'sync_product_status' ), 100, 1 );
			add_action( 'private_to_draft', array( $this, 'sync_product_status' ), 100, 1 );
		}

		public function delay_actions_and_filters_till_load_completed() {
			if ( ! is_user_logged_in() ) {
				// Alle koopfuncties uitschakelen voor niet-ingelogde gebruikers
				add_filter( 'woocommerce_is_purchasable', '__return_false' );

				// Verberg alle niet-OFT-voedingsproducten NIET NODIG, GEEF CUSTOMERS GEWOON 'READ_PRIVATE_PRODUCTS'-RECHTEN M.B.V. ROLE EDITOR
				// add_action( 'woocommerce_product_query', array( $this, 'hide_external_products' ) );
				// public function hide_external_products( $query ) {
				// 	// Altijd alle producten zichtbaar voor beheerders
				// 	if ( ! current_user_can('manage_woocommerce') ) {
				// 		$meta_query = (array) $query->get('meta_query');

				// 		if ( $this->get_client_type() !== 'OWW' ) {
				// 			$meta_query[] = array(
				// 				'key' => '_product_attributes',
				// 				'value' => 'Oxfam Fair Trade',
				// 				'compare' => 'LIKE',
				// 			);
				// 		}
				// 		$query->set( 'meta_query', $meta_query );
				// 		// var_dump_pre($query);	
				// 	}
				// }
			} else {
				// Pas verkoopprijzen aan volgens klantentype
				add_filter( 'woocommerce_product_get_price', array( $this, 'get_price_for_current_client' ), 100, 2 );
				add_filter( 'woocommerce_product_get_regular_price', array( $this, 'get_regular_price_for_current_client' ), 100, 2 );

				// Voeg ompakinfo toe
				add_filter( 'woocommerce_product_title', array( $this, 'add_consumer_units_per_order_unit' ), 1000, 2 );
				add_filter( 'woocommerce_cart_item_name', array( $this, 'add_consumer_units_per_order_unit' ), 10, 2 );
				add_filter( 'woocommerce_order_item_name', array( $this, 'add_consumer_units_per_order_unit' ), 10, 2 );
			}
		}

		public function register_client_type_taxonomy() {
			$taxonomy_name = 'product_client_type';
			
			$labels = array(
				'name' => __( 'Klantengroepen', 'oftc' ),
				'singular_name' => __( 'Klantengroep', 'oftc' ),
				'all_items' => __( 'Alle klantengroepen', 'oftc' ),
				'parent_item' => __( 'Klantengroep', 'oftc' ),
				'parent_item_colon' => __( 'Klantengroep:', 'oftc' ),
				'new_item_name' => __( 'Nieuwe klantengroep', 'oftc' ),
				'add_new_item' => __( 'Voeg nieuwe klantengroep toe', 'oftc' ),
				'view_item' => __( 'Klantengroep bekijken', 'oftc' ),
				'edit_item' => __( 'Klantengroep bewerken', 'oftc' ),
				'update_item' => __( 'Klantengroep bijwerken', 'oftc' ),
				'search_items' => __( 'Klantengroepen doorzoeken', 'oftc' ),
			);

			$args = array(
				'labels' => $labels,
				'description' => __( 'Maak dit product exclusief beschikbaar voor deze klantengroep', 'oftc' ),
				'public' => false,
				'publicly_queryable' => false,
				'hierarchical' => false,
				'show_ui' => true,
				'show_in_menu' => true,
				'show_in_nav_menus' => false,
				'show_in_rest' => true,
				'show_tagcloud' => false,
				'show_in_quick_edit' => false,
				'show_admin_column' => true,
				'query_var' => true,
				'capabilities' => array( 'assign_terms' => 'manage_woocommerce', 'edit_terms' => 'update_core', 'manage_terms' => 'manage_woocommerce', 'delete_terms' => 'update_core' ),
				'rewrite' => array( 'slug' => 'klantengroep', 'with_front' => false, 'hierarchical' => false ),
			);

			register_taxonomy( $taxonomy_name, 'product', $args );
			register_taxonomy_for_object_type( $taxonomy_name, 'product' );
		}

		public function show_extra_user_fields( $user ) {
			if ( current_user_can('manage_woocommerce') ) { 
				?>
				<h3><?php _e( 'Klantengroep', 'oft' ); ?></h3>
				<table class="form-table">
					<tr>
						<th><label for="client_type"><?php _e( 'Klantengroep', 'oft' ); ?></label></th>
						<td>
							<?php
								$args = array(
									'name' => 'client_type',
									'taxonomy' => 'product_client_type',
									'show_option_none' => __( 'selecteer', 'oft' ),
									'option_none_value' => '',
									'value_field' => 'name',
									'selected' => get_the_author_meta( 'client_type', $user->ID ),
								);
								wp_dropdown_categories($args);
								
								// $key = 'client_type';
								// echo '<select name="'.$key.'" id="'.$key.'">';
								// 	$current_type = get_the_author_meta( $key, $user->ID );
								// 	$selected = empty( $current_type ) ? ' selected' : '';
								// 	echo '<option value=""'.$selected.'>('.__( 'selecteer', 'oft' ).')</option>';
								// 	foreach ( get_option('oft_client_types') as $client_type ) {
								// 		$selected = ( $client_type === $current_type ) ? ' selected' : '';
								// 		echo '<option value="'.$client_type.'"'.$selected.'>'.$client_type.'</option>';
								// 	}
								// echo '</select>';
							?>
							<p class="description"><?php _e( 'De taalkeuze blijft vrij, alles is ontdubbeld.', 'oft' ); ?></p>
						</td>
					</tr>
				</table>
				<?php
			}
		}

		public function save_extra_user_fields( $user_id ) {
			if ( ! current_user_can('manage_woocommerce') ) {
				return false;
			}
			update_user_meta( $user_id, 'client_type', $_POST['client_type'] );
		}

		public function add_quantity_inputs_to_add_to_cart_link( $html, $product ) {
			if ( $product->is_type('simple') and $product->is_purchasable() and $product->is_in_stock() and ! $product->is_sold_individually() ) {
				$html = woocommerce_quantity_input( array(
					'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
					'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
					'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( $_POST['quantity'] ) : $product->get_min_purchase_quantity(),
				), $product, false ) . $html;
			}
			return $html;
		}

		public function get_price_for_current_client( $price, $product, $user_id = false, $regular = false ) {
			// Filter wordt ook doorlopen in back-end, prijs daar nooit manipuleren!
			if ( ! is_admin() ) {
				// Huidige prijs expliciet doorgeven aan functie want anders infinite loop!
				$price = $this->get_price_by_client_type( $product, $this->get_client_type($user_id), $price, $regular );
			}
			return $price;
		}

		// Wrapperfunctie om $regular === true door te geven aan universele get_price_for_current_client()
		public function get_regular_price_for_current_client( $price, $product, $user_id = false ) {
			return $this->get_price_for_current_client( $price, $product, $user_id, true );
		}

		public function get_price_by_client_type( $product, $client_type, $price = false, $regular = false ) {
			if ( $client_type !== '' ) {
				if ( $product->meta_exists( '_price_for_client_type_' . strtolower($client_type) ) ) {
					// BTW WORDT NADIEN NOG AFGETROKKEN WANT CP'S GEVEN WE IN INCL BTW ...
					$price = floatval( $product->get_meta( '_price_for_client_type_' . strtolower($client_type) ) );
				} else {
					// TIJDELIJKE FIX, ODISY ZAL OMPAKPRIJZEN DOORGEVEN
					if ( intval( $product->get_meta('_multiple') ) > 1 ) {
						$price *= intval( $product->get_meta('_multiple') );
					}
				}
			}

			return $price;
		}

		public function get_client_type( $user_id = false ) {
			if ( $user_id === false ) {
				$user_id = get_current_user_id();
			}

			// Retourneert een lege string indien klantenrol niet ingesteld
			return get_user_meta( $user_id, 'client_type', true );
		}

		public function add_consumer_units_per_order_unit( $title, $product ) {
			if ( $product instanceof WC_Product_Simple ) {
				return $title . ' X '.$product->get_meta('_multiple');
			} else {
				return $title . ' X '.$product['data']->get_meta('_multiple');
			}
		}

		

		public function modify_my_account_menu_items( $items ) {
			// unset($items['dashboard']);
			unset($items['downloads']);
			return $items;
		}

		public function show_favourite_products() {
			if ( false === ( $favourite_skus = get_transient( get_current_user_id().'_purchased_products_by_frequency' ) ) ) {
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
				set_transient( get_current_user_id().'_purchased_products_by_frequency', $favourite_skus, DAY_IN_SECONDS );
			}

			// var_dump_pre($favourite_skus);

			// Limiteer tot 20 vaakst gekochte producten
			$favourite_skus_top = array_splice( $favourite_skus, 0, 20 );

			if ( count($favourite_skus_top) > 0 ) {
				echo '<p class="woocommerce-Message woocommerce-Message--info woocommerce-info">'.sprintf( __( 'Dit zijn de %s producten die je de voorbije 12 maanden het vaakst bestelde:', 'oft' ), count($favourite_skus_top) ).'</p>';
				// Kan helaas niet gesorteerd worden op custom parameter ...
				echo do_shortcode('[products skus="'.str_replace( 'SKU', '', implode( ',', array_keys($favourite_skus_top) ) ).'" columns="5"]');
			} else {
				echo __( 'Nog geen producten gekocht.', 'oft' );
			}
		}

		public function disable_manual_product_removal( $post_id ) {
			if ( get_post_type($post_id) == 'product' ) {
				wp_die( sprintf( __( 'Uit veiligheidsoverwegingen is het verwijderen van producten niet toegestaan, voor geen enkele gebruikersrol! Vraag &ndash; indien nodig &ndash; dat de hogere machten op %s deze beperking tijdelijk opheffen, zodat je je vuile zaakjes kunt opknappen.', 'oft' ), '<a href="mailto:'.get_option('admin_email').'">'.get_option('admin_email').'</a>' ) );
			}
		}

		public function make_addresses_readonly( $address_fields ) {
			$address_fields['company']['custom_attributes'] = array( 'readonly' => 'readonly' );
			
			$address_fields['address_1']['label'] = __( 'Straat en nummer', 'oft' );
			$address_fields['address_1']['placeholder'] = '';
			$address_fields['address_1']['required'] = true;
			$address_fields['address_1']['custom_attributes'] = array( 'readonly' => 'readonly' );
			
			$address_fields['postcode']['label'] = __( 'Postcode', 'oft' );
			$address_fields['postcode']['placeholder'] = '';
			$address_fields['postcode']['required'] = true;
			$address_fields['postcode']['custom_attributes'] = array( 'readonly' => 'readonly' );
			$address_fields['postcode']['clear'] = false;

			$address_fields['city']['label'] = __( 'Gemeente', 'oft' );
			$address_fields['city']['placeholder'] = '';
			$address_fields['city']['required'] = true;
			$address_fields['city']['custom_attributes'] = array( 'readonly' => 'readonly' );
			$address_fields['city']['clear'] = true;

			if ( self::$company === 'oft' ) {
				$billing_number_key = 'number_oft';
				$address_fields[$billing_number_key]['label'] = __( 'Klantnummer OFT', 'oft' );
			} else {
				$billing_number_key = 'number_omdm';
				$address_fields[$billing_number_key]['label'] = __( 'Klantnummer OMDM', 'oft' );
			}
			$address_fields[$billing_number_key]['placeholder'] = '';
			$address_fields[$billing_number_key]['required'] = true;
			$address_fields[$billing_number_key]['custom_attributes'] = array( 'readonly' => 'readonly' );

			$address_fields['country']['class'] = array('hidden');
			$address_fields['state']['class'] = array('hidden');
			
			return $address_fields;
		}

		public function format_checkout_billing( $address_fields ) {
			$address_fields['billing_first_name']['label'] = __( 'Voornaam', 'oft' );
			$address_fields['billing_first_name']['description'] = __( 'Gelieve je eigen naam in te vullen!', 'oft' );
			$address_fields['billing_first_name']['placeholder'] = "Charles";
			$address_fields['billing_last_name']['description'] = __( 'Gelieve je eigen naam in te vullen!', 'oft' );
			$address_fields['billing_last_name']['label'] = __( 'Familienaam', 'oft' );
			$address_fields['billing_last_name']['placeholder'] = "Michel";
			$address_fields['billing_phone']['label'] = __( 'Telefoonnummer', 'oft' );
			$address_fields['billing_phone']['placeholder'] = "02 501 02 11";
			$address_fields['billing_phone']['description'] = __( 'Zo kunnen we je contacteren bij problemen.', 'oft' );
			$address_fields['billing_email']['label'] = __( 'Mail orderbevestiging naar', 'oft' );
			$address_fields['billing_email']['placeholder'] = "charles.michel@premier.fed.be";
			$address_fields['billing_email']['description'] = __( 'Dit hoeft niet je eigen e-mailadres te zijn.', 'oft' );
			$address_fields['billing_company']['label'] = __( 'Te factureren entiteit', 'oft' );
			$address_fields['billing_company']['required'] = true;
			
			$address_fields['billing_first_name']['priority'] = 1;
			$address_fields['billing_last_name']['priority'] = 2;
			$address_fields['billing_phone']['priority'] = 11;
			$address_fields['billing_email']['priority'] = 12;
			$address_fields['billing_number_oft']['priority'] = 21;
			$address_fields['billing_company']['priority'] = 22;
			$address_fields['billing_address_1']['priority'] = 31;
			$address_fields['billing_postcode']['priority'] = 32;
			$address_fields['billing_city']['priority'] = 41;
			$address_fields['billing_country']['priority'] = 42;

			if ( $this->get_client_type() === 'MDM' ) {
				unset($address_fields['billing_number_oft']);
			}
			
			return $address_fields;
		}

		public function format_checkout_shipping( $address_fields ) {
			$address_fields['shipping_company']['label'] = __( 'Te beleveren winkel', 'oft' );
			$address_fields['shipping_company']['required'] = true;
			$address_fields['shipping_number_oft']['label'] = __( 'Levernummer OFT', 'oft' );
			$address_fields['shipping_number_oft']['placeholder'] = '';
			// Niet algemeen verplichten maar indien WOBAL op het einde wel checken via custom code in 'woocommerce_after_checkout_validation'
			$address_fields['shipping_number_oft']['required'] = false;
			$address_fields['shipping_number_oft']['custom_attributes'] = array( 'readonly' => 'readonly' );

			unset($address_fields['shipping_first_name']);
			unset($address_fields['shipping_last_name']);
			$address_fields['shipping_number_oft']['priority'] = 21;
			$address_fields['shipping_company']['priority'] = 22;
			$address_fields['shipping_address_1']['priority'] = 31;
			$address_fields['shipping_postcode']['priority'] = 32;
			$address_fields['shipping_city']['priority'] = 41;
			$address_fields['shipping_country']['priority'] = 42;
			
			return $address_fields;
		}

		public function define_woocommerce_routecodes( $states ) {
			$routecodes_oft = array(
				'1' => __( 'West-Vlaanderen', 'oft' ),
				'2' => __( 'Oost-Vlaanderen', 'oft' ),
				'3' => __( 'Vlaams-Brabant', 'oft' ),
				'4' => __( 'Antwerpen', 'oft'),
				'5' => __( 'Limburg', 'oft' ),
				// Opsplitsen in vaste leverdag (voor trema) en plandag- en leverschema (na trema)
				// '1-A1A' => __( 'Maandag', 'oft' )." ".__( '(wekelijks)', 'oft' ),
				// '1-A2A' => __( 'Maandag', 'oft' )." ".__( '(tweewekelijks, oneven)', 'oft' ),
				// '1-A2B' => __( 'Maandag', 'oft' )." ".__( '(tweewekelijks, even)', 'oft' ),
				// '1-A3A' => __( 'Maandag', 'oft' )." ".__( '(driewekelijks)', 'oft' ),
				// '1-A3B' => __( 'Maandag', 'oft' )." ".__( '(driewekelijks)', 'oft' ),
				// '1-A3C' => __( 'Maandag', 'oft' )." ".__( '(driewekelijks)', 'oft' ),
			);

			$routecodes_omdm = array(
				'1-AB' => __( 'Henegouwen', 'oft' ),
				'1-A' => __( 'Henegouwen', 'oft' )." ".__( '(even weken)', 'oft' ),
				'1-B' => __( 'Henegouwen', 'oft' )." ".__( '(oneven weken)', 'oft' ),
				'2-AB' => __( 'Namen', 'oft' ),
				'2-A' => __( 'Namen', 'oft' )." ".__( '(even weken)', 'oft' ),
				'2-B' => __( 'Namen', 'oft' )." ".__( '(oneven weken)', 'oft' ),
				'3-AB' => __( 'Luik', 'oft' ),
				'3-A' => __( 'Luik', 'oft' )." ".__( '(even weken)', 'oft' ),
				'3-B' => __( 'Luik', 'oft' )." ".__( '(oneven weken)', 'oft' ),
				'4-AB' => __( 'Luxemburg', 'oft' ),
				'4-A' => __( 'Luxemburg', 'oft' )." ".__( '(even weken)', 'oft' ),
				'4-B' => __( 'Luxemburg', 'oft' )." ".__( '(oneven weken)', 'oft' ),
				'5-AB' => __( 'Brussel', 'oft' ),
				'5-A' => __( 'Brussel', 'oft' )." ".__( '(even weken)', 'oft' ),
				'5-B' => __( 'Brussel', 'oft' )." ".__( '(oneven weken)', 'oft' ),
			);

			$routecodes_ext = array(
				'T' => __( 'Externe klant', 'oft' ),
			);

			$states['BE'] = $routecodes_oft + $routecodes_omdm + $routecodes_ext;
			$states['NL'] = $routecodes_ext;
			$states['LU'] = $routecodes_ext;
			$states['DE'] = $routecodes_ext;
			$states['FR'] = $routecodes_ext;
			$states['ES'] = $routecodes_ext;
		}

		public function check_product_availability( $product_id, $client_type, $available ) {
			if ( ! current_user_can('manage_woocommerce') ) {	
				if ( $client_type !== 'OWW' ) {
					if ( has_term( 'OWW', 'product_client_type', $product_id ) ) {
						$available = false;
					}
				}
				
				if ( $client_type !== 'MDM' ) {
					if ( has_term( 'MDM', 'product_client_type', $product_id ) ) {
						$available = false;
					}
				}
			}

			return $available;
		}

		public function limit_assortment_for_client_type_archives( $query ) {
			if ( ! current_user_can('manage_woocommerce') ) {
				$tax_query = (array) $query->get('tax_query');

				if ( global_get_client_type() !== 'OWW' ) {
					$tax_query[] = array(
						'taxonomy' => 'product_client_type',
						'field' => 'name',
						'terms' => array( 'OWW' ),
						'operator' => 'NOT IN',
					);
				}
				if ( global_get_client_type() !== 'MDM' ) {
					$tax_query[] = array(
						'taxonomy' => 'product_client_type',
						'field' => 'name',
						'terms' => array( 'MDM' ),
						'operator' => 'NOT IN',
					);
				}
				
				$query->set( 'tax_query', $tax_query );	
			}
		}
	
		public function limit_assortment_for_client_type_shortcodes( $query_args ) {
			if ( ! current_user_can('manage_woocommerce') ) {
				if ( get_client_type() !== 'OWW' ) {
					$query_args['tax_query'][] = array(
						'taxonomy' => 'product_client_type',
						'field' => 'name',
						'terms' => array( 'OWW' ),
						'operator' => 'NOT IN',
					);
				}
				if ( get_client_type() !== 'MDM' ) {
					$query_args['tax_query'][] = array(
						'taxonomy' => 'product_client_type',
						'field' => 'name',
						'terms' => array( 'MDM' ),
						'operator' => 'NOT IN',
					);
				}
			}
			return $query_args;
		}

		public function disallow_products_not_in_assortment( $passed, $product_id ) {
			$passed_extra_conditions = apply_filters( 'oxfam_product_is_available', $product_id, $this->get_client_type(), $passed );

			if ( $passed and ! $passed_extra_conditions ) {
				$product = wc_get_product($product_id);
				wc_add_notice( sprintf( __( 'Als %1$s-klant kun je %2$s niet bestellen.', 'oft' ), $this->get_client_type(), $product->get_name() ), 'error' );
			}
			
			return $passed_extra_conditions;
		}

		public function disable_products_not_in_assortment( $purchasable, $product ) {
			return apply_filters( 'oxfam_product_is_available', $product->get_id(), $this->get_client_type(), $purchasable );
		}

		public function prevent_access_to_product_page() {
			if ( is_product() ) {
				$available = apply_filters( 'oxfam_product_is_available', get_the_ID(), $this->get_client_type(), true );
				
				if ( ! $available ) {
					// Als de klant nog niets in het winkelmandje zitten heeft, is er nog geen sessie om notices aan toe te voegen!
					if ( ! WC()->session->has_session() ) {
						WC()->session->set_customer_session_cookie(true);
					}
					wc_add_notice( sprintf( __( '%s is niet beschikbaar voor jou.', 'oft' ), get_the_title() ), 'error' );
					
					if ( wp_get_referer() ) {
						// Keer terug naar de vorige pagina
						wp_safe_redirect( wp_get_referer() );
					} else {
						// Ga naar de hoofdpagina van de winkel
						wp_safe_redirect( get_permalink( wc_get_page_id('shop') ) );
					}
					exit;
				}
			}
		}

		public function sync_product_status( $post ) {
			$post_lang = apply_filters( 'wpml_post_language_details', NULL, $post->ID );
			write_log(serialize($post_lang));
			$default_lang_code = apply_filters( 'wpml_default_language', NULL );
			write_log(serialize($default_lang_code));

			if ( $post->post_type === 'product' and $post_lang['language_code'] === $default_lang_code ) {
				$main_product = wc_get_product($post->ID);
				if ( $main_product !== false ) {
					$languages = apply_filters( 'wpml_active_languages', NULL );
					foreach ( $languages as $lang_code => $language ) {
						$product = wc_get_product( apply_filters( 'wpml_object_id', $post->ID, 'product', false, $lang_code ) );
						if ( $product !== false ) {
							$product->set_status( $main_product->get_status() );
							$product->save();
						}
					}
				}
			}
		}
	}

	new Custom_Business_Logic('oft');
	
	// register_activation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
	// register_deactivation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
?>