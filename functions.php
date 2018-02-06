<?php

	use Spipu\Html2Pdf\Html2Pdf;
    use Spipu\Html2Pdf\Exception\Html2PdfException;
    use Spipu\Html2Pdf\Exception\ExceptionFormatter;
	setlocale( LC_ALL, 'nl_NL' );
	
	if ( ! defined('ABSPATH') ) exit;

	// Laad het child theme na het hoofdthema
	add_action( 'wp_enqueue_scripts', 'load_child_theme', 999 );

	function load_child_theme() {
		// Zorgt ervoor dat de stylesheet van het child theme ZEKER NA alone.css ingeladen wordt
		wp_enqueue_style( 'oft', get_stylesheet_uri(), array(), '1.2.6' );
		// BOOTSTRAP REEDS INGELADEN DOOR ALONE
		// In de languages map van het child theme zal dit niet werken (checkt enkel nl_NL.mo) maar fallback is de algemene languages map (inclusief textdomain)
		load_child_theme_textdomain( 'alone', get_stylesheet_directory().'/languages' );
		load_child_theme_textdomain( 'oft', get_stylesheet_directory().'/languages' );
	}

	// Voeg custom styling toe aan de adminomgeving
	add_action( 'admin_enqueue_scripts', 'load_admin_css' );

	function load_admin_css() {
		wp_enqueue_style( 'oft-admin', get_stylesheet_directory_uri().'/admin.css' );
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
		add_submenu_page( 'woocommerce', 'Changelog', 'Changelog', 'manage_woocommerce', 'product-changelog', 'oxfam_product_changelog_callback' );
		add_media_page( __( 'Bulkregistratie', 'oft-admin' ), __( 'Bulkregistratie', 'oft-admin' ), 'update_core', 'oxfam-photos', 'oxfam_photos_callback' );
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
	// add_action( 'init', 'remove_storefront_actions', 20 );
	
	function remove_storefront_actions() {
		// Verwijder de productzoeker en de mini-cart in de header rechtsboven
		remove_action( 'storefront_header', 'storefront_product_search', 40 );
		remove_action( 'storefront_header', 'storefront_header_cart', 60 );

		// Laat de categorie- en auteurinfo vallen bij blogposts
		remove_action( 'storefront_loop_post', 'storefront_post_meta', 20 );
		remove_action( 'storefront_single_post', 'storefront_post_meta', 20 );

		// Verwijder alle buttons om te kopen
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		
		// Verwijder de default credits in de footer
		remove_action( 'storefront_footer', 'storefront_credit', 20 );
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
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Extra metadata definiëren en bewaren op partnertaxonomie
	// add_action( 'product_partner_add_form_fields', 'add_partner_node_field', 10, 2 );
	// add_action( 'created_product_partner', 'save_partner_node_meta', 10, 2 );
	add_action( 'product_partner_edit_form_fields', 'edit_partner_node_field', 10, 2 );
	add_action( 'edited_product_partner', 'update_partner_node_meta', 10, 2 );
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
		if ( ! empty($_POST['partner_node']) ) {
			add_term_meta( $term_id, 'partner_node', absint($_POST['partner_node']), true );
		}
		if ( ! empty($_POST['partner_type']) ) {
			add_term_meta( $term_id, 'partner_type', sanitize_text_field($_POST['partner_type']), true );
		}
		if ( ! empty($_POST['partner_image_id']) ) {
			add_term_meta( $term_id, 'partner_image_id', absint($_POST['partner_image_id']), true );
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

	function update_partner_node_meta( $term_id, $tt_id ) {
		if ( ! empty($_POST['partner_node']) ) {
			update_term_meta( $term_id, 'partner_node', absint($_POST['partner_node']) );
		} else {
			// VEROORZAAKT PROBLEMEN BIJ IMPORTS
			// delete_term_meta( $term_id, 'partner_node' );
		}
		if ( ! empty($_POST['partner_type']) ) {
			update_term_meta( $term_id, 'partner_type', sanitize_text_field($_POST['partner_type']) );
		} else {
			// delete_term_meta( $term_id, 'partner_type' );
		}
		if ( ! empty($_POST['partner_image_id']) ) {
			update_term_meta( $term_id, 'partner_image_id', absint($_POST['partner_image_id']) );
		} else {
			// delete_term_meta( $term_id, 'partner_image_id' );
		}
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
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'allergeen', 'with_front' => false, 'hierarchical' => false ),
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
			'show_admin_column' => false,
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
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
			if ( $parent->slug === 'wijn' ) {
				// Sommelierinfo uit korte beschrijving tonen
				$tabs['description']['title'] = __( 'Wijnbeschrijving', 'oft' );
			} else {
				// Schakel lange beschrijving uit (werd naar boven verplaatst)
				unset($tabs['description']);
			}
		}

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

		// Voeg tabje met allergenen toe
		$tabs['ingredients_info'] = array(
			'title' 	=> __( 'Ingrediënten', 'oft' ),
			'priority' 	=> 16,
			'callback' 	=> function() { output_tab_content('ingredients'); },
		);

		// Titel wijzigen van standaardtabs kan maar prioriteit niet! (description = 10, additional_information = 20)
		$tabs['additional_information']['title'] = __( 'Technische fiche', 'oft' );
		
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
					echo $en.' kJ (= '.number_format( $en*4.184, 0, ',', '' ).' kcal)' ?></td>
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

			foreach ( $product_metas as $meta_key => $meta_label ) {
				// Check of er een (nul)waarde ingesteld is
				if ( $product->get_meta($meta_key) !== false ) {
					?>
					<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
						<?php
							$submetas = array( '_fasat', '_famscis', '_fapucis', '_sugar', '_polyl', '_starch' );
							if ( in_array( $meta_key, $submetas ) ) {
								echo '<th class="secondary">'.$meta_label.'</th>';
							} else {
								echo '<th class="primary">'.$meta_label.'</th>';
							}
						?>
						<?php
							if ( in_array( $meta_key, $submetas ) ) {
								echo '<td class="secondary">'.$product->get_meta($meta_key).' g</td>';
							} else {
								echo '<td class="primary">'.$product->get_meta($meta_key).' g</td>';
							}
						?>
					</tr>
					<?php
				}
			}

			$product_attributes = array(
				// 'fairtrade' => 'Fairtradegelabeld',
			);

			foreach ( $product_attributes as $attribute_key => $attribute_label ) {
				?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
					<th><?php echo $attribute_label; ?></th>
					<td><?php echo $product->get_attribute($attribute_key); ?></td>
				</tr>
				<?php
			}

		} elseif ( $type === 'ingredients' ) {
			// Allergenentab altijd tonen!
			$has_row = true;
			// TIJDELIJK UITSCHAKELEN
			// $allergens = get_the_terms( $product->get_id(), 'product_allergen' );
			$allergens = false;
			$contains = array();
			$traces = array();
			// echo '<p>'.$product->get_short_description().'</p>';

			?>
			<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
				<th><?php _e( 'Fairtradepercentage', 'oft' ); ?></th>
				<td><?php echo $product->get_meta('_fairtrade_share').' %' ?></td>
			</tr>
			<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
				<th><?php _e( 'Inhoud', 'oft' ); ?></th>
				<td><?php echo $product->get_meta('_net_content').' '.$product->get_meta('_net_unit'); ?></td>
			</tr>
			<?php
			
			if ( $grapes = get_grape_terms_by_product($product) ) {
				$ingredients_th = __( 'Druivensoorten', 'oft' );
				$ingredients_td = implode( ', ', $grapes );
			} elseif ( ! empty( $product->get_meta('_ingredients') ) ) {
				$ingredients_th = __( 'Ingrediënten', 'oft' );
				$ingredients_td = $product->get_meta('_ingredients');
			} else {
				$ingredients_th = false;
			}
			
			if ( $ingredients_th !== false ) {
				?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
					<th><?php echo $ingredients_th; ?></th>
					<td><?php echo $ingredients_td; ?></td>
				</tr>
				<?php
			}

			if ( $allergens !== false ) {
				foreach ( $allergens as $allergen ) {
					if ( get_term_by( 'id', $allergen->parent, 'product_allergen' )->slug === 'contains' ) {
						$contains[] = $allergen;
					} elseif ( get_term_by( 'id', $allergen->parent, 'product_allergen' )->slug === 'may-contain' ) {
						$traces[] = $allergen;
					}
				}
				?>
				<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
					<th><?php _e( 'Dit product bevat', 'oft' ); ?></th>
					<td>
					<?php
						$i = 0;
						$str = __( 'geen meldingsplichtige allergenen', 'oft' );
						if ( count( $contains ) > 0 ) {
							foreach ( $contains as $substance ) {
								$i++;
								if ( $i === 1 ) {
									$str = $substance->name;
								} else {
									$str .= ', '.$substance->name;
								}
							}
						}
						echo $str;
					?>
					</td>
				</tr>

				<tr class="<?php if ( ( $alt = $alt * -1 ) == 1 ) echo 'alt'; ?>">
					<th><?php _e( 'Kan sporen bevatten van', 'oft' ); ?></th>
					<td>
					<?php
						$i = 0;
						$str = __( 'geen meldingsplichtige allergenen', 'oft' );
						if ( count( $traces ) > 0 ) {
							foreach ( $traces as $substance ) {
								$i++;
								if ( $i === 1 ) {
									$str = $substance->name;
								} else {
									$str .= ', '.$substance->name;
								}
							}
						}
						echo $str;
					?>
					</td>
				</tr>
				<?php
			}
		}
		
		echo '</table>';
		
		if ( $has_row ) {
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

	// Verhinder bepaalde selecties in de back-end AAN TE PASSEN NAAR DE NIEUWE ID'S
	add_action( 'admin_footer', 'disable_custom_checkboxes' );

	function disable_custom_checkboxes() {
		global $pagenow, $post_type;

		if ( ( $pagenow === 'post.php' or $pagenow === 'post-new.php' ) and $post_type === 'product' ) {
			$args = array(
				'fields' => 'ids',
				'hide_empty' => false,
				// Enkel de hoofdtermen selecteren!
				'parent' => 0,
			);

			$args['taxonomy'] = 'product_cat';
			$categories = get_terms($args);

			$args['taxonomy'] = 'product_partner';
			$continents = get_terms($args);

			$args['taxonomy'] = 'product_allergen';
			$types = get_terms($args);

			$args['taxonomy'] = 'product_grape';
			$grapes = get_terms($args);
			
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

					/* Artikelnummer enkel bewerkbaar in hoofdtaal! */
					<?php if ( ! post_language_equals_site_language() ) : ?>
						jQuery( '#inventory_product_data' ).find( 'input#_sku' ).prop( 'readonly', true );
					<?php endif; ?>
					
					/* Disable en verberg checkboxes hoofdcategorieën */
					<?php foreach ( $categories as $id ) : ?>
						jQuery( '#in-product_cat-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
					<?php endforeach; ?>
					
					/* Disable en verberg checkboxes continenten */
					<?php foreach ( $continents as $id ) : ?>
						jQuery( '#in-product_partner-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
					<?php endforeach; ?>

					/* Disable en verberg checkboxes allergeenklasses */
					<?php foreach ( $types as $id ) : ?>
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
					<?php endforeach; ?>

					/* Disable en verberg checkboxes rode en witte druiven */
					<?php foreach ( $grapes as $id ) : ?>
						jQuery( '#in-product_grape-<?php echo $id; ?>' ).prop( 'disabled', true ).css( 'display', 'none' );
					<?php endforeach; ?>
					
					/* Disable bovenliggende landen/continenten van alle aangevinkte partners/landen */
					jQuery( '#product_partner-all' ).find( 'input[type=checkbox]:checked' ).closest( 'ul.children' ).siblings( 'label.selectit' ).find( 'input[type=checkbox]' ).prop( 'disabled', true );

					/* Disable/enable het bovenliggende land bij aan/afvinken van een partner en reset de aanvinkstatus van de parent */
					jQuery( '#product_partner-all' ).find( 'input[type=checkbox]' ).on( 'change', function() {
						jQuery(this).closest( 'ul.children' ).siblings( 'label.selectit' ).find( 'input[type=checkbox]' ).prop( 'checked', false ).prop( 'disabled', jQuery(this).is(":checked") );
					});

					/* Disable/enable het overeenkomstige allergeen in contains/may-contain bij aan/afvinken van may-contain/contains */
					jQuery( '#product_allergen-all' ).find( 'input[type=checkbox]' ).on( 'change', function() {
						var changed_box = jQuery(this);
						var label = changed_box.closest( 'label.selectit' ).text();
						changed_box.closest( 'ul.children' ).closest( 'li' ).siblings().find( 'label.selectit' ).each( function() {
							if ( jQuery(this).text() == label ) {
								jQuery(this).find( 'input[type=checkbox]' ).prop( 'checked', false ).prop( 'disabled', changed_box.is(":checked") );
							}
						});
					});

					/* Disable/enable het overeenkomstige allergeen in contains/may-contain bij aan/afvinken van may-contain/contains */
					jQuery( '#product_cat-all' ).find( 'input[type=checkbox]' ).on( 'change', function() {
						jQuery(this).closest( '#product_catchecklist' ).find( 'input[type=checkbox]' ).not(this).prop( 'checked', false );
					});

					/* Vereis dat er één productcategorie en minstens één partner/land aangevinkt is voor het opslaan */
					jQuery( 'input[type=submit]#publish, input[type=submit]#save-post' ).click( function() {
						// ALLE DISABLED DROPDOWNS WEER ACTIVEREN, ANDERS GEEN WAARDE DOORGESTUURD
						jQuery( '#general_product_data' ).find( 'select#_tax_status' ).prop( 'disabled', false );
						jQuery( '#general_product_data' ).find( 'select#_tax_class' ).prop( 'disabled', false );
						jQuery( '#general_product_data' ).find( 'select#_net_unit' ).prop( 'disabled', false );
						jQuery( '#inventory_product_data' ).find( 'select#_stock_status' ).prop( 'disabled', false );
						jQuery( '#shipping_product_data' ).find( 'select#product_shipping_class' ).prop( 'disabled', false );

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
						/* UITSCHAKELEN BIJ WIJNTJES */
						if ( jQuery( '#general_product_data' ).find( 'textarea#_ingredients' ).val() == '' ) {
							pass = false;
							msg += '* Je moet de ingrediëntenlijst nog ingeven!\n';
						}

						if ( pass == false ) {
							// alert(msg);
						}

						return true;
					});

					/* Eventueel: checken of de som van alle secondaries de primary niet overschrijdt! */
					jQuery( '#quality_product_data' ).find( 'p.primary' ).change( function() {
						var max = jQuery(this).children( 'input' ).first().val();
						var sum = 0;
						jQuery(this).siblings( 'p.secondary' ).each( function() {
							sum += Number( jQuery(this).children( 'input' ).first().val() );
						});
						// alert(sum);
					});
				});
			</script>
			<?php
			
			$categories = isset( $_GET['post'] ) ? get_the_terms( $_GET['post'], 'product_cat' ) : false;
			if ( is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					while ( intval($category->parent) !== 0 ) {
						$parent = get_term( $category->parent, 'product_cat' );
						$category = $parent;
					}
				}
				if ( $parent->slug === 'wijn' ) {
					
					?>
					<script>
						jQuery(document).ready( function() {
							/* Vereis dat minstens één druif, gerecht en smaak aangevinkt is voor het opslaan */
							jQuery( 'input[type=submit]#publish, input[type=submit]#save-post' ).click( function() {
								var pass = true;
								var msg = 'Hold your horses, er zijn enkele issues:\n';
								if ( jQuery( '#product_grape-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
									pass = false;
									msg += '* Je moet de druivenrassen nog aanvinken!\n';
								}
								if ( jQuery( '#product_recipe-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
									pass = false;
									msg += '* Je moet de gerechten nog aanvinken!\n';
								}
								if ( jQuery( '#product_taste-all' ).find( 'input[type=checkbox]:checked' ).length == 0 ) {
									pass = false;
									msg += '* Je moet de smaken nog aanvinken!\n';
								}
								
								if ( pass == false ) {
									// alert(msg);
								}

								return true;
							});
						});
					</script>
					<?php

				}
			}
		}
	}

	// Toon metaboxes voor wijninfo enkel voor producten onder de hoofdcategorie 'Wijn'
	add_action( 'admin_init', 'hide_wine_taxonomies' );

	function hide_wine_taxonomies() {
		global $pagenow;
		$remove = true;
		if ( ( $pagenow === 'post.php' or $pagenow === 'post-new.php' ) and ( isset($_GET['post']) and get_post_type($_GET['post']) === 'product' ) ) {
			$categories =  get_the_terms( $_GET['post'], 'product_cat' );
			if ( is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					while ( intval($category->parent) !== 0 ) {
						$parent = get_term( $category->parent, 'product_cat' );
						$category = $parent;
					}
				}
				if ( $parent->slug === 'wijn' ) {
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

	// Creëer een custom hiërarchische taxonomie op producten om allergeneninfo in op te slaan
	add_action( 'init', 'register_hipster_taxonomy', 50 );

	function register_hipster_taxonomy() {
		$taxonomy_name = 'product_hipster';
		
		$labels = array(
			'name' => __( 'Hipstertermen', 'oft' ),
			'singular_name' => __( 'Hipsterterm', 'oft' ),
			'all_items' => __( 'Alle hipstertermen', 'oft' ),
			'parent_item' => __( 'Hipsterterm', 'oft' ),
			'parent_item_colon' => __( 'Hipsterterm:', 'oft' ),
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
			'hierarchical' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_rest' => true,
			'show_tagcloud' => true,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'query_var' => true,
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'eco', 'with_front' => false, 'hierarchical' => false ),
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
		$orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

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
			if ( $key === 'product_cat' ) {
				$new_columns['pa_merk'] = __( 'Merk', 'oft-admin' );
				// $new_columns['modified'] = __( 'Laatst bewerkt', 'oft-admin' );
				// Inhoud van deze kolom is al door WooCommerce gedefinieerd, dit zorgt er gewoon voor dat de kolom ook beschikbaar is indien de optie 'woocommerce_manage_stock' op 'no' staat
				$new_columns['is_in_stock'] = __( 'BestelWeb', 'oft-admin' );
			}
			if ( $key !== 'product_type' ) {
				$new_columns[$key] = $title;
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
				// Gebruik home_url( add_query_arg( 'pa_merk', $attribute->slug ) ) indien je de volledige huidige query-URL wil behouden
				echo '<a href="/wp-admin/edit.php?post_type=product&pa_merk='.$attribute->slug.'">'.$attribute->name.'</a>';
			} else {
				echo '<span aria-hidden="true">&#8212;</span>';
			}
		}
	}

	// Creëer extra merkenfilter bovenaan de productenlijst 
	add_action( 'restrict_manage_posts', 'add_filters_to_products' );

	function add_filters_to_products() {
		global $pagenow, $post_type;
		if ( $pagenow === 'edit.php' and $post_type === 'product' ) {
			// FILTER OP STOCK_STATUS WORDT OPGENOMEN IN CORE VANAF WC 3.3
			
			$args = array( 'taxonomy' => 'pa_merk', 'hide_empty' => false );
			$terms = get_terms( $args );
			$values_brand = array();
			foreach ( $terms as $term ) {
				$values_brand[$term->slug] = $term->name;
			}
			
			$current_brand = isset( $_REQUEST['pa_merk'] ) ? wc_clean( wp_unslash( $_REQUEST['pa_merk'] ) ) : false;
			echo '<select name="pa_merk">';
				echo '<option value="">'.__( 'Op merk filteren', 'oft-admin' ).'</option>';
				foreach ( $values_brand as $status => $label ) {
					echo '<option value="'.$status.'" '.selected( $status, $current_brand, false ).'>'.$label.'</option>';
				}
			echo '</select>';
		}
	}

	// Maak sorteren op custom kolommen mogelijk
	// add_filter( 'manage_edit-product_sortable_columns', 'make_attribute_columns_sortable', 10, 1 );

	function make_attribute_columns_sortable( $columns ) {
		$columns['pa_merk'] = 'pa_merk';
		$columns['is_in_stock'] = 'is_in_stock';
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

	// 1ste mogelijkheid om niet-OFT-producten te verbergen: extra filter in algemene query
	// add_action( 'woocommerce_product_query', 'filter_product_query_by_taxonomy' );

	function filter_product_query_by_taxonomy( $q ){	
		$oft_term = get_term_by( 'name', 'Oxfam Fair Trade', 'pa_merk' );
		$tax_query = (array) $q->get('tax_query');
		$tax_query[] = array(
			'taxonomy' => 'pa_merk',
			'field' => 'term_taxonomy_id',
			'terms' => $oft_term->term_id,
			'operator' => 'IN',
		);
		$q->set( 'tax_query', $tax_query );
	}

	// 2de mogelijkheid om niet-OFT-producten te verbergen: visbiliteit wijzigen INMIDDELS OOK GEÏNTEGREERD IN ERP-IMPORT
	add_action( 'save_post', 'change_product_visibility_on_save', 10, 3 );

	function change_product_visibility_on_save( $post_id, $post, $update ) {
		if ( $post->post_status === 'trash' or $post->post_status === 'draft' ) {
			return;
		}

		if ( $post->post_type !== 'product' or ! $product = wc_get_product( $post_id ) ) {
			return;
		}

		if ( $product->get_attribute('merk') !== 'Oxfam Fair Trade' ) {
			$product->set_status( 'private' );
			$product->save();
		}

		if ( get_option('oft_import_active') !== 'yes' ) {
			// Update de productfiches niet indien er een import bezig is (te langzaam)
			create_product_pdf( $product, 'nl' );
			create_product_pdf( $product, 'fr' );
			create_product_pdf( $product, 'en' );
		}
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
			} elseif( $product->get_meta('_net_unit') === 'g' ) {
				$suffix .= '/kg';
			}

			$category_ids = $product->get_category_ids();
			// In principe slechts één categorie geselecteerd bij ons, dus gewoon 1ste element nemen
			$category = get_term( $category_ids[0], 'product_cat' );
			if ( $category->slug === 'fruitsap' ) {
				woocommerce_wp_text_input(
					array( 
						'id' => '_empty_fee',
						'label' => __( 'Leeggoed (&euro;)', 'oft-admin' ),
						'data_type' => 'price',
					)
				);
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
					'min' => '25',
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
					'label' => __( 'Ingrediëntenlijst', 'oft-admin' ),
					'wrapper_class' => 'important-for-catman',
				)
			);

			// PAS INSCHAKELEN INDIEN WIJNKIEZER AANGESLOTEN
			// woocommerce_wp_textarea_input(
			// 	array( 
			// 		'id' => '_promo_text',
			// 		'label' => __( 'Actuele promotekst', 'oft-admin' ),
			// 		'desc_tip' => true,
			// 		'description' => __( 'Dit tekstje dient enkel om te tonen aan particulieren in de wijnkiezer en de webshops. Te combineren met de actieprijs en -periode hierboven.', 'oft-admin' ),
			// 	)
			// );

		echo '</div>';

		echo '<div class="options_group">';
			$languages = array( 'nl', 'fr', 'en' );
			foreach ( $languages as $language ) {
				$path = '/fiches/'.$language.'/'.$product->get_sku().'.pdf';
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

		$secondary = array(
			'wrapper_class' => 'secondary',
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

				woocommerce_wp_text_input( $args_energy );
			echo '</div>';
		
			echo '<div class="options_group oft">';
				woocommerce_wp_text_input( $fat + $one_decimal_args + $primary );
				woocommerce_wp_text_input( $fasat + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $famscis + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $fapucis + $one_decimal_args + $secondary );
			echo '</div>';
		
			echo '<div class="options_group oft">';
				woocommerce_wp_text_input( $choavl + $one_decimal_args + $primary );
				woocommerce_wp_text_input( $sugar + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $polyl + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $starch + $one_decimal_args + $secondary );
			echo '</div>';
		
			echo '<div class="options_group oft">';
				woocommerce_wp_text_input( $fibtg + $one_decimal_args );
				woocommerce_wp_text_input( $pro + $one_decimal_args );
				
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
					$args_energy['custom_attributes']['readonly'] = true;
				}

				woocommerce_wp_text_input( $args_salteq );
			echo '</div>';
		echo '</div>';
	}

	function save_oft_fields( $post_id ) {
		// Bereken - indien mogelijk - de eenheidsprijs a.d.h.v. alle data in $_POST
		// Laatste parameter: val expliciet niét terug op de (verouderde) databasewaarden!
		update_unit_price( $post_id, $_POST['_regular_price'], $_POST['_net_content'], $_POST['_net_unit'], false );
		
		write_log($_POST);

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
			if ( ! empty( $_POST[$meta_key] ) ) {
				if ( $meta_key === '_cu_ean' and ! check_digit_ean13( $_POST[$meta_key] ) ) {
					delete_post_meta( $post_id, $meta_key );
				} else {
					update_post_meta( $post_id, $meta_key, esc_attr( $_POST[$meta_key] ) );
				}
			} else {
				delete_post_meta( $post_id, $meta_key );
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
			// Geen !empty() gebruiken want we willen nullen expliciet kunnen opslaan!
			if ( $_POST[$meta_key] !== '' ) {
				update_post_meta( $post_id, $meta_key, esc_attr( number_format( floatval( str_replace( ',', '.', $_POST[$meta_key] ) ), 1, '.', '' ) ) );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}

		$price_meta_keys = array(
			'_empty_fee',
		);

		foreach ( $price_meta_keys as $meta_key ) {
			if ( ! empty( $_POST[$meta_key] ) ) {
				update_post_meta( $post_id, $meta_key, esc_attr( number_format( floatval( str_replace( ',', '.', $_POST[$meta_key] ) ), 2, '.', '' ) ) );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}

		$high_precision_meta_keys = array(
			'_weight',
			'_steh_weight',
			'_salteq',	
		);

		foreach ( $high_precision_meta_keys as $meta_key ) {
			// Geen !empty() gebruiken want we willen nullen expliciet kunnen opslaan!
			if ( $_POST[$meta_key] !== '' ) {
				update_post_meta( $post_id, $meta_key, esc_attr( number_format( floatval( str_replace( ',', '.', $_POST[$meta_key] ) ), 3, '.', '' ) ) );
			} else {
				delete_post_meta( $post_id, $meta_key );
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
			'post_status'		=> array( 'publish', 'draft' ),
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
				<label for="oft-post-product" class=""><?php printf( __( 'Selecteer 1 van de %d actuele en publiek te raadplegen producten waarover dit bericht gaat:', 'oft' ), count($list) ); ?></label>
				<select name="oft-post-product" id="oft-post-product">
					<option value="EMPTY"><?php _e( '(geen)', 'oft' ); ?></option>
					<?php foreach ( $list as $sku => $title ) : ?>
						<option value="<?php echo $sku; ?>" <?php if ( isset ( $prfx_stored_meta['oft-post-product'] ) ) selected( $prfx_stored_meta['oft-post-product'][0], $sku ); ?>><?php echo $sku.': '.$title; ?></option>';
					<?php endforeach; ?>
				</select>
			</p>
		<?php
	}

	function oft_post_to_product_save( $post_id ) {
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST[ 'oft_post_to_product_nonce' ] ) && wp_verify_nonce( $_POST[ 'oft_post_to_product_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
		
		if ( $is_autosave or $is_revision or ! $is_valid_nonce ) {
			return;
		}
	 
		if( isset( $_POST[ 'oft-post-product' ] ) ) {
			update_post_meta( $post_id, 'oft-post-product', sanitize_text_field( $_POST[ 'oft-post-product' ] ) );
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
						echo '<div class="oft-partners-th">'.wp_get_attachment_image( $quoted_term_image_id, array( '110', '110' ), false ).'</div>';
						echo '<div class="oft-partners-td">';
						echo '<p class="oft-partners-quote">'.trim($quoted_term->description).'</p>';
						$quoted_term_node = intval( get_term_meta( $quoted_term->term_id, 'partner_node', true ) );
						if ($quoted_term_node > 0 ) {
							$url = 'https://www.oxfamwereldwinkels.be/node/'.$quoted_term_node;
							$handle = curl_init($url);
							curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
							$response = curl_exec($handle);
							$code = curl_getinfo( $handle, CURLINFO_HTTP_CODE );
							if ( $code !== 404 ) {
								// Link staat publiek en mag dus getoond worden WERKT NIET DOOR DE REDIRECTS
								echo '<a href="'.$url.'" target="_blank"><p class="oft-partners-link">'.trim($quoted_term->name).'</p></a>';
							}
							curl_close($handle);	
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
			'meta_key' => 'oft-post-product',
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

		if ( file_exists( WP_CONTENT_DIR.'/fiches/'.$sitepress->get_current_language().'/'.$product->get_sku().'.pdf' ) ) {
			echo '<div class="vc_btn3-container oft-product-sheet vc_btn3-center">';
			echo '<a class="vc_general vc_btn3 vc_btn3-size-md vc_btn3-shape-rounded vc_btn3-style-flat vc_btn3-color-blue" href="'.content_url( '/fiches/'.$sitepress->get_current_language().'/'.$product->get_sku().'.pdf' ).'" target="_blank">'.__( 'Download de productfiche', 'oft' ).'</a>';
			echo '</div>';
		}

		echo '<div class="oft-icons">';
			$yes = array( 'Ja', 'Yes', 'Oui' );
			// SLUGS VAN ATTRIBUTEN WORDEN NIET VERTAALD, ENKEL DE TERMEN
			// TAGS ZIJN A.H.W. TERMEN VAN EEN WELBEPAALD ATTRIBUUT EN WORDEN DUS OOK VERTAALD
			if ( in_array( $product->get_attribute('bio'), $yes ) ) {
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
			if ( in_array( 'zonder-toegevoegde-suikers', $icons ) ) {
				echo "<div class='icon-no-added-sugars'></div>";
			}
			if ( in_array( 'lactosevrij', $icons ) ) {
				echo "<div class='icon-lactose-free'></div>";
			}
			if ( in_array( 'eerlijke-verhandelde-palmolie', $icons ) ) {
				echo "<div class='icon-fairly-traded-palm-oil'></div>";
			}
		echo '</div>';
	}

	// add_action( 'woocommerce_single_product_summary', 'show_hipster_icons', 80 );
	
	function show_hipster_icons() {
		global $product, $sitepress;
		if ( in_array( intval( apply_filters( 'wpml_object_id', get_term_by( 'slug', 'veggie', 'product_tag' )->term_id, 'product_tag', true, $sitepress->get_current_language() ) ), $product->get_tag_ids() ) ) {
			echo "<img class='veggie'>";
		}
		if ( in_array( intval( apply_filters( 'wpml_object_id', get_term_by( 'slug', 'vegan', 'product_tag' )->term_id, 'product_tag', true, $sitepress->get_current_language() ) ), $product->get_tag_ids() ) ) {
			echo "<img class='vegan'>";
		}
		if ( in_array( intval( apply_filters( 'wpml_object_id', get_term_by( 'slug', 'gluten-free', 'product_tag' )->term_id, 'product_tag', true, $sitepress->get_current_language() ) ), $product->get_tag_ids() ) ) {
			echo "<img class='gluten-free'>";
		}
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
					'heading' => __( 'Extra class name', 'js_composer' ),
					'param_name' => 'el_class',
					'description' => __( 'Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer' )
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
		return __( 'Datum: ', 'oft' ).get_the_date( 'd/m/Y' ).'<br>'.__( 'Category:', 'woocommerce' ).' '.get_the_category_list( ', ' );
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
	// ENKEL VERWIJDEREN INDIEN UPSELLS AANWEZIG?
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	add_action( 'woocommerce_single_product_summary', 'output_full_product_description', 20 );
	add_action( 'woocommerce_before_shop_loop', 'output_oft_partner_info', 10 );

	function output_full_product_description() {
		echo '<div class="woocommerce-product-details__short-description">';
		the_content();
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
							echo ' (<a href="https://www.oxfamwereldwinkels.be/node/'.$partner_node.'" target="_blank">lees meer over deze producent op oxfamwereldwinkels.be</a>)';
						}
					echo '</p>';

					global $wp_query;
					$cnt = $wp_query->found_posts;
					if ( $cnt > 0 ) {
						echo '<h4 style="margin: 2em 0;">'.sprintf( _n( 'Momenteel verkopen wij %s product van deze partner:', 'Momenteel verkopen wij %s producten van deze partner:', $cnt, 'oft' ), $cnt ).'</h4>';
					}
				} else {
					// Er is geen parent dus de oorspronkelijke term is een land
				}
			}
		}
	}



	###########
	#  VARIA  #
	###########

	// Creëer een productfiche
	function create_product_pdf( $product, $language ) {
		require_once WP_PLUGIN_DIR.'/html2pdf/autoload.php';
		$templatelocatie = get_stylesheet_directory().'/assets/fiche-'.$language.'.html';
		$templatefile = fopen( $templatelocatie, 'r' );
		$templatecontent = fread( $templatefile, filesize($templatelocatie) );
		$sku = $product->get_sku();

		if ( $partners = get_partner_terms_by_product($product) ) {
			$origin_text = __( 'Herkomst:', 'oft' ).' '.strip_tags( implode( ', ', $partners ) );
		} else {
			// Val terug op de landeninfo ENKEL NODIG VOOR EXTERNE PRODUCTEN, PER DEFINITIE GEEN FICHE NODIG
			$countries = get_country_terms_by_product($product);
			$origin_text = __( 'Herkomst:', 'oft' ).' '.implode( ', ', $countries );
		}

		// ALGEMENE GET_INGREDIENTS FUNCTIE MAKEN?
		// Druiven kunnen door de meta_boxlogica enkel op wijn ingesteld worden, dus geen nood om de categorie te checken
		$ingredients_text = '<p style="font-size: 11pt;">';
		if ( $grapes = get_grape_terms_by_product($product) ) {
			$ingredients_text .= __( 'Druivensoorten:', 'oft' ).' '.implode( ', ', $grapes ).'</p>';
		} elseif ( ! empty( $product->get_meta('_ingredients') ) ) {
			$ingredients_text .= __( 'Ingrediënten:', 'oft' ).' '.$product->get_meta('_ingredients').'.</p>';
		} else {
			$ingredients_text = '';
		}

		$allergens = get_the_terms( $product->get_id(), 'product_allergen' );
		if ( count($allergens) > 0 ) {
			$c_term = get_term_by( 'slug', 'contains', 'product_allergen' );
			$mc_term = get_term_by( 'slug', 'may-contain', 'product_allergen' );
			$c = array();
			$mc = array();
			foreach ( $allergens as $term ) {
				if ( $term->parent == $c_term->term_id ) {
					$c[] = mb_strtolower($term->name);
				} elseif( $term->parent == $mc_term->term_id ) {
					$mc[] = mb_strtolower($term->name);
				}
			}
			$allergens_text = '';
			if ( count($c) > 0 ) {
				$allergens_text .= _( 'Bevat', 'oft' ).' '.implode( ', ', $c ).'. ';
			}
			if ( count($mc) > 0 ) {
				$allergens_text .= __( 'Kan sporen bevatten van', 'oft' ).' '.implode( ', ', $mc ).'. ';
			}
		} else {
			$allergens_text = __( 'geen meldingsplichtige allergenen', 'oft' );
		}

		$labels = array();
		// Labels vereisen 'pa_'-voorvoegsel!
		// Wordt dit altijd in het Nederlands gecheckt?
		if ( $product->get_attribute('pa_bio') === 'Ja' ) {
			$labels[] = wc_attribute_label('pa_bio');
		}
		if ( $product->get_attribute('pa_fairtrade') === 'Ja' ) {
			$labels[] = wc_attribute_label('pa_fairtrade');
		}
		if ( count($labels) > 0 ) {
			$labels_text = format_pdf_block( 'Labels', implode( ', ', $labels ) );
		} else {
			$labels_text = '';
		}

		$templatecontent = str_replace( "###NAME###", $product->get_name(), $templatecontent );
		$templatecontent = str_replace( "###PRICE###", wc_price( $product->get_regular_price() ), $templatecontent );
		$templatecontent = str_replace( "###PERMALINK###", '<a href="'.$product->get_permalink().'">(bekijk product online)</a>', $templatecontent );
		$templatecontent = str_replace( "###NET_CONTENT###", $product->get_meta('_net_content').' '.$product->get_meta('_net_unit'), $templatecontent );
		// Verwijder eventuele enters door HTML-tags
		$templatecontent = str_replace( "###DESCRIPTION###", preg_replace( '/<[^>]+>/', ' ', $product->get_description() ), $templatecontent );
		$templatecontent = str_replace( "###ORIGIN###", $origin_text, $templatecontent );
		$templatecontent = str_replace( "###INGREDIENTS_OPTIONAL###", $ingredients_text, $templatecontent );
		$templatecontent = str_replace( "###LABELS_OPTIONAL###", $labels_text, $templatecontent );
		$templatecontent = str_replace( "###FAIRTRADE_SHARE###", $product->get_meta('_fairtrade_share'), $templatecontent );
		$templatecontent = str_replace( "###BRAND###", $product->get_attribute('merk'), $templatecontent );
		$templatecontent = str_replace( "###ALLERGENS###", $allergens_text, $templatecontent );
		$templatecontent = str_replace( "###SHOPPLUS###", preg_replace( '/[a-zA-Z]/', '', $product->get_meta('_shopplus_sku') ), $templatecontent );
		$templatecontent = str_replace( "###MULTIPLE###", $product->get_meta('_multiple'), $templatecontent );
		$templatecontent = str_replace( "###SKU###", $sku, $templatecontent );
		
		// Let op met fatale error bij het proberen aanmaken van een ongeldige barcode!
		if ( check_digit_ean13( $product->get_meta('_cu_ean') ) ) {
			$cu_ean = format_pdf_ean13( $product->get_meta('_cu_ean') );
		} else {
			$cu_ean = '/';
		}
		$templatecontent = str_replace( "###CU_EAN###", $cu_ean, $templatecontent );
		
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
		$templatecontent = str_replace( "###STEH_EAN###", $steh_ean, $templatecontent );

		if ( intval( $product->get_meta('_shelf_life') ) > 0 ) {
			$shelf_text = format_pdf_block( 'Houdbaarheid na productie', $product->get_meta('_shelf_life').' dagen' );
		} else {
			$shelf_text = '';
		}
		$templatecontent = str_replace( "###SHELF_LIFE_OPTIONAL###", $shelf_text, $templatecontent );

		$templatecontent = str_replace( "###CU_DIMENSIONS###", wc_format_dimensions( $product->get_dimensions(false) ), $templatecontent );
		$steh_dimensions = array(
			'length' => $product->get_meta('_steh_length'),
			'width' => $product->get_meta('_steh_width'),
			'height' => $product->get_meta('_steh_height'),
		);
		$templatecontent = str_replace( "###STEH_DIMENSIONS###", wc_format_dimensions($steh_dimensions), $templatecontent );
		$templatecontent = str_replace( "###NUMBER_OF_LAYERS###", $product->get_meta('_pal_number_of_layers'), $templatecontent );
		$templatecontent = str_replace( "###NUMBER_PER_LAYER###", $product->get_meta('_pal_number_per_layer'), $templatecontent );
		$templatecontent = str_replace( "###TOTAL###", intval( $product->get_meta('_pal_number_of_layers') ) * intval( $product->get_meta('_pal_number_per_layer') ), $templatecontent );
		$templatecontent = str_replace( "###INTRASTAT###", $product->get_meta('_intrastat'), $templatecontent );
		$templatecontent = str_replace( "###FOOTER###", "Aangemaakt op ".date_i18n( 'l j F Y \o\m G\ui' ), $templatecontent );
		
		try {
			$pdffile = new Html2Pdf( 'P', 'A4', 'nl', true, 'UTF-8', array( 15, 5, 15, 5 ) );
			$pdffile->setDefaultFont('Arial');
			$pdffile->pdf->setAuthor('Oxfam Fair Trade cvba');
			$pdffile->pdf->setTitle( __( 'Productfiche', 'oft' ).' '.$sku );
			$pdffile->writeHTML($templatecontent);
			$pdffile->output( WP_CONTENT_DIR.'/fiches/'.$language.'/'.$sku.'.pdf', 'F' );
		} catch ( Html2PdfException $e ) {
			$formatter = new ExceptionFormatter($e);
			add_filter( 'redirect_post_location', 'add_html2pdf_notice_var', 99 );
			update_option( 'html2pdf_notice', $formatter->getHtmlMessage() );
		}
	}

	function format_pdf_block( $title, $value ) {
		return '<p style="font-size: 10pt;"><div style="font-weight: bold; text-decoration: underline; padding-bottom: 1mm;">'.$title.'</div>'.$value.'</p>';
	}

	function format_pdf_ean13( $code ) {
		return '<br><barcode dimension="1D" type="EAN13" value="'.$code.'" label="label" style="width: 80%; height: 10mm; font-size: 10pt;"></barcode>';
	}

	function add_html2pdf_notice_var( $location ) {
		remove_filter( 'redirect_post_location', 'add_html2pdf_notice_var', 99 );
		return add_query_arg( array( 'html2pdf' => 'error' ), $location );
	}

	add_action( 'admin_notices', 'oft_admin_notices' );

	function oft_admin_notices() {
		if ( isset( $_GET['html2pdf'] ) ) {
			echo '<div class="notice notice-error">';
				echo '<p>'.get_option( 'html2pdf_notice' ).'</p>';
			echo '</div>';
		}
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

	// Voeg een bericht toe bovenaan alle adminpagina's
	add_action( 'admin_notices', 'oxfam_admin_notices' );

	function oxfam_admin_notices() {
		global $pagenow;
		$screen = get_current_screen();
		// var_dump($screen);

		if ( $pagenow === 'index.php' and $screen->base === 'dashboard' ) {
			echo '<div class="notice notice-warning">';
				echo '<p>Welkom in het walhalla van de productdata!</p>';
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
		if ( $post_language['language_code'] === $default_language ) {
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
					// REEDS INGESCHREVEN
					$result->invalidate( $tag, __( 'Dit e-mailadres is reeds ingeschreven!', 'oft' ) );
				} else {
					// NIET LANGER INGESCHREVEN
					$result->invalidate( $tag, __( 'Dit e-mailadres was al eens ingeschreven!', 'oft' ) );
				}
			}
		}
		return $result;
	}

	// Voer de effectieve inschrijving uit indien de validatie hierboven geen problemen gaf
 	add_filter( 'wpcf7_posted_data', 'handle_validation_errors', 20, 1 );
	
	function handle_validation_errors( $posted_data ) {
		// Nieuwsbriefformulieren
		$mc_forms = array( 1054, 6757, 6756 );
		if ( in_array( $posted_data['_wpcf7'], $mc_forms ) ) {
			$posted_data['validation_error'] = __( 'Gelieve de fouten op te lossen.', 'oft' );
			$posted_data['newsletter-email'] = strtolower( trim($posted_data['newsletter-email']) );
			$status = get_status_in_mailchimp_list( $posted_data['newsletter-email'] );
						
			if ( $status['response']['code'] == 200 ) {
				$body = json_decode($status['body']);

				if ( $body->status === "subscribed" ) {
					$timestamp = strtotime($body->timestamp_signup);
					$signup_text = '';
					if ( $timestamp !== false ) {
						$signup_text = ' '.sprintf( __( 'sinds %s', 'oft' ), date_i18n( 'j F Y', $timestamp ) );
					};
					// Niet meer nodig
					// $id = $body->unique_email_id;
					// PATCH BESTAANDE GEGEVENS?
					$posted_data['validation_error'] = sprintf( __( 'Je bent%s reeds geabonneerd op onze nieuwsbrief! We werkten je gegevens bij.', 'oft' ), $signup_text );
				} else {
					// PATCH BESTAANDE MEMBER ONMOGELIJK?
					$posted_data['validation_error'] = sprintf( __( 'Je was vroeger al eens geabonneerd op onze nieuwsbrief! Daarom dien je expliciet opnieuw toestemming te geven. <a href="%s" target="_blank">Gelieve dit algemene inschrijvingsformulier te gebruiken</a> en op de bevestigingslink te klikken.', 'oft' ), 'https://oxfamwereldwinkels.us3.list-manage.com/subscribe?u=d66c099224e521aa1d87da403&id='.MC_LIST_ID.'&EMAIL='.$posted_data['newsletter-email'] );
				}
			}
		}
		return $posted_data;
	}

	// Filter om mail tegen te houden ondanks succesvolle validatie
	// add_filter( 'wpcf7_skip_mail', 'abort_mail_sending' );
	function abort_mail_sending( $wpcf7 ){
		return true;
	}

 	// BIJ HET AANROEPEN VAN DEZE FILTER ZIJN WE ZEKER DAT ALLES AL GEVALIDEERD IS
 	add_filter( 'wpcf7_before_send_mail', 'handle_mailchimp_subscribe', 20, 1 );

	function handle_mailchimp_subscribe( $wpcf7 ) {
		$submission = WPCF7_Submission::get_instance();
		if ( $submission ) {
			$posted_data = $submission->get_posted_data();
		}
		write_log($posted_data);
		// Nieuwsbriefformulieren
		$mc_forms = array( 1054, 6757, 6756 );
		if ( in_array( $wpcf7->id(), $mc_forms ) ) {
			if ( empty($posted_data) ) {
				return;
			}
			// $mail = $wpcf7->prop('mail');
			// $mail['subject'] = 'Dit is een alternatief onderwerp';
			// $wpcf7->set_properties( array( 'mail' => $mail ) );
			
			// $posted_data['send_error'] = __( 'Er was een onbekend probleem met Contact Form 7!', 'oft' );
			$posted_data['newsletter-email'] = strtolower( trim($posted_data['newsletter-email']) );
			// Verruim tot hoofdletters na liggende streepjes
			$posted_data['newsletter-name'] = ucwords( strtolower( trim($posted_data['newsletter-name']) ) );
			$status = get_status_in_mailchimp_list( $posted_data['newsletter-email'] );
			
			if ( $status['response']['code'] !== 200 ) {
				$body = json_decode($status['body']);

				// NOG NOOIT INGESCHREVEN, VOER INSCHRIJVING UIT
				$subscription = subscribe_user_to_mailchimp_list( $posted_data['newsletter-email'], $posted_data['newsletter-name'] );
				
				$msgs = $wpcf7->prop('messages');
				write_log($msgs);
				if ( $subscription['response']['code'] == 200 ) {
					$body = json_decode($subscription['body']);
					if ( $body->status === "subscribed" ) {
						$msgs['success'] = __( 'Bedankt, je bent vanaf nu geabonneerd op de nieuwsbrief Oxfam Fair Trade!', 'oft' );
					}
				} else {
					$msgs['success'] = __( 'Er was een onbekend probleem met MailChimp.', 'oft' );
				}
				$wpcf7->set_properties( array( 'messages' => $msgs ) );
			}
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
		$member = md5( strtolower( trim( $email ) ) );
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode( 'user:'.MC_APIKEY )
			)
		);
		$response = wp_remote_get( 'https://'.$server.'.api.mailchimp.com/3.0/lists/'.$list_id.'/members/'.$member, $args );

		return $response;
	}

	function subscribe_user_to_mailchimp_list( $email, $name = '', $company = '', $list_id = MC_LIST_ID ) {
		global $sitepress;
		$language_code = $sitepress->get_current_language();
		$language_details = $sitepress->get_language_details($language_code);
		
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$member = md5( strtolower( trim( $email ) ) );
		$merge_fields = array( $language_details['native_name'], 'SOURCE' => 'OFT-site', );
		
		// Probleem: naam zit hier nog in 1 veld, moeten er 2 worden
		$parts = explode( ' ', $name, 2 );
		$fname = trim($parts[0]);
		$lname = trim($parts[1]);
		if ( strlen($fname) > 2 ) {
			$merge_fields['FNAME'] = $fname;
		}
		if ( strlen($lname) > 2 ) {
			$merge_fields['LNAME'] = $lname;
		}
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

	function update_user_in_mailchimp_list( $email, $name = '', $company = '', $list_id = MC_LIST_ID ) {
		global $sitepress;
		$language_code = $sitepress->get_current_language();
		$language_details = $sitepress->get_language_details($language_code);
		
		// VERGELIJK MET BESTAANDE WAARDES
		$member = get_status_in_mailchimp_list( $email );

		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$member = md5( strtolower( trim( $email ) ) );
		$merge_fields = array( 'LANGUAGE' => $language_details['native_name'], );
		
		// Probleem: naam zit hier nog in 1 veld, moeten er 2 worden
		$parts = explode( ' ', $name, 2 );
		$fname = trim($parts[0]);
		$lname = trim($parts[1]);
		if ( strlen($fname) > 2 ) {
			$merge_fields['FNAME'] = $fname;
		}
		if ( strlen($lname) > 2 ) {
			$merge_fields['LNAME'] = $lname;
		}
		if ( strlen($company) > 2 ) {
			$merge_fields['COMPANY'] = $company;
		}
		
		$args = array(
			'method' => 'PATCH',
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

	add_shortcode( 'latest_newsletter', 'get_latest_newsletter', 2 );

	function get_latest_newsletter() {
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		// Map met enkel de productnieuwsbrieven
		$folder_id = 'd302e08412';

		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode('user:'.MC_APIKEY)
			),
		);

		$response = wp_remote_get( 'https://'.$server.'.api.mailchimp.com/3.0/campaigns?status=sent&list_id='.MC_LIST_ID.'&folder_id='.$folder_id.'&sort_field=send_time&sort_dir=DESC&count=1', $args );
		
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

	// NIET MEER NODIG, NU VIA VOORRAADSTATUS
	add_action( 'pmxi_before_xml_import', 'set_all_out_of_stock', 10, 1 );

	function set_all_out_of_stock( $import_id ) {
		update_option( 'oft_import_active', 'yes' );
		if ( $import_id == 14 ) {
			$args = array(
				'post_type'	=> 'product',
				'post_status' => array( 'publish', 'private', 'draft', 'trash' ),
				'posts_per_page' => -1,
			);

			$out_of_stocks = new WP_Query( $args );

			if ( $out_of_stocks->have_posts() ) {
				while ( $out_of_stocks->have_posts() ) {
					$out_of_stocks->the_post();
					$product = wc_get_product( get_the_ID() );
					$product->set_stock_status( 'outofstock' );
					$product->save();
				}
				wp_reset_postdata();
			}
		}
	}

	// Bereken - indien mogelijk - de eenheidsprijs tijdens de ERP-import
	add_action( 'pmxi_saved_post', 'update_unit_price', 10, 1 );

	function update_unit_price( $post_id, $price = false, $content = false, $unit = false, $from_database = true ) {
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
		if ( $import_id == 14 ) {
			$old = WP_CONTENT_DIR."/B2CImport.csv";
			$new = WP_CONTENT_DIR."/erp-import-".date_i18n('Y-m-d').".csv";
			rename( $old, $new );
		}
	}

	// TE MOEILIJK, VOORLOPIG NIET GEBRUIKEN
	function update_origin( $post_id, $partners, $from_database = true ) {
		$product = wc_get_product( $post_id );
		if ( $product !== false ) {
			if ( $from_database = true ) {
				$partners = get_country_terms_by_product( $product );
			}
			if ( ! empty( $partners ) ) {
				$term_taxonomy_ids = wp_set_object_terms( $post_id, array_keys($partners), 'pa_herkomst', true );
				$data = $product->get_meta( '_product_attributes' );
				unset($data['pa_herkomst']);
				$data['pa_herkomst'] = array(
					'name' => 'pa_herkomst',
					'value' => '',
					'position' => '0',
					'is_visible' => '1',
					'is_variation' => '0',
					'is_taxonomy' => '1',
				);
				$product->update_meta_data( '_product_attributes', $data );
			} else {
				// Indien er geen partners zijn: verwijder het oude attribuut uit de array
				$product->update_meta_data( '_product_attributes', $data );
			}
			$product->save();
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
		if ( is_array($terms) and count($terms) > 0 ) {
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
		
		if ( count($terms) > 0 ) {
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
		
		if ( is_array($terms) and count($terms) > 0 ) {
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

	// Activeer WP API
	// add_action( 'wp_enqueue_scripts', 'load_extra_scripts' );

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

	// Testje met het toevoegen van custom taxonomieën aan de WP API
	// add_filter( 'woocommerce_rest_prepare_product_object', 'add_custom_taxonomies_to_response', 10, 3 );

	function add_custom_taxonomies_to_response( $response, $object, $request ) {
		if ( empty( $response->data ) ) {
			return $response;
		}
		
		foreach ( wp_get_object_terms( $object->id, 'product_recipe' ) as $term ) {
			$recipes[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}
		$response->data['recipes'] = $recipes;

		foreach ( wp_get_object_terms( $object->id, 'product_grape' ) as $term ) {
			$grapes[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}
		$response->data['grapes'] = $grapes;

		foreach ( wp_get_object_terms( $object->id, 'product_taste' ) as $term ) {
			$tastes[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}
		$response->data['tastes'] = $tastes;

		return $response;
	}
	
	// Lukt het intern wél via de JS-library?
	add_shortcode( 'wp-json', 'echo_js' );

	function echo_js() {
		?>
		<script type="text/javascript">
			function dump(obj) {
				var out = '';
				for (var i in obj) {
					out += i + ": " + obj[i] + "\n";
				}

				var pre = document.createElement('pre');
				pre.innerHTML = out;
				document.body.appendChild(pre);
			}

			jQuery(document).ready( function() {
				wp.api.loadPromise.done( function() {
					var post = new wp.api.models.Post( { id : 1077 } );
					post.fetch();
					alert( post.attributes.title.rendered );
				} );
			} );
		</script>
		<?php
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
		$wp_filetype = wp_check_filetype( $filename, null );
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
			// Check of de uploadlocatie ingegeven was!
			if ( ! isset($product_id) ) {
				// Indien het een b/c/d/e/f-foto is zal de search naar $filetitle een 0 opleveren
				// Dat is de bedoeling, want die foto's mogen het hoofdbeeld niet vervangen!
				$product_id = wc_get_product_id_by_sku( $filetitle );
			}

			if ( $product_id > 0 ) {
				// Voeg de nieuwe attachment-ID toe aan het bestaande product
				update_post_meta( $product_id, '_thumbnail_id', $attachment_id );
				// WERKT NOG NIET IN WC 2.6
				// $product->set_image_id($attachment_id);
				// $product->save();

				// Stel de uploadlocatie van de nieuwe afbeelding in
				wp_update_post(
					array(
						'ID' => $attachment_id, 
						'post_parent' => $product_id,
					)
				);
			}

			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
			// Registreer ook de metadata en toon een succesboodschap
			wp_update_attachment_metadata( $attachment_id,  $attachment_data );
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

	// Schakel autosaves uit
	// add_action( 'wp_print_scripts', function() { wp_deregister_script('autosave'); } );

	// Schakel productrevisies in
	add_filter( 'woocommerce_register_post_type_product', 'add_product_revisions' );
	
	function add_product_revisions( $vars ) {
		$vars['supports'][] = 'revisions';
		return $vars;
	}

	// Log wijzigingen aan taxonomieën
	add_action( 'set_object_terms', 'log_product_term_updates', 100, 6 );
	
	function log_product_term_updates( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		$watched_taxonomies = array(
			'product_cat',
			'product_tag',
			'product_partner',
			'product_allergen',
			'product_hipster',
			'product_grape',
			'product_taste',
			'product_recipe',
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
		// Log de data niet indien er een import loopt (doet een volledige delete/create i.p.v. update)
		if ( get_option('oft_import_active') === 'yes' ) {
			return;
		}

		$watched_metas = array(
			'_regular_price',
			'_thumbnail_id',
			'_tax_class',
			'_weight',
			'_length',
			'_width',
			'_height',
			'_empty_fee',
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

	function define_placeholder_texts() {
		$titel_zoekresultaten = __( 'Zoekresultaten', 'oft' );
	}
	
?>