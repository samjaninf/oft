<?php
	/*
	Plugin Name: OFT/OMDM Combined Business Logic
	Description: Deze plugin groepeert alle functies die gedeeld kunnen worden tussen fairtradecrafts.be en oxfamfairtrade.be.
	Version:     0.2.2
	Author:      Full Stack Ahead
	Author URI:  https://www.fullstackahead.be
	Text Domain: oft-mdm
	*/

	defined('ABSPATH') or die('Access prohibited!');

	new Oft_Mdm_Custom_Business_Logic('oft');

	class Oft_Mdm_Custom_Business_Logic {
		// const WOBAL_METHOD = 'free_shipping:1';
		const WOBAL_METHOD = 'flat_rate:5';
		const TOURNEE_METHOD = 'free_shipping:4';
		const OFTL_PIKCUP_METHOD = 'local_pickup:2';
		const MDM_PIKCUP_METHOD = 'local_pickup:3';

		static $company, $routecodes_oww, $routecodes_daily, $routecodes_ext;

		function __construct( $param = 'oft' ) {
			self::$company = $param;
			self::$routecodes_oww = array(
				'A1A' => __( 'Deadline op donderdag', 'oft-mdm' )." ".__( '(wekelijks)', 'oft-mdm' ),
				'A2A' => __( 'Deadline op donderdag', 'oft-mdm' )." ".__( '(tweewekelijks, even weken)', 'oft-mdm' ),
				'A2B' => __( 'Deadline op donderdag', 'oft-mdm' )." ".__( '(tweewekelijks, oneven weken)', 'oft-mdm' ),
				'A3A' => __( 'Deadline op donderdag', 'oft-mdm' )." ".__( '(driewekelijks)', 'oft-mdm' ),
				'B1A' => __( 'Deadline op maandag', 'oft-mdm' )." ".__( '(wekelijks)', 'oft-mdm' ),
				'B2A' => __( 'Deadline op maandag', 'oft-mdm' )." ".__( '(tweewekelijks, even weken)', 'oft-mdm' ),
				'B2B' => __( 'Deadline op maandag', 'oft-mdm' )." ".__( '(tweewekelijks, oneven weken)', 'oft-mdm' ),
				'B3A' => __( 'Deadline op maandag', 'oft-mdm' )." ".__( '(driewekelijks)', 'oft-mdm' ),
			);
			self::$routecodes_daily = array(
				'1' => __( 'Maandag', 'oft-mdm' ),
				'2' => __( 'Dinsdag', 'oft-mdm' ),
				'3' => __( 'Woensdag', 'oft-mdm' ),
				'4' => __( 'Donderdag', 'oft'),
				'5' => __( 'Vrijdag', 'oft-mdm' ),
			);
			self::$routecodes_ext = array(
				'T' => __( 'Externe klant', 'oft-mdm' ),
				'TB' => __( 'Externe klant (B)', 'oft-mdm' ),
				'MDM' => __( 'Magasins du Monde', 'oft-mdm' ),
			);
			
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

			// Verhinder het manueel aanmaken van producten / bestellingen
			add_filter( 'woocommerce_register_post_type_product', array( $this, 'disable_post_creation' ) );
			add_filter( 'woocommerce_register_post_type_shop_order', array( $this, 'disable_post_creation' ) );
			
			// Verhinder het permanent verwijderen van producten (maar na 1 jaar wel automatische clean-up door Wordpress, zie wp-config.php!)
			add_action( 'before_delete_post', array( $this, 'disable_manual_product_removal' ), 10, 1 );

			// Verberg de verzendopties die niet van toepassing zijn
			add_filter( 'woocommerce_package_rates', array( $this, 'hide_invalid_shipping_methods' ), 10, 2 );

			// Maak de adresvelden voor klanten onbewerkbaar en verduidelijk de labels en layout
			add_filter( 'woocommerce_default_address_fields', array( $this, 'make_addresses_readonly' ), 1000, 1 );

			// Label, orden en layout de factuurgegevens
			add_filter( 'woocommerce_billing_fields', array( $this, 'format_checkout_billing' ), 1000, 1 );

			// Label, orden en layout de verzendgegevens (maar filter wordt niet doorlopen via AJAX of indien je van winkelmandje naar afrekenen gaat met een verborgen adres!)
			add_filter( 'woocommerce_shipping_fields', array( $this, 'format_checkout_shipping' ), 1000, 1 );

			// Zorg ervoor dat het 'shipping_state'-veld ook in onze Belgische shop verwerkt wordt STOP IN NIEUW CUSTOM VELD, TE VEEL GEFOEFEL
			// add_filter( 'woocommerce_states', array( $this, 'define_woocommerce_routecodes' ) );

			// Voeg klant- en levernemers toe aan lijst placeholders voor adressen
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_address_replacements' ), 10, 2 );

			// Laad de extra adresdata in
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'load_custom_address_data' ), 10, 3 );

			// Wijzig de algemene adresformaten per land
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'change_address_formats' ), 10, 1 );
			
			// Verduidelijk de adresvelden in de back-end
			add_filter( 'woocommerce_customer_meta_fields', array( $this, 'modify_user_admin_fields' ), 10, 1 );
			
			// Bereken belastingen ook bij afhalingen steeds volgens het factuuradres van de klant!
			add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );

			// Limiteer productaanbod naar gelang klantentype (in cataloguspagina's, shortcodes en detailpagina's)
			add_action( 'woocommerce_product_query', array( $this, 'limit_assortment_for_client_type_archives' ), 10, 1 );
			add_filter( 'woocommerce_shortcode_products_query', array( $this, 'limit_assortment_for_client_type_shortcodes' ), 10, 1 );
			add_action( 'template_redirect', array( $this, 'prevent_access_to_product_page' ) );
			
			// Definieer een eigen filter met de assortimentsvoorwaarden, zodat we alles slechts één keer hoeven in te geven
			add_filter( 'oxfam_product_is_available', array( $this, 'check_product_availability' ), 10, 3 );
			// add_action( 'pre_get_posts', array( $this, 'filter_allowed_products' ) );

			// Verhinder het toevoegen van verboden producten én laat de koopknop verdwijnen + zwier reeds toegevoegde producten uit het winkelmandje
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'disallow_products_not_in_assortment' ), 10, 2 );
			add_filter( 'woocommerce_is_purchasable', array( $this, 'disable_products_not_in_assortment' ), 10, 2 );
			add_filter( 'woocommerce_product_is_visible', array( $this, 'enable_private_products_for_customers' ), 10, 2 );

			// Tweaks aan WooCommerce Product Table
			add_filter( 'wc_product_table_use_numeric_skus', '__return_true' );
			add_filter( 'wc_product_table_open_products_in_new_tab', '__return_true' );
			add_filter( 'wc_product_table_column_searchable_price', '__return_false' );
			add_filter( 'wc_product_table_column_searchable_add_to_cart', '__return_false' );
			add_filter( 'wc_product_table_data_name', array( $this, 'add_consumer_units_per_order_unit' ), 10, 2 );
			add_filter( 'wc_product_table_column_heading_name', function( $label ) {
				return __( 'Omschrijving', 'oft-mdm' );
			} );
			add_filter( 'wc_product_table_column_heading_price', function( $label ) {
				return ucfirst( __( 'per ompak', 'oft-mdm' ) );
			} );
			add_filter( 'wc_product_table_column_heading_add-to-cart', function( $label ) {
				return __( 'Bestellen?', 'oft-mdm' );
			} );
			add_filter( 'wc_product_table_data_add_to_cart', function( $html, $product ) {
				return $html . __( 'OMPAKINFO', 'oft-mdm' );
			}, 10, 2 );

			// Zorg ervoor dat de logica uit de product loop ook toegepast wordt in de tabel
			add_filter( 'wc_product_table_query_args', array( $this, 'add_custom_product_query_args' ), 10, 2 );
			add_filter( 'wc_product_table_search_filter_get_terms_args', function( $term_args, $taxonomy, $product_table_args ) {
				if ( $taxonomy === 'pa_merk' ) {
					$term_args['hide_empty'] = false;
					// var_dump_pre($product_table_args);
					// var_dump_pre($term_args);
				}
				return $term_args;
			}, 10, 3 );
			
			// Synchroniseer de publicatiestatus vanuit de hoofdtaal naar anderstalige producten (zoals bij trashen reeds automatisch door WPML gebeurt)
			// Neem een hoge prioriteit, zodat de functie pas doorlopen wordt na de 1ste 'save_post' die de zichtbaarheid regelt
			add_action( 'draft_to_publish', array( $this, 'sync_product_status' ), 100 );
			add_action( 'draft_to_private', array( $this, 'sync_product_status' ), 100 );
			add_action( 'publish_to_draft', array( $this, 'sync_product_status' ), 100 );
			add_action( 'private_to_draft', array( $this, 'sync_product_status' ), 100 );
		}

		function delay_actions_and_filters_till_load_completed() {
			if ( ! is_user_logged_in() ) {
				// Alle koopfuncties uitschakelen voor niet-ingelogde gebruikers (hoge prioriteit)
				add_filter( 'woocommerce_is_purchasable', '__return_false', 100, 2 );

				// Verberg alle niet-OFT-voedingsproducten NIET NODIG, GEEF CUSTOMERS GEWOON 'READ_PRIVATE_PRODUCTS'-RECHTEN M.B.V. ROLE EDITOR
				// add_action( 'woocommerce_product_query', array( $this, 'hide_external_products' ) );
				// function hide_external_products( $query ) {
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

				// Voeg ompakinfo toe WERKT NIET IN LOOPS EN OP DETAILPAGINA'S
				add_action( 'woocommerce_single_product_summary', array( $this, 'add_order_unit_info' ), 12 );
				add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'add_order_unit_info' ), 12 );
				add_filter( 'woocommerce_product_title', array( $this, 'add_consumer_units_per_order_unit' ), 10, 2 );
				add_filter( 'woocommerce_cart_item_name', array( $this, 'add_consumer_units_per_order_unit' ), 10, 2 );
				add_filter( 'woocommerce_order_item_name', array( $this, 'add_consumer_units_per_order_unit' ), 10, 2 );
			}
		}

		function filter_allowed_products( $query ) {
			if ( ! is_admin() and $query->query_vars['post_type'] === 'product' ) {
				// Alternatieve manier om producten af te schermen?
				write_log( serialize($query) );
			}
		}

		function register_client_type_taxonomy() {
			$taxonomy_name = 'product_client_type';
			
			$labels = array(
				'name' => __( 'Klantengroepen', 'oft-mdm' ),
				'singular_name' => __( 'Klantengroep', 'oft-mdm' ),
				'all_items' => __( 'Alle klantengroepen', 'oft-mdm' ),
				'parent_item' => __( 'Klantengroep', 'oft-mdm' ),
				'parent_item_colon' => __( 'Klantengroep:', 'oft-mdm' ),
				'new_item_name' => __( 'Nieuwe klantengroep', 'oft-mdm' ),
				'add_new_item' => __( 'Voeg nieuwe klantengroep toe', 'oft-mdm' ),
				'view_item' => __( 'Klantengroep bekijken', 'oft-mdm' ),
				'edit_item' => __( 'Klantengroep bewerken', 'oft-mdm' ),
				'update_item' => __( 'Klantengroep bijwerken', 'oft-mdm' ),
				'search_items' => __( 'Klantengroepen doorzoeken', 'oft-mdm' ),
			);

			$args = array(
				'labels' => $labels,
				'description' => __( 'Maak dit product exclusief beschikbaar voor deze klantengroep', 'oft-mdm' ),
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

		function show_extra_user_fields( $user ) {
			if ( current_user_can('manage_woocommerce') ) { 
				?>
				<h3><?php _e( 'Extra instellingen', 'oft-mdm' ); ?></h3>
				<table class="form-table">
					<tr>
						<th><label for="client_type"><?php _e( 'Klantengroep', 'oft-mdm' ); ?></label></th>
						<td>
							<?php
								$args = array(
									'name' => 'client_type',
									'taxonomy' => 'product_client_type',
									'hide_empty' => false,
									'show_option_none' => __( '(selecteer)', 'oft-mdm' ),
									'option_none_value' => '',
									'value_field' => 'name',
									'selected' => get_the_author_meta( 'client_type', $user->ID ),
								);
								wp_dropdown_categories($args);
							?>
							<p class="description"><?php _e( 'Deze instelling bepaalt welk assortiment zichtbaar én bestelbaar is voor deze klant. De taalkeuze is vrij maar kan nog gelimiteerd worden, bv. indien niet alle producten in elke taal beschikbaar zouden zijn (bv. servicemateriaal).', 'oft-mdm' ); ?></p>
						</td>
					</tr>
				</table>
				<?php
			}
		}

		function save_extra_user_fields( $user_id ) {
			if ( ! current_user_can('manage_woocommerce') ) {
				return false;
			}
			update_user_meta( $user_id, 'client_type', $_POST['client_type'] );
		}

		function add_quantity_inputs_to_add_to_cart_link( $html, $product ) {
			if ( $product->is_type('simple') and $product->is_purchasable() and $product->is_in_stock() and ! $product->is_sold_individually() ) {
				$html = woocommerce_quantity_input( array(
					'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
					'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
					'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( $_POST['quantity'] ) : $product->get_min_purchase_quantity(),
				), $product, false ) . $html;
			}
			return $html;
		}

		function get_price_for_current_client( $price, $product, $user_id = false, $regular = false ) {
			// Prijs nooit manipuleren in back-end
			// AJAX-callbacks gebeuren ook 'in de back-end', voorzie hiervoor een uitzondering 
			if ( ! is_admin() or ( defined('DOING_AJAX') and DOING_AJAX ) ) {
				// Huidige prijs expliciet doorgeven aan functie want anders infinite loop!
				$price = $this->get_price_by_client_type( $product, $this->get_client_type( $user_id ), $price, $regular );
			}
			return $price;
		}

		// Wrapperfunctie om $regular === true door te geven aan universele get_price_for_current_client()
		function get_regular_price_for_current_client( $price, $product, $user_id = false ) {
			return $this->get_price_for_current_client( $price, $product, $user_id, true );
		}

		function get_price_by_client_type( $product, $client_type, $price = false, $regular = false ) {
			if ( $client_type !== '' ) {
				if ( $product->meta_exists( '_price_for_client_type_' . strtolower( $client_type ) ) ) {
					// BTW WORDT NADIEN NOG AFGETROKKEN WANT CP'S GEVEN WE IN INCL BTW ...
					$price = floatval( $product->get_meta( '_price_for_client_type_' . strtolower( $client_type ) ) );
				} else {
					// TIJDELIJKE FIX, ODISY ZAL OMPAKPRIJZEN DOORGEVEN
					if ( intval( $product->get_meta('_multiple') ) > 1 ) {
						$price *= intval( $product->get_meta('_multiple') );
					}
				}
			}

			return $price;
		}

		function get_client_type( $user_id = false ) {
			if ( $user_id === false ) {
				$user_id = get_current_user_id();
			}

			// Retourneert een lege string indien klantenrol niet ingesteld
			return get_user_meta( $user_id, 'client_type', true );
		}

		function add_order_unit_info() {
			global $post;
			_e( 'OMPAKINFO', 'oft-mdm' );
		}
				
		function add_consumer_units_per_order_unit( $title, $product ) {
			if ( ! $product instanceof WC_Product_Simple ) {
				$product = $product['data'];
			}
			if ( intval( $product->get_meta('_multiple') ) > 1 ) {
				$title .= ' x ' . $product->get_meta('_multiple') . ' ';
				if ( $product->get_meta('_vkeh_uom') !== '' ) {
					$title .= __( strtolower( $product->get_meta('_multiple_unit') ), 'oft-mdm' );
				} else {
					$title .= __( 'stuks', 'oft-mdm' );
				}
			}
			return $title;
		}

		function disable_post_creation( $fields ) {
			if ( ! current_user_can('edit_products') ) {
				$fields['capabilities'] = array( 'create_posts' => false );
			}
			return $fields;
		}

		function disable_manual_product_removal( $post_id ) {
			if ( 'product' === get_post_type( $post_id ) and $_SERVER['SERVER_NAME'] === 'www.oxfamfairtrade.be' ) {
				wp_die( sprintf( __( 'Uit veiligheidsoverwegingen is het verwijderen van producten niet toegestaan, voor geen enkele gebruikersrol! Vraag &ndash; indien nodig &ndash; dat de hogere machten op %s deze beperking tijdelijk opheffen, zodat je je vuile zaakjes kunt opknappen.', 'oft-mdm' ), '<a href="mailto:'.get_option('admin_email').'">'.get_option('admin_email').'</a>' ) );
			}
		}

		function get_routecode( $user_id = false ) {
			if ( $user_id === false ) $user_id = get_current_user_id();
			// MOET UIT HUIDIG GESELECTEERDE VERZENDADRES KOMEN, NIET UIT USER PROFIEL
			$helper = explode( '-', get_user_meta( $user_id, 'shipping_routecode', true ) );
			// AANPASSEN AAN DRIEWEKELIJKS SYSTEEM
			if ( is_numeric($helper[0]) ) {
				$routecode = intval($helper[0]);
			} else {
				$routecode = $helper[0];
			}
			return $routecode;
		}

		function customer_still_has_open_orders( $status = 'processing', $user_id = false, $delivery_date = false ) {
			if ( $user_id === false ) {
				$user_id = get_current_user_id();
			}

			// TO DO: Filter op specifieke leverdatum toevoegen
			$args = array(
				'status' => $status,
				'type' => 'shop_order',
				'customer_id' => $user_id,
				'limit' => -1,
				'return' => 'objects',
			);
			// Zoek alle orders van de klant met deze status op
			$open_orders = wc_get_orders($args);

			if ( count( $open_orders ) > 0 ) {
				return $open_orders;
			} else {
				return false;
			}
		}

		function oftc_hide_invalid_shipping_methods( $rates, $package ) {
			if ( $this->get_client_type() !== 'OWW' ) {
				// WOBAL-levering uitschakelen
				unset( $rates[ self::WOBAL_METHOD ] );
				// Afhaling in Waver uitschakelen
				unset( $rates[ self::MDM_PIKCUP_METHOD ] );
			} else {
				// TO DO: Leeggoed uit alle subtotalen weglaten
				$current_subtotal = WC()->cart->get_subtotal();
				
				// Subtotalen optellen en checken of we samen met de huidige inhoud al boven FRC/FRCG zitten
				if ( $orders = $this->customer_still_has_open_orders() ) {
					foreach ( $orders as $$order ) {
						$current_subtotal += $order->get_subtotal();
					}
				}

				if ( $current_subtotal > 1000 ) {
					// Onmiddellijke gratis WOBAL-levering vanaf 1000 euro = FRCG
					// TO DO: Check of de huidige datum toevallig een default leverdag is, of dat we dynamisch van routecode moeten switchen
					// Misschien beter een aparte levermethode aanmaken voor deadline hoppers? Handig voor rapportering!
					$rates[ self::WOBAL_METHOD ]->set_cost(0);
				} elseif ( $current_subtotal > 500 ) {
					// Reguliere gratis WOBAL-levering vanaf 500 euro = FRC
					$rates[ self::WOBAL_METHOD ]->set_cost(0);
					// unset( $rates[ self::EXPRESS_METHOD ] );
				} else {
					// Betalende WOBAL-levering
				}
			}

			if ( $this->get_client_type() !== 'MDM' ) {
				// TOURNEE-levering uitschakelen
				unset( $rates[ self::TOURNEE_METHOD ] );
			}

			return $rates;
		}

		function make_addresses_readonly( $address_fields ) {
			if ( is_admin() and current_user_can('update_core') ) {
				$custom_attributes = array();
			} else {
				$custom_attributes = array( 'readonly' => 'readonly' );
			}

			$address_fields['company']['custom_attributes'] = $custom_attributes;
			
			$address_fields['address_1']['label'] = __( 'Straat en nummer', 'oft-mdm' );
			$address_fields['address_1']['placeholder'] = '';
			$address_fields['address_1']['required'] = true;
			$address_fields['address_1']['custom_attributes'] = $custom_attributes;
			
			$address_fields['postcode']['label'] = __( 'Postcode', 'oft-mdm' );
			$address_fields['postcode']['placeholder'] = '';
			$address_fields['postcode']['required'] = true;
			$address_fields['postcode']['custom_attributes'] = $custom_attributes;
			$address_fields['postcode']['clear'] = false;
			$address_fields['postcode']['class'] = array('form-row-first');

			$address_fields['city']['label'] = __( 'Gemeente', 'oft-mdm' );
			$address_fields['city']['placeholder'] = '';
			$address_fields['city']['required'] = true;
			$address_fields['city']['custom_attributes'] = $custom_attributes;
			$address_fields['city']['clear'] = true;
			$address_fields['city']['class'] = array('form-row-last');

			if ( self::$company === 'oft' ) {
				$billing_number_key = 'number_oft';
				$address_fields[$billing_number_key]['label'] = __( 'Klantnummer OFT', 'oft-mdm' );
			} else {
				$billing_number_key = 'number_omdm';
				$address_fields[$billing_number_key]['label'] = __( 'Klantnummer OMDM', 'oft-mdm' );
			}
			$address_fields[$billing_number_key]['placeholder'] = '';
			$address_fields[$billing_number_key]['required'] = true;
			$address_fields[$billing_number_key]['custom_attributes'] = $custom_attributes;
			$address_fields[$billing_number_key]['class'] = false;

			// Land voorlopig nog verbergen
			$address_fields['country']['class'] = array('hidden');
			// Provincie volledig verwijderen
			unset( $address_fields['state'] );
			
			return $address_fields;
		}

		function format_checkout_billing( $address_fields ) {
			$address_fields['billing_first_name']['label'] = __( 'Voornaam', 'oft-mdm' );
			$address_fields['billing_first_name']['description'] = __( 'Gelieve je eigen naam in te vullen!', 'oft-mdm' );
			$address_fields['billing_first_name']['placeholder'] = "Charles";
			$address_fields['billing_last_name']['description'] = __( 'Gelieve je eigen naam in te vullen!', 'oft-mdm' );
			$address_fields['billing_last_name']['label'] = __( 'Familienaam', 'oft-mdm' );
			$address_fields['billing_last_name']['placeholder'] = "Michel";
			$address_fields['billing_phone']['label'] = __( 'Telefoonnummer', 'oft-mdm' );
			$address_fields['billing_phone']['placeholder'] = "02 501 02 11";
			$address_fields['billing_phone']['description'] = __( 'Zo kunnen we je contacteren bij problemen.', 'oft-mdm' );
			$address_fields['billing_email']['label'] = __( 'Mail orderbevestiging naar', 'oft-mdm' );
			$address_fields['billing_email']['placeholder'] = "charles.michel@premier.fed.be";
			$address_fields['billing_email']['description'] = __( 'Dit hoeft niet je eigen e-mailadres te zijn.', 'oft-mdm' );
			$address_fields['billing_company']['label'] = __( 'Te factureren entiteit', 'oft-mdm' );
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

			if ( self::$company === 'mdm' and $this->get_client_type() === 'MDM' ) {
				unset( $address_fields['billing_number_oft'] );
			}
			
			return $address_fields;
		}

		function format_checkout_shipping( $address_fields ) {
			if ( is_admin() and current_user_can('update_core') ) {
				$custom_attributes = array();
			} else {
				$custom_attributes = array( 'readonly' => 'readonly' );
			}
			
			$address_fields['shipping_company']['label'] = __( 'Te beleveren winkel', 'oft-mdm' );
			$address_fields['shipping_company']['required'] = true;
			$address_fields['shipping_number_oft']['label'] = __( 'Levernummer OFT', 'oft-mdm' );
			$address_fields['shipping_number_oft']['placeholder'] = '';
			// Hier nu wel algemeen verplichten i.p.v. pas checken in 'woocommerce_after_checkout_validation'-filter (maar veld verwijderen indien onnodig, zie verder)
			$address_fields['shipping_number_oft']['required'] = true;
			$address_fields['shipping_number_oft']['custom_attributes'] = $custom_attributes;
			$address_fields['shipping_number_oft']['class'] = array('form-row-first');

			// Of toch state blijven manipuleren?
			$address_fields['shipping_routecode']['label'] = __( 'Routecode', 'oft-mdm' );
			$address_fields['shipping_routecode']['placeholder'] = '';
			$address_fields['shipping_routecode']['required'] = true;
			$address_fields['shipping_routecode']['custom_attributes'] = $custom_attributes;
			$address_fields['shipping_routecode']['class'] = array('form-row-last');
			
			unset( $address_fields['shipping_first_name'] );
			unset( $address_fields['shipping_last_name'] );
			$address_fields['shipping_number_oft']['priority'] = 21;
			$address_fields['shipping_routecode']['priority'] = 22;
			$address_fields['shipping_company']['priority'] = 23;
			$address_fields['shipping_address_1']['priority'] = 31;
			$address_fields['shipping_postcode']['priority'] = 32;
			$address_fields['shipping_city']['priority'] = 41;
			$address_fields['shipping_country']['priority'] = 42;

			if ( $this->get_client_type() === 'MDM' ) {
				unset( $address_fields['shipping_number_oft'] );
				unset( $address_fields['shipping_routecode'] );
				unset( $address_fields['shipping_default_day'] );
			}

			return $address_fields;
		}

		function define_woocommerce_routecodes( $states ) {
			$routecodes_mdm = array(
				'1-AB' => __( 'Henegouwen', 'oft-mdm' ),
				'1-A' => __( 'Henegouwen', 'oft-mdm' )." ".__( '(even weken)', 'oft-mdm' ),
				'1-B' => __( 'Henegouwen', 'oft-mdm' )." ".__( '(oneven weken)', 'oft-mdm' ),
				'2-AB' => __( 'Namen', 'oft-mdm' ),
				'2-A' => __( 'Namen', 'oft-mdm' )." ".__( '(even weken)', 'oft-mdm' ),
				'2-B' => __( 'Namen', 'oft-mdm' )." ".__( '(oneven weken)', 'oft-mdm' ),
				'3-AB' => __( 'Luik', 'oft-mdm' ),
				'3-A' => __( 'Luik', 'oft-mdm' )." ".__( '(even weken)', 'oft-mdm' ),
				'3-B' => __( 'Luik', 'oft-mdm' )." ".__( '(oneven weken)', 'oft-mdm' ),
				'4-AB' => __( 'Luxemburg', 'oft-mdm' ),
				'4-A' => __( 'Luxemburg', 'oft-mdm' )." ".__( '(even weken)', 'oft-mdm' ),
				'4-B' => __( 'Luxemburg', 'oft-mdm' )." ".__( '(oneven weken)', 'oft-mdm' ),
				'5-AB' => __( 'Brussel', 'oft-mdm' ),
				'5-A' => __( 'Brussel', 'oft-mdm' )." ".__( '(even weken)', 'oft-mdm' ),
				'5-B' => __( 'Brussel', 'oft-mdm' )." ".__( '(oneven weken)', 'oft-mdm' ),
			);

			$states['BE'] = self::$routecodes_oww + $routecodes_mdm + self::$routecodes_ext;
			$states['NL'] = self::$routecodes_ext;
			$states['LU'] = self::$routecodes_ext;
			$states['DE'] = self::$routecodes_ext;
			$states['FR'] = self::$routecodes_ext;
			$states['ES'] = self::$routecodes_ext;
		}

		function load_custom_address_data( $args, $customer_id, $address_type ) {
			$value = get_user_meta( $customer_id, $address_type . '_number_' . self::$company, true );
			$args['client_number'] = $value;
			// Hoe veralgemenen we dit naar adressen die niet rechtstreeks op het gebruikersprofiel opgeslagen zijn?
			write_log($value);
			return $args;
		}

		function add_address_replacements( $placeholders, $args ) {
			$placeholders['{client_number}'] = $args['client_number'];
			return $placeholders;
		}

		function change_address_formats( $formats ) {
			// Dubbele quotes zijn nodig voor correcte interpretatie van line breaks!
			$formats['BE'] = __( 'Adresnummer', 'oft-mdm' )." {client_number}\n{company}\n{address_1}\n{postcode} {city}";
			$formats['NL'] = "{client_number}\n{company}\n{address_1}\n{postcode} {city}\n{country_upper}";
			$formats['LU'] = "{client_number}\n{company}\n{address_1}\n{postcode} {city}\n{country_upper}";
			$formats['DE'] = "{client_number}\n{company}\n{address_1}\n{postcode} {city}\n{country_upper}";
			$formats['FR'] = "{client_number}\n{company}\n{address_1}\n{postcode} {city}\n{country_upper}";
			$formats['ES'] = "{client_number}\n{company}\n{address_1}\n{postcode} {city}\n{country_upper}";
			return $formats;
		}

		function modify_user_admin_fields( $profile_fields ) {
			global $user_id;
			$blocking_warning = __( 'Kan niet gewijzigd worden door klant (verplicht veld)', 'oft-mdm' );
			
			$profile_fields['billing']['fields']['billing_first_name']['label'] = __( 'Voornaam besteller', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_first_name']['description'] = __( 'Bevat gegevens van de laatste bestelling', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_last_name']['label'] = __( 'Familienaam besteller', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_last_name']['description'] = __( 'Bevat gegevens van de laatste bestelling', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_company']['label'] = __( 'Te factureren entiteit', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_company']['description'] = $blocking_warning;
			$profile_fields['billing']['fields']['billing_address_1']['label'] = __( 'Straat en nummer', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_address_1']['description'] = $blocking_warning;
			$profile_fields['billing']['fields']['billing_city']['label'] = __( 'Gemeente', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_city']['description'] = $blocking_warning;
			$profile_fields['billing']['fields']['billing_postcode']['label'] = __( 'Postcode', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_postcode']['description'] = $blocking_warning;
			$profile_fields['billing']['fields']['billing_phone']['label'] = __( 'Telefoonnummer besteller', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_phone']['description'] = __( 'Bevat gegevens van de laatste bestelling', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_email']['label'] = __( 'Mailadres voor orderbevestigingen', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_email']['description'] = __( 'Bevat gegevens van de laatste bestelling', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_number_oft']['label'] = __( 'Klantnummer OFT', 'oft-mdm' );
			$profile_fields['billing']['fields']['billing_number_oft']['description'] = $blocking_warning;
			
			$profile_fields['shipping']['fields']['shipping_company']['label'] = __( 'Te beleveren winkel', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_company']['description'] = $blocking_warning;
			$profile_fields['shipping']['fields']['shipping_address_1']['label'] = __( 'Straat en nummer', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_address_1']['description'] = $blocking_warning;
			$profile_fields['shipping']['fields']['shipping_city']['label'] = __( 'Gemeente', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_city']['description'] = $blocking_warning;
			$profile_fields['shipping']['fields']['shipping_postcode']['label'] = __( 'Postcode', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_postcode']['description'] = $blocking_warning;
			$profile_fields['shipping']['fields']['shipping_routecode']['label'] = __( 'Routecode', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_routecode']['description'] = __( 'Bepaalt automatisch de besteldeadlines en eerstmogelijke leverdag', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_routecode']['type'] = 'select';
			// CSS NOG TOE TE VOEGEN AAN ADMIN.SCSS INDIEN WE VELD WILLEN DISABLEN (MAAR MISSCHIEN PROBLEEM MET OPSLAAN, BETER VIA SAVE ACTION UITSCHAKELEN?)
			// $profile_fields['shipping']['fields']['shipping_number_oft']['class'] = 'readonly';

			// Toon de juiste routecodes, naar gelang het kanaal van de GERAADPLEEGDE user (dus niet get_current_user_id() gebruiken!)
			$client_type = $this->get_client_type( $user_id );
			$empty = array( 'EMPTY' => __( '(selecteer)', 'oftc' ) );

			if ( $client_type === 'OWW' ) {
				// Géén array_merge() gebruiken want numerieke keys worden dan hernummerd vanaf 0!
				$available_codes = $empty + self::$routecodes_oww;
			} else {
				// Externe B2B-klanten
				$available_codes = $empty + self::$routecodes_daily + self::$routecodes_ext;
			}
			$profile_fields['shipping']['fields']['shipping_routecode']['options'] = $available_codes;
			$profile_fields['shipping']['fields']['shipping_default_day']['label'] = __( 'Default leverdag', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_default_day']['description'] = __( 'Hier kan altijd nog van afgeweken worden', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_default_day']['type'] = 'select';
			$profile_fields['shipping']['fields']['shipping_default_day']['options'] = $empty + self::$routecodes_daily;
			$profile_fields['shipping']['fields']['shipping_number_oft']['label'] = __( 'Levernummer OFT', 'oft-mdm' );
			$profile_fields['shipping']['fields']['shipping_number_oft']['description'] = $blocking_warning;

			// Herorden de factuurvelden en laat 'billing_address_2' en 'billing_state' weg
			$bill_order = array(
				'billing_first_name',
				'billing_last_name',
				'billing_phone',
				'billing_email',
				'billing_number_oft',
				'billing_company',
				'billing_address_1',
				'billing_postcode',
				'billing_city',
				'billing_country',
			);

			foreach ( $bill_order as $field ) {
				$ordered_billing_fields[$field] = $profile_fields['billing']['fields'][$field];
			}

			// Herorden de verzendvelden en laat 'shipping_first_name', 'shipping_last_name' en 'shipping_address_2' weg
			$ship_order = array(
				'shipping_number_oft',
				'shipping_routecode',
				'shipping_default_day',
				'shipping_company',
				'shipping_address_1',
				'shipping_postcode',
				'shipping_city',
				'shipping_country',
			);

			foreach ( $ship_order as $field ) {
				$ordered_shipping_fields[$field] = $profile_fields['shipping']['fields'][$field];
			}

			$profile_fields['billing']['fields'] = $ordered_billing_fields;
			$profile_fields['shipping']['fields'] = $ordered_shipping_fields;

			if ( $client_type !== 'OWW' ) {
				// Klantnummer OFT niet nodig voor B2B/MDM-klanten!
			}

			return $profile_fields;
		}

		function check_product_availability( $product_id, $client_type, $available ) {
			if ( is_user_logged_in() and ! current_user_can('manage_woocommerce') ) {	
				if ( $this->enable_private_products_for_customers( $available, $product_id ) ) {
					$available = true;
				}

				if ( ! has_term( $client_type, 'product_client_type', $product_id ) ) {
					$available = false;
				}
			}

			// if ( $available ) write_log("PRODUCT IS ".$product_id." IS AVAILABLE");
			return $available;
		}

		function limit_assortment_for_client_type_archives( $query ) {
			if ( is_user_logged_in() and ! current_user_can('manage_woocommerce') ) {
				$assortments = array();
				$assortments[] = $this->get_client_type();
				
				$tax_query = (array) $query->get('tax_query');
				$tax_query[] = array(
					'taxonomy' => 'product_client_type',
					'field' => 'name',
					'terms' => $assortments,
					'operator' => 'IN',
				);

				$query->set( 'tax_query', $tax_query );	
			}
		}
	
		function limit_assortment_for_client_type_shortcodes( $query_args ) {
			if ( is_user_logged_in() and ! current_user_can('manage_woocommerce') ) {
				$assortments = array();
				$assortments[] = $this->get_client_type();
				
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_client_type',
					'field' => 'name',
					'terms' => $assortments,
					'operator' => 'IN',
				);
			}

			return $query_args;
		}

		function disallow_products_not_in_assortment( $passed, $product_id ) {
			$passed_extra_conditions = apply_filters( 'oxfam_product_is_available', $product_id, $this->get_client_type(), $passed );

			if ( $passed and ! $passed_extra_conditions ) {
				$product = wc_get_product( $product_id );
				wc_add_notice( sprintf( __( 'Als %1$s-klant kun je %2$s niet bestellen.', 'oft-mdm' ), $this->get_client_type(), $product->get_name() ), 'error' );
			}
			
			return $passed_extra_conditions;
		}

		function disable_products_not_in_assortment( $purchasable, $product ) {
			return apply_filters( 'oxfam_product_is_available', $product->get_id(), $this->get_client_type(), $purchasable );
		}

		function enable_private_products_for_customers( $visible, $product_id ) {
			if ( $visible === false and is_user_logged_in() ) {
				$user = wp_get_current_user();
				// Overrule de zichtbaarheid van private producten voor ingelogde klanten met 'read_private_products' maar zonder 'edit_posts'-rechten
				// if ( in_array( 'customer', (array) $user->roles ) ) {
				if ( current_user_can('read_private_products') ) {
					if ( get_post_status( $product_id ) === 'private' ) {
						// Indien het product niet in het assortiment van de klant zit, is het reeds eerder weggefilterd uit de query
						$visible = true;
					}
				}
			}
			return $visible;
		}

		function prevent_access_to_product_page() {
			if ( is_product() ) {
				$available = apply_filters( 'oxfam_product_is_available', get_the_ID(), $this->get_client_type(), true );
				
				if ( ! $available ) {
					// Als de klant nog niets in het winkelmandje zitten heeft, is er nog geen sessie om notices aan toe te voegen!
					if ( ! WC()->session->has_session() ) {
						WC()->session->set_customer_session_cookie(true);
					}
					wc_add_notice( sprintf( __( '%s is niet beschikbaar voor jou.', 'oft-mdm' ), get_the_title() ), 'error' );
					
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

		function add_custom_product_query_args( $wp_query_args, $product_table_query ) {
			// Quick order is enkel beschikbaar op pagina die afgeschermd is voor gewone bezoekers, dus extra check op gebruikersrechten is niet nodig
			$wp_query_args['post_status'] = array( 'publish', 'private' );
			$wp_query_args['tax_query'] = $this->limit_assortment_for_client_type_shortcodes( $wp_query_args['tax_query'] );
			// var_dump_pre($wp_query_args);
			return $wp_query_args;
		}

		function sync_product_status( $post ) {
			$post_lang = apply_filters( 'wpml_post_language_details', NULL, $post->ID );
			$default_lang_code = apply_filters( 'wpml_default_language', NULL );

			// Werkt enkel indien we in de hoofdtaal bezig zijn!
			// write_log( serialize($post_lang) );
			if ( $post->post_type === 'product' and $post_lang['language_code'] === $default_lang_code ) {
				$main_product = wc_get_product( $post->ID );
				if ( $main_product !== false ) {
					$languages = apply_filters( 'wpml_active_languages', NULL );
					unset( $languages[ $default_lang_code ] );
					foreach ( $languages as $lang_code => $language ) {
						$product = wc_get_product( apply_filters( 'wpml_object_id', $post->ID, 'product', false, $lang_code ) );
						if ( $product !== false ) {
							write_log( "SYNCING ".strtoupper($default_lang_code)." PRODUCT STATUS TO ".strtoupper($lang_code)." VERSION" );
							$product->set_status( $main_product->get_status() );
							$product->save();
						}
					}
				}
			}
		}
	}
?>