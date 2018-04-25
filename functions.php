<?php

	use Spipu\Html2Pdf\Html2Pdf;
	use Spipu\Html2Pdf\Exception\Html2PdfException;
	use Spipu\Html2Pdf\Exception\ExceptionFormatter;
	setlocale( LC_ALL, 'nl_NL' );
	
	if ( ! defined('ABSPATH') ) exit;

	// Nonces niet checken in VC-grids, oplossing voor cachingprobleem?
	add_filter( 'vc_grid_get_grid_data_access','__return_true' );

	// Laad het child theme na het hoofdthema
	add_action( 'wp_enqueue_scripts', 'load_child_theme', 999 );

	function load_child_theme() {
		// Zorgt ervoor dat de stylesheet van het child theme ZEKER NA alone.css ingeladen wordt
		wp_enqueue_style( 'oft', get_stylesheet_uri(), array(), '1.2.15' );
		// BOOTSTRAP REEDS INGELADEN DOOR ALONE
		// In de languages map van het child theme zal dit niet werken (checkt enkel nl_NL.mo) maar fallback is de algemene languages map (inclusief textdomain)
		load_child_theme_textdomain( 'alone', get_stylesheet_directory().'/languages' );
		load_child_theme_textdomain( 'oft', get_stylesheet_directory().'/languages' );
	}

	// Voeg custom styling toe aan de adminomgeving
	add_action( 'admin_enqueue_scripts', 'load_admin_css' );

	function load_admin_css() {
		wp_enqueue_style( 'oft-admin', get_stylesheet_directory_uri().'/admin.css', '1.1.3' );
	}

	// Sta HTML-attribuut 'target' toe in beschrijvingen van taxonomieën
	add_action( 'init', 'allow_target_tag', 20 );

	function allow_target_tag() { 
		global $allowedtags;
		$allowedtags['a']['target'] = 1;
	}

	// Voeg een menu toe met onze custom functies
	add_action( 'admin_menu', 'register_oft_menus', 99 );

	function register_oft_menus() {
		add_submenu_page( 'edit.php?post_type=product', 'Changelog', 'Changelog', 'publish_products', 'product-changelog', 'oxfam_product_changelog_callback' );
		add_media_page( __( 'Bulkregistratie', 'oft-admin' ), __( 'Bulkregistratie', 'oft-admin' ), 'upload_files', 'oxfam-photos', 'oxfam_photos_callback' );
	}

	function oxfam_product_changelog_callback() {
		include get_stylesheet_directory().'/changelog.php';
	}

	// Fixes i.v.m. cURL
	add_action( 'http_api_curl', 'custom_curl_timeout', 10, 3 );
	
	function custom_curl_timeout( $handle, $r, $url ) {
		// Fix error 28 - Operation timed out after 10000 milliseconds with 0 bytes received (bij het connecteren van Jetpack met Wordpress.com)
		curl_setopt( $handle, CURLOPT_TIMEOUT, 180 );
		// Fix error 60 - SSL certificate problem: unable to get local issuer certificate (bij het downloaden van een CSV in WP All Import)
		curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, false );
	}

	// Allerhande tweaks na het initialiseren van WordPress
	add_action( 'init', 'remove_alone_actions', 20 );

	function remove_alone_actions() {
		// Verwijder alle buttons om te kopen BEARS THEMES HEEFT ZE AL VERWIJDERD, PAK DE CUSTOM GEDEFINIEERDE ACTIONS
		remove_action( 'bearsthemes_woocommerce_after_thumbnail_loop', 'woocommerce_template_loop_add_to_cart', 10 );
		remove_action( 'bearsthemes_woocommerce_after_thumbnail_loop', '_bearsthemes_yith_add_compare_button', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		// add_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		remove_action( 'woocommerce_after_shop_loop_item_title', '_bearsthemes_woocommerce_get_taxonomy_loop', 10 );
		remove_action( 'bearsthemes_woocommerce_after_thumbnail_loop', '_bearsthemes_yith_add_compare_button', 10 );
		if ( function_exists('YITH_WCQV_Frontend') ) {
			remove_action( 'bearsthemes_woocommerce_after_thumbnail_loop', array( YITH_WCQV_Frontend(), 'yith_add_quick_view_button' ), 10 );
		}
		remove_action( 'admin_notices', '_alone_admin_notice_theme_message' );
	}

	// Alle verwijzingen naar promoties (badge, doorstreepte adviesprijs) uitschakelen in B2B-setting
	add_filter( 'woocommerce_sale_flash', '__return_false' );
	add_filter( 'woocommerce_format_sale_price', 'format_sale_as_regular_price', 10, 3 );

	function format_sale_as_regular_price( $price, $regular_price, $sale_price ) {
		return wc_price($regular_price);
	}

	// Laad niet-prioritaire JavaScript (die bv. moet wachten op jQuery) 
	add_action( 'wp_footer', 'add_scripts_to_front_end' );
	
	function add_scripts_to_front_end() {
		?>
		<script>
			jQuery(document).ready( function() {
				jQuery( '.oft-link-target' ).click( function() {
					var href = jQuery(this).find( '.vc_btn3-shape-rounded.vc_btn3-style-flat' ).attr('href');
					if ( href.length > 5 ) {
						window.location.href = href;
						return false;
					}
				});
				jQuery( '.oft-link-target-title' ).click( function() {
					var href = jQuery(this).find( '.wpb_text_column > .wpb_wrapper > h2 > a' ).attr('href');
					if ( href.length > 5 ) {
						window.location.href = href;
						return false;
					}
				});
			});
		</script>
		<?php
	}

	// Verberg de quick edit op producten
	add_filter( 'post_row_actions', 'remove_row_actions', 10, 1 );
	
	function remove_row_actions( $actions ) {
		if ( get_post_type() === 'product' ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}



	################
	#  TAXONOMIES  #
	################

	// Creëer een custom hiërarchische taxonomie op producten om partner/landinfo in op te slaan
	add_action( 'init', 'register_partner_taxonomy', 2 );
	
	function register_partner_taxonomy() {
		$taxonomy_name = 'product_partner';
		
		$labels = array(
			'name' => __( 'Partners', 'oft' ),
			'singular_name' => __( 'Partner', 'oft' ),
			'all_items' => __( 'Alle partners', 'oft' ),
			'edit_item' => __( 'Partner bewerken', 'oft' ),
			'update_item' => __( 'Partner bijwerken', 'oft' ),
			'view_item' => __( 'Partner bekijken', 'oft' ),
			'add_new_item' => __( 'Voeg nieuwe partner toe', 'oft' ),
			'new_item_name' => __( 'Nieuwe partner', 'oft' ),
			'parent_item' => __( 'Land', 'oft' ),
			'parent_item_colon' => __( 'Land:', 'oft' ),
			'search_items' => __( 'Partners doorzoeken', 'oft' ),
			'not_found' => __( 'Geen partners gevonden!', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_rest' => true,
			'show_tagcloud' => true,
			'show_in_quick_edit' => false,
			'show_admin_column' => true,
			'description' => __( 'Ken het product toe aan een partner/land', 'oft' ),
			'hierarchical' => true,
			'query_var' => true,
			// Slugs van custom taxonomieën kunnen helaas niet vertaald worden 
			'rewrite' => array( 'slug' => 'herkomst', 'with_front' => true, 'hierarchical' => true ),
			// Geef catmans rechten om zelf termen toe te kennen (+ overzicht te bekijken) maar niet om te bewerken (+ toe te voegen) / te verwijderen!
			'capabilities' => array( 'assign_terms' => 'manage_product_terms', 'edit_terms' => 'update_core', 'manage_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Extra metadata definiëren en bewaren op partnertaxonomie
	// add_action( 'product_partner_add_form_fields', 'add_partner_node_field', 10, 2 );
	add_action( 'created_product_partner', 'save_partner_node_meta', 10, 2 );
	add_action( 'product_partner_edit_form_fields', 'edit_partner_node_field', 10, 2 );
	add_action( 'edited_product_partner', 'save_partner_node_meta', 10, 2 );
	add_action( 'admin_enqueue_scripts', 'load_wp_media' );
	add_action( 'admin_footer', 'add_media_library_script' );

	function add_partner_node_field( $taxonomy ) {
		?>
			<div class="form-field term-node">
				<label for="partner_node"><?php _e( 'Node OWW-site', 'oft-admin' ); ?></label>
				<input type="number" min="1" max="99999" class="postform" id="partner_node" name="partner_node">
			</div>
			<div class="form-field term-type">
				<label for="partner_type"><?php _e( 'Partnertype', 'oft-admin' ); ?></label>
				<select class="postform" id="partner_type" name="partner_type">
					<option value="">(selecteer)</option>
					<option value="A">A</option>
					<option value="B">B</option>
					<option value="C">C</option>
				</select>
			</div>
		<?php
	}

	function save_partner_node_meta( $term_id, $tt_id ) {
		if ( isset( $_POST['partner_node'] ) ) {
			update_term_meta( $term_id, 'partner_node', absint($_POST['partner_node']) );
		} else {
			update_term_meta( $term_id, 'partner_node', '' );
		}
		if ( isset( $_POST['partner_type'] ) ) {
			update_term_meta( $term_id, 'partner_type', sanitize_text_field($_POST['partner_type']) );
		} else {
			update_term_meta( $term_id, 'partner_type', '' );
		}
		if ( isset( $_POST['partner_image_id'] ) ) {
			update_term_meta( $term_id, 'partner_image_id', absint($_POST['partner_image_id']) );
		} else {
			update_term_meta( $term_id, 'partner_image_id', '' );
		}
	}

	function edit_partner_node_field( $term, $taxonomy ) {
		?>
			<tr class="form-field term-node-wrap">
				<th scope="row"><label for="partner_node"><?php _e( 'Node OWW-site', 'oft-admin' ); ?></label></th>
				<td>
					<?php $partner_node = get_term_meta( $term->term_id, 'partner_node', true ); ?>
					<input type="number" min="1" max="99999" class="postform" id="partner_node" name="partner_node" value="<?php if ($partner_node) echo esc_attr($partner_node); ?>">
				</td>
			</tr>
			<tr class="form-field term-node-wrap">
				<th scope="row"><label for="partner_type"><?php _e( 'Partnertype', 'oft-admin' ); ?></label></th>
				<td>
					<?php $partner_type = get_term_meta( $term->term_id, 'partner_type', true ); ?>
					<select class="postform" id="partner_type" name="partner_type">
						<option value="">(selecteer)</option>
						<option value="A" <?php selected( 'A', $partner_type ); ?>>A</option>
						<option value="B" <?php selected( 'B', $partner_type ); ?>>B</option>
						<option value="C" <?php selected( 'C', $partner_type ); ?>>C</option>
					</select>
				</td>
			</tr>
			 <tr class="form-field term-image-wrap">
				<th scope="row"><label for="partner_image_id"><?php _e( 'Beeld', 'oft-admin' ); ?></label></th>
				<td>
					<?php $image_id = get_term_meta( $term->term_id, 'partner_image_id', true ); ?>
					<input type="hidden" id="partner_image_id" name="partner_image_id" value="<?php if ($image_id) echo esc_attr($image_id); ?>">
					<div id="partner-image-wrapper">
						<?php if ($image_id) echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
					</div>
					<p>
						<input type="button" class="button button-secondary showcase_tax_media_button" id="showcase_tax_media_button" name="showcase_tax_media_button" value="<?php _e( 'Kies foto', 'oft-admin' ); ?>" />
						<input type="button" class="button button-secondary showcase_tax_media_remove" id="showcase_tax_media_remove" name="showcase_tax_media_remove" value="<?php _e( 'Verwijder foto', 'oft-admin' ); ?>" />
					</p>
				</td>
			</tr>
		<?php
	}

	function load_wp_media() {
		if( ! isset( $_GET['taxonomy'] ) || $_GET['taxonomy'] != 'product_partner' ) {
			return;
		}
		wp_enqueue_media();
	}

	function add_media_library_script() {
		if( ! isset( $_GET['taxonomy'] ) || $_GET['taxonomy'] != 'product_partner' ) {
			return;
		}
		?>
		<script>
			jQuery(document).ready( function($) {
				_wpMediaViewsL10n.insertIntoPost = '<?php _e( 'Stel in', 'oft-admin' ); ?>';
				function ct_media_upload(button_class) {
					var _custom_media = true, _orig_send_attachment = wp.media.editor.send.attachment;
					$('body').on('click', button_class, function(e) {
						var button_id = '#'+$(this).attr('id');
						var send_attachment_bkp = wp.media.editor.send.attachment;
						var button = $(button_id);
						_custom_media = true;
						wp.media.editor.send.attachment = function(props, attachment){
							if( _custom_media ) {
								$('#partner_image_id').val(attachment.id);
								$('#partner-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
								$('#partner-image-wrapper .custom_media_image').attr( 'src',attachment.url ).css( 'display','block' );
							} else {
								return _orig_send_attachment.apply( button_id, [props, attachment] );
							}
						}
						wp.media.editor.open(button); return false;
					});
				}
				ct_media_upload('.showcase_tax_media_button.button');
				$('body').on('click','.showcase_tax_media_remove',function(){
					$('#partner_image_id').val('');
					$('#partner-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
				});
				$(document).ajaxComplete(function(event, xhr, settings) {
					var queryStringArr = settings.data.split('&');
					if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
						var xml = xhr.responseXML;
						$response = $(xml).find('term_id').text();
						if($response!=""){
							// Clear the thumb image
							$('#partner-image-wrapper').html('');
						}
					}
				});
			});
		</script>
		<?php
	}

	// Creëer een custom hiërarchische taxonomie op producten om allergeneninfo in op te slaan
	add_action( 'init', 'register_allergen_taxonomy', 4 );

	function register_allergen_taxonomy() {
		$taxonomy_name = 'product_allergen';
		
		$labels = array(
			'name' => __( 'Allergenen', 'oft' ),
			'singular_name' => __( 'Allergeen', 'oft' ),
			'all_items' => __( 'Alle allergenen', 'oft' ),
			'parent_item' => __( 'Allergeen', 'oft' ),
			'parent_item_colon' => __( 'Allergeen:', 'oft' ),
			'new_item_name' => __( 'Nieuw allergeen', 'oft' ),
			'add_new_item' => __( 'Voeg nieuw allergeen toe', 'oft' ),
			'view_item' => __( 'Allergeen bekijken', 'oft' ),
			'edit_item' => __( 'Allergeen bewerken', 'oft' ),
			'update_item' => __( 'Allergeen bijwerken', 'oft' ),
			'search_items' => __( 'Allergenen doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Geef aan dat het product dit bevat', 'oft' ),
			'public' => false,
			'publicly_queryable' => true,
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_tagcloud' => false,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'query_var' => true,
			// Geef catmans rechten om zelf termen toe te kennen (+ overzicht te bekijken) maar niet om te bewerken (+ toe te voegen) / te verwijderen!
			'capabilities' => array( 'assign_terms' => 'manage_product_terms', 'edit_terms' => 'update_core', 'manage_terms' => 'manage_product_terms', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'allergeen', 'with_front' => false, 'hierarchical' => true ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Creëer drie custom hiërarchische taxonomieën op producten om wijninfo in op te slaan
	add_action( 'init', 'register_wine_taxonomy', 100 );
	
	function register_wine_taxonomy() {
		$name = 'druif';
		$taxonomy_name = 'product_grape';
		
		$labels = array(
			'name' => __( 'Druivenrassen', 'oft' ),
			'singular_name' => __( 'Druivenras', 'oft' ),
			'all_items' => __( 'Alle druivenrassen', 'oft' ),
			'parent_item' => __( 'Kleur', 'oft' ),
			'parent_item_colon' => __( 'Kleur:', 'oft' ),
			'new_item_name' => __( 'Nieuw druivenras', 'oft' ),
			'add_new_item' => __( 'Voeg nieuw druivenras toe', 'oft' ),
			'view_item' => __( 'Druivenras bekijken', 'oft' ),
			'edit_item' => __( 'Druivenras bewerken', 'oft' ),
			'update_item' => __( 'Druivenras bijwerken', 'oft' ),
			'search_items' => __( 'Druivenrassen doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => sprintf( __( 'Voeg de wijn toe aan een %s in de wijnkiezer', 'oft' ), $name ),
			'public' => false,
			'publicly_queryable' => true,
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_tagcloud' => true,
			'show_in_quick_edit' => false,
			'show_admin_column' => true,
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'manage_product_terms', 'edit_terms' => 'manage_product_terms', 'manage_terms' => 'manage_product_terms', 'delete_terms' => 'update_core' ),
			// In de praktijk niet bereikbaar op deze URL want niet publiek!
			'rewrite' => array( 'slug' => $name, 'with_front' => false, 'hierarchical' => false ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );

		unset( $labels );
		$name = 'gerecht';
		$taxonomy_name = 'product_recipe';
		
		$labels = array(
			'name' => __( 'Gerechten', 'oft' ),
			'singular_name' => __( 'Gerecht', 'oft' ),
			'all_items' => __( 'Alle gerechten', 'oft' ),
			'parent_item' => __( 'Gerecht', 'oft' ),
			'parent_item_colon' => __( 'Gerecht:', 'oft' ),
			'new_item_name' => __( 'Nieuw gerecht', 'oft' ),
			'add_new_item' => __( 'Voeg nieuw gerecht toe', 'oft' ),
			'view_item' => __( 'Gerecht bekijken', 'oft' ),
			'edit_item' => __( 'Gerecht bewerken', 'oft' ),
			'update_item' => __( 'Gerecht bijwerken', 'oft' ),
			'search_items' => __( 'Gerechten doorzoeken', 'oft' ),
		);

		$args['labels'] = $labels;
		$args['description'] = sprintf( __( 'Voeg de wijn toe aan een %s in de wijnkiezer', 'oft' ), $name );
		$args['rewrite']['slug'] = $name;

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );

		unset( $labels );
		$name = 'smaak';
		$taxonomy_name = 'product_taste';
		
		$labels = array(
			'name' => __( 'Smaken', 'oft' ),
			'singular_name' => __( 'Smaak', 'oft' ),
			'all_items' => __( 'Alle smaken', 'oft' ),
			'parent_item' => __( 'Smaak', 'oft' ),
			'parent_item_colon' => __( 'Smaak:', 'oft' ),
			'new_item_name' => __( 'Nieuwe smaak', 'oft' ),
			'add_new_item' => __( 'Voeg nieuwe smaak toe', 'oft' ),
			'view_item' => __( 'Smaak bekijken', 'oft' ),
			'edit_item' => __( 'Smaak bewerken', 'oft' ),
			'update_item' => __( 'Smaak bijwerken', 'oft' ),
			'search_items' => __( 'Smaken doorzoeken', 'oft' ),
		);

		$args['labels'] = $labels;
		$args['description'] = sprintf( __( 'Voeg de wijn toe aan een %s in de wijnkiezer', 'oft' ), $name );
		$args['rewrite']['slug'] = $name;

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Maak onze custom taxonomiën beschikbaar in menu editor
	add_filter( 'woocommerce_attribute_show_in_nav_menus', 'register_custom_taxonomies_for_menus', 1, 2 );

	function register_custom_taxonomies_for_menus( $register, $name = '' ) {
		$register = true;
		return $register;
	}

	// Vermijd dat geselecteerde termen in hiërarchische taxonomieën naar boven springen
	add_filter( 'wp_terms_checklist_args', 'do_not_jump_to_top', 10, 2 );

	function do_not_jump_to_top( $args, $post_id ) {
		if ( is_admin() ) {
			$args['checked_ontop'] = false;
		}
		return $args;
	}

	// Titel uitschakelen bij technische fiche
	add_filter( 'woocommerce_product_additional_information_heading', '__return_false' );

	// Registreer een extra tabje op de productdetailpagina voor de voedingswaardes
	add_filter( 'woocommerce_product_tabs', 'add_extra_product_tabs' );
	
	function add_extra_product_tabs( $tabs ) {
		global $product;
		
		$categories = $product->get_category_ids();
		if ( is_array( $categories ) ) {
			foreach ( $categories as $category_id ) {
				$category = get_term( $category_id, 'product_cat' );
				while ( intval($category->parent) !== 0 ) {
					$parent = get_term( $category->parent, 'product_cat' );
					$category = $parent;
				}
			}
			if ( $parent->slug === 'wijn' or $parent->slug === 'vin' or $parent->slug === 'wine' ) {
				// Sommelierinfo uit lange beschrijving tonen
				$tabs['description']['title'] = __( 'Wijnbeschrijving', 'oft' );
			} else {
				// Schakel lange beschrijving uit (werd naar boven verplaatst)
				unset($tabs['description']);
			}
		}

		// Voeg tabje met ingrediënten en allergenen toe
		$tabs['ingredients_info'] = array(
			'title' 	=> __( 'Ingrediënten', 'oft' ),
			'priority' 	=> 12,
			'callback' 	=> function() { output_tab_content('ingredients'); },
		);

		// Voeg tabje met voedingswaardes toe (indien niet leeg)
		if ( get_tab_content('food') !== false ) {
			$unit = $product->get_meta( '_net_unit' );
			if ( $unit === 'cl' ) {
				$suffix = 'ml';
			} else {
				$suffix = 'g';
			}
			$tabs['food_info'] = array(
				'title' 	=> sprintf( __( 'Voedingswaarde per 100 %s', 'oft' ), $suffix ),
				'priority' 	=> 14,
				'callback' 	=> function() { output_tab_content('food'); },
			);
		}

		// Titel wijzigen van standaardtabs kan maar prioriteit niet! (description = 10, additional_information = 20)
		$tabs['additional_information']['title'] = __( 'Technische fiche', 'oft' );

		// TIJDELIJK UITSCHAKELEN
		unset($tabs['additional_information']);
		
		return $tabs;
	}

	// Retourneer de gegevens voor een custom tab (antwoordt met FALSE indien geen gegevens beschikbaar)
	function get_tab_content( $type ) {
		global $product;
		$has_row = false;
		$alt = 1;
		ob_start();
		echo '<table class="shop_attributes">';

		if ( $type === 'food' ) {
			
			// Blokje tonen van zodra energie ingevuld?
			if ( intval( $product->get_meta('_energy') ) > 0 ) {
				$has_row = true;
			}
			
			if ( $product->get_meta('_net_unit') === 'cl' ) {
				$unit = 'ml';
			} else {
				$unit = 'g';
			}

			?>
			<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
				<th><?php echo __( 'Energie', 'oft' ); ?></th>
				<td><?php
					$en = intval( $product->get_meta('_energy') );
					echo $en.' kJ (= '.number_format( $en/4.184, 0, ',', '' ).' kcal)' ?></td>
			</tr>
			<?php

			$product_metas = array(
				'_fat' => __( 'Vetten', 'oft' ),
				'_fasat' => __( 'waarvan verzadigde vetzuren', 'oft' ),
				'_famscis' => __( 'waarvan enkelvoudig onverzadigde vetzuren', 'oft' ),
				'_fapucis' => __( 'waarvan meervoudig onverzadigde vetzuren', 'oft' ),
				'_choavl' => __( 'Koolhydraten', 'oft' ),
				'_sugar' => __( 'waarvan suikers', 'oft' ),
				'_polyl' => __( 'waarvan polyolen', 'oft' ),
				'_starch' => __( 'waarvan zetmeel', 'oft' ),
				'_fibtg' => __( 'Vezels', 'oft' ),
				'_pro' => __( 'Eiwitten', 'oft' ),
				'_salteq' => __( 'Zout', 'oft' ),
			);
			$requireds = array( '_fat', '_fasat', '_choavl', '_sugar', '_pro', '_salteq' );
			$secondaries = array( '_fasat', '_famscis', '_fapucis', '_sugar', '_polyl', '_starch' );
			
			foreach ( $product_metas as $meta_key => $meta_label ) {
				// Toon voedingswaarde als het een verplicht veld is en in 2de instantie als er expliciet een (nul)waarde ingesteld is
				if ( in_array( $meta_key, $requireds ) or $product->get_meta($meta_key) !== '' ) {
					if ( $product->get_meta($meta_key) === '' or floatval( $product->get_meta($meta_key) ) === 0 ) {
						// Zet zeker een nul (zonder expliciete precisie)
						$meta_value = '0';
					} else {
						// Formatter het getal als Belgische tekst
						$meta_value = str_replace( '.', ',', $product->get_meta($meta_key) );
					}
					?>
					<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
						<th class="<?php echo in_array( $meta_key, $secondaries ) ? 'secondary' : 'primary'; ?>"><?php echo $meta_label; ?></th>
						<td class="<?php echo in_array( $meta_key, $secondaries ) ? 'secondary' : 'primary'; ?>"><?php echo $meta_value; ?> g</td>
					</tr>
					<?php
				}
			}

		} elseif ( $type === 'ingredients' ) {
			
			// Allergenentab altijd tonen!
			$has_row = true;
			
			?>
			<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
				<th><?php _e( 'Netto-inhoud', 'oft' ); ?></th>
				<td><?php echo get_net_weight($product); ?></td>
			</tr>
			<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
				<th><?php _e( 'Fairtradepercentage', 'oft' ); ?></th>
				<td><?php echo $product->get_meta('_fairtrade_share').' %' ?></td>
			</tr>
			<?php

			$product_attributes = array( 'pa_fairtrade', 'pa_bio' );
			foreach ( $product_attributes as $attribute_key ) {
				?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
					<th><?php echo wc_attribute_label($attribute_key); ?></th>
					<td><?php echo $product->get_attribute($attribute_key); ?></td>
				</tr>
				<?php
			}
			
			$ingredients = get_ingredients($product);
			if ( $ingredients !== false ) {
				?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
					<th><?php echo $ingredients['label']; ?></th>
					<td><?php echo $ingredients['value']; ?></td>
				</tr>
				<?php
			}

			$allergens = get_allergens($product);
			?>
			<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
				<th><?php _e( 'Dit product bevat', 'oft' ); ?></th>
				<td>
				<?php
					if ( is_array( $allergens['contains'] ) ) {
						echo implode( ', ', $allergens['contains'] );
					} elseif ( $allergens['none'] === true or is_array( $allergens['may-contain'] ) ) {
						// Enkel tonen indien expliciet zo aangegeven in database!
						_e( 'geen meldingsplichtige allergenen', 'oft' );
					} else {
						echo '/';
					}
				?>
				</td>
			</tr>
			<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
				<th><?php _e( 'Kan sporen bevatten van', 'oft' ); ?></th>
				<td>
				<?php
					if ( is_array( $allergens['may-contain'] ) ) {
						echo implode( ', ', $allergens['may-contain'] );
					} elseif ( $allergens['none'] === true or is_array( $allergens['contains'] ) ) {
						// Enkel tonen indien expliciet zo aangegeven in database!
						_e( 'geen meldingsplichtige allergenen', 'oft' );
					} else {
						echo '/';
					}
				?>
				</td>
			</tr>
			<?php

		}
		
		echo '</table>';
		
		if ( $has_row ) {
			// Legende toevoegen indien ingrediënten aanwezig met deze eigenschap
			if ( $type === 'ingredients' ) {
				if ( count( get_ingredients_legend($product) ) > 0 ) {
					echo '<p class="legend">'.implode( '<br>', get_ingredients_legend($product) ).'</p>';
				}
			}
			return ob_get_clean();
		} else {
			ob_end_clean();
			return false;
		}
	}

	// Print de inhoud van een tab
	function output_tab_content( $type ) {
		if ( get_tab_content( $type ) !== false ) {
			echo get_tab_content( $type );
		} else {
			echo '<i>'.__( 'Geen info beschikbaar.', 'oft' ).'</i>';
		}
	}

	// Haal de toepasselijke ingrediëntenlijst op (wijn/postmeta/attribute), retourneer false indien niets beschikbaar
	function get_ingredients( $product, $with_colon = false ) {
		$result = array();
		$grapes = get_grape_terms_by_product($product);
		if ( is_array($grapes) ) {
			// Druiven kunnen door de meta_boxlogica enkel op wijn ingesteld worden, dus niet nodig om categorie te checken
			if ( $with_colon === true ) {
				$result['label'] = __( 'Druivenrassen:', 'oft' );
			} else {
				$result['label'] = __( 'Druivenrassen', 'oft' );
			}
			$result['value'] = implode( ', ', $grapes );
		} elseif ( ! empty( $product->get_meta('_ingredients') ) ) {
			if ( $with_colon === true ) {
				$result['label'] = __( 'Ingrediënten:', 'oft' );
			} else {
				$result['label'] = __( 'Ingrediënten', 'oft' );
			}
			$result['value'] = $product->get_meta('_ingredients');
		} else {
			$result = false;
		}
		return $result;
	}

	// Haal de legende op die bij het ingrediëntenlijstje hoort
	function get_ingredients_legend( $product ) {
		$legend = array();
		if ( ! empty( $product->get_meta('_ingredients') ) ) {
			if ( strpos( $product->get_meta('_ingredients'), '*' ) !== false ) {
				$legend[] = '* '.__( 'ingrediënt uit een eerlijke handelsrelatie', 'oft' );
			}
			if ( strpos( $product->get_meta('_ingredients'), '°' ) !== false ) {
				$legend[] = '° '.__( 'ingrediënt van biologische landbouw', 'oft' );
			}
		}
		return $legend;
	}

	// Haal het netto-gewicht op (en druk zware producten daarbij uit in kilo)
	function get_net_weight( $product ) {
		$content = $product->get_meta('_net_content');
		$unit = $product->get_meta('_net_unit');
		if ( $content !== '' and $unit !== '' ) {
			$content = intval( str_replace( ',', '', $content ) );
			if ( $content >= 1000 ) {
				$content = $content/1000;
				$unit = 'k'.$unit;
			}
			return $content.' '.$unit;
		} else {
			return '/';
		}
	}

	// Haal de allergenen op als een opgesplitste array van termnamen
	function get_allergens( $product ) {
		$result = array( 'contains' => false, 'may-contain' => false, 'none' => false );
		$allergens = get_the_terms( $product->get_id(), 'product_allergen' );
		if ( is_array($allergens) ) {
			// Retourneert automatisch de vertaalde term in FR/EN
			$contains_term = get_term_by( 'slug', 'contains', 'product_allergen' );
			$may_contain_term = get_term_by( 'slug', 'may-contain', 'product_allergen' );
			$none_term = get_term_by( 'slug', 'none', 'product_allergen' );
			$contains = array();
			$may_contain = array();
			foreach ( $allergens as $term ) {
				if ( $term->parent == $contains_term->term_id ) {
					$contains[] = mb_strtolower($term->name);
				} elseif ( $term->parent == $may_contain_term->term_id ) {
					$may_contain[] = mb_strtolower($term->name);
				} elseif ( $term->term_id == $none_term->term_id ) {
					$result['none'] = true;
				}
			}
			if ( count($contains) > 0 ) {
				$result['contains'] = $contains;
			}
			if ( count($may_contain) > 0 ) {
				$result['may-contain'] = $may_contain;
			}
		}
		return $result;
	}

	// Verhinder bepaalde selecties in de back-end
	add_action( 'admin_footer', 'disable_custom_checkboxes' );

	function disable_custom_checkboxes() {
		global $pagenow, $post_type;

		// 'Vertaalinterface' en 'Stringvertaling' verbergen in het menu voor niet-beheerders
		if ( ! current_user_can('update_core') ) {
			?>
			<style>
				li#toplevel_page_wpml-translation-management-menu-translations-queue,
				li#toplevel_page_wpml-string-translation-menu-string-translation {
					display: none !important;
				}
			</style>
			<?php
		}

		// Functies die we zowel op individuele als op bulkbewerkingen willen toepassen
		if ( ( $pagenow === 'post.php' or $pagenow === 'post-new.php' or $pagenow === 'edit.php' ) and $post_type === 'product' ) {
			$args = array(
				'fields' => 'ids',
				'hide_empty' => false,
				// Enkel de hoofdtermen selecteren!
				'parent' => 0,
			);

			$args['taxonomy'] = 'product_cat';
			$categories = get_terms($args);
			$uncategorized_term = get_term_by( 'slug', 'geen-categorie', $args['taxonomy'] );
			
			$args['taxonomy'] = 'product_allergen';
			$types = get_terms($args);
			$none_term = get_term_by( 'slug', 'none', $args['taxonomy'] );

			$args['taxonomy'] = 'product_packaging';
			$units = get_terms($args);
			
			?>
			<script>
				jQuery(document).ready( function() {
					/* Disable en verberg checkboxes hoofdcategorieën */
					<?php foreach ( $categories as $id ) : ?>
						<?php if ( $id != $uncategorized_term->term_id ) : ?> 
							jQuery( '#in-product_cat-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
						<?php else : ?>
							jQuery( '#product_cat-<?php echo $id; ?>' ).css( 'display', 'none' );
						<?php endif; ?>
					<?php endforeach; ?>
					
					/* Uncheck de vorige waarde indien je een nieuwe productcategorie selecteert */
					jQuery( '#product_cat-all' ).find( 'input[type=checkbox]' ).on( 'change', function() {
						jQuery(this).closest( '#product_catchecklist' ).find( 'input[type=checkbox]' ).not(this).prop( 'checked', false );
					});

					/* Disable en verberg checkboxes allergeenklasses (behalve none) */
					<?php foreach ( $types as $id ) : ?>
						<?php if ( $id != $none_term->term_id ) : ?> 
							jQuery( '#in-product_allergen-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
							jQuery( '#product_allergen-all' ).find( 'input[type=checkbox]:checked' ).each( function() {
								var checked_box = jQuery(this);
								var label = checked_box.closest( 'label.selectit' ).text();
								checked_box.closest( 'ul.children' ).closest( 'li' ).siblings().find( 'label.selectit' ).each( function() {
									if ( jQuery(this).text() == label ) {
										jQuery(this).find( 'input[type=checkbox]' ).prop( 'disabled', true );
									}
								});
							});
						<?php endif; ?>
					<?php endforeach; ?>

					/* Disable en verberg checkboxes besteleenheid / consumenteneenheid */
					<?php foreach ( $units as $id ) : ?>
						jQuery( '#in-product_packaging-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
					<?php endforeach; ?>
				});
			</script>
			<?php

		}

		// Functies die we niet op bulkbewerkingen willen toepassen
		if ( ( $pagenow === 'post.php' or $pagenow === 'post-new.php' ) and $post_type === 'product' ) {
			$args = array(
				'fields' => 'ids',
				'hide_empty' => false,
				// Enkel de hoofdtermen selecteren!
				'parent' => 0,
			);

			$args['taxonomy'] = 'product_partner';
			$continents = get_terms($args);
			
			$args['taxonomy'] = 'product_grape';
			$grapes = get_terms($args);

			$categories = isset( $_GET['post'] ) ? get_the_terms( $_GET['post'], 'product_cat' ) : false;
			if ( is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					while ( intval($category->parent) !== 0 ) {
						$parent = get_term( $category->parent, 'product_cat' );
						$category = $parent;
					}
				}
			}

			?>
			<script>
				jQuery(document).ready( function() {
					/* Disable enkele core WC-velden én een custom select waarop het attribuut 'readonly' niet werkt */
					/* Door opgeven van 'disabled'-attribuut in velddefinitie verdwijnt de waarde tijdens het opslaan, dus via jQuery oplossen */
					jQuery( '#general_product_data' ).find( 'input#_regular_price' ).prop( 'readonly', true );
					jQuery( '#general_product_data' ).find( 'select#_tax_status' ).prop( 'disabled', true );
					jQuery( '#general_product_data' ).find( 'select#_tax_class' ).prop( 'disabled', true );
					jQuery( '#general_product_data' ).find( 'select#_net_unit' ).prop( 'disabled', true );
					jQuery( '#inventory_product_data' ).find( 'select#_stock_status' ).prop( 'disabled', true );
					jQuery( '#inventory_product_data' ).find( 'input[name=_sold_individually]' ).prop( 'disabled', true );
					jQuery( '#shipping_product_data' ).find( 'input[name=_weight]' ).prop( 'readonly', true );
					jQuery( '#shipping_product_data' ).find( 'input[name=_length]' ).prop( 'readonly', true );
					jQuery( '#shipping_product_data' ).find( 'input[name=_width]' ).prop( 'readonly', true );
					jQuery( '#shipping_product_data' ).find( 'input[name=_height]' ).prop( 'readonly', true );
					jQuery( '#shipping_product_data' ).find( 'select#product_shipping_class' ).prop( 'disabled', true );

					/* Disable en verberg checkboxes continenten */
					<?php foreach ( $continents as $id ) : ?>
						jQuery( '#in-product_partner-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
					<?php endforeach; ?>

					/* Disable bovenliggende landen/continenten van alle aangevinkte partners/landen */
					jQuery( '#product_partner-all' ).find( 'input[type=checkbox]:checked' ).closest( 'ul.children' ).siblings( 'label.selectit' ).find( 'input[type=checkbox]' ).prop( 'disabled', true );

					/* Disable/enable het bovenliggende land bij aan/afvinken van een partner en reset de aanvinkstatus van de parent */
					jQuery( '#product_partner-all' ).find( 'input[type=checkbox]' ).on( 'change', function() {
						jQuery(this).closest( 'ul.children' ).siblings( 'label.selectit' ).find( 'input[type=checkbox]' ).prop( 'checked', false ).prop( 'disabled', jQuery(this).is(":checked") );
					});

					/* Disable/enable het overeenkomstige allergeen in contains/may-contain bij aan/afvinken van may-contain/contains */
					jQuery( '#product_allergen-all, #product_allergen-checklist' ).find( 'input[type=checkbox]' ).on( 'change', function() {
						var changed_box = jQuery(this);
						var label = changed_box.closest( 'label.selectit' ).text();
						changed_box.closest( 'ul.children' ).closest( 'li' ).siblings().find( 'label.selectit' ).each( function() {
							if ( jQuery(this).text() == label ) {
								jQuery(this).find( 'input[type=checkbox]' ).prop( 'checked', false ).prop( 'disabled', changed_box.is(":checked") );
							}
						});
					});

					/* Disable alle allergenen indien expliciet aangegeven werd dat er geen allergenen zijn */
					jQuery( '#in-product_allergen-<?php echo $none_term->term_id; ?>:checked' ).each( function() {
						var none_box = jQuery(this);
						none_box.closest( 'li' ).siblings().find( 'input[type=checkbox]' ).prop( 'checked', false ).prop( 'disabled', none_box.is(":checked") );
					});
					/* Ook on toggle */
					jQuery( '#in-product_allergen-<?php echo $none_term->term_id; ?>' ).on( 'change', function() {
						var none_box = jQuery(this);
						none_box.closest( 'li' ).siblings().find( 'input[type=checkbox]' ).prop( 'checked', false ).prop( 'disabled', none_box.is(":checked") );
					});

					/* Disable en verberg checkboxes rode en witte druiven */
					<?php foreach ( $grapes as $id ) : ?>
						jQuery( '#in-product_grape-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
					<?php endforeach; ?>
					
					/* Vereis dat er één productcategorie en minstens één partner/land aangevinkt is voor het opslaan */
					jQuery( 'input[type=submit]#publish, input[type=submit]#save-post' ).click( function() {
						var pass = true;
						var msg = 'Hold your horses, er zijn enkele issues:\n';
						if ( jQuery( '#product_partner-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
							pass = false;
							msg += '* Je moet de herkomst nog aanvinken!\n';
						}
						if ( jQuery( '#product_cat-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
							pass = false;
							msg += '* Je moet de productcategorie nog aanvinken!\n';
						}
						if ( jQuery( '#general_product_data' ).find( 'input#_fairtrade_share' ).val() == '' ) {
							pass = false;
							msg += '* Je moet het fairtradepercentage nog ingeven!\n';
						}

						<?php if ( $parent->slug !== 'wijn' or $parent->slug === 'vin' or $parent->slug === 'wine' ) : ?>
							if ( jQuery( '#general_product_data' ).find( 'textarea#_ingredients' ).val() == '' ) {
								pass = false;
								msg += '* Je moet de ingrediëntenlijst nog ingeven!\n';
							}
						<?php else : ?>
							/* Vereis dat minstens één druif, gerecht en smaak aangevinkt is voor het opslaan van wijntjes */
							if ( jQuery( '#product_grape-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
								// pass = false;
								msg += '* Je moet de druivenrassen nog aanvinken!\n';
							}
							if ( jQuery( '#product_recipe-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
								// pass = false;
								msg += '* Je moet de gerechten nog aanvinken!\n';
							}
							if ( jQuery( '#product_taste-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
								// pass = false;
								msg += '* Je moet de smaken nog aanvinken!\n';
							}
						<?php endif; ?>

						/* Check of de som van alle secundaire voedingswaardes de primaire niet overschrijdt */
						jQuery( '#quality_product_data' ).find( 'p.primary' ).each( function() {
							/* Kleine marge nemen voor afrondingsfouten */
							var max = Number( jQuery(this).children( 'input' ).first().val() );
							var sum = 0;
							jQuery(this).siblings( 'p.secondary' ).each( function() {
								sum += Number( jQuery(this).children( 'input' ).first().val() );
							});
							if ( sum > max + 0.1 ) {
								pass = false;
								msg += '* Som van secundaire waardes is groter dan primaire voedingswaarde ('+sum.toFixed(1)+' g > '+max.toFixed(1)+' g)!\n';
							}
						});

						if ( pass == false ) {
							alert(msg);
						}
						
						// ALLE DISABLED DROPDOWNS WEER ACTIVEREN, ANDERS GEEN WAARDE DOORGESTUURD
						// In ELSE-blok stoppen indien we de controle activeren, om te vermijden dat de data beschikbaar wordt indien er geen page reload plaatsvindt wegens blokkage
						jQuery( '#general_product_data' ).find( 'select#_tax_status' ).prop( 'disabled', false );
						jQuery( '#general_product_data' ).find( 'select#_tax_class' ).prop( 'disabled', false );
						jQuery( '#general_product_data' ).find( 'select#_net_unit' ).prop( 'disabled', false );
						jQuery( '#inventory_product_data' ).find( 'select#_stock_status' ).prop( 'disabled', false );
						jQuery( '#shipping_product_data' ).find( 'select#product_shipping_class' ).prop( 'disabled', false );

						// Voorlopig niet afdwingen dat de fouten eerst opgelost moeten worden
						// return pass;
						return true;
						
					});
				});
			</script>
			<?php

		}
	}

	// Toon metaboxes voor wijninfo enkel voor producten onder de hoofdcategorie 'Wijn'
	add_action( 'admin_init', 'hide_wine_taxonomies' );

	function hide_wine_taxonomies() {
		global $pagenow;
		$remove = true;
		if ( ( $pagenow === 'post.php' or $pagenow === 'post-new.php' ) and ( isset( $_GET['post'] ) and get_post_type( $_GET['post'] ) === 'product' ) ) {
			$categories =  get_the_terms( $_GET['post'], 'product_cat' );
			if ( is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					while ( intval($category->parent) !== 0 ) {
						$parent = get_term( $category->parent, 'product_cat' );
						$category = $parent;
					}
				}
				// VERTALEN EN CHECKEN IN HOOFDTAAL
				if ( $parent->slug === 'wijn' or $parent->slug === 'vin' or $parent->slug === 'wine' ) {
					$remove = false;
				}
			}
		}
		if ( $remove ) {
			remove_meta_box( 'product_grapediv', 'product', 'normal' );
			remove_meta_box( 'product_recipediv', 'product', 'normal' );
			remove_meta_box( 'product_tastediv', 'product', 'normal' );
		}
	}

	// Creëer een custom vlakke taxonomie op producten om hipsterinfo in op te slaan
	add_action( 'init', 'register_hipster_taxonomy', 50 );

	function register_hipster_taxonomy() {
		$taxonomy_name = 'product_hipster';
		
		$labels = array(
			'name' => __( 'Hipstertermen', 'oft' ),
			'singular_name' => __( 'Hipsterterm', 'oft' ),
			'all_items' => __( 'Alle hipstertermen', 'oft' ),
			'new_item_name' => __( 'Nieuwe hipsterterm', 'oft' ),
			'add_new_item' => __( 'Voeg nieuwe hipsterterm toe', 'oft' ),
			'view_item' => __( 'Hipsterterm bekijken', 'oft' ),
			'edit_item' => __( 'Hipsterterm bewerken', 'oft' ),
			'update_item' => __( 'Hipsterterm bijwerken', 'oft' ),
			'search_items' => __( 'Hipstertermen doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Duid de eigenschappen van het product aan', 'oft' ),
			'public' => true,
			'publicly_queryable' => true,
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_rest' => true,
			'show_tagcloud' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'query_var' => true,
			// Geef catmans rechten om zelf termen toe te kennen (+ overzicht te bekijken) maar niet om te bewerken (+ toe te voegen) / te verwijderen!
			'capabilities' => array( 'assign_terms' => 'manage_product_terms', 'edit_terms' => 'update_core', 'manage_terms' => 'manage_product_terms', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'eco', 'with_front' => false, 'hierarchical' => false ),
			// ZORGT ERVOOR DAT DE ID ALS TERM OPGESLAGEN WORDT, NIET BRUIKBAAR
			// 'meta_box_cb' => 'post_categories_meta_box',
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Creëer een custom hiërarchische taxonomie op producten om verpakkingsinfo in op te slaan
	add_action( 'init', 'register_packaging_taxonomy', 50 );

	function register_packaging_taxonomy() {
		$taxonomy_name = 'product_packaging';
		
		$labels = array(
			'name' => __( 'Verpakkingswijzes', 'oft' ),
			'singular_name' => __( 'Verpakkingswijze', 'oft' ),
			'all_items' => __( 'Alle verpakkingswijzes', 'oft' ),
			'parent_item' => __( 'Type', 'oft' ),
			'parent_item_colon' => __( 'Type:', 'oft' ),
			'new_item_name' => __( 'Nieuwe verpakkingswijze', 'oft' ),
			'add_new_item' => __( 'Voeg nieuwe verpakkingswijze toe', 'oft' ),
			'view_item' => __( 'Verpakkingswijzes bekijken', 'oft' ),
			'edit_item' => __( 'Verpakkingswijze bewerken', 'oft' ),
			'update_item' => __( 'Verpakkingswijze bijwerken', 'oft' ),
			'search_items' => __( 'Verpakkingswijzes doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Duid de eigenschappen van het product aan', 'oft' ),
			'public' => false,
			'publicly_queryable' => false,
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_tagcloud' => false,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'query_var' => true,
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'manage_product_terms', 'edit_terms' => 'manage_product_terms', 'manage_terms' => 'manage_product_terms', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'packaging', 'with_front' => false, 'hierarchical' => true ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Creëer een custom vlakke taxonomie op producten om bewaar- en gebruiksvoorschriften in op te slaan
	add_action( 'init', 'register_storage_taxonomy', 50 );

	function register_storage_taxonomy() {
		$taxonomy_name = 'product_storage';
		
		$labels = array(
			'name' => __( 'Bewaarvoorschriften', 'oft' ),
			'singular_name' => __( 'Bewaarvoorschrift', 'oft' ),
			'all_items' => __( 'Alle bewaarvoorschriften', 'oft' ),
			'new_item_name' => __( 'Nieuw bewaarvoorschrift', 'oft' ),
			'add_new_item' => __( 'Voeg nieuw bewaarvoorschrift toe', 'oft' ),
			'view_item' => __( 'Bewaarvoorschriften bekijken', 'oft' ),
			'edit_item' => __( 'Bewaarvoorschrift bewerken', 'oft' ),
			'update_item' => __( 'Bewaarvoorschrift bijwerken', 'oft' ),
			'search_items' => __( 'Bewaarvoorschriften doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Duid de eigenschappen van het product aan', 'oft' ),
			'public' => false,
			'publicly_queryable' => false,
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_rest' => true,
			'show_tagcloud' => false,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'query_var' => true,
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'manage_product_terms', 'edit_terms' => 'manage_product_terms', 'manage_terms' => 'manage_product_terms', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'storage', 'with_front' => false, 'hierarchical' => false ),
			// ZORGT ERVOOR DAT DE ID ALS TERM OPGESLAGEN WORDT, NIET BRUIKBAAR
			// 'meta_box_cb' => 'post_categories_meta_box',
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}



	#################
	#  WOOCOMMERCE  #
	#################

	add_filter( 'woocommerce_breadcrumb_defaults', 'change_woocommerce_breadcrumb_delimiter' );
	
	function change_woocommerce_breadcrumb_delimiter( $defaults ) {
		$defaults['delimiter'] = ' &rarr; ';
		return $defaults;
	}

	// Voeg sorteren op artikelnummer toe aan de opties op cataloguspagina's
	add_filter( 'woocommerce_get_catalog_ordering_args', 'add_extra_sorting_filters' );

	function add_extra_sorting_filters( $args ) {
		// Kan door veralgemening van code nu ook een shortcode argument zijn i.p.v. van een GET-paramater in de URL!
		// Verstoort shortcodes, zie gelijkaardige mixing issue op https://github.com/woocommerce/woocommerce/issues/18859
		// Nettere voorwaarde wc_get_loop_prop('is_shortcode') lijkt niet te werken ...
		if ( is_woocommerce() ) {
			$orderby_value = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
			if ( isset( $_GET['orderby'] ) ) {
				$orderby_value = wc_clean( $_GET['orderby'] );
			}

			if ( 'alpha' === $orderby_value ) {
				$args['orderby'] = 'title';
				$args['order'] = 'ASC';
			}

			if ( 'alpha-desc' === $orderby_value ) {
				$args['orderby'] = 'title';
				$args['order'] = 'DESC';
			}

			if ( 'sku' === $orderby_value ) {
				$args['orderby'] = 'meta_value_num';
				$args['order'] = 'ASC';
				$args['meta_key'] = '_sku';
			}

			if ( 'sku-desc' === $orderby_value ) {
				$args['orderby'] = 'meta_value_num';
				$args['order'] = 'DESC';
				$args['meta_key'] = '_sku';
			}
		}

		return $args;
	}
	
	add_filter( 'woocommerce_catalog_orderby', 'sku_sorting_orderby' );
	add_filter( 'woocommerce_default_catalog_orderby_options', 'sku_sorting_orderby' );

	function sku_sorting_orderby( $sortby ) {
		unset( $sortby['popularity'] );
		unset( $sortby['rating'] );
		$sortby['date'] = __( 'Laatst toegevoegd', 'oft-admin' );
		$sortby['alpha'] = __( 'Van A tot Z', 'oft-admin' );
		$sortby['alpha-desc'] = __( 'Van Z tot A', 'oft-admin' );
		$sortby['price'] = __( 'Stijgende prijs', 'oft-admin' );
		$sortby['price-desc'] = __( 'Dalende prijs', 'oft-admin' );
		$sortby['sku'] = __( 'Stijgend artikelnummer', 'oft-admin' );
		$sortby['sku-desc'] = __( 'Dalend artikelnummer', 'oft-admin' );
		return $sortby;
	}

	// Voeg ook een kolom toe aan het besteloverzicht in de back-end
	add_filter( 'manage_edit-product_columns', 'add_attribute_columns', 20, 1 );

	function add_attribute_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			// Nieuwe kolommen invoegen net voor productcategorie
			if ( $key === 'product_cat' ) {
				$new_columns['pa_merk'] = __( 'Merk', 'oft-admin' );
				// Inhoud van deze kolom is al door WooCommerce gedefinieerd, dit zorgt er gewoon voor dat de kolom ook beschikbaar is indien de optie 'woocommerce_manage_stock' op 'no' staat
				$new_columns['is_in_stock'] = __( 'BestelWeb', 'oft-admin' );
			}
			// Nutteloze kolom met producttype weglaten
			if ( $key !== 'product_type' ) {
				if ( $key === 'sku' ) {
					$new_columns[$key] = __( 'Ompaknummer', 'oft-admin' );
				} elseif ( $key === 'price' ) {
					if ( get_option('woocommerce_tax_display_shop') === 'excl' ) {
						$new_columns[$key] = __( 'Prijs (excl. BTW)', 'oft-admin' );
					} else {
						$new_columns[$key] = __( 'Prijs (incl. BTW)', 'oft-admin' );
					}
				} elseif ( $key === 'icl_translations' ) {
					$new_columns[$key] = __( 'Talen', 'oft-admin' );
				} else {
					$new_columns[$key] = $title;
				}
			}
		}
		return $new_columns;
	}

	// Toon de data van elk order in de kolom
	add_action( 'manage_product_posts_custom_column' , 'get_attribute_column_value', 10, 2 );
	
	function get_attribute_column_value( $column, $post_id ) {
		global $wp, $the_product;
		
		if ( $column === 'pa_merk' ) {
			if ( ! empty( $the_product->get_attribute('pa_merk') ) ) {
				// OPGELET: Kan theoretisch meer dan één term bevatten!
				$attribute = get_term_by( 'name', $the_product->get_attribute('pa_merk'), 'pa_merk' );
				// Gebruik home_url( add_query_arg( 'term', $attribute->slug ) ) indien je de volledige huidige query-URL wil behouden
				echo '<a href="/wp-admin/edit.php?post_type=product&taxonomy=pa_merk&term='.$attribute->slug.'">'.$attribute->name.'</a>';
			} else {
				echo '<span aria-hidden="true">&#8212;</span>';
			}
		}
	}

	// Creëer extra merkenfilter bovenaan de productenlijst VOORLOPIG WEGLATEN
	// add_action( 'restrict_manage_posts', 'add_filters_to_products' );

	function add_filters_to_products() {
		global $pagenow, $post_type;
		if ( $pagenow === 'edit.php' and $post_type === 'product' ) {
			$args = array( 'taxonomy' => 'pa_merk', 'hide_empty' => false );
			$terms = get_terms( $args );
			$values_brand = array();
			foreach ( $terms as $term ) {
				$values_brand[$term->slug] = $term->name;
			}
			
			$current_brand = isset( $_REQUEST['term'] ) ? wc_clean( wp_unslash( $_REQUEST['term'] ) ) : false;
			// echo '<select name="taxonomy"><option value="pa_merk"></option></select>';
			echo '<select name="term">';
				echo '<option value="">'.__( 'Op merk filteren', 'oft-admin' ).'</option>';
				foreach ( $values_brand as $status => $label ) {
					echo '<option value="'.$status.'" '.selected( $status, $current_brand, false ).'>'.$label.'</option>';
				}
			echo '</select>';
		}
	}

	// Maak sorteren op custom kolommen mogelijk
	add_filter( 'manage_edit-product_sortable_columns', 'make_attribute_columns_sortable', 10, 1 );

	function make_attribute_columns_sortable( $columns ) {
		$columns['featured'] = 'featured';
		// BETER VIA FILTERS BOVENAAN
		// $columns['pa_merk'] = 'pa_merk';
		// $columns['is_in_stock'] = 'is_in_stock';
		return $columns;
	}

	// Voer de sortering uit tijdens het bekijken van producten in de admin NIET NODIG VOOR STANDAARD EIGENSCHAPPEN
	// add_action( 'pre_get_posts', 'sort_products_on_custom_column', 20 );
	
	function sort_products_on_custom_column( $query ) {
		global $pagenow, $post_type;
		if ( $pagenow === 'edit.php' and $post_type === 'product' and $query->query['post_type'] === 'product' ) {
			// Check of we moeten sorteren op één van onze custom kolommen
			if ( $query->get( 'orderby' ) === 'pa_merk' ) {
				$query->set( 'meta_key', 'pa_merk' );
				$query->set( 'orderby', 'meta_value' );
			}
		}
	}

	// Verberg niet-OFT-producten door automatisch 'private'-status toe te kennen bij het publiceren
	add_action( 'save_post', 'change_external_product_status', 10, 3 );

	function change_external_product_status( $post_id, $post, $update ) {
		if ( defined('DOING_AUTOSAVE') and DOING_AUTOSAVE ) {
			return;
		}
		
		if ( $post->post_status === 'trash' ) {
			return;
		}

		if ( $post->post_type !== 'product' or ! $product = wc_get_product( $post_id ) ) {
			return;
		}

		$brand = $product->get_attribute('pa_merk');
		if ( $post->post_status !== 'draft' and $brand !== '' and $brand !== 'Oxfam Fair Trade' and $brand !== 'Maya' ) {
			$product->set_status('private');
			$product->save();
		}

		// Update de productfiches na een handmatige bewerking
		if ( get_option('oft_import_active') !== 'yes' and isset( $_POST['_update_product_sheet'] ) and $_POST['_update_product_sheet'] === 'yes' ) {
			// Enkel proberen aanmaken indien foto reeds aanwezig
			if ( intval( $product->get_image_id() ) > 0 ) {
				create_product_pdf( $product->get_id(), 'nl' );
				create_product_pdf( $product->get_id(), 'fr' );
				create_product_pdf( $product->get_id(), 'en' );
			}
		}
	}

	// Check na het publiceren van een product of de datum moet bijgewerkt worden
	add_action( 'draft_to_publish', 'check_publish_date_update', 10, 1 );
	
	function check_publish_date_update( $post ) {
		if ( $post->post_type === 'product' ) {
			wp_update_post(
				array(
					'ID' => $post->ID,
					'post_date' => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', 1 ),
				)
			);
		}
	}

	// Synchroniseer de publicatiestatus van/naar draft naar de anderstalige producten (gebeurt bij trashen reeds automatisch door WPML)
	// Neem een erg hoge prioriteit, zodat de hook pas doorlopen wordt na de 1ste 'save_post', die de zichtbaarheid regelt
	add_action( 'draft_to_publish', 'sync_product_status', 100, 1 );
	add_action( 'draft_to_private', 'sync_product_status', 100, 1 );
	add_action( 'publish_to_draft', 'sync_product_status', 100, 1 );
	add_action( 'private_to_draft', 'sync_product_status', 100, 1 );

	function sync_product_status( $post ) {
		// Frans en Engels publiceren van zodra Nederlands product online komt!
		$lang = apply_filters( 'wpml_post_language_details', NULL, $post->ID );
		if ( $post->post_type === 'product' and $lang['language_code'] === 'nl' ) {
			$nl_product = wc_get_product($post->ID);
			if ( $nl_product !== false ) {
				$status = $nl_product->get_status();

				$fr_product_id = apply_filters( 'wpml_object_id', $post->ID, 'product', false, 'fr' );
				$fr_product = wc_get_product($fr_product_id);
				if ( $fr_product !== false ) {
					$fr_product->set_status($status);
					$fr_product->save();
				}

				$en_product_id = apply_filters( 'wpml_object_id', $post->ID, 'product', false, 'en' );
				$en_product = wc_get_product($en_product_id);
				if ( $en_product !== false ) {
					$en_product->set_status($status);
					$en_product->save();
				}
			}
		}
	}

	// Verduidelijk de status van een product in de overzichtslijst
	add_filter( 'display_post_states', 'clarify_draft_private_products', 100, 2 );

	function clarify_draft_private_products( $post_states, $post ) {
		if ( 'product' === get_post_type($post) ) {
			// Door Unyson Event Helper eerder weggefilterde statussen opnieuw toevoegen (of overrulen indien gedeactiveerd)
			if ( 'private' === get_post_status($post) ) {
				$post_states = array( 'private' => 'NIET ZICHTBAAR' );
			} elseif ( 'draft' === get_post_status($post) ) {
				$post_states = array( 'draft' => 'NOG NIET GEPUBLICEERD' );
			}
		}
		return $post_states;
	}

	// Verduidelijk de titel van niet-OFT-producten in front-end
	add_filter( 'private_title_format', 'hide_private_on_title' );

	function hide_private_on_title( $format ) {
		if ( is_feed() ) {
			return '%s';
		} else {
			return 'NIET ZICHTBAAR: %s';
		}
	}

	// Voeg klasse toe indien recent product
	add_filter( 'post_class', 'add_recent_product_class' );

	function add_recent_product_class( $classes ) {
		global $post;
		if ( get_the_date( 'Y-m-d', $post->ID ) > date_i18n( 'Y-m-d', strtotime('-3 months') ) ) {
			$classes[] = 'newbee';
		}
		return $classes;
	}

	// Toon metavelden netjes in de WooCommerce-tabbladen en werk ze bij tijdens het opslaan
	add_action( 'woocommerce_product_options_general_product_data', 'add_oft_general_fields', 5 );
	add_action( 'woocommerce_product_options_inventory_product_data', 'add_oft_inventory_fields', 5 );
	add_action( 'woocommerce_product_options_shipping', 'add_oft_shipping_fields', 5 );
	add_filter( 'woocommerce_product_data_tabs', 'add_product_quality_tab' );
	add_action( 'woocommerce_product_data_panels', 'add_oft_quality_fields' ); 
	add_action( 'woocommerce_process_product_meta_simple', 'save_oft_fields' );
	
	function add_oft_general_fields() {
		global $post;
		$product = wc_get_product($post->ID);
		
		echo '<div class="options_group oft">';
			
			$suffix = '&euro;';
			if ( $product->get_meta('_net_unit') === 'cl' ) {
				$suffix .= '/l';
			} elseif ( $product->get_meta('_net_unit') === 'g' ) {
				$suffix .= '/kg';
			}

			$category_ids = $product->get_category_ids();
			if ( is_array($category_ids) and count($category_ids) > 0 ) {
				// In principe slechts één categorie geselecteerd bij ons, dus gewoon 1ste element nemen
				$category = get_term( $category_ids[0], 'product_cat' );
				if ( $category->slug === 'fruitsap' or $category->slug === 'jus-de-fruit' or $category->slug === 'fruit-juice' ) {
					woocommerce_wp_text_input(
						array( 
							'id' => '_empty_fee',
							'label' => __( 'Leeggoed (&euro;)', 'oft-admin' ),
							'wrapper_class' => 'important-for-catman',
							'data_type' => 'price',
						)
					);
				}
			}

			woocommerce_wp_text_input(
				array( 
					'id' => '_unit_price',
					'label' => sprintf( __( 'Eenheidsprijs (%s)', 'oft-admin' ), $suffix ),
					'placeholder' => __( 'nog niet berekenbaar', 'oft-admin' ),
					'desc_tip' => true,
					'description' => __( 'Deze waarde wordt automatisch berekend bij het opslaan, op voorwaarde dat zowel prijs, inhoudsmaat als netto-inhoud ingevuld zijn.', 'oft-admin' ),
					'data_type' => 'price',
					'custom_attributes' => array(
						'readonly' => true,
					),
				)
			);

			woocommerce_wp_select(
				array( 
					'id' => '_net_unit',
					'label' => __( 'Inhoudsmaat', 'oft-admin' ),
					'options' => array(
						'' => __( '(selecteer)', 'oft-admin' ),
						'g' => __( 'gram (vast product)', 'oft-admin' ),
						'cl' => __( 'centiliter (vloeibaar product)', 'oft-admin' ),
					),
				)
			);

			// Toon het veld voor de netto-inhoud pas na het instellen van de eenheid!
			if ( ! empty( $product->get_meta('_net_unit') ) ) {
				$unit = $product->get_meta('_net_unit');
			} else {
				$unit = 'g of cl';
			}

			woocommerce_wp_text_input(
				array( 
					'id' => '_net_content',
					'label' => sprintf( __( 'Netto-inhoud (%s)', 'oft-admin' ), $unit ),
					'type' => 'number',
					'custom_attributes' => array(
						'step' => '1',
						'min' => '1',
						'max' => '10000',
						'readonly' => true,
					),
				)
			);

			$args_share = array( 
				'id' => '_fairtrade_share',
				'label' => __( 'Aandeel fairtrade (%)', 'oft-admin' ),
				'type' => 'number',
				'wrapper_class' => 'important-for-catman',
				'custom_attributes' => array(
					'step' => '1',
					'min' => '10',
					'max' => '100',
				),
			);

			// Enkel bewerkbaar maken in hoofdtaal, veld wordt gekopieerd naar andere talen
			if ( ! post_language_equals_site_language() ) {
				$args_share['custom_attributes']['readonly'] = true;
			}

			woocommerce_wp_text_input( $args_share );

			woocommerce_wp_textarea_input(
				array( 
					'id' => '_ingredients',
					'label' => __( 'Ingrediëntenlijst', 'oft-admin' ).'<br>* = '.__( 'fair trade', 'oft-admin' ).'<br>° = '.__( 'biologisch', 'oft-admin' ).'<br>'.mb_strtoupper( __( 'allergeen', 'oft-admin' ) ),
					'wrapper_class' => 'important-for-catman',
					'rows' => 4,
				)
			);

			woocommerce_wp_textarea_input(
				array( 
					'id' => '_promo_text',
					'label' => __( 'Actuele promotekst', 'oft-admin' ),
					'wrapper_class' => 'important-for-catman',
					'desc_tip' => true,
					'description' => __( 'Dit tekstje dient enkel om te tonen aan particulieren in de wijnkiezer en de webshops. Te combineren met de actieprijs en -periode hierboven.', 'oft-admin' ),
				)
			);

		echo '</div>';

		echo '<div class="options_group">';

			woocommerce_wp_checkbox( 
				array( 
					'id' => '_update_product_sheet',
					'label' => __( 'Fiches updaten', 'oft' ),
					'description' => __( 'Vink dit aan als je de PDF\'s wil bijwerken wanneer je het product opslaat (werkt ook voor niet-OFT-producten!)', 'oft' ),
				)
			);

			$languages = array( 'nl', 'fr', 'en' );
			foreach ( $languages as $language ) {
				$path = '/sheets/'.$language.'/'.$product->get_sku().'.pdf';
				if ( file_exists( WP_CONTENT_DIR.$path ) ) {
					echo '<p class="form-field"><label>Productfiche '.mb_strtoupper($language).'</label><a href="'.content_url($path).'" target="_blank">'.__( 'Download PDF', 'oft-admin' ).'</a> ('.get_date_from_gmt( date_i18n( 'Y-m-d H:i:s', filemtime(WP_CONTENT_DIR.$path) ), 'd/m/Y @ H:i' ).')</p>';
				}
			}
		echo '</div>';
	}
	
	function add_oft_inventory_fields() {
		echo '<div class="options_group oft">';
			
			woocommerce_wp_text_input(
				array( 
					'id' => '_shopplus_sku',
					'label' => __( 'ShopPlus', 'oft-admin' ),
					'custom_attributes' => array(
						'readonly' => true,
					),
				)
			);

			woocommerce_wp_text_input(
				array( 
					'id' => '_shelf_life',
					'label' => __( 'Houdbaarheid na productie (dagen)', 'oft-admin' ),
					'type' => 'number',
					'custom_attributes' => array(
						'step'	=> '1',
						'min'	=> '1',
						'max'	=> '10000',
						'readonly' => true,
					),
				)
			);

		echo '</div>';
	}

	function add_oft_shipping_fields() {
		global $post;

		// Kan een 14de controlecijfer als voorvoegsel bevatten!
		$barcode_args = array( 
			'type' => 'number',
			'wrapper_class' => 'wide',
			'custom_attributes' => array(
				'step' => '1',
				'min' => '1000000000000',
				'max' => '99999999999999',
				'readonly' => true,
			),
		);

		$number_args = array( 
			'type' => 'number',
			'custom_attributes' => array(
				'step' => '1',
				'min' => '1',
				'max' => '1000',
				'readonly' => true,
			),
		);

		$cu_ean = array(
			'id' => '_cu_ean',
			'label' => __( 'EAN-code', 'oft-admin' ),
		);

		$steh_ean = array(
			'id' => '_steh_ean',
			'label' => __( 'EAN-code <u>ompak</u>', 'oft-admin' ),
		);

		$multiple = array(
			'id' => '_multiple',
			'label' => __( 'Aantal stuks per ompak', 'oft-admin' ),
		);

		$pal_number_per_layer = array(
			'id' => '_pal_number_per_layer',
			'label' => __( 'Aantal ompakken per laag', 'oft-admin' ),
		);
		
		$pal_number_of_layers = array(
			'id' => '_pal_number_of_layers',
			'label' => __( 'Aantal lagen per pallet', 'oft-admin' ),
		);

		echo '<div class="options_group oft">';
			woocommerce_wp_text_input(
				array( 
					'id' => '_intrastat',
					'label' => __( 'Intrastatcode', 'oft-admin' ),
					'custom_attributes' => array(
						'readonly' => true,
					),
				)
			);
			woocommerce_wp_text_input( $cu_ean + $barcode_args );
		echo '</div>';
		
		echo '<div class="options_group oft">';
			woocommerce_wp_text_input( $multiple + $number_args );
			woocommerce_wp_text_input( $pal_number_per_layer + $number_args );
			woocommerce_wp_text_input( $pal_number_of_layers + $number_args );
		echo '</div>';

		echo '<div class="options_group oft">';
			woocommerce_wp_text_input(
				array(
					'id' => '_steh_weight',
					'label' => __( 'Gewicht <u>ompak</u> (kg)', 'oft-admin' ),
					'placeholder' => wc_format_localized_decimal( 0 ),
					'desc_tip' => true,
					'description' => __( 'Weight in decimal form', 'woocommerce' ),
					'data_type' => 'decimal',
					'custom_attributes' => array(
						'readonly' => true,
					),
				)
			);
			?>
			<!-- ZIE: woocommerce/includes/admin/meta-boxes/views/html-product-data-shipping.php -->
			<p class="form-field dimensions_field">
				<label for="box_length"><?php printf( __( 'Afmetingen <u>ompak</u> (%s)', 'oft-admin' ), get_option( 'woocommerce_dimension_unit' ) ); ?></label>
				<span class="wrap">
					<input id="box_length" placeholder="<?php esc_attr_e( 'Length', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_steh_length" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $post->ID, '_steh_length', true ) ) ); ?>" readonly />
					<input placeholder="<?php esc_attr_e( 'Width', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_steh_width" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $post->ID, '_steh_width', true ) ) ); ?>" readonly />
					<input placeholder="<?php esc_attr_e( 'Height', 'woocommerce' ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="_steh_height" value="<?php echo esc_attr( wc_format_localized_decimal( get_post_meta( $post->ID, '_steh_height', true ) ) ); ?>" readonly />
				</span>
				<?php echo wc_help_tip( __( 'LxWxH in decimal form', 'woocommerce' ) ); ?>
			</p>
			<?php
			woocommerce_wp_text_input( $steh_ean + $barcode_args );
		echo '</div>';
	}

	function add_product_quality_tab( $product_data_tabs ) {
		$product_data_tabs['quality'] = array(
			'label' => __( 'Voedingsinfo', 'oft-admin' ),
			'target' => 'quality_product_data',
			'class' => array( 'hide_if_virtual' ),
		);
		// Herbenoem tabjes
		$product_data_tabs['shipping']['label'] = __( 'Logistiek', 'oft-admin' );
		// $product_data_tabs['linked_product']['label'] = __( 'Gerelateerd', 'oft-admin' );
		// Verwijder overbodig tabje
		unset($product_data_tabs['advanced']);
		return $product_data_tabs;
	}

	function add_oft_quality_fields() {
		global $post;

		$suffix = ' (g)';
		$hint = ' &nbsp; <small><u>'.mb_strtoupper( __( 'per 100 gram', 'oft-admin' ) ).'</u></small>';
		
		$one_decimal_args = array( 
			// Niet doen, zorgt ervoor dat waardes met een punt niet goed uitgelezen worden in back-endformulier
			// 'data_type' => 'decimal',
			'type' => 'number',
			'custom_attributes' => array(
				'step' => '0.1',
				'min' => '0.0',
				'max' => '100.0',
			),
		);

		if ( ! post_language_equals_site_language() ) {
			$one_decimal_args['custom_attributes']['readonly'] = true;
		}

		$primary = array(
			'wrapper_class' => 'primary',
		);

		$important = array(
			'wrapper_class' => 'important-for-catman',
		);

		$important_primary = array(
			'wrapper_class' => 'important-for-catman primary',
		);

		$secondary = array(
			'wrapper_class' => 'secondary',
		);

		$important_secondary = array(
			'wrapper_class' => 'important-for-catman secondary',
		);

		$fat = array(
			'id' => '_fat',
			'label' => __( 'Vetten', 'oft' ).$suffix.$hint,
		);
		
		$fasat = array(
			'id' => '_fasat',
			'label' => __( 'waarvan verzadigde vetzuren', 'oft' ).$suffix,
		);

		$famscis = array(
			'id' => '_famscis',
			'label' => __( 'waarvan enkelvoudig onverzadigde vetzuren', 'oft' ).$suffix,
		);

		$fapucis = array(
			'id' => '_fapucis',
			'label' => __( 'waarvan meervoudig onverzadigde vetzuren', 'oft' ).$suffix,
		);
		
		$choavl = array(
			'id' => '_choavl',
			'label' => __( 'Koolhydraten', 'oft' ).$suffix.$hint,
		);

		$sugar = array(
			'id' => '_sugar',
			'label' => __( 'waarvan suikers', 'oft' ).$suffix,
		);

		$polyl = array(
			'id' => '_polyl',
			'label' => __( 'waarvan polyolen', 'oft' ).$suffix,
		);

		$starch = array(
			'id' => '_starch',
			'label' => __( 'waarvan zetmeel', 'oft' ).$suffix,
		);
		
		$fibtg = array(
			'id' => '_fibtg',
			'label' => __( 'Vezels', 'oft' ).$suffix.$hint,
		);

		$pro = array(
			'id' => '_pro',
			'label' => __( 'Eiwitten', 'oft' ).$suffix.$hint,
		);

		echo '<div id="quality_product_data" class="panel woocommerce_options_panel">';
			echo '<div class="options_group oft">';
				$args_energy = array( 
					'id' => '_energy',
					'label' => __( 'Energie', 'oft' ).' (kJ)'.$hint,
					'type' => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min' => '1',
						'max' => '10000',
					),
				);

				if ( ! post_language_equals_site_language() ) {
					$args_energy['custom_attributes']['readonly'] = true;
				}

				woocommerce_wp_text_input( $args_energy + $important );
			echo '</div>';
		
			echo '<div class="options_group oft">';
				woocommerce_wp_text_input( $fat + $one_decimal_args + $important_primary );
				woocommerce_wp_text_input( $fasat + $one_decimal_args + $important_secondary );
				woocommerce_wp_text_input( $famscis + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $fapucis + $one_decimal_args + $secondary );
			echo '</div>';
		
			echo '<div class="options_group oft">';
				woocommerce_wp_text_input( $choavl + $one_decimal_args + $important_primary );
				woocommerce_wp_text_input( $sugar + $one_decimal_args + $important_secondary );
				woocommerce_wp_text_input( $polyl + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $starch + $one_decimal_args + $secondary );
			echo '</div>';
		
			echo '<div class="options_group oft">';
				woocommerce_wp_text_input( $fibtg + $one_decimal_args );
				woocommerce_wp_text_input( $pro + $one_decimal_args + $important );
				
				$args_salteq = array( 
					'id' => '_salteq',
					'label' => __( 'Zout', 'oft' ).$suffix.$hint,
					'type' => 'number',
					'custom_attributes' => array(
						'step' => '0.001',
						'min' => '0.000',
						'max' => '100.000',
					),
				);

				if ( ! post_language_equals_site_language() ) {
					$args_salteq['custom_attributes']['readonly'] = true;
				}

				woocommerce_wp_text_input( $args_salteq + $important );
			echo '</div>';
		echo '</div>';
	}

	function save_oft_fields( $post_id ) {
		// Bereken - indien mogelijk - de eenheidsprijs a.d.h.v. alle data in $_POST
		// Laatste parameter: val expliciet niét terug op de (verouderde) databasewaarden!
		update_unit_price( $post_id, $_POST['_regular_price'], $_POST['_net_content'], $_POST['_net_unit'], false );
		
		$regular_meta_keys = array(
			'_net_unit',
			'_net_content',
			'_fairtrade_share',
			'_ingredients',
			'_promo_text',
			'_shopplus_sku',
			'_shelf_life',
			'_intrastat',
			'_cu_ean',
			'_steh_ean',
			'_multiple',
			'_pal_number_per_layer',
			'_pal_number_of_layers',
			'_steh_length',
			'_steh_width',
			'_steh_height',
			'_energy',
		);

		foreach ( $regular_meta_keys as $meta_key ) {
			if ( isset( $_POST[$meta_key] ) ) {
				if ( $meta_key === '_ingredients' or $meta_key === '_promo_text' ) {
					update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $_POST[$meta_key] ) );
				} else {
					update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[$meta_key] ) );
				}
			} else {
				update_post_meta( $post_id, $meta_key, '' );
			}
		}

		$decimal_meta_keys = array(
			'_fat',
			'_fasat',
			'_famscis',
			'_fapucis',
			'_choavl',
			'_sugar',
			'_polyl',
			'_starch',
			'_fibtg',
			'_pro',
		);

		foreach ( $decimal_meta_keys as $meta_key ) {
			// Zeker geen !empty() gebruiken want we willen nullen expliciet kunnen opslaan!
			if ( isset( $_POST[$meta_key] ) and $_POST[$meta_key] !== '' ) {
				update_post_meta( $post_id, $meta_key, number_format( floatval( str_replace( ',', '.', $_POST[$meta_key] ) ), 1, '.', '' ) );
			} else {
				update_post_meta( $post_id, $meta_key, '' );
			}
		}

		$price_meta_keys = array(
			'_empty_fee',
		);

		foreach ( $price_meta_keys as $meta_key ) {
			if ( isset( $_POST[$meta_key] ) and $_POST[$meta_key] !== '' ) {
				update_post_meta( $post_id, $meta_key, number_format( floatval( str_replace( ',', '.', $_POST[$meta_key] ) ), 2, '.', '' ) );
			} else {
				update_post_meta( $post_id, $meta_key, '' );
			}
		}

		$high_precision_meta_keys = array(
			'_weight',
			'_steh_weight',
			'_salteq',	
		);

		foreach ( $high_precision_meta_keys as $meta_key ) {
			// Zeker geen !empty() gebruiken want we willen nullen expliciet kunnen opslaan!
			if ( isset( $_POST[$meta_key] ) and $_POST[$meta_key] !== '' ) {
				update_post_meta( $post_id, $meta_key, number_format( floatval( str_replace( ',', '.', $_POST[$meta_key] ) ), 3, '.', '' ) );
			} else {
				update_post_meta( $post_id, $meta_key, '' );
			}
		}
	}

	add_action( 'add_meta_boxes', 'register_custom_meta_boxes' );
	add_action( 'save_post', 'oft_post_to_product_save' );

	function register_custom_meta_boxes() {
		add_meta_box( 'oft_post_to_product', __( 'Gelinkt product', 'oft' ), 'oft_post_to_product_callback', 'post', 'advanced', 'high' );
	}

	function oft_post_to_product_callback( $post ) {
		wp_nonce_field( basename( __FILE__ ), 'oft_post_to_product_nonce' );
		$prfx_stored_meta = get_post_meta( $post->ID );

		$query_args = array(
			'post_type'			=> 'product',
			'post_status'		=> array( 'publish' ),
			'posts_per_page'	=> 500,
			'meta_key'			=> '_sku',
			'orderby'			=> 'meta_value_num',
			'order'				=> 'ASC',
		);

		$current_products = new WP_Query( $query_args );
		
		if ( $current_products->have_posts() ) {
			while ( $current_products->have_posts() ) {
				$current_products->the_post();
				$sku = get_post_meta( get_the_ID(), '_sku', true );
				$list[$sku] = get_the_title();
			}
			wp_reset_postdata();
		}

		?>
			<p>
				<label for="oft_post_product" class=""><?php printf( __( 'Kies 1 van de %d actuele OFT-producten om onderaan het bericht toe te voegen:', 'oft' ), count($list) ); ?></label>
				<select name="oft_post_product" id="oft_post_product">
					<option value=""><?php _e( '(selecteer)', 'oft' ); ?></option>
					<?php foreach ( $list as $sku => $title ) : ?>
						<option value="<?php echo $sku; ?>" <?php if ( isset( $prfx_stored_meta['oft_post_product'] ) ) selected( $prfx_stored_meta['oft_post_product'][0], $sku ); ?>><?php echo $sku.': '.$title; ?></option>';
					<?php endforeach; ?>
				</select>
			</p>
			<p><?php _e( 'Gebruik de shortcode [products skus="A,B,C" columns="3"] indien je meerdere producten wil tonen!', 'oft' ); ?></p>
		<?php
	}

	function oft_post_to_product_save( $post_id ) {
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST['oft_post_to_product_nonce'] ) && wp_verify_nonce( $_POST['oft_post_to_product_nonce'], basename( __FILE__ ) ) ) ? 'true' : 'false';
		
		if ( $is_autosave or $is_revision or ! $is_valid_nonce ) {
			return;
		}
	 
		if ( isset( $_POST['oft_post_product'] ) ) {
			update_post_meta( $post_id, 'oft_post_product', sanitize_text_field( $_POST['oft_post_product'] ) );
		} else {
			delete_post_meta( $post_id, 'oft_post_product' );
		}
	}

	add_action( 'woocommerce_single_product_summary', 'show_additional_information', 70 );

	function show_additional_information() {
		global $product, $sitepress;
		
		$partners = get_partner_terms_by_product($product);
		if ( $partners ) {
			echo '<div class="oft-partners">';
				echo '<div class="oft-partners-row">';
					echo '<div class="oft-partners-th">'._n( 'Partner:', 'Partners:', count($partners), 'oft' ).'</div>';
					echo '<div class="oft-partners-td">'.str_replace( ')', ')</span>', str_replace( '(', '<span class="oft-country">(', implode( ', ', $partners ) ) ).'</div>';
				echo '</div>';
				echo '<div class="oft-partners-row">';
					$quoted_term = get_term_by( 'id', array_rand($partners), 'product_partner' );
					$quoted_term_image_id = intval( get_term_meta( $quoted_term->term_id, 'partner_image_id', true ) );
					$cnt = 0;
					while( ( strlen($quoted_term->description) < 20 or $quoted_term_image_id < 1 ) and $cnt < 3*count($partners) ) {
						$quoted_term = get_term_by( 'id', array_rand($partners), 'product_partner' );
						$quoted_term_image_id = intval( get_term_meta( $quoted_term->term_id, 'partner_image_id', true ) );
						$cnt++;
					}
					if ( strlen($quoted_term->description) >= 20 and $quoted_term_image_id >= 1 ) {
						$quoted_parent_term = get_term_by( 'id', $quoted_term->parent, 'product_partner' );
						echo '<div class="oft-partners-th">'.wp_get_attachment_image( $quoted_term_image_id, array( '110', '110' ), false ).'</div>';
						echo '<div class="oft-partners-td">';
						echo '<p class="oft-partners-quote">'.trim($quoted_term->description).'</p>';
						$quoted_term_node = intval( get_term_meta( $quoted_term->term_id, 'partner_node', true ) );
						if ($quoted_term_node > 0 ) {
							$url = 'https://www.oxfamwereldwinkels.be/node/'.$quoted_term_node;
							// $handle = curl_init($url);
							// curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
							// $response = curl_exec($handle);
							// $code = curl_getinfo( $handle, CURLINFO_HTTP_CODE );
							// if ( $code !== 404 ) {
							// Link staat publiek en mag dus getoond worden WERKT NIET DOOR DE REDIRECTS
							echo '<a href="'.$url.'" target="_blank"><p class="oft-partners-link">'.trim($quoted_term->name).', '.trim($quoted_parent_term->name).'</p></a>';
							// }
							// curl_close($handle);	
						}
						echo '</div>';
					}
				echo '</div>';
			echo '</div>';
		}

		$args = array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'orderby' => 'date',
			'order' => 'DESC',
			'meta_key' => 'oft_post_product',
			'meta_value' => $product->get_sku(),
			'meta_compare' => '=',
			'numberposts' => 1,
		);
		$news_posts = new WP_Query( $args );

		if ( $news_posts->have_posts() ) {
			while ( $news_posts->have_posts() ) {
				$news_posts->the_post();
				echo '<p>'.sprintf( __( 'Lees ook de blogpost "%s".', 'oft' ), '<a href='.get_permalink().'>'.get_the_title().'</a>' ).'</p>';
				// echo "<div class='oft-latest-news'>";
				// echo '<p>'.apply_filters( 'the_content', preg_replace( '#\[[^\]]+\]#', '', get_the_excerpt() ) ).'</p>';
				// echo "</div>";
			}
			wp_reset_postdata();
		}

		if ( file_exists( WP_CONTENT_DIR.'/sheets/'.$sitepress->get_current_language().'/'.$product->get_sku().'.pdf' ) ) {
			echo '<div class="vc_btn3-container oft-product-sheet vc_btn3-center">';
			echo '<a class="vc_general vc_btn3 vc_btn3-size-md vc_btn3-shape-rounded vc_btn3-style-flat vc_btn3-color-blue" href="'.content_url( '/sheets/'.$sitepress->get_current_language().'/'.$product->get_sku().'.pdf' ).'" target="_blank">'.__( 'Download de productfiche', 'oft' ).'</a>';
			echo '</div>';
		}

		echo '<div class="oft-icons">';
			// SLUGS VAN ATTRIBUTEN WORDEN NIET VERTAALD, ENKEL DE TERMEN
			// TAGS ZIJN A.H.W. TERMEN VAN EEN WELBEPAALD ATTRIBUUT EN SLUGS WORDEN DAAR DUS OOK VERTAALD
			// VERGELIJK DE TERMEN DAAROM ALTIJD IN HET NEDERLANDS
			$prev_lang = $sitepress->get_current_language();
			$sitepress->switch_lang( apply_filters( 'wpml_default_language', NULL ) );
			
			if ( strtolower( $product->get_attribute('pa_bio') ) === 'ja' ) {
				echo "<div class='icon-organic'></div>";
			}
			
			$icons = array();
			foreach ( wp_get_object_terms( $product->get_id(), 'product_hipster' ) as $term ) {
				$icons[] = $term->slug;
			}
			if ( in_array( 'veganistisch', $icons ) ) {
				echo "<div class='icon-vegan'></div>";
			}
			if ( in_array( 'glutenvrij', $icons ) ) {
				echo "<div class='icon-gluten-free'></div>";
			}
			if ( in_array( 'zonder-toegevoegde-suiker', $icons ) ) {
				echo "<div class='icon-no-added-sugar'></div>";
			}
			if ( in_array( 'lactosevrij', $icons ) ) {
				echo "<div class='icon-lactose-free'></div>";
			}

			// Switch terug naar gebruikerstaal
			$sitepress->switch_lang( $prev_lang, true );
		echo '</div>';
	}

	// Aantal producten per pagina wijzigen
	add_filter( 'loop_shop_per_page', create_function( '$cols', 'return 50;' ), 20 );

	// Aantal gerelateerde producten wijzigen
	add_filter( 'woocommerce_output_related_products_args', 'alter_related_products_args', 20 );

	function alter_related_products_args( $args ) {
		$args['posts_per_page'] = 4;
		$args['columns'] = 4;
		return $args;
	}



	#############
	#  CONTENT  #
	#############

	add_filter( 'excerpt_more', 'wpdocs_excerpt_more', 100 );
	function wpdocs_excerpt_more( $more ) {
		return ' ...';
	}

	// Werkt per woord!
	add_filter( 'excerpt_length', 'wpdocs_custom_excerpt_length', 1000 );
	function wpdocs_custom_excerpt_length( $length ) {
		return 50;
	}

	// Forceer snellere RSS-updates
	add_filter( 'wp_feed_cache_transient_lifetime', create_function( '', 'return 3600;' ) );

	// Toon in de 'Deadlines' en 'Voorraadnieuws'-feed ook het (enige) private bericht
	add_action( 'pre_get_posts', 'show_private_posts_in_rss_feeds' );

	function show_private_posts_in_rss_feeds( $query ) {
		if ( is_feed() ) {
			if ( $query->is_category('voorraadnieuws') or $query->is_category('deadlines') or $query->is_category('nouvelles-de-stock') or $query->is_category('delais') or $query->is_category('stock-changes') or $query->is_category('deadlines-en') ) {
				$query->set( 'post_status', array( 'publish', 'private' ) );
			}
		}
		return $query;
	}

	// Wijzig het formaat van de korte RSS-feed
	add_filter( 'the_excerpt_rss', 'alter_rss_feed_excerpt' );

	function alter_rss_feed_excerpt( $feed_type = null ) {
		global $more, $post;
		$more_restore = $more;
		if ( !$feed_type ) {
			$feed_type = get_default_feed();
		}
		// Reset aantal woorden zodat de inleiding na <!--more--> afgeknipt wordt, net zoals op de site
		$more = 0;
		// Voeg geen 'lees verder'-link meer toe onder tekst door lege string als argument mee te geven
		$content = apply_filters( 'the_content', get_the_content('') );
		$more = $more_restore;
		
		// Sta slechts bepaalde HTML-tags toe
		$allowed_tags = '<h4>,<p>,<em>,<b>,<strong>,<u>,<a>,<br>,<ul>,<ol>,<li>,<table>,<tr>,<th>,<td>';
		$content = strip_tags( $content, $allowed_tags );
		
		// Verwijder de linebreaks vooraleer we betrouwbare preg_match kunnen doen (dubbele quotes verplicht!)
		// $content = str_replace( array( "\r", "\n", "\t" ), "", $content );
		
		// Voeg extra witruimte toe en zorg ervoor dat tabellen ook in oude versies van Outlook de huidige typografie van de B2B-nieuwsbrief overnemen
		$content = str_replace( array(
			'<h4>',
			'<p>',
			'<th class="column-1">',
			'<th class="column-2">',
			'<th class="column-3">',
			'<td class="column-1">',
			'<td class="column-2">',
			'<td class="column-3">'
		), array(
			'<br><h4>',
			'<br><p>',
			'<th class="column-1" style="color: #202020; font-family: Helvetica; font-size: 13px; line-height: 125%; text-align: left; width: 28%;">',
			'<th class="column-2" style="color: #202020; font-family: Helvetica; font-size: 13px; line-height: 125%; text-align: left; width: 28%;">',
			'<th class="column-3" style="color: #202020; font-family: Helvetica; font-size: 13px; line-height: 125%; text-align: left; width: 44%;">',
			'<td class="column-1" style="color: #202020; font-family: Helvetica; font-size: 13px; line-height: 125%; text-align: left; width: 28%;">',
			'<td class="column-2" style="color: #202020; font-family: Helvetica; font-size: 13px; line-height: 125%; text-align: left; width: 28%;">',
			'<td class="column-3" style="color: #202020; font-family: Helvetica; font-size: 13px; line-height: 125%; text-align: left; width: 44%;">'
		), $content );
		
		$image = '';
		if ( has_post_thumbnail( $post->ID ) ) {
			$image = '<a href="'.get_permalink($post->ID).'" target="_blank">'.get_the_post_thumbnail( $post->ID, 'shop_single' ).'</a><br>&nbsp;<br>';
		}

		return $image.$content;
	}

	// Custom shortcode om titel en URL van (onzichtbare) producten op te halen
	add_shortcode( 'newsletter', 'get_product_for_newsletter' );

	function get_product_for_newsletter( $atts ) {
		// Overschrijf defaults met expliciete data van de gebruiker
		$atts = shortcode_atts( array( 'sku' => '' ), $atts );
		
		$args = array(
			'post_type'	=> 'product',
			// Ook verborgen / toekomstige / verwijderde producten opnemen!
			'post_status' => array( 'publish', 'private', 'draft', 'future', 'trash' ),
			'posts_per_page' => 1,
			'meta_key' => '_sku',
			'meta_value' => $atts['sku'],
			'meta_compare' => '=',
		);
		$result = new WP_Query( $args );

		$output = '';
		if ( $result->have_posts() ) {
			$result->the_post();
			$output = str_replace( 'NIET ZICHTBAAR: ', '', get_the_title() );
			
			// Zet ompakhoeveelheid tussen haakjes achteraan
			// if ( intval( get_post_meta( get_the_ID(), '_multiple', true ) ) > 1 ) {
			// 	$output .= ' (x'.get_post_meta( get_the_ID(), '_multiple', true ).')';
			// }

			if ( get_post_status() === 'publish' ) {
				$output = '<a href="'.get_permalink().'">'.$output.'</a>';
			} else {
				$terms = wp_get_post_terms( get_the_ID(), 'pa_merk' );
				if ( count($terms) > 0 ) {
					$brand = $terms[0];
					if ( $brand->name !== 'Oxfam Fair Trade' and $brand->name !== 'Maya' ) {
						$output .= ' ('.$brand->name.')';
					}
				}
			}
			wp_reset_postdata();
		}

		return $atts['sku'].' '.$output;
	}

	// Definieer extra element met post data voor grids
	add_filter( 'vc_grid_item_shortcodes', 'add_grid_shortcodes_to_wpbakery' );
	function add_grid_shortcodes_to_wpbakery( $shortcodes ) {
		$shortcodes['list_post_date_categories'] = array(
			'name' => 'Datum en categorie',
			'base' => 'list_post_date_categories',
			'category' => 'Post',
			'description' => __( 'Toon de datum en eventuele categorieën van de post.', 'oft-admin' ),
			'post_type' => Vc_Grid_Item_Editor::postType(),
			'params' => array(
				array(
					'type' => 'textfield',
					'heading' => 'Extra class name',
					'param_name' => 'el_class',
					'description' => 'Style particular content element differently - add a class name and refer to it in custom CSS.',
				),
			),
		);
		return $shortcodes;
	}

	// Haal extra data op die hier beschikbaar is op basis van global $post!
	add_filter( 'vc_gitem_template_attribute_post_date_categories', 'vc_gitem_template_attribute_post_date_categories', 10, 2 );
	function vc_gitem_template_attribute_post_date_categories( $value, $data ) {
		extract( array_merge( array(
			'post' => null,
			'data' => '',
		), $data ) );
		return __( 'Gepubliceerd:', 'oft' ).' '.get_the_date( 'd/m/Y' ).'<br>'.__( 'Categorie:', 'oft' ).' '.get_the_category_list( ', ' );
	}

	// Output
	add_shortcode( 'list_post_date_categories', 'vc_list_post_date_categories' );
	function vc_list_post_date_categories() {
		return '<p class="oft-grid-post-date-tags">{{ post_data:post_date_categories }}</p>';
	}

	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_single_product_summary', 'output_full_product_description', 20 );
	add_action( 'woocommerce_before_shop_loop', 'output_oft_partner_info', 10 );

	function output_full_product_description() {
		global $product;
		$categories = $product->get_category_ids();
		if ( is_array( $categories ) ) {
			foreach ( $categories as $category_id ) {
				$category = get_term( $category_id, 'product_cat' );
				while ( intval($category->parent) !== 0 ) {
					$parent = get_term( $category->parent, 'product_cat' );
					$category = $parent;
				}
			}
		}
		echo '<div class="woocommerce-product-details__short-description">';
			if ( $parent->slug === 'wijn' or $parent->slug === 'vin' or $parent->slug === 'wine' ) {
				// Korte 'Lekker bij' tonen
				the_excerpt();
			} else {
				// Lange productbeschrijving tonen
				the_content();
			}
		echo '</div>';
	}
	
	function output_oft_partner_info() {
		if ( is_tax('product_partner') ) {
			$term_id = get_queried_object()->term_id;
			$parent_term_id = absint( get_term( $term_id, 'product_partner' )->parent );
			if ( $parent_term_id > 0 ) {
				$parent_term = get_term( $parent_term_id, 'product_partner' );
				// Er is een parent dus het is een land of een partner
				$grandparent_term_id = absint( get_term( $parent_term_id, 'product_partner' )->parent );
				if ( $grandparent_term_id > 0 ) {
					// Er is opnieuw een parent dus de oorspronkelijke term is een partner
					$grandparent_term = get_term( $grandparent_term_id, 'product_partner' );
					
					if ( strlen(term_description()) > 10 ) {
						remove_filter( 'term_description', 'wpautop' );
						echo '<blockquote>&laquo; '.term_description().' &raquo;</blockquote>';
						echo '<p style="text-align: right;">'.single_term_title( '', false ).' &mdash; '.$parent_term->name.', '.$grandparent_term->name.'</p>';
						add_filter( 'term_description', 'wpautop' );
						$image_id = get_term_meta( get_queried_object()->term_id, 'partner_image_id', true );
						if ($image_id) {
							echo wp_get_attachment_image( $image_id, array(300,300), false, array( 'class' => 'partner-quote-icon' ) );
						}
					}

					echo '<p style="margin: 2em 0;">';
						_e( 'Deze boeren zijn voor ons geen leveranciers, het zijn partners. Dankzij jullie steun kunnen coöperaties uitgroeien tot bloeiende ondernemingen die hun fairtradeproducten wereldwijd verkopen.', 'oft' );
						$partner_node = get_term_meta( get_queried_object()->term_id, 'partner_node', true );
						if ( $partner_node > 0 ) {
							echo ' <a href="https://www.oxfamwereldwinkels.be/node/'.$partner_node.'" target="_blank">'.__( 'Lees meer over deze producent op oxfamwereldwinkels.be', 'oft' ).'.</a>';
						}
					echo '</p>';

					global $wp_query;
					$cnt = $wp_query->found_posts;
					if ( $cnt > 0 ) {
						echo '<h4 style="margin: 2em 0;">'.sprintf( _n( 'Momenteel verkopen wij één product van deze partner:', 'Momenteel verkopen wij %s producten van deze partner:', $cnt, 'oft' ), $cnt ).'</h4>';
					}
				} else {
					// Er is geen parent dus de oorspronkelijke term is een land
				}
			}
		}
	}



	############
	#  SEARCH  #
	############

	function define_placeholder_texts() {
		$titel_zoekresultaten = __( 'Zoekresultaten', 'oft' );
		$navigatie_vorige = __( 'Vorige', 'oft' );
		$navigatie_volgende = __( 'Volgende', 'oft' );
	}
	
	// Voeg ook het artikelnummer, de ingrediënten en de bovenliggende categorie toe aan de te indexeren content bij producten
	add_filter( 'relevanssi_content_to_index', 'add_extra_searchable_content', 10, 2 );

	function add_extra_searchable_content( $content, $post ) {
		global $relevanssi_variables;
		if ( get_post_type($post) === 'product' ) {
			$content .= get_post_meta( $post->ID, '_sku', true ).' ';
			$content .= get_post_meta( $post->ID, '_ingredients', true ).' ';
			$categories = get_the_terms( $post->ID, 'product_cat' );
			if ( is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					if ( ! empty( $category->parent ) ) {
						$parent = get_term( $category->parent, 'product_cat' );
						// Voer de synoniemen ook hierop door
						// $search = array_keys($relevanssi_variables['synonyms']);
						// $replace = array_values($relevanssi_variables['synonyms']);
						// $content .= str_ireplace($search, $replace, $parent->name).' ';
					}
				}
			}
		}
		return $content;
	}

	// Verhoog het aantal producten per resultatenpagina en fix de zoekfunctie
	add_filter( 'relevanssi_modify_wp_query', 'modify_posts_per_page', 10, 1 );

	function modify_posts_per_page( $wp_query ) {
		$wp_query->query_vars['posts_per_page'] = 25;
		return $wp_query;
	}

	// Enkel PDF-attachments indexeren
	add_filter( 'relevanssi_do_not_index', 'rlv_only_pdfs', 10, 2 );
	
	function rlv_only_pdfs( $block, $post_id ) {
		$mime = get_post_mime_type($post_id);
		if ( ! empty($mime) ) {
			$block = true;
			if ( substr( $mime, -3, 3 ) === 'pdf' ) {
				$block = false;
			}
		}
		return $block;
	}



	###########
	#  VARIA  #
	###########

	// Creëer een productfiche
	function create_product_pdf( $product_id, $language ) {
		global $sitepress;
		require_once WP_PLUGIN_DIR.'/html2pdf/autoload.php';
		$templatelocatie = get_stylesheet_directory().'/assets/fiche-'.$language.'.html';
		$main_product_id = apply_filters( 'wpml_object_id', $product_id, 'product', false, 'nl' );
		$prev_lang = $sitepress->get_current_language();

		// Switch eerst naar Nederlands voor het vergelijken van taalgevoelige slugs
		$sitepress->switch_lang( apply_filters( 'wpml_default_language', NULL ) );
		$icons = array();
		foreach ( wp_get_object_terms( $main_product_id, 'product_hipster' ) as $term ) {
			$icons[] = $term->slug;
		}
		$icons_text = '';
		if ( in_array( 'veganistisch', $icons ) ) {
			$icons_text .= '<img src="'.get_stylesheet_directory_uri().'/assets/icon-vegan.png" style="width: 60px; margin-left: -12px; margin-bottom: -12px;">';
		}
		if ( in_array( 'glutenvrij', $icons ) ) {
			$icons_text .= '<img src="'.get_stylesheet_directory_uri().'/assets/icon-gluten-free.png" style="width: 60px; margin-left: -12px; margin-bottom: -12px;">';
		}
		if ( in_array( 'zonder-toegevoegde-suiker', $icons ) ) {
			$icons_text .= '<img src="'.get_stylesheet_directory_uri().'/assets/icon-no-added-sugar.png" style="width: 60px; margin-left: -12px; margin-bottom: -12px;">';
		}
		if ( in_array( 'lactosevrij', $icons ) ) {
			$icons_text .= '<img src="'.get_stylesheet_directory_uri().'/assets/icon-lactose-free.png" style="width: 60px; margin-left: -12px; margin-bottom: -12px;">';
		}
		
		// Switch nu naar de gevraagde fichetaal
		$sitepress->switch_lang($language);
		$lang_details = $sitepress->get_language_details($language);
		unload_textdomain( 'oft' );
		load_textdomain( 'oft', WP_CONTENT_DIR.'/languages/themes/oft-'.$lang_details['default_locale'].'.mo' );

		// Creëer product in lokale taal (false = negeer indien het nog niet bestaat)
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, $language ) );

		$templatefile = fopen( $templatelocatie, 'r' );
		$templatecontent = fread( $templatefile, filesize($templatelocatie) );
		$sku = $product->get_sku();

		if ( $product->get_status() === 'publish' ) {
			$permalink = '<a href="'.$product->get_permalink().'">('.__( 'bekijk product online', 'oft' ).')</a>';
		} else {
			$permalink = ' ';
		}

		$origin_text = '';
		if ( $partners = get_partner_terms_by_product($product) ) {
			$origin_text .= __( 'Partners:', 'oft' ).' '.strip_tags( implode( ', ', $partners ) );
		} elseif ( $countries = get_country_terms_by_product($product) ) {
			$origin_text .= __( 'Herkomst:', 'oft' ).' '.implode( ', ', $countries );
		}

		$ingredients_text = '';
		if ( get_ingredients($product) !== false ) {
			$ingredients_text = '<p style="font-size: 10pt;">';
			// Vraag label op mét dubbele punt
			$ingredients = get_ingredients( $product, true );
			$ingredients_text .= $ingredients['label'].' '.$ingredients['value'];
			$ingredients_text .= '</p>';
		}

		$ingredients_legend = '';
		if ( count( get_ingredients_legend($product) ) > 0 ) {
			$ingredients_legend = '<p style="font-size: 8pt; text-align: right; margin-top: 0;">';
			$ingredients_legend .= implode( '<br>', get_ingredients_legend($product) );
			$ingredients_legend .= '</p>';
		}

		$allergens_text = '';
		$allergens = get_allergens($product);
		if ( $allergens['contains'] !== false or $allergens['may-contain'] !== false ) {
			if ( is_array( $allergens['contains'] ) ) {
				// Laatste komma vervangen door voegwoord
				$allergens_text .= __( 'Bevat', 'oft' ).' '.str_lreplace( ', ', ' '.__( 'en', 'oft' ).' ', implode( ', ', $allergens['contains'] ) ).'. ';
			}
			if ( is_array( $allergens['may-contain'] ) ) {
				$allergens_text .= __( 'Kan sporen bevatten van', 'oft' ).' '.str_lreplace( ', ', ' '.__( 'en', 'oft' ).' ', implode( ', ', $allergens['may-contain'] ) ).'.';
			}
		} elseif ( $allergens['none'] === true ) {
			$allergens_text = __( 'Geen meldingsplichtige allergenen aanwezig.', 'oft' );
		} else {
			$allergens_text = '/';
		}

		$packaging = get_the_terms( $product->get_id(), 'product_packaging' );
		$cu_packaging_text = '/';
		$steh_packaging_text = '/';
		if ( is_array($packaging) ) {
			$cu_term = get_term_by( 'slug', 'cu', 'product_packaging' );
			$steh_term = get_term_by( 'slug', 'steh', 'product_packaging' );
			$cu = array();
			$steh = array();
			foreach ( $packaging as $term ) {
				if ( $term->parent == $cu_term->term_id ) {
					// GEEN MB_STRTOLOWER() TOEPASSEN, GEEFT PROBLEMEN MET ACCENTEN
					$cu[] = ucfirst($term->name);
				} elseif ( $term->parent == $steh_term->term_id ) {
					$steh[] = ucfirst($term->name);
				}
			}
			if ( count($cu) > 0 ) {
				$cu_packaging_text = implode( ', ', $cu );
				if ( floatval( $product->get_meta('_empty_fee') ) > 0 ) {
					$cu_packaging_text .= ' ('.__( 'met statiegeld', 'oft' ).')';
				}
			}
			if ( count($steh) > 0 ) {
				$steh_packaging_text = implode( ', ', $steh );
				if ( floatval( $product->get_meta('_empty_fee') ) > 0 ) {
					$steh_packaging_text .= ' ('.__( 'met statiegeld', 'oft' ).')';
				}
			}
		}

		$storages = get_the_terms( $product->get_id(), 'product_storage' );
		$storage_text = '/';
		if ( is_array($storages) ) {
			foreach ( $storages as $term ) {
				$store[] = ucfirst($term->name);
			}
			$storage_text = implode( '. ', $store ).'.';
		}
		
		$labels = array();
		$bio = strtolower( $product->get_attribute('pa_bio') );
		if ( $bio === 'ja' or $bio === 'oui' or $bio === 'yes' ) {
			$labels[] = wc_attribute_label('pa_bio');
		}
		$ft = strtolower( $product->get_attribute('pa_fairtrade') );
		if ( $ft === 'ja' or $ft === 'oui' or $ft === 'yes' ) {
			$labels[] = wc_attribute_label('pa_fairtrade');
		}

		// Nu pas labeltekst opmaken zodat we zeker weer in de fichetaal werken
		if ( count($labels) > 0 ) {
			$labels_text = format_pdf_block( __( 'Labels', 'oft' ), implode( ', ', $labels ) );
		} else {
			$labels_text = '';
		}

		// Thumbnail 'large' is bij oude vertaalde afbeeldingen nog niet geregistreerd, dus gebruik de ID van het Nederlandstalige product
		$images = wp_get_attachment_image_src( get_post_meta( $main_product_id, '_thumbnail_id', true ), 'large' );
		if ( $images !== false ) {
			$image_url = '<img src="'.$images[0].'" style="width: 100%;">';
		} else {
			$image_url = '';
		}

		// Let op met fatale error bij het proberen aanmaken van een ongeldige barcode!
		if ( check_digit_ean13( $product->get_meta('_cu_ean') ) ) {
			$cu_ean = format_pdf_ean13( $product->get_meta('_cu_ean') );
		} else {
			$cu_ean = '/';
		}

		if ( strlen( $product->get_meta('_steh_ean') ) >= 8 ) {
			if ( check_digit_ean13( $product->get_meta('_steh_ean') ) ) {
				$steh_ean = format_pdf_ean13( $product->get_meta('_steh_ean') );
			} else {
				// Ompaknummers kunnen ook 14 cijfers bevatten!
				$steh_ean = $product->get_meta('_steh_ean');
			}
		} else {
			$steh_ean = '/';
		}

		if ( intval( $product->get_meta('_shelf_life') ) > 0 ) {
			$shelf_text = format_pdf_block( __( 'Houdbaarheid na productie', 'oft' ), $product->get_meta('_shelf_life').' '.__( 'dagen', 'oft' ) );
		} else {
			$shelf_text = '';
		}

		$steh_dimensions = array(
			'length' => $product->get_meta('_steh_length'),
			'width' => $product->get_meta('_steh_width'),
			'height' => $product->get_meta('_steh_height'),
		);

		$multiple = intval( $product->get_meta('_multiple') );
		$number_of_layers = intval( $product->get_meta('_pal_number_of_layers') );
		$number_per_layer = intval( $product->get_meta('_pal_number_per_layer') );
		$subtotal = $number_of_layers * $number_per_layer;
		$total = $multiple * $subtotal;

		$templatecontent = str_replace( "###BRAND###", $product->get_attribute('pa_merk'), $templatecontent );
		$templatecontent = str_replace( "###LOGO###", sanitize_title( $product->get_attribute('pa_merk'), 'oxfam-fair-trade' ), $templatecontent );
		$templatecontent = str_replace( "###PERMALINK###", $permalink, $templatecontent );
		$templatecontent = str_replace( "###NAME###", $product->get_name(), $templatecontent );
		$templatecontent = str_replace( "###IMAGE_URL###", $image_url, $templatecontent );
		
		// Toon in principe de lange beschrijving
		$product_text = $product->get_description();
		// Maar check of we de tekst in combinatie met de ingrediëntenlijst niet te lang is!
		if ( strlen($product_text) + strlen($ingredients_text) + 2*strlen($ingredients_legend) + strlen($origin_text) > 800 ) {
			// Check of de korte beschrijving wel inhoud bevat
			if ( strlen( $product->get_short_description() ) > 10 ) {
				$product_text = $product->get_short_description();
			}
		}
		// Verwijder eventueel de enters door HTML-tags
		// preg_replace( '/<[^>]+>/', ' ', $product_text );
		
		$templatecontent = str_replace( "###DESCRIPTION###", $product_text, $templatecontent );
		$templatecontent = str_replace( "###INGREDIENTS_OPTIONAL###", $ingredients_text, $templatecontent );
		$templatecontent = str_replace( "###LEGEND_OPTIONAL###", $ingredients_legend, $templatecontent );
		$templatecontent = str_replace( "###ORIGIN###", $origin_text, $templatecontent );
		$templatecontent = str_replace( "###FAIRTRADE_SHARE###", $product->get_meta('_fairtrade_share'), $templatecontent );
		
		$templatecontent = str_replace( "###ALLERGENS###", $allergens_text, $templatecontent );
		$templatecontent = str_replace( "###LABELS_OPTIONAL###", $labels_text, $templatecontent );
		$templatecontent = str_replace( "###SHOPPLUS###", preg_replace( '/[a-zA-Z]/', '', $product->get_meta('_shopplus_sku') ), $templatecontent );
		$templatecontent = str_replace( "###CU_PACKAGING###", $cu_packaging_text, $templatecontent );
		$templatecontent = str_replace( "###CU_DIMENSIONS###", wc_format_dimensions( $product->get_dimensions(false) ), $templatecontent );
		$templatecontent = str_replace( "###CU_EAN###", $cu_ean, $templatecontent );
		
		$templatecontent = str_replace( "###MULTIPLE###", $multiple, $templatecontent );
		$templatecontent = str_replace( "###SKU###", $sku, $templatecontent );
		$templatecontent = str_replace( "###STEH_PACKAGING###", $steh_packaging_text, $templatecontent );
		$templatecontent = str_replace( "###STEH_DIMENSIONS###", wc_format_dimensions($steh_dimensions), $templatecontent );
		$templatecontent = str_replace( "###STEH_EAN###", $steh_ean, $templatecontent );
		
		$templatecontent = str_replace( "###NET_CONTENT###", get_net_weight($product), $templatecontent );
		$templatecontent = str_replace( "###STORAGE_CONDITIONS###", $storage_text, $templatecontent );
		$templatecontent = str_replace( "###SHELF_LIFE_OPTIONAL###", $shelf_text, $templatecontent );
		$templatecontent = str_replace( "###NUMBER_OF_LAYERS###", $number_of_layers, $templatecontent );
		$templatecontent = str_replace( "###NUMBER_PER_LAYER###", $number_per_layer, $templatecontent );
		$templatecontent = str_replace( "###SUBTOTAL###", $subtotal, $templatecontent );
		$templatecontent = str_replace( "###TOTAL###", $total, $templatecontent );
		$templatecontent = str_replace( "###INTRASTAT###", $product->get_meta('_intrastat'), $templatecontent );
		$templatecontent = str_replace( "###ICONS###", $icons_text, $templatecontent );
		
		$templatecontent = str_replace( "###FOOTER###", sprintf( __( 'Aangemaakt %s', 'oft' ), date_i18n( 'Y-m-d @ G:i' ) ), $templatecontent );
		
		try {
			$pdffile = new Html2Pdf( 'P', 'A4', $language, true, 'UTF-8', array( 15, 5, 15, 5 ) );
			$pdffile->setDefaultFont('Arial');
			$pdffile->pdf->setAuthor('Oxfam Fair Trade cvba');
			$pdffile->pdf->setTitle( $product->get_name() );
			$pdffile->writeHTML($templatecontent);
			$pdffile->output( WP_CONTENT_DIR.'/sheets/'.$language.'/'.$sku.'.pdf', 'F' );
		} catch ( Html2PdfException $e ) {
			$formatter = new ExceptionFormatter($e);
			add_filter( 'redirect_post_location', 'add_html2pdf_notice_var', 99 );
			update_option( 'html2pdf_notice', $formatter->getHtmlMessage() );
		}

		$sitepress->switch_lang($prev_lang);
		$prev_lang_details = $sitepress->get_language_details($prev_lang);
		unload_textdomain( 'oft' );
		load_textdomain( 'oft', WP_CONTENT_DIR.'/languages/themes/oft-'.$prev_lang_details['default_locale'].'.mo' );
	}

	function format_pdf_block( $label, $value ) {
		return '<p style="font-size: 10pt;"><div style="font-weight: bold; text-decoration: underline; padding-bottom: 1mm;">'.$label.'</div>'.$value.'</p>';
	}

	function format_pdf_ean13( $code ) {
		return '<br><barcode dimension="1D" type="EAN13" value="'.$code.'" label="label" style="width: 80%; height: 10mm; font-size: 9pt;"></barcode>';
	}

	function add_html2pdf_notice_var( $location ) {
		remove_filter( 'redirect_post_location', 'add_html2pdf_notice_var', 99 );
		return add_query_arg( array( 'html2pdf' => 'error' ), $location );
	}

	function check_digit_ean13( $ean ) {
		if ( strlen( trim($ean) ) !== 13 ) {
			return false;
		} else {
			$chars = str_split( trim($ean) );
			$ints = array_map( 'intval', $chars );
			// Rekenregel toepassen op de 12 eerste cijfers
			$check_sum = $ints[0]+$ints[2]+$ints[4]+$ints[6]+$ints[8]+$ints[10] + 3*($ints[1]+$ints[3]+$ints[5]+$ints[7]+$ints[9]+$ints[11]);
			$check_digit = ( 10 - ($check_sum % 10) ) % 10;
			return ( $check_digit === $ints[12] );
		}
	}

	// Voeg berichten toe aan adminpagina's
	add_action( 'admin_notices', 'oxfam_admin_notices' );

	function oxfam_admin_notices() {
		global $pagenow;
		// $screen = get_current_screen();
		// var_dump($screen);

		// Pas op dat de 'Show plugins/themes notices to admin only'-optie van User Role Editor meldingen niet verbergt!
		if ( $pagenow === 'post-new.php' and ( isset( $_GET['post_type'] ) and $_GET['post_type'] === 'product' ) ) {
			if ( ! isset( $_GET['lang'] ) or ( isset( $_GET['lang'] ) and $_GET['lang'] === 'nl' ) ) {
				echo '<div class="notice notice-warning">';
					echo "<p>Alle belangrijke catmanvelden zijn aangeduid in het groen. Begin met het ompaknummer onder het tabblad 'Voorraad'. Vergeet het product na het <u>opslaan als concept</u> niet te vertalen naar het Frans en het Engels!</p>";
				echo '</div>';
			}
		}

		if ( isset( $_GET['html2pdf'] ) ) {
			echo '<div class="notice notice-error">';
				echo '<p>'.get_option( 'html2pdf_notice' ).'</p>';
			echo '</div>';
		}
	}

	// Wijzig het gebruikte datumformaat in widgets
	add_filter( '_filter_widget_time_format', 'get_belgian_date_format' );

	function get_belgian_date_format( $date_format ) {
		return 'l j F Y';
	}

	// Bekijken we een post in de hoofdtaal?
	function post_language_equals_site_language() {
		$default_language = apply_filters( 'wpml_default_language', NULL );
		$post_language = apply_filters( 'wpml_post_language_details', NULL );
		// Bij post-new.php is de taal nog niet ingesteld maar willen we de velden wel vrijgeven! 
		if ( empty($post_language['language_code']) or $post_language['language_code'] === $default_language ) {
			return true;
		} else {
			return false;
		}	
	}



	###############
	#  MAILCHIMP  #
	###############

	// Controleer of het e-mailadres niet (ooit) geabonneerd was
	add_filter( 'wpcf7_validate_email*', 'check_mailchimp_status', 10, 2 );

	function check_mailchimp_status( $result, $tag ) {
		if ( $tag->name === 'newsletter-email' ) {
			$response = get_status_in_mailchimp_list( $_POST['newsletter-email'] );
			if ( $response['response']['code'] == 200 ) {
				$body = json_decode($response['body']);
				if ( $body->status === "subscribed" ) {
					$result->invalidate( $tag, __( 'Dit e-mailadres is reeds ingeschreven!', 'oft' ) );
				} else {
					$result->invalidate( $tag, __( 'Dit e-mailadres was al eens ingeschreven!', 'oft' ) );
				}
			}
		}
		return $result;
	}

	// Overrule de is_email() validatie die geen rekening houdt met internationale domeinnamen
	add_filter( 'wpcf7_is_email', 'allow_idn_domains', 10, 2 );

	function allow_idn_domains( $result, $email ) {
		if ( $result === false ) {
			// Zorg ervoor dat de module php7.0-intl geactiveerd is!
			return is_email( substr( $email, 0, strpos( $email, '@' ) + 1 ).idn_to_ascii( substr( $email, strpos( $email, '@' ) + 1 ) ) );
		} else {
			return $result;
		}
	}

	// Voer de effectieve inschrijving uit indien de validatie hierboven geen problemen gaf
	add_filter( 'wpcf7_posted_data', 'handle_validation_errors', 20, 1 );
	
	function handle_validation_errors( $posted_data ) {
		global $sitepress;

		// Nieuwsbriefformulieren
		$mailchimp_forms = array( 1054, 6757, 6756 );
		if ( in_array( $posted_data['_wpcf7'], $mailchimp_forms ) ) {
			$posted_data['validation_error'] = __( 'Gelieve de fouten op te lossen.', 'oft' );
			$posted_data['newsletter-name'] = trim_and_uppercase_words( $posted_data['newsletter-name'] );
			$posted_data['newsletter-email'] = strtolower( trim($posted_data['newsletter-email']) );
			
			$status = get_status_in_mailchimp_list( $posted_data['newsletter-email'] );
						
			if ( $status['response']['code'] == 200 ) {
				$body = json_decode($status['body']);
				write_log("IEMAND DIE AL INGESCHREVEN WAS PROBEERT ZICH TE ABONNEREN OP DE NIEUWSBRIEF");

				if ( $body->status === "subscribed" ) {
					$timestamp = strtotime($body->timestamp_opt);
					if ( $timestamp !== false ) {
						$signup_text = ' '.sprintf( __( 'sinds %s', 'oft' ), date_i18n( 'j F Y', $timestamp ) );
					} else {
						$signup_text = '';
					}
					$posted_data['validation_error'] = sprintf( __( 'Je bent%s reeds geabonneerd op onze nieuwsbrief!', 'oft' ), $signup_text );
					// Patch de bestaande gegevens met eventuele aanvullingen
					$updated = update_user_in_mailchimp_list( $body->merge_fields, $posted_data['newsletter-email'], $posted_data['newsletter-name'] );
					if ( $updated['response']['code'] == 200 ) {
						$posted_data['validation_error'] .= ' '.__( 'We werkten je gegevens bij.', 'oft' );
					}
				} else {
					$posted_data['validation_error'] = sprintf( __( 'Je was vroeger al eens geabonneerd op onze nieuwsbrief!', 'oft' ), $signup_text );
					// Zet de gebruiker weer op 'subscribed' en patch de bestaande gegevens
					$updated = update_user_in_mailchimp_list( $body->merge_fields, $posted_data['newsletter-email'], $posted_data['newsletter-name'] );
					if ( $updated['response']['code'] == 200 ) {
						$posted_data['validation_error'] .= ' '.__( 'We heractiveerden je inschrijving.', 'oft' );
					} else {
						$language_details = $sitepress->get_language_details( $sitepress->get_current_language() );
						$posted_data['validation_error'] .= ' '.sprintf( __( 'We konden je inschrijving niet automatisch vernieuwen. <a href="%s" target="_blank">Gelieve dit algemene inschrijvingsformulier te gebruiken</a> en op de bevestigingslink te klikken.', 'oft' ), 'https://oxfamwereldwinkels.us3.list-manage.com/subscribe?u=d66c099224e521aa1d87da403&id='.MC_LIST_ID.'&EMAIL='.$posted_data['newsletter-email'].'&LANGUAGE='.$language_details['native_name'] );
					}
				}
			}
		}

		// Algemene veldcorrecties
		if ( isset( $posted_data['your-name'] ) ) {
			$posted_data['your-name'] = trim_and_uppercase_words( $posted_data['your-name'] );
		}
		if ( isset( $posted_data['your-first-name'] ) ) {
			$posted_data['your-first-name'] = trim_and_uppercase_words( $posted_data['your-first-name'] );
		}
		if ( isset( $posted_data['your-last-name'] ) ) {
			$posted_data['your-last-name'] = trim_and_uppercase_words( $posted_data['your-last-name'] );
		}
		if ( isset( $posted_data['your-email'] ) ) {
			$posted_data['your-email'] = strtolower( trim($posted_data['your-email']) );
		}
		if ( isset( $posted_data['your-company'] ) ) {
			$posted_data['your-company'] = trim_and_uppercase_words( $posted_data['your-company'] );
		}
		if ( isset( $posted_data['your-street'] ) ) {
			$posted_data['your-street'] = trim_and_uppercase_words( $posted_data['your-street'] );
		}
		if ( isset( $posted_data['your-city'] ) ) {
			$posted_data['your-city'] = trim_and_uppercase_words( $posted_data['your-city'] );
		}

		return $posted_data;
	}

	function trim_and_uppercase_words( $value ) {
		return implode( '-', array_map( 'ucwords', explode( '-', mb_strtolower( trim($value) ) ) ) );
	}

	// Filter om mail tegen te houden ondanks succesvolle validatie
	// add_filter( 'wpcf7_skip_mail', 'abort_mail_sending' );
	function abort_mail_sending( $wpcf7 ) {
		return true;
	}

	// BIJ HET AANROEPEN VAN DEZE FILTER ZIJN WE ZEKER DAT ALLES AL GEVALIDEERD IS
	add_filter( 'wpcf7_before_send_mail', 'handle_mailchimp_subscribe', 20, 1 );

	function handle_mailchimp_subscribe( $wpcf7 ) {
		$submission = WPCF7_Submission::get_instance();
		if ( $submission ) {
			$posted_data = $submission->get_posted_data();
		}
		
		// Nieuwsbriefformulieren
		$mailchimp_forms = array( 1054, 6757, 6756 );
		if ( in_array( $wpcf7->id(), $mailchimp_forms ) ) {
			if ( empty($posted_data) ) {
				return;
			}
			
			// $mail = $wpcf7->prop('mail');
			// $mail['subject'] = 'Dit is een alternatief onderwerp';
			// $wpcf7->set_properties( array( 'mail' => $mail ) );

			$msgs = $wpcf7->prop('messages');
			$msgs['mail_sent_ng'] = __( 'Er was een onbekend probleem met Contact Form 7. Probeer het later eens opnieuw.', 'oft' );
			
			$status = get_status_in_mailchimp_list( $posted_data['newsletter-email'] );
			
			if ( $status['response']['code'] !== 200 ) {
				$body = json_decode($status['body']);

				// Gebruiker was nog nooit ingeschreven, voer nieuwe inschrijving uit
				$subscription = subscribe_user_to_mailchimp_list( $posted_data['newsletter-email'], $posted_data['newsletter-name'] );
				
				if ( $subscription['response']['code'] == 200 ) {
					$body = json_decode($subscription['body']);
					if ( $body->status === "subscribed" ) {
						$msgs['mail_sent_ok'] = __( 'Bedankt, je bent vanaf nu geabonneerd op de nieuwsbrief van Oxfam Fair Trade!', 'oft' );
					}
				} else {
					$msgs['mail_sent_ok'] = __( 'Je inschrijving kon niet uitgevoerd worden op de MailChimp-servers. Probeer het later eens opnieuw.', 'oft' );
				}
			}

			$wpcf7->set_properties( array( 'messages' => $msgs ) );
		}
		
		return $wpcf7;
	}

	// Sta HTML-tags weer toe in resultaatboodschappen
	add_filter( 'wpcf7_display_message', 'decode_html_characters', 10, 2 );
	
	function decode_html_characters( $message, $status ) {
		return htmlspecialchars_decode($message);
	}

	function get_status_in_mailchimp_list( $email, $list_id = MC_LIST_ID ) {
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$hash = md5( strtolower( trim( $email ) ) );
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode( 'user:'.MC_APIKEY )
			)
		);
		$response = wp_remote_get( 'https://'.$server.'.api.mailchimp.com/3.0/lists/'.$list_id.'/members/'.$hash, $args );

		return $response;
	}

	function subscribe_user_to_mailchimp_list( $email, $name = '', $company = '', $list_id = MC_LIST_ID ) {
		global $sitepress;
		$language_details = $sitepress->get_language_details( $sitepress->get_current_language() );

		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		
		$merge_fields = split_full_name($name);
		$merge_fields['LANGUAGE'] = $language_details['native_name'];
		$merge_fields['SOURCE'] = 'OFT-site';
		if ( strlen($company) > 2 ) {
			$merge_fields['COMPANY'] = $company;
		}
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode( 'user:'.MC_APIKEY )
			),
			'body' => json_encode( array(
				'email_address' => $email,
				'status' => 'subscribed',
				'merge_fields' => $merge_fields,
			) ),
		);
		$response = wp_remote_post( 'https://'.$server.'.api.mailchimp.com/3.0/lists/'.$list_id.'/members', $args );

		return $response;
	}

	function update_user_in_mailchimp_list( $old_merge_fields, $email, $name = '', $company = '', $list_id = MC_LIST_ID ) {
		global $sitepress;
		$language_details = $sitepress->get_language_details( $sitepress->get_current_language() );
		
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$hash = md5( strtolower( trim( $email ) ) );
		
		$merge_fields = split_full_name($name);
		$merge_fields['LANGUAGE'] = $language_details['native_name'];
		if ( strlen($company) > 2 ) {
			$merge_fields['COMPANY'] = $company;
		}

		// Vergelijk met bestaande waardes? Update enkel niet-lege velden?
		// OPGELET: $old_merge_fields is een object, geen array!
		foreach ( $merge_fields as $key => $value ) {
			if ( $value === '' ) {
				unset($merge_fields[$key]);
			}
		}
		
		$args = array(
			'method' => 'PATCH',
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode( 'user:'.MC_APIKEY )
			),
			'body' => json_encode( array(
				'status' => 'subscribed',
				'merge_fields' => $merge_fields,
			) ),
		);
		$response = wp_remote_post( 'https://'.$server.'.api.mailchimp.com/3.0/lists/'.$list_id.'/members/'.$hash, $args );

		return $response;
	}

	// Splits naam die in één veld zit in voor- en familienaam
	function split_full_name( $name ) {
		$merge_fields = array();
		// Splits bij de 1ste spatie
		$parts = explode( ' ', $name, 2 );
		$fname = trim($parts[0]);
		if ( strlen($fname) > 2 ) {
			$merge_fields['FNAME'] = $fname;
		}
		if ( count($parts) > 1 ) {
			$lname = trim($parts[1]);
			if ( strlen($lname) > 2 ) {
				$merge_fields['LNAME'] = $lname;
			}
		}
		return $merge_fields;
	}

	// Toon link naar de laatste B2B-nieuwsbrief
	add_shortcode( 'latest_newsletter', 'get_latest_newsletter', 2 );

	function get_latest_newsletter() {
		global $sitepress;

		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		// Map met enkel de productnieuwsbrieven
		$folder_id_nl = 'd302e08412';
		$folder_id_fr = '2d4de81b52';
		$folder_id_en = $folder_id_nl;

		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode('user:'.MC_APIKEY)
			),
		);

		$response = wp_remote_get( 'https://'.$server.'.api.mailchimp.com/3.0/campaigns?status=sent&list_id='.MC_LIST_ID.'&folder_id='.$folder_id_{$sitepress->get_current_language()}.'&sort_field=send_time&sort_dir=DESC&count=1', $args );
		
		if ( $response['response']['code'] == 200 ) {
			$body = json_decode($response['body']);
			$campaign = reset($body->campaigns);
			$mailing = sprintf( __( 'Bekijk <a href="%s" target="_blank">de recentste nieuwsbrief</a>.', 'oft' ), $campaign->long_archive_url );
		}		

		return $mailing;
	}



	###############
	#  IMPORTING  #
	###############

	// Alles uit voorraad zetten zodat we ook plotse verdwijnen uit BestelWeb kunnen opmerken
	add_action( 'pmxi_before_xml_import', 'set_all_out_of_stock', 10, 1 );

	function set_all_out_of_stock( $import_id ) {
		update_option( 'oft_import_active', 'yes' );

		if ( $import_id == 14 ) {
			$args = array(
				'post_type'	=> 'product',
				'post_status' => array( 'publish', 'private', 'draft', 'trash' ),
				'posts_per_page' => -1,
			);

			// Lijkt te lukken in één batch
			$out_of_stocks = new WP_Query( $args );

			if ( $out_of_stocks->have_posts() ) {
				while ( $out_of_stocks->have_posts() ) {
					$out_of_stocks->the_post();
					$product = wc_get_product( get_the_ID() );
					$product->set_stock_status('outofstock');
					$product->save();
				}
				wp_reset_postdata();
			}
		}
		if ( $import_id == 14 or $import_id == 22 or $import_id == 33 ) {
			update_option( 'oft_erp_import_active', 'yes' );
		}
	}

	// Bereken - indien mogelijk - de eenheidsprijs tijdens de ERP-import
	add_action( 'pmxi_saved_post', 'update_unit_price', 10, 1 );

	function update_unit_price( $post_id, $price = false, $content = false, $unit = false, $from_database = true ) {
		global $sitepress;
		if ( get_option( 'oft_erp_import_active' ) === 'yes' ) {
			$product = wc_get_product( $post_id );
			if ( $product !== false ) {
				if ( $from_database === true ) {
					$price = $product->get_regular_price();
					$content = $product->get_meta('_net_content');
					$unit = $product->get_meta('_net_unit');
				}
				if ( ! empty( $price ) and ! empty( $content ) and ! empty( $unit ) ) {
					$unit_price = calculate_unit_price( $price, $content, $unit );
					// PROBLEEM: deze WC-functie voert eigenlijk een delete/create i.p.v. update uit, waardoor onze logs overspoeld worden
					// $product->update_meta_data( '_unit_price', number_format( $unit_price, 2, '.', '' ) );
					update_post_meta( $product->get_id(), '_unit_price', number_format( $unit_price, 2, '.', '' ) );
				} else {
					// Indien er een gegeven ontbreekt: verwijder sowieso de oude waarde
					$product->delete_meta_data( '_unit_price' );
				}
				$product->save();

				// Maak de productfiche aan indien gepubliceerd (dus in de praktijk enkel OFT-producten!) én foto aanwezig
				if ( 'publish' === get_post_status($post_id) ) {
					if ( intval( $product->get_image_id() ) > 0 ) {
						// Enkel in huidige taal van import aanmaken!
						create_product_pdf( $product->get_id(), $sitepress->get_current_language() );
					}
				}
			}
		}
	}

	function calculate_unit_price( $price, $content, $unit ) {
		$unit_price = floatval( str_replace( ',', '.', $price ) ) / floatval( $content );
		if ( $unit === 'g' ) {
			$unit_price *= 1000;
		} elseif ( $unit === 'cl' ) {
			$unit_price *= 100;
		}
		return $unit_price;
	}

	add_action( 'pmxi_after_xml_import', 'rename_import_file', 10, 1 );

	function rename_import_file( $import_id ) {
		delete_option( 'oft_import_active' );
		delete_option( 'oft_erp_import_active' );

		if ( $import_id == 10 ) {
			$old = WP_CONTENT_DIR."/pos-import.csv";
			$new = WP_CONTENT_DIR."/pos-import-".date_i18n('Y-m-d').".csv";
			rename( $old, $new );
		}

		if ( $import_id == 14 ) {
			// Trigger de Franstalige import, de CSV-file staat er nog
			$args = array(
				'timeout' => 180,
			);
			$response = wp_remote_get( site_url( '/wp-cron.php?import_id=22&action=trigger&import_key='.IMPORT_KEY ), $args );
		}

		if ( $import_id == 22 ) {
			// Trigger de Engelstalige import, de CSV-file staat er nog
			$args = array(
				'timeout' => 180,
			);
			$response = wp_remote_get( site_url( '/wp-cron.php?import_id=33&action=trigger&import_key='.IMPORT_KEY ), $args );
		}

		if ( $import_id == 33 ) {
			$old = WP_CONTENT_DIR."/B2CImport.csv";
			$new = WP_CONTENT_DIR."/erp-import-".date_i18n('Y-m-d').".csv";
			rename( $old, $new );
		}
	}

	// Retourneert een array term_id => name van landen waaruit dit product afkomstig is (en anders false)
	function get_country_terms_by_product( $product ) {
		// Producten worden door de import + checkboxlogica enkel aan de laagste hiërarchische term gelinkt, dus dit zijn per definitie landen of partners!
		$terms = get_the_terms( $product->get_id(), 'product_partner' );
		
		// Vraag de term-ID's van de continenten op
		$args = array( 'taxonomy' => 'product_partner', 'parent' => 0, 'hide_empty' => false, 'fields' => 'ids' );
		$continents = get_terms( $args );
		
		$countries = array();
		if ( is_array($terms) ) {
			foreach ( $terms as $term ) {
				if ( ! in_array( $term->parent, $continents, true ) ) {
					// De bovenliggende term is geen continent, dus het is een partner!
					$parent_term = get_term_by( 'id', $term->parent, 'product_partner' );
					// Voeg de naam van de BOVENLIGGENDE term (= land) toe aan het lijstje
					$countries[$parent_term->term_id] = $parent_term->name;
				} else {
					// In dit geval is het zeker een land (en sowieso geen continent, zie boven)
					$countries[$term->term_id] = $term->name;
				}
			}
			// Sorteer de landen alfabetisch maar bewaar de indices (parent_terms reeds automatisch ontdubbeld dankzij de unieke array_key = term_id)
			asort($countries);
		}

		if ( count($countries) < 1 ) {
			// Fallback indien geen herkomstinfo bekend
			$countries = false;
		}
		
		return $countries;
	}

	// Retourneert een array term_id => name (parent_name) van de partners die bijdragen aan het product (en anders false)
	function get_partner_terms_by_product( $product ) {
		// Producten worden door de import + checkboxlogica enkel aan de laagste hiërarchische term gelinkt, dus dit zijn per definitie landen of partners!
		$terms = get_the_terms( $product->get_id(), 'product_partner' );
		
		if ( is_array($terms) ) {
			// Vraag de term-ID's van de continenten op
			$args = array( 'taxonomy' => 'product_partner', 'parent' => 0, 'hide_empty' => false, 'fields' => 'ids' );
			$continents = get_terms( $args );
			
			$partners = array();
			foreach ( $terms as $term ) {
				if ( ! in_array( $term->parent, $continents, true ) ) {
					// De bovenliggende term is geen continent, dus het is een partner!
					$parent_term = get_term_by( 'id', $term->parent, 'product_partner' );
					// VERVANG EVENTUEEL DOOR 'node/'.get_term_meta( $term->term_id, 'partner_node', true )
					// OOK LINK OP COUNTRY_TERM TOEVOEGEN?
					$partners[$term->term_id] = '<a href="'.get_term_link( $term->term_id, 'product_partner' ).'">'.$term->name.'</a> ('.$parent_term->name.')';
				}
			}

			if ( count($partners) > 0 ) {
				// Sorteer de partners alfabetisch maar bewaar de indices
				asort($partners);
				return $partners;
			} else {
				// Geen partnerinfo bekend
				return false;
			}
		} else {
			// Geen herkomstinfo bekend
			return false;
		}
	}

	// Retourneert een array term_id => name van de druiven in de wijn (en anders false)
	function get_grape_terms_by_product( $product ) {
		$terms = get_the_terms( $product->get_id(), 'product_grape' );
		
		if ( is_array($terms) ) {
			$grapes = array();
			foreach ( $terms as $term ) {
				$grapes[$term->term_id] = $term->name;
			}
			asort($grapes);
		} else {
			$grapes = false;
		}

		return $grapes;
	}



	############
	#  WP API  #
	############

	// Activeer de WP API
	add_action( 'wp_enqueue_scripts', 'load_extra_scripts' );

	function load_extra_scripts() {
		wp_enqueue_script( 'wp-api' );
	}

	// Verhinder het lekken van gegevens via de WP API NIET DOEN, BLOKKEERT DE WERKING VAN CF7
	// add_filter( 'rest_authentication_errors', 'only_allow_administrator_rest_access' );

	function only_allow_administrator_rest_access( $access ) {
		if( ! is_user_logged_in() or ! current_user_can( 'update_core' ) ) {
			return new WP_Error( 'rest_cannot_access', 'Access prohibited!', array( 'status' => rest_authorization_required_code() ) );
		}
		return $access;
	}

	// Voeg custom postmetadata toe aan de WP API door de metadata algemeen te registreren
	$api_args = array(
		'type' => 'integer',
		'description' => 'Artikelnummer waarover het bericht gaat.',
		'single' => true,
		'show_in_rest' => true,
	);
	register_meta( 'post', 'oft_post_product', $api_args );

	// Voeg custom producttaxonomieën toe aan de WC API
	add_filter( 'woocommerce_rest_prepare_product_object', 'add_custom_taxonomies_to_response', 10, 3 );

	function add_custom_taxonomies_to_response( $response, $object, $request ) {
		if ( empty( $response->data ) ) {
			return $response;
		}
		
		$custom_taxonomies = array( 'product_allergen', 'product_grape', 'product_taste', 'product_recipe' );
		foreach ( $custom_taxonomies as $taxonomy ) {
			foreach ( wp_get_object_terms( $object->id, $taxonomy ) as $term ) {
				$terms[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
			$response->data[$taxonomy] = $terms;
			unset($terms);
		}

		return $response;
	}

	function oxfam_photos_callback() {
		include get_stylesheet_directory().'/register-bulk-images.php';
	}
	
	// Registreer de AJAX-acties
	add_action( 'wp_ajax_oxfam_photo_action', 'oxfam_photo_action_callback' );
	
	function oxfam_photo_action_callback() {
		echo register_photo( $_POST['name'], $_POST['timestamp'], $_POST['path'] );
		wp_die();
	}

	function wp_get_attachment_id_by_post_name( $post_title ) {
		$args = array(
			// We gaan ervan uit dat ons proces waterdicht is en er dus maar één foto met dezelfde titel kan bestaan
			'posts_per_page'	=> 1,
			'post_type'			=> 'attachment',
			// Moet er in principe bij, want anders wordt de default 'publish' gebruikt en die bestaat niet voor attachments!
			'post_status'		=> 'inherit',
			// De titel is steeds gelijk aan de bestandsnaam en beter dan de 'name' die uniek moet zijn en door WP automatisch voorzien wordt van volgnummers
			'title'				=> trim($post_title),
		);
		$attachments = new WP_Query($args);
		if ( $attachments->have_posts() ) {
			$attachments->the_post();
			$attachment_id = get_the_ID();
			wp_reset_postdata();
		} else {
			$attachment_id = false;
		}
		return $attachment_id;
	}

	function register_photo( $filename, $filestamp, $filepath ) {			
		global $sitepress;
		$default_language = apply_filters( 'wpml_default_language', NULL );
		$sitepress->switch_lang($default_language);

		// Parse de fototitel
		$filetitle = explode( '.jpg', $filename );
		$filetitle = $filetitle[0];
		
		// Check of er al een vorige versie bestaat
		$updated = false;
		$deleted = false;
		$old_id= wp_get_attachment_id_by_post_name($filetitle);
		if ( $old_id ) {
			// Bewaar de post_parent van het originele attachment
			$product_id = wp_get_post_parent_id($old_id);
			
			// Stel het originele bestand veilig
			rename( $filepath, WP_CONTENT_DIR.'/uploads/temporary.jpg' );
			// Verwijder de versie
			if ( wp_delete_attachment( $old_id, true ) ) {
				// Extra check op het succesvol verwijderen
				$deleted = true;
			}
			$updated = true;
			// Hernoem opnieuw zodat de links weer naar de juiste file wijzen 
			rename( WP_CONTENT_DIR.'/uploads/temporary.jpg', $filepath );
		}
		
		// Creëer de parameters voor de foto
		$wp_filetype = wp_check_filetype( $filename, NULL );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => $filetitle,
			'post_content' => '',
			'post_author' => get_current_user_id(),
			'post_status' => 'inherit',
		);

		// Probeer de foto in de mediabibliotheek te stoppen
		$msg = "";
		$attachment_id = wp_insert_attachment( $attachment, $filepath );
		if ( ! is_wp_error( $attachment_id ) ) {
			// Check of de uploadlocatie ingegeven was, zo nee: zoek het product op o.b.v. SKU!
			if ( ! isset($product_id) or $product_id < 1 ) {
				$product_id = wc_get_product_id_by_sku( $filetitle );
			}

			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
			// Registreer ook de metadata en toon een succesboodschap
			wp_update_attachment_metadata( $attachment_id,  $attachment_data );

			if ( $product_id > 0 ) {
				// Voeg de nieuwe attachment-ID toe aan het bestaande product MOET IN ELKE TAAL EXPLICIET GEBEUREN
				$languages = apply_filters( 'wpml_active_languages', NULL );
				foreach ( $languages as $lang_code => $language ) {
					$sitepress->switch_lang($lang_code);
					$local_product_id = apply_filters( 'wpml_object_id', $product_id, 'product', false, $lang_code );
					$local_attachment_id = apply_filters( 'wpml_object_id', $attachment_id, 'attachment', false, $lang_code );
					if ( $local_product_id > 0 and $local_attachment_id > 0 ) {
						$product = wc_get_product($local_product_id);
						$product->set_image_id($local_attachment_id);
						$product->save();
					}
					$sitepress->switch_lang($default_language);
				}

				// Stel de uploadlocatie van de nieuwe afbeelding in WORDT DOOR WPML MEDIA SCRIPT NADIEN OOK INGESTELD IN ANDERE TALEN
				wp_update_post(
					array(
						'ID' => $attachment_id, 
						'post_parent' => $product_id,
					)
				);
			}

			if ( $updated ) {
				$deleted = $deleted ? "verwijderd en opnieuw aangemaakt" : "bijgewerkt";
				$msg .= "<i>".$filename."</i> ".$deleted." in de mediabibliotheek om ".date_i18n('H:i:s')." ...";
			} else {
				$msg .= "<i>".$filename."</i> aangemaakt in de mediabibliotheek om ".date_i18n('H:i:s')." ...";
			}
			// Sla het uploadtijdstip van de laatste succesvolle registratie op (kan gebruikt worden als limiet voor nieuwe foto's!)
			update_option( 'oft_timestamp_last_photo', $filestamp );
			$registered = true;
		} else {
			// Geef een waarschuwing als de aanmaak mislukte
			$msg .= "Opgelet, er liep iets mis met <i>".$filename."</i>!";
		}

		return $msg;
	}



	#############
	#  LOGGING  #
	#############

	// Schakel autosaves op producten uit
	add_action( 'admin_enqueue_scripts', 'disable_autosave' );
	
	function disable_autosave() {
		 if ( 'product' === get_post_type() ) {
			wp_deregister_script('autosave');
		 }
	}

	// Schakel productrevisies in
	add_filter( 'woocommerce_register_post_type_product', 'add_product_revisions' );
	
	function add_product_revisions( $vars ) {
		$vars['supports'][] = 'revisions';
		return $vars;
	}

	// Log wijzigingen aan taxonomieën
	add_action( 'set_object_terms', 'log_product_term_updates', 100, 6 );
	
	function log_product_term_updates( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		// Enkel wijzigingen in de hoofdtaal loggen
		if ( apply_filters( 'wpml_element_language_code', NULL, array( 'element_id'=> $object_id, 'element_type'=> 'product' ) ) !== apply_filters( 'wpml_default_language', NULL ) ) {
			return;
		}

		// Log de data niet indien er een import loopt (doet een volledige delete/create i.p.v. update)
		if ( get_option('oft_import_active') === 'yes' ) {
			return;
		}

		$watched_taxonomies = array(
			'product_cat',
			'product_tag',
			'product_partner',
			'product_allergen',
			'product_hipster',
			'product_grape',
			'product_taste',
			'product_recipe',
			'product_packaging',
			'product_storage',
			'pa_bio',
			'pa_merk',
			'pa_fairtrade',
		);

		if ( in_array( $taxonomy, $watched_taxonomies ) ) {
			
			$added_terms = array_diff( $tt_ids, $old_tt_ids );
			if ( count($added_terms) > 0 ) {
				foreach ( $added_terms as $term_id ) {
					$added_term = get_term_by( 'id', $term_id, $taxonomy );
					$product = wc_get_product($object_id);
					$user = wp_get_current_user();
					
					// Schrijf weg in log per weeknummer (zonder leading zero's)
					$str = date_i18n('d/m/Y H:i:s') . "\t" . $product->get_sku() . "\t" . $product->get_name() . "\t" . $user->user_firstname." (".$user->user_login.")" . "\t" . $taxonomy . "\t" . "TERM CREATED" . "\t" . $added_term->name . "\n";
					file_put_contents( WP_CONTENT_DIR."/changelog-week-".intval( date_i18n('W') ).".csv", $str, FILE_APPEND );
				}
			}
			
			$removed_terms = array_diff( $old_tt_ids, $tt_ids );
			if ( count($removed_terms) > 0 ) {
				foreach ( $removed_terms as $term_id ) {
					$removed_term = get_term_by( 'id', $term_id, $taxonomy );
					$product = wc_get_product($object_id);
					$user = wp_get_current_user();
					
					// Schrijf weg in log per weeknummer (zonder leading zero's)
					$str = date_i18n('d/m/Y H:i:s') . "\t" . $product->get_sku() . "\t" . $product->get_name() . "\t" . $user->user_firstname." (".$user->user_login.")" . "\t" . $taxonomy . "\t" . "TERM DELETED" . "\t" . $removed_term->name . "\n";
					file_put_contents( WP_CONTENT_DIR."/changelog-week-".intval( date_i18n('W') ).".csv", $str, FILE_APPEND );
				}
			}
		}
	}

	// Log wijzigingen aan metadata (en taxonomieën?)
	add_action( 'added_post_meta', 'hook_product_meta_adds', 100, 4 );
	add_action( 'updated_post_meta', 'hook_product_meta_updates', 100, 4 );
	add_action( 'deleted_post_meta', 'hook_product_meta_deletes', 100, 4 );

	function hook_product_meta_adds( $meta_id, $post_id, $meta_key, $new_meta_value ) {
		if ( get_post_type($post_id) === 'product' ) {
			log_product_meta_changes( $meta_id, $post_id, $meta_key, $new_meta_value, 'created' );
		}
	}

	function hook_product_meta_updates( $meta_id, $post_id, $meta_key, $new_meta_value ) {
		if ( get_post_type($post_id) === 'product' ) {
			log_product_meta_changes( $meta_id, $post_id, $meta_key, $new_meta_value, 'updated' );
		}
	}

	function hook_product_meta_deletes( $meta_id, $post_id, $meta_key ) {
		if ( get_post_type($post_id) === 'product' ) {
			log_product_meta_changes( $meta_id, $post_id, $meta_key, '', 'deleted' );
		}
	}

	function log_product_meta_changes( $meta_id, $post_id, $meta_key, $new_meta_value, $mode ) {
		// Enkel wijzigingen in de hoofdtaal loggen
		if ( apply_filters( 'wpml_element_language_code', NULL, array( 'element_id'=> $post_id, 'element_type'=> 'product' ) ) !== apply_filters( 'wpml_default_language', NULL ) ) {
			return;
		}

		// Log de data niet indien er een import loopt (doet een volledige delete/create i.p.v. update)
		if ( get_option('oft_import_active') === 'yes' ) {
			return;
		}

		$watched_metas = array(
			'_regular_price',
			'_sale_price',
			'_thumbnail_id',
			'_tax_class',
			'_weight',
			'_length',
			'_width',
			'_height',
			'_net_unit',
			'_net_content',
			'_unit_price',
			'_fairtrade_share',
			'_ingredients',
			'_promo_text',
			'_shopplus_sku',
			'_shelf_life',
			'_intrastat',
			'_cu_ean',
			'_steh_ean',
			'_multiple',
			'_empty_fee',
			'_pal_number_per_layer',
			'_pal_number_of_layers',
			'_steh_length',
			'_steh_width',
			'_steh_height',
			'_steh_weight',
			'_energy',
			'_fat',
			'_fasat',
			'_famscis',
			'_fapucis',
			'_choavl',
			'_sugar',
			'_polyl',
			'_starch',
			'_fibtg',
			'_pro',
			'_salteq',
			'_upsell_ids',
		);
		
		// Deze actie vuurt bij 'single value meta keys' enkel indien er een wezenlijke wijziging was, dus oude waarde vergelijken hoeft niet meer
		if ( in_array( $meta_key, $watched_metas ) ) {
			
			if ( ! $product = wc_get_product( $post_id ) ) {
				return;
			}

			$user = wp_get_current_user();
			if ( is_array($new_meta_value) ) {
				$new_meta_value = serialize($new_meta_value);
			}
			
			// Schrijf weg in log per weeknummer (zonder leading zero's)
			$str = date_i18n('d/m/Y H:i:s') . "\t" . $product->get_sku() . "\t" . $product->get_name() . "\t" . $user->user_firstname." (".$user->user_login.")" . "\t" . $meta_key . "\t" . mb_strtoupper($mode) . "\t" . $new_meta_value . "\n";
			file_put_contents( WP_CONTENT_DIR."/changelog-week-".intval( date_i18n('W') ).".csv", $str, FILE_APPEND );

		}
	}

	// Print variabelen op een overzichtelijke manier naar debug.log
	if ( ! function_exists( 'write_log' ) ) {
		function write_log ( $log )  {
			if ( true === WP_DEBUG ) {
				if ( is_array( $log ) || is_object( $log ) ) {
					error_log( print_r( $log, true ) );
				} else {
					error_log( $log );
				}
			}
		}
	}

	// Definieer overzichtelijkere debugfunctie
	function var_dump_pre( $variable ) {
		echo '<pre>';
		var_dump($variable);
		echo '</pre>';
		return null;
	}

	// Tab-delimited CSV omzetten in een paginabrede tabel, ongeacht het aantal kolommen
	function parse_csv_to_table( $handle ) {
		// Initialiseer
		$body = '';
		while ( ( $line = fgetcsv( $handle, 0, "\t" ) ) !== false ) {
			// Reset variabele
			$row = '';
			foreach ( $line as $column ) {
				$row .= '<td>'.$column.'</td>';
			}
			// Voeg vooraan de lijst toe!
			$body = '<tr>'.$row.'</tr>'.$body;
		}
		fclose($handle);
		return '<table style="width: 100%;">'.$body.'</table>';
	}

	// Check of een string eindigt op iets
	function ends_with( $haystack, $needle ) {
		return $needle === '' or ( ( $temp = strlen($haystack) - strlen($needle) ) >= 0 and strpos( $haystack, $needle, $temp ) !== false );
	}

	// Sorteer arrays in stijgende volgorde op basis van hun 'timestamp'-eigenschap  
	function sort_by_time( $a, $b ) {
		return $a['timestamp'] - $b['timestamp'];
	}

	// Laatste voorkomen van een substring vervangen
	function str_lreplace( $search, $replace, $subject ) {
		$pos = strrpos( $subject, $search );
		if ( $pos !== false ) {
			$subject = substr_replace( $subject, $replace, $pos, strlen($search) );
		}
		return $subject;
	}

?>