<?php

	if ( ! defined('ABSPATH') ) exit;

	// Laad het child theme NIET MEER NODIG BIJ STOREFRONT MAAR NUTTIG VOOR VERTALINGEN EN BOOTSTRAP
	add_action( 'wp_enqueue_scripts', 'load_child_theme' );

	function load_child_theme() {
		// VERSTOORT LAYOUT
		// wp_enqueue_style( 'bootstrap_css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css' );
		// In de languages map van het child theme zal dit niet werken (checkt enkel nl_NL.mo) maar fallback is de algemene languages map (inclusief textdomain)
		load_child_theme_textdomain( 'oft', get_stylesheet_directory().'/languages' );
	}

	// Laad custom JS-files
	add_action( 'wp_enqueue_scripts', 'load_extra_js');

	function load_extra_js() {
		global $wp_scripts;
		// DOET NIKS?
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
		// remove_action( 'storefront_loop_post', 'storefront_post_meta', 20 );
		// remove_action( 'storefront_single_post', 'storefront_post_meta', 20 );

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
		// remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
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
			'capabilities' => array( 'assign_terms' => 'edit_products', 'manage_terms' => 'manage_options', 'edit_terms' => 'manage_options', 'delete_terms' => 'manage_options' ),
			'rewrite' => array( 'slug' => 'partner', 'with_front' => false, 'ep_mask' => 'test' ),
		);

		register_taxonomy( $taxonomy_name, 'product', $args );
		register_taxonomy_for_object_type( $taxonomy_name, 'product' );
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
			// Geef catmans rechten om zelf termen toe te kennen / te bewerken / toe te voegen
			'capabilities' => array( 'assign_terms' => 'edit_products', 'edit_terms' => 'edit_products', 'manage_terms' => 'edit_products', 'delete_terms' => 'create_sites' ),
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
			// Allergenen zullen in principe nooit meer toegevoegd moeten worden, dus catmans enkel rechten geven op toekenning
			'capabilities' => array( 'assign_terms' => 'edit_products', 'edit_terms' => 'create_sites', 'manage_terms' => 'create_sites', 'delete_terms' => 'create_sites' ),
			'rewrite' => array( 'slug' => 'allergen', 'with_front' => false ),
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

	add_action( 'woocommerce_single_product_summary', 'show_hipster_icons', 75 );

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



	###########
	#  VARIA  #
	###########

	function create_product_pdf( $product ) {
		require_once WP_CONTENT_DIR.'/plugins/html2pdf/html2pdf.class.php';
		
		$templatelocatie = get_stylesheet_directory().'/productfiche.html';
		$templatefile = fopen( $templatelocatie, 'r' );
		$templatecontent = fread( $templatefile, filesize($templatelocatie) );
		
		$sku = $product->get_sku();
		$templatecontent = str_replace( "###SKU###", $sku, $templatecontent );
		$templatecontent = str_replace( "###DESCRIPTION###", wc_price( $product->get_description() ), $templatecontent );
		// $templatecontent = str_replace( "###BRAND###", $product->get_attribute('pa_merk'), $templatecontent );
		$templatecontent = str_replace( "###EAN###", $product->get_attribute('pa_ean'), $templatecontent );
		$templatecontent = str_replace( "###OMPAK###", $product->get_attribute('pa_ompakhoeveelheid'), $templatecontent );
		$templatecontent = str_replace( "###LABELS###", $product->get_attribute('pa_biocertificatie'), $templatecontent );
		
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
					echo '<p>Hou er rekening mee dat alle volumes in g / ml ingegeven worden, zonder eenheid!</p>';
				echo '</div>';
			}
		}
	}



	###############
	#  MAILCHIMP  #
	###############

	// Voer de shortcodes uit
	add_shortcode( 'mailchimp_subscribe', 'output_mailchimp_form' );
	add_shortcode( 'latest_post', 'output_latest_post' );

	function output_mailchimp_form() {
		global $sitepress;
		?>
		<form novalidate>
			<div class="form-row">
				<div class="">
					<input type="text" class="form-control" name="fname" id="fname" placeholder="Voornaam" value="" maxlength="35" autocomplete="off" required>
					<div class="feedback">Gelieve je voornaam in te geven</div>
				</div>
				<div class="">
					<input type="text" class="form-control" name="lname" id="lname" placeholder="Familienaam" value="" maxlength="35" autocomplete="off" required>
					<div class="feedback">Gelieve je familienaam in te geven</div>
				</div>
				<div class="">
					<input type="email" class="form-control" name="email" id="email" placeholder="E-mailadres" maxlength="50" autocomplete="off" required>
					<div class="feedback">Geef een geldig e-mailadres in</div>
				</div>
				<div class="">
					<input type="hidden" class="form-control" name="lang" id="lang" value="<?php echo $sitepress->get_current_language(); ?>">
				</div>
			</div>
			<div class="form-row">
				<div class="">
					<small><span id="info">Je hebt nog niet alle vereiste velden ingevuld. Nog even volhouden!</span></small>
				</div>
				<div class="">
					<button type="submit" class="btn btn-primary" disabled>Hou me op de hoogte</button>
					<div class="fa fa-spinner fa-spin"></div>
				</div>
			</div>
			<div class="form-row">
				<div class="result"></div>
			</div>
		</form>
		<?php
	}

	function output_latest_post( $atts ) {
		// Geef de gewenste SLUG van de categorie in
		$args = shortcode_atts( array(
			'category' => 'productnieuws',
		), $atts );
		$my_posts = get_posts( array( 'numberposts' => 1, 'category_name' => $args['category'] ) );
		
		// var_dump_pre($my_posts);

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

	function get_latest_newsletters() {
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$folder_id = 'd302e08412';

		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode('user:'.MC_APIKEY)
			)
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

	function subscribe_to_mailchimp_list( $email, $list_id = MC_LIST_ID ) {
		global $sitepress;
		$server = substr( MC_APIKEY, strpos( MC_APIKEY, '-' ) + 1 );
		$language = $sitepress->get_current_language();
		$member = md5( strtolower( $email ) );
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' .base64_encode( 'user:'.MC_APIKEY )
			)
		);

		$response = wp_remote_get( 'https://'.$server.'.api.mailchimp.com/3.0/lists/'.$list_id.'/members/'.$member, $args );
		 
		$msg = "";
		if ( $response['response']['code'] == 200 ) {
			$body = json_decode($response['body']);

			if ( $body->status === "subscribed" ) {
				// INGESCHREVEN
			} else {
				// NIET MEER INGESCHREVEN
			}
		} else {
			// NOG NOOIT INGESCHREVEN
		}

		return "<p>".__( 'U bent vanaf nu geabonneerd op de OFT-nieuwsbrief.', 'oft' )."</p>";
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