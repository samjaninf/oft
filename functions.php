<?php

	if ( ! defined('ABSPATH') ) exit;

	// Laad het child theme na het hoofdthema
	add_action( 'wp_enqueue_scripts', 'load_child_theme', 999 );

	function load_child_theme() {
		// Zorgt ervoor dat de stylesheet van het child theme zeker na alone.css ingeladen wordt
		wp_enqueue_style( 'oft', get_stylesheet_uri(), array(), '1.0.0' );
		// BOOTSTRAP REEDS INGELADEN DOOR ALONE
		// In de languages map van het child theme zal dit niet werken (checkt enkel nl_NL.mo) maar fallback is de algemene languages map (inclusief textdomain)
		load_child_theme_textdomain( 'alone', get_stylesheet_directory().'/languages' );
	}

	// Laad custom JS-files
	add_action( 'wp_enqueue_scripts', 'load_extra_js');

	function load_extra_js() {
		global $wp_scripts;
		// wp_enqueue_script( 'bootstrap_js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js');
	}

	// Sta HTML-attribuut 'target' toe in beschrijvingen van taxonomieën
	add_action( 'init', 'allow_target_tag', 20 );

	function allow_target_tag() { 
		global $allowedtags;
		$allowedtags['a']['target'] = 1;
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
	}



	################
	#  TAXONOMIES  #
	################

	// Creëer een custom hiërarchische taxonomie op producten om partner/landinfo in op te slaan
	add_action( 'init', 'register_partner_taxonomy', 0 );
	
	function register_partner_taxonomy() {
		$taxonomy_name = 'product_partner';
		
		$labels = array(
			'name' => __( 'Partners', 'oft' ),
			'singular_name' => __( 'Partner', 'oft' ),
			'all_items' => __( 'Alle partners', 'oft' ),
			'parent_item' => __( 'Land', 'oft' ),
			'parent_item_colon' => __( 'Land:', 'oft' ),
			'new_item_name' => __( 'Nieuwe partner', 'oft' ),
			'add_new_item' => __( 'Voeg nieuwe partner toe', 'oft' ),
			'view_item' => __( 'Partner bekijken', 'oft' ),
			'edit_item' => __( 'Partner bewerken', 'oft' ),
			'search_items' => __( 'Partners doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Ken het product toe aan een partner/land', 'oft' ),
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
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'partner', 'with_front' => false, 'hierarchical' => false ),
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
			<div class="form-field term-group">
			<label for="partner_node"><?php _e( 'Partnernode OWW-site', 'oft' ); ?></label>
			<input type="number" min="1" max="99999" class="postform" id="partner_node" name="partner_node">
			</div>
		<?php
	}

	function save_partner_node_meta( $term_id, $tt_id ) {
		if ( isset($_POST['partner_node']) && '' !== $_POST['partner_node'] ) {
			add_term_meta( $term_id, 'partner_node', absint($_POST['partner_node']), true );
		}
		if( isset($_POST['partner_image_id']) && '' !== $_POST['partner_image_id'] ) {
			update_term_meta( $term_id, 'partner_image_id', absint($_POST['partner_image_id']) );
		}
	}

	function edit_partner_node_field( $term, $taxonomy ) {
		?>
			<tr class="form-field term-group-wrap">
				<th scope="row"><label for="partner_node"><?php _e( 'Partnernode OWW-site', 'oft' ); ?></label></th>
				<td>
					<?php $partner_node = get_term_meta( $term->term_id, 'partner_node', true ); ?>
					<input type="number" min="1" max="99999" class="postform" id="partner_node" name="partner_node" value="<?php if ($partner_node) echo esc_attr($partner_node); ?>">
				</td>
			</tr>
			 <tr class="form-field term-group-wrap">
				<th scope="row"><label for="partner_image_id"><?php _e( 'Beeld', 'oft' ); ?></label></th>
				<td>
					<?php $image_id = get_term_meta( $term->term_id, 'partner_image_id', true ); ?>
					<input type="hidden" id="partner_image_id" name="partner_image_id" value="<?php if ($image_id) echo esc_attr($image_id); ?>">
					<div id="partner-image-wrapper">
						<?php if ($image_id) echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
					</div>
					<p>
						<input type="button" class="button button-secondary showcase_tax_media_button" id="showcase_tax_media_button" name="showcase_tax_media_button" value="<?php _e( 'Kies foto', 'oft' ); ?>" />
						<input type="button" class="button button-secondary showcase_tax_media_remove" id="showcase_tax_media_remove" name="showcase_tax_media_remove" value="<?php _e( 'Verwijder foto', 'oft' ); ?>" />
					</p>
				</td>
			</tr>
		<?php
	}

	function update_partner_node_meta( $term_id, $tt_id ) {
		if ( isset($_POST['partner_node']) && '' !== $_POST['partner_node'] ) {
			add_term_meta( $term_id, 'partner_node', absint($_POST['partner_node']), true );
		} else {
			delete_term_meta( $term_id, 'partner_node' );
		}
		if( isset($_POST['partner_image_id']) && '' !== $_POST['partner_image_id'] ) {
			update_term_meta( $term_id, 'partner_image_id', absint($_POST['partner_image_id']) );
		} else {
			delete_term_meta( $term_id, 'partner_image_id' );
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
				_wpMediaViewsL10n.insertIntoPost = '<?php _e( 'Stel in', 'oft' ); ?>';
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
								$( '#partner-image-wrapper .custom_media_image' ).attr( 'src',attachment.url ).css( 'display','block' );
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

	// Creëer drie custom hiërarchische taxonomieën op producten om wijninfo in op te slaan
	add_action( 'init', 'register_wine_taxonomy', 0 );
	
	function register_wine_taxonomy() {
		$name = 'druif';
		$taxonomy_name = 'product_grape';
		
		$labels = array(
			'name' => __( 'Druiven', 'oft' ),
			'singular_name' => __( 'Druif', 'oft' ),
			'all_items' => __( 'Alle druivensoorten', 'oft' ),
			'parent_item' => __( 'Druif', 'oft' ),
			'parent_item_colon' => __( 'Druif:', 'oft' ),
			'new_item_name' => __( 'Nieuwe druivensoort', 'oft' ),
			'add_new_item' => __( 'Voeg nieuwe druivensoort toe', 'oft' ),
			'view_item' => __( 'Druivensoort bekijken', 'oft' ),
			'edit_item' => __( 'Druivensoort bewerken', 'oft' ),
			'search_items' => __( 'Druivensoorten doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => sprintf( __( 'Voeg de wijn toe aan een %s in de wijnkiezer', 'oft' ), $name ),
			'public' => false,
			'publicly_queryable' => true,
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_menu' => false,
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
			'search_items' => __( 'Smaken doorzoeken', 'oft' ),
		);

		$args['labels'] = $labels;
		$args['description'] = sprintf( __( 'Voeg de wijn toe aan een %s in de wijnkiezer', 'oft' ), $name );
		$args['rewrite']['slug'] = $name;

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Creëer een custom hiërarchische taxonomie op producten om allergeneninfo in op te slaan
	add_action( 'init', 'register_allergen_taxonomy', 0 );

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
			'search_items' => __( 'Allergenen doorzoeken', 'oft' ),
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Geef aan dat het product dit bevat', 'oft' ),
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
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'allergeen', 'with_front' => false ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}

	// Maak onze custom taxonomiën beschikbaar in menu editor
	add_filter('woocommerce_attribute_show_in_nav_menus', 'register_custom_taxonomies_for_menus', 1, 2 );

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

	// Verhinder bepaalde selecties in de back-end AAN TE PASSEN NAAR DE NIEUWE ID'S
	add_action( 'admin_footer', 'disable_custom_checkboxes' );

	function disable_custom_checkboxes() {
		?>
		<script>
			/* Disable hoofdcategorieën */
			jQuery( '#in-product_cat-93' ).prop( 'disabled', true );
			jQuery( '#in-product_cat-133' ).prop( 'disabled', true );
			jQuery( '#in-product_cat-63' ).prop( 'disabled', true );
			jQuery( '#in-product_cat-204' ).prop( 'disabled', true );
			jQuery( '#in-product_cat-28' ).prop( 'disabled', true );
			
			/* Disable continenten */
			jQuery( '#in-product_partner-252' ).prop( 'disabled', true );
			jQuery( '#in-product_partner-277' ).prop( 'disabled', true );
			jQuery( '#in-product_partner-249' ).prop( 'disabled', true );
			jQuery( '#in-product_partner-265' ).prop( 'disabled', true );
			
			/* Disable bovenliggende landen/continenten van alle aangevinkte partners/landen */
			jQuery( '#taxonomy-product_partner' ).find( 'input[type=checkbox]:checked' ).closest( 'ul.children' ).siblings( 'label.selectit' ).find( 'input[type=checkbox]' ).prop( 'disabled', true );

			/* Disable/enable het bovenliggende land bij aan/afvinken van een partner */
			jQuery( '#taxonomy-product_partner' ).find( 'input[type=checkbox]' ).on( 'change', function() {
				jQuery(this).closest( 'ul.children' ).siblings( 'label.selectit' ).find( 'input[type=checkbox]' ).prop( 'disabled', jQuery(this).is(":checked") );
			});

			/* Disable allergeenklasses */
			jQuery( '#in-product_allergen-170' ).prop( 'disabled', true );
			jQuery( '#in-product_allergen-171' ).prop( 'disabled', true );

			/* Disable rode en witte druiven */
			jQuery( '#in-product_grape-1724' ).prop( 'disabled', true );
			jQuery( '#in-product_grape-1725' ).prop( 'disabled', true );
		</script>
		<?php
	}

	// Toon metaboxes voor wijninfo enkel voor producten onder de hoofdcategorie 'Wijn'
	add_action( 'admin_init', 'hide_wine_taxonomies' );

	function hide_wine_taxonomies() {
		if ( isset($_GET['action']) and $_GET['action'] === 'edit' ) {
			// SIMPELER VIA GLOBAL $POST?
			$post_id = isset( $_GET['post'] ) ? $_GET['post'] : $_POST['post_ID'];
			$categories = get_the_terms( $post_id, 'product_cat' );
			if ( is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					while ( $category->parent !== 0 ) {
						$parent = get_term( $category->parent, 'product_cat' );
						$category = $parent;
					}
				}
				if ( $parent->slug !== 'wijn' ) {
					remove_meta_box('product_grapediv', 'product', 'normal');
					remove_meta_box('product_recipediv', 'product', 'normal');
					remove_meta_box('product_tastediv', 'product', 'normal');
				}
			}
		}
	}

	// Creëer een custom hiërarchische taxonomie op producten om allergeneninfo in op te slaan
	add_action( 'init', 'register_hipster_taxonomy', 0 );

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
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen maar niet om te verwijderen!
			'capabilities' => array( 'assign_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'delete_terms' => 'update_core' ),
			'rewrite' => array( 'slug' => 'eco', 'with_front' => false ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
	}



	#################
	#  WOOCOMMERCE  #
	#################

	// Toon metavelden netjes in de WooCommerce-tabbladen en werk ze bij tijdens het opslaan
	add_action( 'woocommerce_product_options_general_product_data', 'add_oft_general_fields', 5 );
	add_action( 'woocommerce_product_options_inventory_product_data', 'add_oft_inventory_fields', 5 );
	add_action( 'woocommerce_product_options_shipping', 'add_oft_shipping_fields', 5 );
	add_filter( 'woocommerce_product_data_tabs', 'add_product_quality_tab' );
	add_action( 'woocommerce_product_data_panels', 'add_oft_quality_fields' ); 
	add_action( 'woocommerce_process_product_meta_simple', 'save_oft_fields' );
	
	function add_oft_general_fields() {
		global $post;
		echo '<div class="options_group oft">';
			
			$suffix = '&euro;';
			if ( get_post_meta( $post->ID, '_net_unit', true ) === 'cl' ) {
				$suffix .= '/l';
			} elseif( get_post_meta( $post->ID, '_net_unit', true ) === 'g' ) {
				$suffix .= '/kg';
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
						// Nooit handmatig laten bewerken!
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

			if ( ! post_language_equals_site_language() ) {
				$number_args['custom_attributes']['readonly'] = true;
			}

			// Toon het veld voor de netto-inhoud pas na het instellen van de eenheid!
			if ( ! empty( get_post_meta( $post->ID, '_net_unit', true ) ) ) {
				$unit = get_post_meta( $post->ID, '_net_unit', true );
			} else {
				$unit = 'g of cl';
			}

			woocommerce_wp_text_input(
				array( 
					'id' => '_net_content',
					'label' => sprintf( __( 'Netto-inhoud (%s)', 'oft-admin' ), $unit ),
					'type' => 'number',
					'custom_attributes' => array(
						'step'	=> '1',
						'min'	=> '1',
						'max'	=> '10000',
					),
				)
			);

			woocommerce_wp_text_input(
				array( 
					'id' => '_fairtrade_share',
					'label' => __( 'Aandeel fairtrade (%)', 'oft-admin' ),
					'type' => 'number',
					'custom_attributes' => array(
						'step'	=> '1',
						'min'	=> '25',
						'max'	=> '100',
					),
				)
			);

		echo '</div>';
	}
	
	function add_oft_inventory_fields() {
		echo '<div class="options_group oft">';
			
			$args = array( 
				'id' => '_cu_ean',
				'label' => __( 'EAN Consument', 'oft-admin' ),
				'type' => 'number',
				'custom_attributes' => array(
					'step'	=> 'any',
					'min'	=> '1000000000000',
					'max'	=> '9999999999999',
					'readonly' => true,
				),
			);

			$args2 = array( 
				'id' => '_shopplus_sku',
				'label' => __( 'ShopPlus', 'oft-admin' ),
				'custom_attributes' => array(
					'readonly' => true,
				),
			);

			if ( post_language_equals_site_language() ) {
				unset($args['custom_attributes']['readonly']);
				unset($args2['custom_attributes']['readonly']);
			}

			woocommerce_wp_text_input( $args );
			
			$args['id'] = '_steh_ean';
			$args['label'] = __( 'EAN Ompak', 'oft-admin' );
			woocommerce_wp_text_input( $args );

			woocommerce_wp_text_input( $args2 );

			$args3 = array( 
				'id' => '_shelf_life',
				'label' => __( 'Houdbaarheid na productie (dagen)', 'oft-admin' ),
				'type' => 'number',
				'custom_attributes' => array(
					'step'	=> 'any',
					'min'	=> '1',
					'max'	=> '10000',
					'readonly' => true,
				),
			);

			woocommerce_wp_text_input( $args3 );

			$args4 = array( 
				'id' => '_intrastat',
				'label' => __( 'Intrastatcode', 'oft-admin' ),
				'custom_attributes' => array(
					'readonly' => true,
				),
			);

			woocommerce_wp_text_input( $args4 );

			woocommerce_wp_checkbox( 
				array( 
					'id' => '_in_bestelweb',
					'label' => __( 'Bestelbaar?', 'oft-admin' ),
				)
			);

		echo '</div>';
	}

	function add_oft_shipping_fields() {
		global $post;
		echo '<div class="options_group oft">';
			
			$number_args = array( 
				'type' => 'number',
				'custom_attributes' => array(
					'step'	=> 'any',
					'min'	=> '1',
					'max'	=> '1000',
				),
			);

			if ( ! post_language_equals_site_language() ) {
				$number_args['custom_attributes']['readonly'] = true;
			}

			$multiple = array(
				'id' => '_multiple',
				'label' => __( 'Ompakhoeveelheid', 'oft-admin' ),
			);

			$pal_number_of_layers = array(
				'id' => '_pal_number_of_layers',
				'label' => __( 'Aantal palletlagen', 'oft-admin' ),
			);

			$pal_number_per_layer = array(
				'id' => '_pal_number_per_layer',
				'label' => __( 'Aantal per palletlaag', 'oft-admin' ),
			);
			
			woocommerce_wp_text_input( $multiple + $number_args );
			woocommerce_wp_text_input( $pal_number_of_layers + $number_args );
			woocommerce_wp_text_input( $pal_number_per_layer + $number_args );

		echo '</div>';
	}

	function add_product_quality_tab( $product_data_tabs ) {
		$product_data_tabs['quality-tab'] = array(
			'label' => __( 'Voedingswaarden', 'oft-admin' ),
			'target' => 'quality_product_data',
			'class' => array( 'hide_if_virtual' ),
		);
		// Verwijder overbodig tabje
		unset($product_data_tabs['advanced']);
		return $product_data_tabs;
	}

	function add_oft_quality_fields() {
		global $post;
		echo '<div id="quality_product_data" class="panel woocommerce_options_panel">';
			echo '<div class="options_group oft">';
				
				$one_decimal_args = array( 
					'data_type' => 'decimal',
					'type' => 'number',
					'custom_attributes' => array(
						'step'	=> '0.1',
						'min'	=> '0.1',
						'max'	=> '100.0',
					),
				);

				if ( ! post_language_equals_site_language() ) {
					$one_decimal_args['custom_attributes']['readonly'] = true;
				}

				$secondary = array(
					'wrapper_class' => 'secondary',
				);

				$fat = array(
					'id' => '_fat',
					'label' => __( 'Vetten (g)', 'oft-admin' ),
				);
				
				$fasat = array(
					'id' => '_fasat',
					'label' => __( 'waarvan verzadigde vetzuren (g)', 'oft-admin' ),
				);

				$famscis = array(
					'id' => '_famscis',
					'label' => __( 'waarvan enkelvoudig onverzadigde vetzuren (g)', 'oft-admin' ),
				);

				$fapucis = array(
					'id' => '_fapucis',
					'label' => __( 'waarvan meervoudig onverzadigde vetzuren (g)', 'oft-admin' ),
				);
				
				// Beter via JavaScript checken of de som van alle secondaries de primary niet overschrijdt!
				if ( ! empty( get_post_meta( $post->ID, '_fat', true ) ) ) {
					$fat_limit = array(
						'custom_attributes' => array( 'max' => get_post_meta( $post->ID, '_fat', true ) ),
					);
				}

				$choavl = array(
					'id' => '_choavl',
					'label' => __( 'Koolhydraten (g)', 'oft-admin' ),
				);

				$sugar = array(
					'id' => '_sugar',
					'label' => __( 'waarvan suikers (g)', 'oft-admin' ),
				);

				$polyl = array(
					'id' => '_polyl',
					'label' => __( 'waarvan polyolen (g)', 'oft-admin' ),
				);

				$starch = array(
					'id' => '_starch',
					'label' => __( 'waarvan zetmeel (g)', 'oft-admin' ),
				);

				// Beter via JavaScript checken of de som van alle secondaries de primary niet overschrijdt!
				if ( ! empty( get_post_meta( $post->ID, '_choavl', true ) ) ) {
					$choavl_limit = array(
						'custom_attributes' => array( 'max' => get_post_meta( $post->ID, '_choavl', true ) ),
					);
				}
				
				$fibtg = array(
					'id' => '_fibtg',
					'label' => __( 'Vezels (g)', 'oft-admin' ),
				);

				$pro = array(
					'id' => '_pro',
					'label' => __( 'Eiwitten (g)', 'oft-admin' ),
				);

				woocommerce_wp_text_input(
					array( 
						'id' => '_energy',
						'label' => __( 'Energie (kJ)', 'oft-admin' ),
						'type' => 'number',
						'custom_attributes' => array(
							'step'	=> 'any',
							'min'	=> '1',
							'max'	=> '10000',
						),
					)
				);

				woocommerce_wp_text_input( $fat + $one_decimal_args );
				woocommerce_wp_text_input( $fasat + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $famscis + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $fapucis + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $choavl + $one_decimal_args );
				woocommerce_wp_text_input( $sugar + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $polyl + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $starch + $one_decimal_args + $secondary );
				woocommerce_wp_text_input( $fibtg + $one_decimal_args );
				woocommerce_wp_text_input( $pro + $one_decimal_args );
				
				woocommerce_wp_text_input(
					array( 
						'id' => '_salteq',
						'label' => __( 'Zout (g)', 'oft-admin' ),
						'data_type' => 'decimal',
						'type' => 'number',
						'custom_attributes' => array(
							'step'	=> '0.001',
							'min'	=> '0.001',
							'max'	=> '100.000',
						),
					)
				);

			echo '</div>';
		echo '</div>';
	}

	// Voeg CSS toe aan adminomgeving in afwachting van style-admin.scss
	add_action( 'admin_head', 'custom_admin_css' );

	function custom_admin_css() {
		?>
		<style>
			#woocommerce-product-data ul.wc-tabs li.quality-tab_options a:before {
				font-family: FontAwesome;
				content: '\f0c3';
			}

			div.options_group.oft > p.form-field.secondary > label {
				padding-left: 30px;
				font-style: italic;
			}

			#quality_product_data div.options_group.oft > p.form-field > label {
				width: 90%;
				max-width: 400px;
			}

			#quality_product_data div.options_group.oft > p.form-field:not(.secondary) > label {
				padding-right: 30px;
			}

			div.options_group.oft > p.form-field > input[type=number] {
				width: 10%;
				max-width: 80px;
				min-width: 60px;
				text-align: right;
			}
		</style>
		<?php
	}

	function save_oft_fields( $post_id ) {
		if ( ! empty( $_POST['_regular_price'] ) and ! empty( $_POST['_net_unit'] ) and ! empty( $_POST['_net_content'] ) ) {
			// Bereken de eenheidsprijs a.d.h.v. prijs en netto-inhoud in $_POST
			$unit_price = wc_format_decimal( $_POST['_regular_price'] ) / floatval( $_POST['_net_content'] );
			if ( $_POST['_net_unit'] === 'g' ) {
				$unit_price *= 1000;
			} elseif ( $_POST['_net_unit'] === 'cl' ) {
				$unit_price *= 100;
			}
			update_post_meta( $post_id, '_unit_price', esc_attr( number_format( $unit_price, 2 ) ) );
		} else {
			// Indien er een gegeven ontbreekt: verwijder sowieso de oude waarde
			delete_post_meta( $post_id, '_unit_price' );
		}

		$regular_meta_keys = array(
			'_cu_ean',
			'_steh_ean',
			'_shelf_life',
			'_in_bestelweb',
			'_shopplus_sku',
			'_fairtrade_share',
			'_net_unit',
			'_net_content',
			'_multiple',
			'_pal_number_per_layer',
			'_pal_number_of_layers',
			'_energy',
		);

		foreach( $regular_meta_keys as $meta_key ) {
			if ( ! empty( $_POST[$meta_key] ) ) {
				update_post_meta( $post_id, $meta_key, esc_attr( $_POST[$meta_key] ) );
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

		foreach( $decimal_meta_keys as $meta_key ) {
			if ( ! empty( $_POST[$meta_key] ) ) {
				update_post_meta( $post_id, $meta_key, esc_attr( number_format( $_POST[$meta_key], 1 ) ) );
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
		}

		if ( ! empty( $_POST['_salteq'] ) ) {
			update_post_meta( $post_id, '_salteq', esc_attr( number_format( $_POST['_salteq'], 3 ) ) );
		} else {
			delete_post_meta( $post_id, '_salteq' );
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
				<label for="oft-post-product" class=""><?php printf( __( 'Selecteer 1 van de %d actuele producten waarover dit bericht gaat:', 'oft' ), count($list) ); ?></label>
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

	// add_action( 'woocommerce_single_product_summary', 'show_hipster_icons', 75 );

	function show_hipster_icons() {
		global $product, $sitepress;
		// var_dump_pre( $veggie->term_id );
		if ( in_array( intval( apply_filters( 'wpml_object_id', get_term_by( 'slug', 'veggie', 'product_tag' )->term_id, 'product_tag', true, $sitepress->get_current_language() ) ), $product->get_tag_ids() ) ) {
			echo "<img class='veggie'>";
		}
		if ( in_array( intval( apply_filters( 'wpml_object_id', get_term_by( 'slug', 'vegan', 'product_tag' )->term_id, 'product_tag', true, $sitepress->get_current_language() ) ), $product->get_tag_ids() ) ) {
			echo "<img class='vegan'>";
		}
		if ( in_array( intval( apply_filters( 'wpml_object_id', get_term_by( 'slug', 'gluten-free', 'product_tag' )->term_id, 'product_tag', true, $sitepress->get_current_language() ) ), $product->get_tag_ids() ) ) {
			echo "<img class='gluten-free'>";
		}
		var_dump_pre( $product->get_attribute('biocertificatie') );
		$yes = array( 'nl' => 'Ja', 'en' => 'Yes', 'fr' => 'Oui' );
		// SLUGS VAN ATTRIBUTEN WORDEN NIET VERTAALD, ENKEL DE TERMEN
		// TAGS ZIJN A.H.W. TERMEN VAN EEN WELBEPAALD ATTRIBUUT EN WORDEN DUS OOK VERTAALD
		if ( $product->get_attribute('biocertificatie') === $yes[$sitepress->get_current_language()] ) {
			echo "<img class='organic'>";
		}
	}

	// Aantal gerelateerde producten wijzigen
	// add_filter( 'woocommerce_output_related_products_args', 'alter_related_products_args', 20 );

	function alter_related_products_args( $args ) {
		$args['posts_per_page'] = 3;
		$args['columns'] = 3;
		return $args;
	}



	#############
	#  CONTENT  #
	#############

	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	add_action( 'woocommerce_before_shop_loop', 'output_oft_partner_info', 10 );
	
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
						echo '<blockquote>'.wc_format_content( term_description() ).'</blockquote>';
						echo '<h5 style="text-align: right;">'.single_term_title( '', false ).' &mdash; '.$parent_term->name.', '.$grandparent_term->name.'</h5>';
						$image_id = get_term_meta( get_queried_object()->term_id, 'partner_image_id', true );
						if ($image_id) {
							echo wp_get_attachment_image( $image_id, array(300,300), false, array( 'class' => 'partner-quote-icon' ) );
						}
					}
					$partner_node = get_term_meta( get_queried_object()->term_id, 'partner_node', true );
					if ( $partner_node > 0 ) {
						echo '<h4><a href="https://www.oxfamwereldwinkels.be/node/'.$partner_node.'" target="_blank">Lees meer over deze partner =></a></h4>';
					}
					global $wp_query;
					echo '<h3>'.$wp_query->found_posts.' producten van deze partner ...</h3>';
				} else {
					// Er is geen parent dus de oorspronkelijke term is een land
				}
			}
		}
	}

	add_shortcode( 'latest_post', 'output_latest_post' );
	
	function output_latest_post( $atts ) {
		// Geef de gewenste SLUG van de categorie in
		$args = shortcode_atts( array(
			'category' => 'productnieuws',
		), $atts );
		$my_posts = get_posts( array( 'numberposts' => 1, 'category_name' => $args['category'] ) );

		if ( count($my_posts) > 0 ) {
			foreach ( $my_posts as $post ) {
				setup_postdata( $post );
				$msg .= "<div class='latest-news'><h1>".get_the_title( $post->ID )."</h1>".apply_filters( 'the_content', get_the_content( $post->ID ) )."</div>";
			}
		} else {
			$msg .= "<p>Geen berichten gevonden in de categorie '".$atts['category']."'.</p>";
		}

		wp_reset_postdata();

		return $msg;
	}



	###########
	#  VARIA  #
	###########

	// Creëer een productfiche
	function create_product_pdf( $product ) {
		require_once WP_CONTENT_DIR.'/plugins/html2pdf/html2pdf.class.php';
		
		$templatelocatie = get_stylesheet_directory().'/productfiche.html';
		$templatefile = fopen( $templatelocatie, 'r' );
		$templatecontent = fread( $templatefile, filesize($templatelocatie) );
		
		$sku = $product->get_sku();
		$templatecontent = str_replace( "###SKU###", $sku, $templatecontent );
		$templatecontent = str_replace( "###DESCRIPTION###", wc_price( $product->get_description() ), $templatecontent );
		$templatecontent = str_replace( "###BRAND###", $product->get_attribute('pa_merk'), $templatecontent );
		$templatecontent = str_replace( "###EAN###", $product->get_meta('_cu_ean'), $templatecontent );
		$templatecontent = str_replace( "###OMPAK###", $product->get_meta('_multiple'), $templatecontent );
		$templatecontent = str_replace( "###LABELS###", $product->get_attribute('pa_bio'), $templatecontent );
		
		$pdffile = new HTML2PDF( "P", "A4", "nl" );
		$pdffile->pdf->SetAuthor( "Oxfam Fair Trade cvba" );
		$pdffile->pdf->SetTitle( "Productfiche ".$sku );
		$pdffile->WriteHTML($templatecontent);
		$pdffile->Output( WP_CONTENT_DIR."/".$sku.".pdf", "F" );
	}

	// Voeg een bericht toe bovenaan alle adminpagina's
	add_action( 'admin_notices', 'oxfam_admin_notices' );

	function oxfam_admin_notices() {
		global $pagenow, $post_type;
		$screen = get_current_screen();
		// var_dump($screen);

		if ( $pagenow === 'index.php' and $screen->base === 'dashboard' ) {
			if ( $pagenow === 'edit.php' and $post_type === 'product' and current_user_can( 'edit_products' ) ) {
				echo '<div class="notice notice-warning">';
					echo '<p>Hou er rekening mee dat alle volumes in g / cl ingegeven worden, zonder eenheid!</p>';
				echo '</div>';
			}
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
			// ALTIJD TRUE RETOURNEREN IN AFWACHTING ACTIVATIE WPML
			return true;
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
		write_log($result);
		return $result;
	}

	// Voer de effectieve inschrijving uit indien de validatie hierboven geen problemen gaf
 	add_filter( 'wpcf7_posted_data', 'handle_validation_errors', 20, 1 );
	
	function handle_validation_errors( $posted_data ) {
		// Nederlandstalige inschrijvingsformulier
		if ( $posted_data['_wpcf7'] == 1054 ) {
			$posted_data['validation_error'] = __( 'Gelieve de fouten op te lossen.', 'oft' );

			$status = get_status_in_mailchimp_list( $_POST['newsletter-email'] );
			
			if ( $status['response']['code'] == 200 ) {
				$body = json_decode($status['body']);

				if ( $body->status === "subscribed" ) {
					// REEDS INGESCHREVEN
					// TOON LINK OM PROFIEL TE BEKIJKEN
					// PATCH BESTAANDE GEGEVENS?
					$timestamp = strtotime($body->timestamp_signup);
					if ( $timestamp !== false ) {
						$signup_text = ' sinds '.date_i18n( 'j F Y', $timestamp );
					} else {
						$signup_text = '';
					}
					$id = $body->unique_email_id;
					$posted_data['validation_error'] = sprintf( __( 'U bent%1$s reeds geabonneerd op onze nieuwsbrief! <a href="%2$s" target="_blank">Kijk hier uw profiel in.</a>', 'oft' ), $signup_text, 'http://oxfamwereldwinkels.us3.list-manage.com/profile?u=d66c099224e521aa1d87da403&id='.MC_LIST_ID.'&e='.$id );
				} else {
					// NIET LANGER INGESCHREVEN
					// TOON LINK NAAR EXPLICIET INSCHRIJVINGSFORMULIER
					// PATCH BESTAANDE MEMBER?
					$posted_data['validation_error'] = sprintf( __( 'U was vroeger al eens geabonneerd op onze nieuwsbrief! Daarom dient u uw expliciete toestemming te geven. <a href="%s" target="_blank">Gelieve dit algemene inschrijvingsformulier te gebruiken.</a>', 'oft' ), 'http://oxfamwereldwinkels.us3.list-manage.com/subscribe?u=d66c099224e521aa1d87da403&id='.MC_LIST_ID.'&FNAME='.$_POST['newsletter-name'].'&EMAIL='.$_POST['newsletter-email'] );
				}
			}
		}
		write_log("wpcf7_posted_data");
		return $posted_data;
	}

 	// BIJ HET AANROEPEN VAN DEZE FILTER ZIJN WE ZEKER DAT ALLES AL GEVALIDEERD IS
 	add_filter( 'wpcf7_before_send_mail', 'handle_mailchimp_subscribe', 20, 1 );

	function handle_mailchimp_subscribe( $posted_data ) {
		// Nederlandstalige inschrijvingsformulier
		if ( $posted_data['_wpcf7'] == 1054 ) {
			$posted_data['send_error'] = __( 'Er was een onbekend probleem met Contact Form 7!', 'oft' );
			$status = get_status_in_mailchimp_list( $_POST['newsletter-email'] );
			
			if ( $status['response']['code'] !== 200 ) {
				$body = json_decode($status['body']);

				// NOG NOOIT INGESCHREVEN, VOER INSCHRIJVING UIT
				// Probleem: naam zit hier nog in 1 veld, moeten er 2 worden
				$subscription = subscribe_user_to_mailchimp_list( $posted_data['newsletter-email'], $posted_data['newsletter-name'] );
				
				if ( $subscription['response']['code'] == 200 ) {
					$body = json_decode($subscription['body']);
					if ( $body->status === "subscribed" ) {
						$posted_data['success'] = __( 'U bent vanaf nu geabonneerd op de OFT-nieuwsbrief.', 'oft' );
					}
				} else {
					$posted_data['success'] = __( 'Er was een onbekend probleem met MailChimp!', 'oft' );
				}
			}
		}
		write_log("wpcf7_before_send_mail");
		write_log($posted_data);
		return $posted_data;
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

	function subscribe_user_to_mailchimp_list( $email, $fname = '', $lname = '', $company = '', $list_id = MC_LIST_ID ) {
		// global $sitepress;
		// $language = $sitepress->get_current_language();
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$member = md5( strtolower( trim( $email ) ) );
		$merge_fields = array( 'LANGUAGE' => 'Nederlands', 'SOURCE' => 'OFT-site', );
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

	function update_user_in_mailchimp_list( $email, $fname = '', $lname = '', $company = '', $list_id = MC_LIST_ID ) {
		// global $sitepress;
		// $language = $sitepress->get_current_language();
		
		// VERGELIJK MET BESTAANDE WAARDES
		$member = get_status_in_mailchimp_list( $email );

		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$member = md5( strtolower( trim( $email ) ) );
		$merge_fields = array( 'LANGUAGE' => 'Nederlands', 'SOURCE' => 'OFT-site', );
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

	function get_latest_newsletters() {
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$folder_id = 'd302e08412';

		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode('user:'.MC_APIKEY)
			),
		);

		$response = wp_remote_get( 'https://'.$server.'.api.mailchimp.com/3.0/campaigns?since_send_time='.date_i18n( 'Y-m-d', strtotime('-3 months') ).'&status=sent&list_id='.$list_id.'&folder_id='.$folder_id.'&sort_field=send_time&sort_dir=ASC', $args );
		
		$mailings = "";
		if ( $response['response']['code'] == 200 ) {
			$body = json_decode($response['body']);
			$mailings .= "<p>Dit zijn de nieuwsbrieven van de afgelopen drie maanden:</p><ul>";

			foreach ( array_reverse($body->campaigns) as $campaign ) {
				$mailings .= '<li><a href="'.$campaign->long_archive_url.'" target="_blank">'.$campaign->settings->subject_line.'</a> ('.trim( date_i18n( 'j F Y', strtotime($campaign->send_time) ) ).')</li>';
			}

			$mailings .= "</ul>";
		}		

		return $mailings;
	}



	###############
	#  DEBUGGING  #
	###############

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
	
?>