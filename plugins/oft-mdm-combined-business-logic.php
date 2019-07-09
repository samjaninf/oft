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
		const OFTL_PICKUP_METHOD = 'local_pickup:2';
		const MDM_PICKUP_METHOD = 'local_pickup:3';

		static $company, $empties, $routecodes_oww, $routecodes_daily, $routecodes_ext, $routecodes_mdm;

		function __construct( $param = 'oft' ) {
			self::$company = $param;
			self::$empties = array( '29900', '29902', '29906', '29916' );
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
				'4' => __( 'Donderdag', 'oft-mdm' ),
				'5' => __( 'Vrijdag', 'oft-mdm' ),
			);
			self::$routecodes_ext = array(
				'T' => __( 'Externe klant', 'oft-mdm' ),
				'TB' => __( 'Externe klant (B)', 'oft-mdm' ),
				'MDM' => __( 'Magasins du Monde', 'oft-mdm' ),
			);
			self::$routecodes_mdm = array(
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
			add_filter( 'oft_mdm_product_is_available', array( $this, 'check_product_availability' ), 10, 3 );
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
			add_filter( 'wc_product_table_column_heading_sku', function( $label ) {
				return __( 'Artikel', 'oft-mdm' );
			} );
			add_filter( 'wc_product_table_column_heading_image', function( $label ) {
				return __( 'Foto', 'oft-mdm' );
			} );
			add_filter( 'wc_product_table_column_heading_name', function( $label ) {
				return __( 'Omschrijving', 'oft-mdm' );
			} );
			add_filter( 'wc_product_table_column_heading_price', function( $label ) {
				return __( 'Aankoopprijs', 'oft-mdm' );
			} );
			add_filter( 'wc_product_table_column_heading_add-to-cart', function( $label ) {
				return __( 'Bestellen?', 'oft-mdm' );
			} );

			// Wordt overruled door style-attribuut indien parameter auto_width niet expliciet op false staat
			add_filter( 'wc_product_table_column_width_sku', function( $width ) {
				return '75px';
			} );
			add_filter( 'wc_product_table_column_width_image', function( $width ) {
				return '75px';
			} );
			add_filter( 'wc_product_table_column_width_price', function( $width ) {
				return '150px';
			} );
			add_filter( 'wc_product_table_column_width_add_to_cart', function( $width ) {
				return '100px';
			} );

			add_filter( 'wc_product_table_data_name', array( $this, 'add_consumer_units_per_order_unit' ), 10, 2 );
			add_filter( 'wc_product_table_data_price', function( $html, $product ) {
				switch ( $product->get_meta('_vkeh_uom') ) {
					case 'ST':
						$unit = __( 'stuk', 'oft-mdm' );
						break;
					
					default:
						$unit = strtolower( $product->get_meta('_vkeh_uom') );
				}
				return $html . '<br/>' . sprintf( __( 'per %s', 'oft-mdm' ), $unit );
			}, 10, 2 );
			add_filter( 'wc_product_table_data_add_to_cart', function( $html, $product ) {
				$suffix = '';

				if ( ! $product->is_in_stock() ) {
					return __( 'Onbeschikbaar', 'oft-mdm' );
				}
				
				if ( $product->get_meta('_vkeh_uom') === 'OMPAK' ) {
					$suffix .= 'of <span class="consumer-units-count" data-product_id="'.$product->get_id().'">' . $product->get_meta('_multiple') . '</span> stuks';
				}

				return $html . $suffix;
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
			add_action( 'draft_to_publish', array( $this, 'sync_product_status' ), 100, 1 );
			add_action( 'draft_to_private', array( $this, 'sync_product_status' ), 100, 1 );
			add_action( 'publish_to_draft', array( $this, 'sync_product_status' ), 100, 1 );
			add_action( 'private_to_draft', array( $this, 'sync_product_status' ), 100, 1 );

			// Toon de eerst mogelijke leverdag onder elke verzendmethode
			add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'print_estimated_delivery' ), 10, 2 );

			// Sla de geschatte lever- en shuttledatum op
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_delivery_date' ), 100, 2 );

			// Maak de Excel voor het ERP-systeem aan, onafhankelijk van het versturen van de bevestigingsmail
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'process_order_excel' ), 1000, 1 );

			// Vraag het ordernummer mét prefix op
			add_filter( 'woocommerce_order_number', array( $this, 'add_order_number_prefix' ), 1000, 2 );

			// Geef wat uitleg waarom de gebruiker een loginscherm te zien krijgt
			add_action( 'woocommerce_before_customer_login_form', array( $this, 'add_login_message' ) );

			// Voeg JavaScript-functies toe aan front-end
			add_action( 'wp_footer', array( $this, 'add_front_end_scripts' ) );
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

		function get_consumer_price( $product ) {
			// Vraag de consumentenprijs op door de prijsfilter tijdelijk te verwijderen
			remove_filter( 'woocommerce_product_get_regular_price', array( $this, 'get_regular_price_for_current_client' ), 100 );
			$cp = floatval( $product->get_regular_price() );
			add_filter( 'woocommerce_product_get_regular_price', array( $this, 'get_regular_price_for_current_client' ), 100, 3 );
			return $cp;
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
			$suffix = '';

			if ( ! $product instanceof WC_Product_Simple ) {
				$product = $product['data'];
				if ( empty( $product ) ) {
					write_log("CHECK CONTENTS OF $PRODUCT IN ADD_CONSUMER_UNITS_PER_ORDER_UNIT()");
					write_log( $product['data'] );
				}
			}

			if ( $product instanceof WC_Product_Simple and intval( $product->get_meta('_multiple') ) > 1 ) {
				$suffix .= 'x ';
				switch ( $product->get_meta('_vkeh_uom') ) {
					case 'ZAK':
						$suffix .= sprintf( __( '%s kilogram', 'oft-mdm' ), $product->get_meta('_multiple') );
						break;

					// 'ST' dienen we niet te beschouwen want voor die producten geldt per definitie: $product->get_meta('_multiple') === 1

					default:
						$suffix .= sprintf( _n( '%d stuk', '%d stuks', intval( $product->get_meta('_multiple') ), 'oft-mdm' ), intval( $product->get_meta('_multiple') ) );
				}
			}

			return $title . '<span class="oft-consumer-units">' . $suffix . '</span>';
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

		function get_routecode( $user_id = false, $shipping_number_oft = 0 ) {
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

		function hide_invalid_shipping_methods( $rates, $package ) {
			if ( $this->get_client_type() !== 'OWW' ) {
				// WOBAL-levering uitschakelen
				unset( $rates[ self::WOBAL_METHOD ] );
			} else {
				// TO DO: Leeggoed uit alle subtotalen weglaten
				$current_subtotal = WC()->cart->get_subtotal();
				
				// Subtotalen optellen en checken of we samen met de huidige inhoud al boven FRC/FRCG zitten
				if ( $orders = $this->customer_still_has_open_orders() ) {
					foreach ( $orders as $order ) {
						$current_subtotal += $order->get_subtotal();
					}
				}

				if ( $current_subtotal > 1000 ) {
					// Onmiddellijke gratis WOBAL-levering vanaf 1000 euro = FRCG
					// TO DO: Check of de huidige datum toevallig een default leverdag is, of dat we dynamisch van routecode moeten switchen
					// Misschien beter een aparte levermethode aanmaken voor deadline hoppers? Handig voor rapportering!
					$rates[ self::WOBAL_METHOD ]->set_cost('0');
				}

				if ( $current_subtotal > 500 ) {
					// Reguliere gratis WOBAL-levering vanaf 500 euro = FRC
					$rates[ self::WOBAL_METHOD ]->set_cost('0');
				} else {
					// Betalende WOBAL-levering
				}
			}

			if ( $this->get_client_type() !== 'MDM' ) {
				// TOURNEE-levering uitschakelen
				unset( $rates[ self::TOURNEE_METHOD ] );
				// Afhaling in Waver uitschakelen
				unset( $rates[ self::MDM_PICKUP_METHOD ] );
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
			$empty = array( 'EMPTY' => __( '(selecteer)', 'oft-mdm' ) );

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

				// Leeggoed altijd beschikbaar maken om problemen te voorkomen
				$product = wc_get_product( $product_id );
				if ( $product !== false and in_array( $product->get_sku(), self::$empties ) ) {
					$available = true;
					write_log("EMPTIES PRODUCT SKU ".$product->get_sku()." SET TO AVAILABLE");
				}
			}

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
			$passed_extra_conditions = apply_filters( 'oft_mdm_product_is_available', $product_id, $this->get_client_type(), $passed );

			if ( $passed and ! $passed_extra_conditions ) {
				$product = wc_get_product( $product_id );
				wc_add_notice( sprintf( __( 'Als %1$s-klant kun je %2$s niet bestellen.', 'oft-mdm' ), $this->get_client_type(), $product->get_name() ), 'error' );
			}
			
			return $passed_extra_conditions;
		}

		function disable_products_not_in_assortment( $purchasable, $product ) {
			return apply_filters( 'oft_mdm_product_is_available', $product->get_id(), $this->get_client_type(), $purchasable );
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
				$available = apply_filters( 'oft_mdm_product_is_available', get_the_ID(), $this->get_client_type(), true );
				
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
			// Quick order is enkel beschikbaar op pagina die afgeschermd is voor gewone bezoekers, dus extra check op gebruikersrechten is in principe niet nodig
			$wp_query_args['post_status'] = array( 'publish', 'private' );
			$wp_query_args['tax_query'] = $this->limit_assortment_for_client_type_shortcodes( $wp_query_args['tax_query'] );
			// Zorg ervoor dat permissies gecheckt worden en private resultaten enkel zichtbaar worden voor rechthebbenden!
			$wp_query_args['perm'] = 'readable';
			write_log("CHECK QUERY ARGS PRODUCT TABLE ...");
			write_log( serialize( $wp_query_args ) );
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

		function print_estimated_delivery( $title, $shipping_rate ) {
			// Haal het levernummer op
			if ( WC()->session->has_session() ) {
				$shipping_address_1 = WC()->session->get('shipping_address_1');
				$shipping_number_oft = WC()->session->get('shipping_number_oft');
				write_log( "SHIPPING NUMBER OF SELECTED ADDRESS: ".$shipping_address_1 );
			} else {
				// Fallback voor klanten zonder winkelmandje / leveradres (bv. bij afhaling)
				// Ophalen uit metadata klant?
				$shipping_number_oft = get_user_meta( get_current_user_id(), 'shipping_number_oft', true );
				$shipping_number_oft = 2128;
			}

			// Methode get_id() uit klasse WC_Shipping_Rate retourneert string van de vorm method_id:instance_id REKENT MET ROUTECODE VAN GESELECTEERD LEVERADRES
			$timestamp = $this->calculate_delivery_day( $shipping_rate->get_id(), $this->get_routecode( false, $shipping_number_oft ) );

			switch ( $shipping_rate->get_id() ) {
				case self::WOBAL_METHOD:
				case self::TOURNEE_METHOD:
					/* TRANSLATORS: %s: geschatte leverdatum, inclusief weekdag (vb. maandag 16/10/2017) */
					$date = sprintf( __( 'Ten vroegste op %s', 'oft-mdm' ), date_i18n( 'l d/m/Y', $timestamp ) );
					break;
				case self::OFTL_PICKUP_METHOD:
				case self::MDM_PICKUP_METHOD:
					$date = sprintf( __( 'Vanaf %s', 'oft-mdm' ), date_i18n( 'l d/m/Y \o\m G\u', $timestamp ) );
					break;
				case self::BPOST_METHOD:
					/* TRANSLATORS: %s: geschatte leverdatum, inclusief weekdag (vb. maandag 16/10/2017) */
					$date = sprintf( __( 'Ten laatste vanaf %s', 'oft-mdm' ), date_i18n( 'l d/m/Y', $timestamp ) );
					break;
				default:
					$date = __( 'Geen schatting beschikbaar', 'oft-mdm' );
			}
			
			return $title.'<br/><small class="delivery-estimate">'.$date.'</small>';
		}

		function save_delivery_date( $order_id, $data ) {
			$order = wc_get_order( $order_id );
			$shipping_method = $this->get_shipping_method( $order );
			$shipping_method_id = $shipping_method->get_method_id().':'.$shipping_method->get_instance_id();

			// Stel de default datum in LOGICA VOOR ROUTECODE VERHUIZEN NAAR CALCULATE_DELIVERY_DAY()?
			$shipping_number_oft = 2128;
			$delivery_timestamp = $this->calculate_delivery_day( $shipping_method_id, $this->get_routecode( false, $shipping_number_oft ) );

			// We moeten blijkbaar niet saven om data in de volgende stap te kunnen opvragen
			$order->update_meta_data( '_orddd_lite_timestamp', $delivery_timestamp );
			
			// if ( $this->is_wobal_delivery( $order ) or $this->is_local_pickup( $order, 'oft' ) ) {
			// 	$shuttle_timestamp = get_shuttle_from_delivery_estimate( $order );
			// 	if ( $shuttle_timestamp !== false ) {
			// 		$order->update_meta_data( '_orddd_shuttle_timestamp', $shuttle_timestamp );
			// 	}
			// }

			// Sla het kanaaltype op bij het order, zodat we later eventueel makkelijk kunnen filteren in de rapporten (zonder JOIN op users)
			$order->update_meta_data( 'client_type', $this->get_client_type( $order->get_customer_id() ) );
			// Sla geformatteerde orderreferentie op (opeenvolgende nummers worden automatisch voorzien door gratis plugin)
			$order->update_meta_data( '_order_number_formatted', $order->get_order_number() );
			$order->save();

			// Verwijder de transient met de meest gekochte producten van de klant
			delete_transient( 'products_purchased_by_frequency_user_'.$order->get_customer_id() );
		}

		function process_order_excel( $order_id ) {
			$start = microtime(true);
			// Creëer de Excel in de taal van het order
			$local_file_path = $this->create_order_excel( wc_get_order( $order_id ) );
			write_log( number_format( microtime(true)-$start, 4, ',', '.' )." s EXCEL CREATED" );

			if ( $local_file_path !== false ) {
				copy( $local_file_path, WP_CONTENT_DIR.'/odisy/import/test.xlsx' );
			}
		}

		function create_order_excel( $order ) {
			global $sitepress;
			require_once WP_PLUGIN_DIR.'/phpspreadsheet/autoload.php';
			
			$logger = wc_get_logger();
			$context = array( 'source' => 'PhpSpreadsheet' );
			
			// Bewaar huidige taal en switch naar de taal van het order
			$previous_lang = apply_filters( 'wpml_current_language', NULL );
			$lang = $order->get_meta('wpml_language');
			$sitepress->switch_lang( $lang, true );
			
			// Laad het bestelsjabloon in de juiste taal
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
			// TO DO: Verhuizen naar plugin, mag niet afhangen van het geselecteerde thema
			$spreadsheet = $reader->load( get_stylesheet_directory().'/assets/order-template.xlsx' );
			
			// Selecteer het eerste werkblad
			$spreadsheet->setActiveSheetIndex(0);
			$order_sheet = $spreadsheet->getActiveSheet();

			// Bepaal enkele infovelden voor de Excel die nog overschreven kunnen worden
			$billing_number_oft = $order->get_meta('_billing_number_oft');
			// Zal 'false' opleveren bij MDM-klanten
			$shipping_number_oft = $order->get_meta('_shipping_number_oft');

			$shipping_method = $this->get_shipping_method( $order );
			$shipping_method_id = $shipping_method->get_method_id().':'.$shipping_method->get_instance_id();
			
			// Haal de voorziene leverdag op
			$timestamp = $order->get_meta('_orddd_lite_timestamp');
			if ( strlen( $timestamp ) !== 10 ) {
				// Fallback indien niet ingesteld VERWIJDEREN?
				$timestamp = $this->calculate_delivery_day( $shipping_method_id, $this->get_routecode( false, $shipping_number_oft ), $order->get_date_created()->getTimestamp() );
			}
			
			switch ( $shipping_method_id ) {
				case self::OFTL_PICKUP_METHOD:
					$order_sheet->setCellValue( 'D6', strtoupper( __( 'Zelf afhalen in:', 'oft-mdm' ) ) );
					$shipping_company = 'Oxfam Fair Trade Logistics Wondelgem';
					$shipping_number_oft = 0;
					break;
				case self::MDM_PICKUP_METHOD:
					$order_sheet->setCellValue( 'D6', strtoupper( __( 'Zelf afhalen in:', 'oft-mdm' ) ) );
					$shipping_company = 'Oxfam Magasins du Monde Wavre';
					$shipping_number_oft = 2388;
					break;
				case self::TOURNEE_METHOD:
					$order_sheet->setCellValue( 'D6', strtoupper( __( 'Levering in MDM:', 'oft-mdm' ) ) );
					$shipping_number_oft = 2388;
					break;
				default:
					// Zet levernummer op 0 indien het gelijk is aan het klantnummer
					if ( intval( $billing_number_oft ) === intval( $shipping_number_oft ) ) {
						$shipping_number_oft = 0;
					}
			}

			// Formatteer leverdag expliciet als een Excel-datum
			// Alle tijden in UST, zie https://phpspreadsheet.readthedocs.io/en/develop/topics/recipes/#write-a-date-or-time-into-a-cell
			$order_sheet->getStyle('B4')->getNumberFormat()->setFormatCode( \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DMYSLASH );
			
			$order_number = $order->get_order_number();
			// Vul de headervelden in
			// B5 = maximum 116 tekens!
			// TO DO: MDM-routecode ophalen
			$tourneecode = '';
			$order_sheet->setCellValue( 'B1', $order->get_billing_first_name().' '.$order->get_billing_last_name().' - '.$order->get_billing_email().' - '.$order->get_billing_phone() )
				->setCellValue( 'B2', $order_number )
				->setCellValue( 'B3', $billing_number_oft )
				->setCellValue( 'B4', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( $timestamp ) )
				->setCellValue( 'B5', $tourneecode.'|'.get_user_meta( $order->get_customer_id(), 'customer_reference', true ).'|'.$order_number.'|WK'.date_i18n( 'W d/m/Y H:i', $order->get_date_created()->getTimestamp() ) )
				->setCellValue( 'B8', $shipping_number_oft )
				->setCellValue( 'B9', $order->get_customer_note() )
				->setCellValue( 'C4', $order->get_customer_note() )
				->setCellValue( 'D6', $order->get_billing_company().' '.$order->get_billing_address_1().', '.$order->get_billing_postcode().' '.$order->get_billing_city() )
				->setCellValue( 'D8', $order->get_shipping_company().' '.$order->get_shipping_address_1().', '.$order->get_shipping_postcode().' '.$order->get_shipping_city() )
				->setCellValue( 'E5', get_option('woocommerce_store_address').', '.get_option('woocommerce_store_postcode').' '.get_option('woocommerce_store_city').' | '.get_option('woocommerce_store_address_2') );

			// Check of we het ordertype moeten wijzigen
			if ( $order->get_meta('_order_has_cheques') === 'yes' ) {
				// NIET NODIG, MACRO DOET HET WERK
				// $order_sheet->setCellValue( 'B6', 'CH' );
			}
			
			// Vul extra specifieke parameters voor MDM's in
			if ( $order->get_meta('client_type') === 'MDM' ) {
				// TOURNEE-code?
			}
			
			$i = 11;
			// Vul de artikeldata item per item in vanaf rij 11
			foreach ( $order->get_items() as $order_item_id => $order_item ) {
				// Doorgaans zal de creatie meteen gebeuren maar eigenlijk kunnen we niet 100% zeker zijn dat het product nog bestaat ...
				$product = $order_item->get_product();

				if ( in_array( $product->get_sku(), self::$empties ) ) {
					// Skip leeggoedartikels, worden automatisch toegevoegd door Odisy!
					continue;
				}

				$tax_class = $order_item->get_tax_class();
				if ( $tax_class === 'zero-rate' ) {
					$tariff = '0.00';
				} elseif ( $tax_class === 'reduced-rate' ) {
					$tariff = '0.06';
				} else {
					$tariff = '0.21';
				}
				// Stukprijs niét ophalen via wc_get_price_to_display() want:
				// - het product kan theoretisch inmiddels een andere prijs hebben (vb. door wachtperiode tot in behandeling nemen van rollijst)
				// - de Excel kan handmatig opnieuw gecreëerd worden vanuit de back-end door ingelogde gebruiker uit andere klantengroep
				// Formattering van prijzen gebeurt in Excel-template!
				// ->setCellValue( 'A'.$i, $order_item->get_total() / $order_item->get_quantity() )
				$order_sheet->setCellValue( 'A'.$i, number_format( $this->get_consumer_price( $product ), 2, ',', '.' ).' '.__( 'euro', 'oft-mdm' ) )
					->setCellValue( 'B'.$i, $product->get_sku() )
					->setCellValue( 'C'.$i, $order_item->get_quantity() )
					->setCellValue( 'D'.$i, $order_item->get_name() )
					->setCellValue( 'E'.$i, $product->get_meta('_vkeh_uom') )
					->setCellValue( 'F'.$i, $tariff )
					->setCellValue( 'G'.$i, $order_item->get_total() + $order_item->get_total_tax() );
				$i++;
			}

			// Bereken ordertotaal
			$order_sheet->getCell('G8')->getCalculatedValue();

			// Bepaal het weeknummer van de voorziene levering van de bestelling
			$week = date_i18n( 'W', $timestamp );
			// Haal de winkelnaam op uit user- i.p.v. orderdata en zet de spaties om
			$filename = str_replace( 'Magasin-du-Monde-', 'MDM-', str_replace( 'Oxfam-Wereldwinkel-', 'OWW-', sanitize_file_name( get_user_meta( $order->get_customer_id(), 'shipping_company', true ) ) ) ).'-'.__( 'WK', 'oft-mdm' ).$week.'-'.$order->get_order_number().'.xlsx';
			
			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
			// Check of we de Excel voor het eerst aanmaken
			$saved_filename = $order->get_meta('_excel_file_name');
			if ( $saved_filename === '' ) {
				$folder = date_i18n('Y');
				$saved_filename = '/odisy/'.$folder.'/'.$filename;
				
				// Check of de map al bestaat
				if ( ! file_exists( WP_CONTENT_DIR.'/odisy/'.$folder.'/' ) ) {
					mkdir( WP_CONTENT_DIR.'/odisy/'.$folder.'/', 0755, true );
				}
				
				try {
					// Bewaar de nieuwe file (Excel 2007+)
					$writer->save( WP_CONTENT_DIR.$saved_filename );
					$logger->info( $order->get_order_number().": Excel saved in year archive", $context );
					
					// Sla de locatie op als metadata
					$order->add_meta_data( '_excel_file_name', $saved_filename, true );
					$order->save_meta_data();
				} catch ( InvalidArgumentException $e ) {
					$logger->error( $order->get_order_number().": ".$e->getMessage(), $context );
				}
			} else {
				if ( file_exists( WP_CONTENT_DIR.$saved_filename ) ) {
					try {
						// Overschrijf de bestaande file (Excel 2007+)
						$writer->save( WP_CONTENT_DIR.$saved_filename );
						$logger->info( $order->get_order_number().": Excel updated in year archive", $context );
					} catch ( InvalidArgumentException $e ) {
						$logger->error( $order->get_order_number().": ".$e->getMessage(), $context );
					}	
				} else {
					$logger->warning( $order->get_order_number().": path ".$saved_filename." does not exist", $context );
				}
			}

			// Switch taal voor alle zekerheid terug
			$sitepress->switch_lang( $previous_lang, true );

			return WP_CONTENT_DIR.$saved_filename;
		}

		function get_shipping_method( $order ) {
			$shipping_methods = $order->get_shipping_methods();
			$shipping_method = reset($shipping_methods);
			
			if ( $shipping_method instanceof WC_Order_Item_Shipping ) {
				return $shipping_method;
			}

			return false;
		}

		// TO DO: Optionele parameter $shipping_method_id van de vorm 'method_id:instance_id' verwerken om lookup in order overbodig te maken
		function is_wobal_delivery( $order, $shipping_method_id = false ) {
			$shipping_method = $this->get_shipping_method( $order );

			if ( $shipping_method !== false ) {
				if ( $shipping_method->get_method_id() === 'free_shipping' and $shipping_method->get_instance_id() == '1' ) {
					return true;
				}
			}

			return false;
		}

		function is_tournee_delivery( $order ) {
			$shipping_method = $this->get_shipping_method( $order );

			if ( $shipping_method !== false ) {
				if ( $shipping_method->get_method_id() === 'free_shipping' and ( $shipping_method->get_instance_id() == '10' or $shipping_method->get_instance_id() == '1' ) ) {
					return true;
				}
			}

			return false;
		}

		function is_local_pickup( $order, $location = false ) {
			$shipping_method = $this->get_shipping_method( $order );

			if ( $shipping_method !== false ) {
				if ( $shipping_method->get_method_id() === 'local_pickup' ) {
					if ( $location === false ) {
						// Elke vorm van afhaling
						return true;
					} elseif ( $location === 'mdm' and ( $shipping_method->get_instance_id() == '2' or $shipping_method->get_instance_id() == '9' ) ) {
						// Afhaling in Waver
						return true;
					} elseif ( $location === 'oft' and $shipping_method->get_instance_id() == '7' ) {
						// Afhaling in Wondelgem
						return true;
					}
				}
			}

			return false;
		}

		function get_first_deadline( $routecode, $from = false ) {
			write_log("GET FIRST DEADLINE FOR ROUTECODE ".$routecode." ...");

			if ( $from === false ) {
				// Neem de huidige tijd als vertrekpunt
				$from = current_time('timestamp');
			}

			require_once WP_PLUGIN_DIR.'/oft-mdm/class-oft-mdm-ms-graph.php';
			$graph_api = new Oft_Mdm_Microsoft_Graph();
			$deadline = '2020-01-01T10:00:00';

			try {

				$events = $graph_api->get_events_by_routecode( $routecode, 'deadline' );
				$instances = $graph_api->get_first_instances_of_event( $events[0]->getId() );
				// Meest nabije komt altijd als eerste!
				$event = $instances[0];
				echo $event->getSubject().' &mdash; '.$event->getStart()->getDateTime().' &mdash; '.str_replace( 'Z', '', implode( ', ', $event->getCategories() ) ).'<br/>';
				$deadline = $event->getStart()->getDateTime();

			} catch( Exception $e ) {

				exit( $e->getMessage() );

			}

			return $deadline;
		}

		function get_first_delivery_day( $routecode, $deadline ) {
			write_log("GET FIRST DELIVERY DAY FOR ROUTECODE ".$routecode." ...");

			if ( $deadline === false ) {
				// Neem de huidige tijd als vertrekpunt
				$deadline = current_time('timestamp');
			}

			require_once WP_PLUGIN_DIR.'/oft-mdm/class-oft-mdm-ms-graph.php';
			$graph_api = new Oft_Mdm_Microsoft_Graph();
			$delivery_day = '2020-01-01T10:00:00';

			try {

				$events = $graph_api->get_events_by_routecode( $routecode, 'leverdag' );
				$instances = $graph_api->get_first_instances_of_event( $events[0]->getId(), $deadline );
				// Meest nabije komt altijd als eerste!
				$event = $instances[0];
				echo $event->getSubject().' &mdash; '.$event->getStart()->getDateTime().' &mdash; '.str_replace( 'Z', '', implode( ', ', $event->getCategories() ) ).'<br/>';
				$delivery_day = $event->getStart()->getDateTime();

			} catch( Exception $e ) {

				exit( $e->getMessage() );

			}

			return $delivery_day;
		}

		function calculate_delivery_day( $shipping_method_id, $routecode = 'A', $from = false ) {
			if ( $from === false ) {
				// Neem de huidige tijd als vertrekpunt
				$from = current_time('timestamp');
			}

			// Hier de werkdagen van OFTL in stoppen?
			$holidays = array( '2019-07-22', '2019-07-23', '2019-07-24', '2019-07-25', '2019-07-26' );
			
			if ( $shipping_method_id === self::WOBAL_METHOD ) {
				
				// Zoek de eerstvolgende deadline voor deze methode
				$deadline = $this->get_first_deadline( $routecode, $from );
				
				// Zoek de eerstvolgende leverdag na die deadline
				// TO DO: neem 12u 's middags van deze dag om tijdzoneproblemen te voorkomen!
				$delivery = strtotime( $this->get_first_delivery_day( $routecode, $deadline ) );

			} elseif ( $shipping_method_id === self::OFTL_PICKUP_METHOD ) {
				// $today = new DateTime( $from );
				// $deadline_10am = $today->setTime( 10, 0, 0 );
				// $deadline_15am = $today->setTime( 15, 0, 0 );

				if ( date( 'N', $from ) > 5 or ( date( 'N', $from ) == 5 and date( 'G', $from ) >= 15 ) ) {
					// Ga naar eerstvolgende werkdag
					$timestamp = strtotime( '+1 weekday', $from );
				} else {
					$timestamp = $from;
				}
				
				write_log( date('Y-m-d H:i:s', $timestamp ) );
				write_log( serialize( date( 'G', $timestamp ) ) );

				// Geen kalender gebruiken, gewoon werkdagen en uren checken
				if ( date( 'G', $timestamp ) < 10 ) {
					$delivery = strtotime( 'today 2pm', $timestamp );
				} else {
					$delivery = strtotime( '+1 weekday 9am', $timestamp );
				}
				
				// UITZONDERLIJKE SLUITINGSDAGEN CHECKEN

			} else {

				$timestamp = $from;
				// Negeer huidige dag als vertrekpunt indien we nog voor de deadline zitten maar het een dag is waarop het magazijn niet werkt
				while ( date_i18n( 'G', $from ) < $cut_off and ( date_i18n( 'N', $timestamp ) > 5 or in_array( date_i18n( 'd/m/Y', $timestamp ), $holidays ) ) ) {
					$timestamp = strtotime( '+1 weekday', $timestamp );
				}
				
				// Zoek gewoon eerstvolgende leverdag na huidige deadline
				$processing_days = 2;
				// Na de deadline komt er nog een extra dagje bij!
				if ( date_i18n( 'G', $from ) >= $cut_off ) {
					$processing_days++;	
				}

				for ( $i = 1; $i <= $processing_days; $i++ ) {
					$timestamp = strtotime( '+1 weekday', $timestamp );
					// Tel er een werkdag bij tot we niet langer op een dag zitten waarop het magazijn niet werkt
					while ( in_array( date_i18n( 'd/m/Y', $timestamp ), $holidays ) ) {
						$timestamp = strtotime( '+1 weekday', $timestamp );
					}
				}

			}

			write_log("CALCULATED: ".$delivery);
			return $delivery;
		}

		function add_order_number_prefix( $order_number, $order ) {
			if ( $order->get_meta('_order_number_formatted') !== '' ) {
				return $order->get_meta('_order_number_formatted');
			} else {
				return get_option('woocommerce_order_number_prefix') . $order_number;
			}
		}

		function add_login_message() {
			/* TRANSLATORS: %1$s: naam van webshop
			%2$s: e-mailadres van klantendienst */
			echo "<p>".sprintf( __( 'Deze pagina is enkel toegankelijk voor geregistreerde gebruikers van %1$s.<br/>Contacteer <a href="mailto:%2$s?subject=Aanvraag account B2B-webshop">onze Klantendienst</a> indien je ook B2B-klant wenst te worden.', 'oft-mdm' ), get_bloginfo('name'), get_option('woocommerce_email_from_address') )."</p>";
		}

		function add_front_end_scripts() {
			?>
			<script type="text/javascript">
				jQuery(document).ready( function() {
					// Maak de hoeveelheidsknoppen overal functioneel
					jQuery(document).on( 'change', 'input.qty', function() {
						// jQuery plaatst de waardes van de attributen na $thisbutton.data() in woocommerce/assets/js/frontend/add-to-cart.js in de DOM-cache
						// Indien de hoeveelheid daarna gewijzigd wordt, worden de attributen niet opnieuw expliciet uitgelezen, en wordt opnieuw de oude hoeveelheid toegevoegd
						// In dit geval is het dus beter om expliciet de 'onzichtbare' data te manipuleren, zie o.a. https://stackoverflow.com/a/8708345
						jQuery(this).parent('.quantity').next('.add_to_cart_button').data( 'quantity', jQuery(this).val() );
						// Aantal consumenteneenheden ook onmiddellijk aanpassen
						jQuery(this).parent('.col-add-to-cart').find('.consumer-units-count').text( jQuery(this).val() );
					});

					<?php if ( WC()->cart->get_cart_contents_count() > 0 ) : ?>
						// Indien er nog iets in het winkelmandje zit: vraag bevestig vooraleer een bestelling opnieuw te plaatsen
						jQuery('.order-again').find('a.button').on( 'click', function(e) {
							if ( confirm("<?php _e( 'Opgelet: de huidige inhoud van je winkelmandje zal onmiddellijk vervangen worden door de beschikbare producten uit deze bestelling. Wil je verdergaan?', 'oft-mdm' ) ; ?>") == false ) {
								alert("<?php _e( 'Begrepen, we doen niks!', 'oft-mdm' ) ; ?>");
								e.preventDefault();
							}
						});
					<?php endif; ?>
					
					// Verberg aantal reeds in winkelmandje meteen na aanklikken van deleteknop (ook al loopt er onderweg misschien nog iets mis) 
					jQuery('#site-header-cart').on( 'click', '.remove_from_cart_button', function() {
						jQuery( '.badge-for-product-id-'+jQuery(this).attr('data-product_id') ).addClass('hidden').text(0);
					});

					<?php if ( is_cart() ) : ?>
						var wto;
						// Herlaad winkelmandje automatisch na aanpassen hoeveelheid
						jQuery('div.woocommerce').on( 'change', '.qty', function() {
							clearTimeout(wto);
							// Time-out net iets groter dan buffertijd zodat we bij ingedrukt houden van de spinner niet gewoon +1/-1 doen
							wto = setTimeout( function() {
								jQuery("[name='update_cart']").trigger('click');
							}, 500 );
						});
					<?php endif; ?>
				});
			</script>
			<?php
		}
	}
?>