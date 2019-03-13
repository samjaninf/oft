<?php
	/*
	Plugin Name: OFT/OMDM Combined Business Logic
	Description: Deze plug-in groepeert alle functies die gedeeld kunnen worden tussen fairtradecrafts.be en oxfamfairtrade.be.
	Version:     0.1.1
	Author:      Full Stack Ahead
	Author URI:  https://www.fullstackahead.be
	Text Domain: oft
	*/

	defined('ABSPATH') or die('Access prohibited!');

	class Custom_Business_Logic {
		public static $company, $endpoint;

		public function __construct( $param = 'oft' ) {
			self::$company = $param;

			// Vermijd dat bv. is_user_logged_in() nog niet gedefinieerd is!
			add_action( 'init', array( $this, 'delay_actions_and_filters_till_load_completed' ) );
		}

		public function delay_actions_and_filters_till_load_completed() {
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

			if ( ! is_user_logged_in() ) {
				// Alle koopfuncties verhinderen voor niet-ingelogde gebruikers
				add_filter( 'woocommerce_is_purchasable', '__return_false' );
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

			// if ( get_client_type() === 'MDM' ) {
			// 	unset($address_fields['billing_number_oft']);
			// }
			
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
	}

	new Custom_Business_Logic('oft');
	
	// register_activation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
	// register_deactivation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
?>