<?php
if (!function_exists('_alone_action_theme_setup')) :
	/**
	 * Theme setup.
	 *
	 * Set up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support post thumbnails.
	 * @internal
	 */
	function _alone_action_theme_setup() {
		// Make Theme available for translation.
		load_theme_textdomain('alone', get_template_directory() . '/languages');

		// setup cornerstone
		if(function_exists('cornerstone_theme_integration')) :
			cornerstone_theme_integration( array(
			  'remove_global_validation_notice' => true,
			  'remove_themeco_offers'           => true,
			  'remove_purchase_link'            => true,
			  'remove_support_box'              => true
			) );
		endif;

		// Add RSS feed links to <head> for posts and comments.
		add_theme_support('automatic-feed-links');

		// title tag
		add_theme_support('title-tag');

		// register image sizes
		add_image_size('alone-image-large', 1228, 691, true);
		add_image_size('alone-image-medium', 614, 346, true);
		add_image_size('alone-image-small', 295, 166, true);

		add_image_size('alone-image-square-800', 800, 800, true); // 1:1
		add_image_size('alone-image-square-300', 300, 300, true); // 1:1

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support('html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption'
		));

		/*
		 * Enable support for Post Formats.
		 * See http://codex.wordpress.org/Post_Formats
		 */
		add_theme_support('post-formats', array(
			'aside',
			'image',
			'video',
			'audio',
			'quote',
			'link',
			'gallery',
		));

		// Add support for featured content.
		add_theme_support('featured-content', array(
			'featured_content_filter' => 'alone_get_featured_posts',
			'max_posts' => 6,
		));

		// This theme uses its own gallery styles.
		add_filter('use_default_gallery_style', '__return_false');

		// Theme support woocommerce plugin
		add_theme_support('woocommerce');

		// START - fix theme check
		// add_theme_support( "custom-header", array() );
		// add_theme_support( "custom-background", array() );
		// END - fix theme check

		// Add excerpt support to portfolio
		add_post_type_support('fw-portfolio', 'excerpt');
		add_post_type_support('fw-portfolio', 'comments');

		// Add excerpt support to event post
		add_post_type_support('fw-event', 'excerpt');

		// Add favicon
		add_theme_support('favicon');

	}
endif;
add_action('after_setup_theme', '_alone_action_theme_setup');

if (!function_exists('_alone_action_theme_includes_additional_option_types')) :
	/**
	 * Include addition option types
	 */
	function _alone_action_theme_includes_additional_option_types()
	{
		$alone_template_directory = get_template_directory();

		load_template($alone_template_directory . '/theme-includes/includes/option-types/color-palette/class-fw-color-palette-new.php');
		load_template($alone_template_directory . '/theme-includes/includes/option-types/tf-animation/class-fw-option-type-tf-animation.php');
		load_template($alone_template_directory . '/theme-includes/includes/option-types/tf-typography/class-fw-option-type-tf-typography.php');
	}

	add_action('fw_option_types_init', '_alone_action_theme_includes_additional_option_types');
endif;

function _alone_init() {
	// Enable support for Post Thumbnails
	add_theme_support( 'post-thumbnails' );

	// Remove page thumbnail support
	remove_post_type_support('page', 'thumbnail');

	// check Vc exist
	if(class_exists('Vc_Manager')) : _alone_js_composer_init_hook(); endif;

	// add_filter( 'http_request_args', '_bearsthemes_deny_theme_updates', 5, 2 );

	if (class_exists('Give')) : _alone_give_init_hook(); endif;

	$_FW = defined( 'FW' );
	if($_FW) fw()->backend->option_type('icon-v2')->packs_loader->enqueue_frontend_css();
}
add_action( 'init', '_alone_init' );

{
	/**
	 * Give plugin donation handle
	 * - Single give
	 * 	+ give_default_wrapper_start, give_default_wrapper_end
	 * 	+ give_default_wrapper_start, give_default_wrapper_end
	 * 	+ give_after_single_form_summary
	 * 	+ remove 'give_get_forms_sidebar' on 'give_before_single_form_summary'
	 *	+ add post per page for give archive
	 */
	if(! function_exists('_alone_give_init_hook')) :
		function _alone_give_init_hook() {
			/* single */
			add_filter('give_default_wrapper_start', '_alone_give_default_wrapper_start');
			add_filter('give_default_wrapper_end', '_alone_give_default_wrapper_end');

			add_filter('give_left_sidebar_pre_wrap', '_alone_give_left_sidebar_pre_wrap');
			add_filter('give_left_sidebar_post_wrap', '_alone_give_left_sidebar_post_wrap');

			add_action('give_before_single_form_summary', '_alone_give_before_single_form_summary', 2);
			add_action('give_after_single_form_summary', '_alone_give_load_sidebar', 10);

			add_action('pre_get_posts', '_alone_give_set_posts_per_page', 10);

			/* _alone_action_give_single_form_summary */
			// add_action( '_alone_action_give_single_form_summary', '_alone_give_get_donation_form', 10 );
			add_action( '_alone_action_give_single_form_summary', 'give_get_donation_form', 10 );
		}
	endif;

	if(! function_exists('_alone_give_get_donation_form')) :
		/**
		 * Get Donation Form.
		 *
		 * @since  1.0
		 *
		 * @param  array $args An array of form arguments.
		 *
		 * @return string Donation form.
		 */
		function _alone_give_get_donation_form( $args = array() ) {

			global $post;

			$form_id = is_object( $post ) ? $post->ID : 0;

			if ( isset( $args['id'] ) ) {
				$form_id = $args['id'];
			}

			$defaults = apply_filters( 'give_form_args_defaults', array(
				'form_id' => $form_id,
			) );

			$args = wp_parse_args( $args, $defaults );

			$form = new Give_Donate_Form( $args['form_id'] );

			//bail if no form ID.
			if ( empty( $form->ID ) ) {
				return false;
			}

			$payment_mode = give_get_chosen_gateway( $form->ID );

			$form_action = add_query_arg( apply_filters( 'give_form_action_args', array(
				'payment-mode' => $payment_mode,
			) ),
				give_get_current_page_url()
			);

			//Sanity Check: Donation form not published or user doesn't have permission to view drafts.
			if (
				( 'publish' !== $form->post_status && ! current_user_can( 'edit_give_forms', $form->ID ) )
				|| ( 'trash' === $form->post_status )
			) {
				return false;
			}

			//Get the form wrap CSS classes.
			$form_wrap_classes = $form->get_form_wrap_classes( $args );

			//Get the <form> tag wrap CSS classes.
			$form_classes = $form->get_form_classes( $args );

			ob_start();

			/**
			 * Fires while outputting donation form, before the form wrapper div.
			 *
			 * @since 1.0
			 *
			 * @param int   $form_id The form ID.
			 * @param array $args    An array of form arguments.
			 */
			do_action( 'give_pre_form_output', $form->ID, $args );

			?>
		    <div id="give-form-<?php echo $form->ID; ?>-wrap" class="<?php echo $form_wrap_classes; ?>">

				<?php if ( $form->is_close_donation_form() ) {

					// Get Goal thank you message.
					$goal_achieved_message = get_post_meta( $form->ID, '_give_form_goal_achieved_message', true );
					$goal_achieved_message = ! empty( $goal_achieved_message ) ? apply_filters( 'the_content', $goal_achieved_message ) : '';

					// Print thank you message.
					echo apply_filters( 'give_goal_closed_output', $goal_achieved_message, $form->ID );

				} else {
					/**
					 * Show form title:
					 * 1. if show_title params set to true
					 * 2. if admin set form display_style to button
					 */
					$form_title = apply_filters( 'give_form_title', '<h2 class="give-form-title">' . get_the_title( $form_id ) . '</h2>' );
					if (
						( isset( $args['show_title'] ) && $args['show_title'] == true )
						&& ! doing_action( 'give_single_form_summary' )
					) {
						echo $form_title;
					}

					/**
					 * Fires while outputing donation form, before the form.
					 *
					 * @since 1.0
					 *
					 * @param int   $form_id The form ID.
					 * @param array $args    An array of form arguments.
					 */
					 $args['show_goal'] = false;
					do_action( 'give_pre_form', $form->ID, $args );
					?>

		            <form id="give-form-<?php echo $form_id; ?>" class="<?php echo $form_classes; ?>"
		                  action="<?php echo esc_url_raw( $form_action ); ?>" method="post">
		                <input type="hidden" name="give-form-id" value="<?php echo $form->ID; ?>"/>
		                <input type="hidden" name="give-form-title" value="<?php echo htmlentities( $form->post_title ); ?>"/>
		                <input type="hidden" name="give-current-url"
		                       value="<?php echo htmlspecialchars( give_get_current_page_url() ); ?>"/>
		                <input type="hidden" name="give-form-url"
		                       value="<?php echo htmlspecialchars( give_get_current_page_url() ); ?>"/>
		                <input type="hidden" name="give-form-minimum"
		                       value="<?php echo give_format_amount( give_get_form_minimum_price( $form->ID ) ); ?>"/>

		                <!-- The following field is for robots only, invisible to humans: -->
		                <span class="give-hidden" style="display: none !important;">
							<label for="give-form-honeypot-<?php echo $form_id; ?>"></label>
							<input id="give-form-honeypot-<?php echo $form_id; ?>" type="text" name="give-honeypot"
		                           class="give-honeypot give-hidden"/>
						</span>

						<?php

						// Price ID hidden field for variable (mult-level) donation forms.
						if ( give_has_variable_prices( $form_id ) ) {
							// Get default selected price ID.
							$prices   = apply_filters( 'give_form_variable_prices', give_get_variable_prices( $form_id ), $form_id );
							$price_id = 0;
							//loop through prices.
							foreach ( $prices as $price ) {
								if ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) {
									$price_id = $price['_give_id']['level_id'];
								};
							}
							?>
		                    <input type="hidden" name="give-price-id" value="<?php echo $price_id; ?>"/>
						<?php }

						/**
						 * Fires while outputting donation form, before all other fields.
						 *
						 * @since 1.0
						 *
						 * @param int   $form_id The form ID.
						 * @param array $args    An array of form arguments.
						 */
						do_action( 'give_checkout_form_top', $form->ID, $args );

						/**
						 * Fires while outputing donation form, for payment gatways fields.
						 *
						 * @since 1.7
						 *
						 * @param int   $form_id The form ID.
						 * @param array $args    An array of form arguments.
						 */
						do_action( 'give_payment_mode_select', $form->ID, $args );

						/**
						 * Fires while outputing donation form, after all other fields.
						 *
						 * @since 1.0
						 *
						 * @param int   $form_id The form ID.
						 * @param array $args    An array of form arguments.
						 */
						do_action( 'give_checkout_form_bottom', $form->ID, $args );

						?>
		            </form>

					<?php
					/**
					 * Fires while outputing donation form, after the form.
					 *
					 * @since 1.0
					 *
					 * @param int   $form_id The form ID.
					 * @param array $args    An array of form arguments.
					 */
					do_action( 'give_post_form', $form->ID, $args );

				}
				?>

		    </div><!--end #give-form-<?php echo absint( $form->ID ); ?>-->
			<?php

			/**
			 * Fires while outputing donation form, after the form wapper div.
			 *
			 * @since 1.0
			 *
			 * @param int   $form_id The form ID.
			 * @param array $args    An array of form arguments.
			 */
			do_action( 'give_post_form_output', $form->ID, $args );

			$final_output = ob_get_clean();

			echo apply_filters( 'give_donate_form', $final_output, $args );
		}
	endif;

	if(! function_exists('_alone_give_set_posts_per_page')) :
		function _alone_give_set_posts_per_page( $query ) {
			$_FW = defined( 'FW' );
			if($_FW) {
				if(is_post_type_archive('give_forms') && fw_akg('query_vars/post_type', $query) == 'give_forms') {
					$give_posts_per_page = fw_get_db_customizer_option('give_settings/give_archive/number_form_per_page', 9);
					// echo '<pre>'; print_r($query); echo '</pre>';
					$query->set('posts_per_page', $give_posts_per_page);
				}
			}
		}
	endif;

	if(! function_exists('_alone_give_before_single_form_summary')) :
		function _alone_give_before_single_form_summary() {
			remove_action('give_before_single_form_summary', 'give_get_forms_sidebar', 20);
		}
	endif;

	/**
	 * _alone_give_load_sidebar
	 */
	if(! function_exists('_alone_give_load_sidebar')) :
		function _alone_give_load_sidebar() {
			if ( give_is_setting_enabled( give_get_option( 'form_sidebar' ) ) ) {
				echo '<div class="give-sidebar-wrap">';
				give_get_template_part( 'single-give-form/sidebar' );
				echo '</div>';
			}
		}
	endif;

	/**
	 * _alone_give_default_wrapper_start
	 */
	if(! function_exists('_alone_give_default_wrapper_start')) :
		function _alone_give_default_wrapper_start($output) {
			return '';
		}
	endif;

	/**
	 * _alone_give_default_wrapper_start
	 */
	if(! function_exists('_alone_give_default_wrapper_end')) :
		function _alone_give_default_wrapper_end($output) {
			return '';
		}
	endif;

	/**
	 * _alone_give_left_sidebar_pre_wrap
	 */
	if ( ! function_exists( '_alone_give_left_sidebar_pre_wrap' ) ) {
		function _alone_give_left_sidebar_pre_wrap($output) {
			return '<div id="single-give-thubnail" class="single-give-thubnail">';
		}
	}

	/**
	 * _alone_give_left_sidebar_post_wrap
	 */
	if ( ! function_exists( '_alone_give_left_sidebar_post_wrap' ) ) {
		function _alone_give_left_sidebar_post_wrap($output) {
			return '</div>';
		}
	}
}


if(! function_exists('_bearsthemes_filter_fdm_styles')) :
	function _bearsthemes_filter_fdm_styles($style) {
		// print_r($style); die;
		$style['custom'] = new fdmStyle(
			array(
				'id'	=> 'custom_theme',
				'label'	=> __( 'Custom Style (themes)', 'alone' ),
				'css'	=> array(
					'base' => FDM_PLUGIN_URL . '/assets/css/base.css',
				)
			)
		);

		$style['custom_2'] = new fdmStyle(
			array(
				'id'	=> 'custom_thumb_round_theme',
				'label'	=> __( 'Custom Thumb Round Style (themes)', 'alone' ),
				'css'	=> array(
					'base' => FDM_PLUGIN_URL . '/assets/css/base.css',
				)
			)
		);

		return $style;
	}
endif;
// fdmFoodAndDrinkMenu
add_filter('fdm_styles', '_bearsthemes_filter_fdm_styles');

if(! function_exists('_bearsthemes_deny_theme_updates')) :
	function _bearsthemes_deny_theme_updates( $r, $url )
	{
		// echo $url;
		if ( 0 !== strpos( $url, 'https://api.wordpress.org/themes/update-check/' ) ) return $r;

		$themes = json_decode( $r['body']['themes'], true );
		$theme_active = trim($themes['active']);
		unset( $themes['themes'][$theme_active] );

		$r['body']['themes'] = json_encode( $themes );
		return $r;
	}
endif;

if( ! function_exists( '_alone_js_composer_init_hook' ) ) :
	function _alone_js_composer_init_hook() {
		add_filter( 'bearsthemes_visual_builder_temp_before_content', '_bearsthemes_add_element_container_open' );
		add_filter( 'bearsthemes_visual_builder_temp_after_content', '_bearsthemes_add_element_container_close' );
	}
endif;

if( ! function_exists( '_bearsthemes_add_element_container_open' ) ) :
		function _bearsthemes_add_element_container_open() {
			return '<div class="container">';
		}
endif;

if( ! function_exists( '_bearsthemes_add_element_container_close' ) ) :
		function _bearsthemes_add_element_container_close() {
			return '</div>';
		}
endif;

if ( ! function_exists( '_alone_google_fonts_url' ) ) :
/**
 * Register Google fonts.
 *
 * @return string Google fonts URL for the theme.
 */
function _alone_google_fonts_url() {
    $fonts_url = '';
    $fonts     = array();
    $subsets   = 'latin,latin-ext';

    /* translators: If there are characters in your language that are not supported by this font, translate this to 'off'. Do not translate into your own language. */
    if ( 'off' !== esc_html_x( 'on', 'Raleway font: on or off', 'alone' ) ) {
        $fonts[] = 'Raleway:300,400,400i,700,700i';
    }

    /* translators: If there are characters in your language that are not supported by this font, translate this to 'off'. Do not translate into your own language. */
    /* if ( 'off' !== esc_html_x( 'on', 'Lato font: on or off', 'textdomain' ) ) {
        $fonts[] = 'Lato';
    } */

    if ( $fonts ) {
        $fonts_url = add_query_arg( array(
            'family' => urlencode( implode( '|', $fonts ) ),
            'subset' => urlencode( $subsets ),
        ), 'https://fonts.googleapis.com/css' );
    }

    return $fonts_url;
}
endif;

if(! function_exists('_alone_admin_enqueue_scripts')) :
	function _alone_admin_enqueue_scripts() {
		wp_enqueue_style( 'alone-google-font-backend', _alone_google_fonts_url(), false, '1.0.0' );
	}
endif;
add_action( 'admin_enqueue_scripts', '_alone_admin_enqueue_scripts' );

if(! function_exists('_alone_woocommerce_init_hook')) :
	function _alone_woocommerce_init_hook() {
		// woo 3.x
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		// remove woocommerce breadcrumb
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );

		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
		add_action( 'woocommerce_before_shop_loop_item_title', '_alone_woocommerce_template_loop_product_thumbnail', 10);

		//remove link wrap product loop
	  remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	  remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

		// wrap link -> title
		add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_link_open', 10 );
		add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_product_link_close', 5 );
		add_action( 'woocommerce_after_shop_loop_item_title', '_bearsthemes_woocommerce_get_taxonomy_loop', 10 );


	  remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

		add_action( 'bearsthemes_woocommerce_after_thumbnail_loop', 'woocommerce_template_loop_add_to_cart', 10 );
		if(function_exists('YITH_WCQV_Frontend')) :
			# check YITH_WCQV_Frontend exist
		  remove_action( 'woocommerce_after_shop_loop_item', array(YITH_WCQV_Frontend(), 'yith_add_quick_view_button'), 15 );
			add_action( 'bearsthemes_woocommerce_after_thumbnail_loop', array(YITH_WCQV_Frontend(), 'yith_add_quick_view_button'), 10 );
		endif;
		add_action( 'bearsthemes_woocommerce_after_thumbnail_loop', '_bearsthemes_yith_add_compare_button', 10 );
	}
endif;
add_action( 'init', '_alone_woocommerce_init_hook' );

if(! function_exists('_bearsthemes_yith_add_compare_button')) :
	function _bearsthemes_yith_add_compare_button() {
		if ( get_option('yith_woocompare_compare_button_in_products_list') == 'yes' ){
			echo do_shortcode('[yith_compare_button container="no"]');
		}
	}
endif;

if(! function_exists('_bearsthemes_woocommerce_get_taxonomy_loop')) :
	function _bearsthemes_woocommerce_get_taxonomy_loop() {
		global $post;
		$term_str = get_the_term_list($post->ID, 'product_cat', '', ', ');
		if(empty($term_str)) return;

		echo '<div class="woocommerce-taxonomy-loop">' . $term_str . '</div>';
	}
endif;

/**
 * WooCommerce Loop Product Thumbs
 **/
if ( ! function_exists( '_alone_woocommerce_template_loop_product_thumbnail' ) ) {
	function _alone_woocommerce_template_loop_product_thumbnail() {
		echo alone_woocommerce_get_product_thumbnail();
	}
}

if (!function_exists('_alone_action_widgets_init')) :
	/**
	 * Register widget areas
	 * @internal
	 */
	function _alone_action_widgets_init()
	{
		$beforeWidget = '<aside id="%1$s" class="widget %2$s">';
		$afterWidget = '</aside>';
		$beforeTitle = '<h2 class="widget-title"><span>';
		$afterTitle = '</span></h2>';
		register_sidebar(array(
			'name' => esc_html__('General Widget Area', 'alone'),
			'id' => 'sidebar-1',
			'description' => '',
			'before_widget' => $beforeWidget,
			'after_widget' => $afterWidget,
			'before_title' => $beforeTitle,
			'after_title' => $afterTitle,
		));
	}
endif;
add_action('widgets_init', '_alone_action_widgets_init');

if(! function_exists('_alone_scroll_to_top_button')) :
	function _alone_scroll_to_top_button() {
		$enable_scroll_to = (function_exists('fw_get_db_customizer_option')) ? fw_get_db_customizer_option('enable_scroll_to') : 'no';
	  echo ($enable_scroll_to == 'yes') ? '<a id="scroll-to-top-button" href="#" title="'. __('Back to top', 'alone') .'"><span></span></a>' : '';
	}
endif;
add_action( 'wp_footer', '_alone_scroll_to_top_button', 20 );

if(! function_exists('_alone_add_extra_meta_tag')) :
	function _alone_add_extra_meta_tag() {
		global $alone_color_settings;
		$theme_color = (is_array($alone_color_settings) && isset($alone_color_settings['color_1'])) ? $alone_color_settings['color_1'] : '#333';
		?>
		<!-- Chrome, Firefox OS and Opera -->
		<meta name="theme-color" content="<?php echo $theme_color; ?>">
		<!-- Windows Phone -->
		<meta name="msapplication-navbutton-color" content="<?php echo $theme_color; ?>">
		<!-- iOS Safari -->
		<meta name="apple-mobile-web-app-status-bar-style" content="<?php echo $theme_color; ?>">
		<?php

		if( is_single() ) {
			global $post;
			$meta_data = array(
				'{title}'				=> get_the_title($post->ID),
				'{description}' => get_the_excerpt($post->ID),
				'{keywords}' 		=> get_the_title($post->ID),
				'{image}'				=> get_the_post_thumbnail_url($post->ID, 'large'),
				'{url}'					=> get_permalink($post->ID),
			);

			$meta_output = '
			<!-- Google -->
			<meta name="name" content="{title}" />
			<meta name="description" content="{description}" />
			<meta name="image" content="{url}" />
			<!-- Facebook -->
			<meta property="og:title" content="{title}" />
			<meta property="og:type" content="article" />
			<meta property="og:image" content="{image}" />
			<meta property="og:url" content="{url}" />
			<meta property="og:description" content="{description}" />
			<!-- Twitter -->
			<meta name="twitter:card" content="summary" />
			<meta name="twitter:title" content="{title}" />
			<meta name="twitter:description" content="{description}" />
			<meta name="twitter:image" content="{url}" />';

			echo str_replace(array_keys($meta_data), array_values($meta_data), $meta_output);
		}
  }
	add_action( 'wp_head', '_alone_add_extra_meta_tag' );
endif;

if(!function_exists('_alone_action_theme_generate_styles')) :
	/**
 	 * Scss handle
 	 * Mode Developer TRUE: SCSS always running
 	 * Mode Developer FALSE: SCSS run when admin change theme settings
 	 */
	function _alone_action_theme_generate_styles() {
		global $alone_scss_variables;
		$alone_scss_variables = array();
		$quick_css = '';
		$scss_content = '';
		$scss_extra_string = '';
		$last_md5_scss = get_option('alone_less_option_hash', '');

		if(defined('FW')) {

			fw_render_view( get_template_directory().'/theme-includes/styling.php', array(), false );
			$alone_scss_content = alone_scss_variables_handle($alone_scss_variables);


			if (function_exists('fw_get_db_settings_option')) {
				$quick_css = fw_get_db_settings_option('quick_css');
				$scss_extra_string .= str_replace(array('{accent-color}', '{secondary-color}'), array('$theme-color-1', '$theme-color-2'), $quick_css);
			}

			/* extra font */
			$scss_extra_string .= alone_get_extra_typography('build_class_css');

			if(WP_DEBUG) {
				/**
				 * On debug scss always running
				 */
				$scss_content = implode('; ', $alone_scss_content);
				alone_scss_handle($scss_content . ';' . $scss_extra_string);
			}else {
				$md5_scss_variables = md5(serialize($alone_scss_content));

				if($last_md5_scss != $md5_scss_variables) {

					update_option('alone_less_option_hash', $md5_scss_variables);
					$scss_content = implode('; ', $alone_scss_content);
					// alone_scss_handle($scss_content);
					alone_scss_handle($scss_content . ';' . $scss_extra_string);
				}
			}
		}
	}
endif;
add_action('wp_enqueue_scripts', '_alone_action_theme_generate_styles', 99);

if (!function_exists('_alone_action_print_google_fonts_link')) :
	/**
	 * Print google fonts link
	 */
	function _alone_action_print_google_fonts_link()
	{
		global $post, $google_fonts_list, $post_google_fonts_list;
		$google_fonts_list = $post_google_fonts_list = array();
		// get general font list
		$fw_theme_google_fonts_list = get_option('fw_theme_google_fonts_list', array());

		// get font for specific post or term
		if (!is_singular() && function_exists('get_term_meta')) {
			if (is_category()) {
				$term = get_category(get_query_var('cat'), false);
			} else {
				$term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
			}
			if (isset($term->term_id)) {
				$term_id = $term->term_id;
				$fw_theme_post_google_fonts = get_term_meta($term_id, 'fw_theme_post_google_fonts', true);
				if (!empty($fw_theme_post_google_fonts)) {
					$fw_theme_google_fonts_list = alone_array_merge_recursive($fw_theme_google_fonts_list, $fw_theme_post_google_fonts);
				}
			}
		} else {
			if (isset($post->ID)) {
				$fw_theme_post_google_fonts = get_post_meta($post->ID, 'fw_theme_post_google_fonts', true);
				if (!empty($fw_theme_post_google_fonts)) {
					$fw_theme_google_fonts_list = alone_array_merge_recursive($fw_theme_google_fonts_list, $fw_theme_post_google_fonts);
				}
			}
		}
		// echo $post->ID;
		// echo '<pre>'; print_r($fw_theme_google_fonts_list); echo '</pre>';

		$off_googlefont = get_post_meta($post->ID, 'off-googlefont', true);
		if (! empty($fw_theme_google_fonts_list) && $off_googlefont !== 'true') {
			wp_register_style('fw-googleFonts', alone_get_remote_fonts($fw_theme_google_fonts_list));
			wp_enqueue_style('fw-googleFonts');
		}
	}
endif;
add_action('wp_print_styles', '_alone_action_print_google_fonts_link');

if (!function_exists('_alone_action_set_global_colors')) :
	/**
	 * Set global colors
	 */
	function _alone_action_set_global_colors()
	{
		global $alone_color_settings;
		$alone_colors = array(
			'color_1' => '#88C000',
			'color_2' => '#ACF300',
			'color_3' => '#1f1f1f',
			'color_4' => '#808080',
			'color_5' => '#ebebeb'
		);
		$alone_color_settings = function_exists('fw_get_db_customizer_option') ? fw_get_db_customizer_option('color_settings', $alone_colors) : $alone_colors;
	}
endif;
add_action('init', '_alone_action_set_global_colors');

if (!function_exists('_alone_action_dashboard_palette_colors')) :
	/**
	 * Dashboard color palette styles
	 */
	function _alone_action_dashboard_palette_colors()
	{
		global $alone_color_settings;
		echo '<style>
			.fw_theme_bg_color_1{ background-color: ' . $alone_color_settings['color_1'] . '; }
			.fw_theme_bg_color_2{ background-color: ' . $alone_color_settings['color_2'] . '; }
			.fw_theme_bg_color_3{ background-color: ' . $alone_color_settings['color_3'] . '; }
			.fw_theme_bg_color_4{ background-color: ' . $alone_color_settings['color_4'] . '; }
			.fw_theme_bg_color_5{ background-color: ' . $alone_color_settings['color_5'] . '; }
		</style>';
	}
endif;
add_action('admin_head', '_alone_action_dashboard_palette_colors');

if (!function_exists('_alone_action_count_post_visits')) :
	/**
	 * Count post visits
	 */
	function _alone_action_count_post_visits()
	{
		if (!is_single()) {
			return;
		}
		global $post;
		$views = get_post_meta($post->ID, 'fw_post_views', true);
		$views = intval($views);
		update_post_meta($post->ID, 'fw_post_views', ++$views);
	}
endif;
add_action('wp_head', '_alone_action_count_post_visits');

if (!function_exists('_alone_filter_body_class')) :
	/**
	 * Filter for add space/top-bar/absolute-header class
	 *
	 * @param array $classes
	 */
	function _alone_filter_body_class($classes) {
		global $coming_soon;

		if (function_exists('fw_get_db_settings_option')) {
			$alone_general_settings = defined('FW') ? fw_get_db_customizer_option() : array();

			if (isset($alone_general_settings['container_site_type']['selected'])) {
				if (!$coming_soon) {
					if(isset($_GET['container_site_type']) && ! empty($_GET['container_site_type'])) {
						$classes[] = $_GET['container_site_type'];
					}else{
						$classes[] = $alone_general_settings['container_site_type']['selected'];
					}
				}
			}

		}

		return $classes;
	}
	add_filter('body_class', '_alone_filter_body_class');
endif;

if( !function_exists('_alone_action_theme_includes_tgm') ) :
	/**
	 * Include TGM
	 */
	function _alone_action_theme_includes_tgm() {
		$theme_id = defined('FW') ? fw()->theme->manifest->get_id() : 'alone';
		$option_auto_setup = get_option('tfuse' . '_' . $theme_id . '_auto_install_state', array() );

		// if option auto setup have the "auto-setup-step-choosed" set to "skip"
		if( isset( $option_auto_setup['steps']['auto-setup-step-choosed'] ) && $option_auto_setup['steps']['auto-setup-step-choosed'] == 'skip' ) {
			// load tgm if user skip the auto-setup
			load_template( get_template_directory() . '/theme-includes/register-required-plugins.php' );
		}
	}
	add_action('after_setup_theme', '_alone_action_theme_includes_tgm');
endif;

if (!function_exists('_alone_action_shortcode_section_enqueue_dynamic_css')):
	/**
	 * @internal
	 *
	 * @param array $data
	 */
	function _alone_action_shortcode_section_enqueue_dynamic_css($data) {
		$shortcode = 'section';
		$atts = shortcode_parse_atts($data['atts_string']);

		/**
		 * Decode attributes
		 * ( The below weird code is because of this https://github.com/ThemeFuse/Unyson/issues/469 )
		 */
		$atts = fw_ext_shortcodes_decode_attr($atts, $shortcode, $data['post']->ID);
		$final_styles = '';
		if( isset($atts['background_options']['background']) && $atts['background_options']['background'] == 'gradient_color' ) {
			// gradient styling
			if( !empty($atts['background_options']['gradient_color']['gradient_bg_color']['primary']) ) {
				$gradient_orientation = $filter = '';
				$gradient = 'linear-gradient';
				$gradient_type = '1';
				if( $atts['background_options']['gradient_color']['gradient_orientation'] == 'horizontal' ) {
					$gradient_orientation = 'left';
					$filter = 'to right';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'vertical' ) {
					$gradient_orientation = 'top';
					$filter = 'to bottom';
					$gradient_type = '0';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'diagonal' ) {
					$gradient_orientation = '-45deg';
					$filter = '135de';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'diagonal_bottom' ) {
					$gradient_orientation = '45deg';
					$filter = '45deg';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'radial' ) {
					$gradient = 'radial-gradient';
					$gradient_orientation = 'center, ellipse cover';
					$filter = 'ellipse at center';
				}

				$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' {
					background: '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].';
					background: -moz-'.$gradient.'('.$gradient_orientation.', '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].' 0%, '.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].' 100%);
					background: -webkit-'.$gradient.'('.$gradient_orientation.', '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].' 0%,'.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].' 100%);
					background: '.$gradient.'('.$filter.', '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].' 0%,'.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].' 100%);
					filter: progid:DXImageTransform.Microsoft.gradient( startColorstr="'.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].'", endColorstr="'.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].'",GradientType='.$gradient_type.' );
				}';
			}
		}

		if (empty($final_styles)) {
			return;
		}

		wp_add_inline_style( 'fw-theme-style', $final_styles );
	}
	add_action( 'fw_ext_shortcodes_enqueue_static:section', '_alone_action_shortcode_section_enqueue_dynamic_css' );
endif;

if (!function_exists('_alone_action_shortcode_column_enqueue_dynamic_css')):
	/**
	 * @internal
	 *
	 * @param array $data
	 */
	function _alone_action_shortcode_column_enqueue_dynamic_css($data)
	{
		$shortcode = 'column';
		$atts = shortcode_parse_atts($data['atts_string']);

		/**
		 * Decode attributes
		 * ( The below weird code is because of this https://github.com/ThemeFuse/Unyson/issues/469 )
		 */
		$atts = fw_ext_shortcodes_decode_attr($atts, $shortcode, $data['post']->ID);

		$final_styles = $bg_image = $bg_color = '';
		// additional spacing
		$atts['padding_top'] = (int)$atts['padding_top'];
		$atts['padding_right'] = (int)$atts['padding_right'];
		$atts['padding_bottom'] = (int)$atts['padding_bottom'];
		$atts['padding_left'] = (int)$atts['padding_left'];
		if ($atts['padding_top'] != 0 || $atts['padding_right'] != 0 || $atts['padding_bottom'] != 0 || $atts['padding_left'] != 0) {
			$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' .fw-col-inner{padding: ' . $atts['padding_top'] . 'px ' . $atts['padding_right'] . 'px ' . $atts['padding_bottom'] . 'px ' . $atts['padding_left'] . 'px;}';
		}
		// background
		if (isset($atts['background_options']['background']) && $atts['background_options']['background'] == 'image' && !empty($atts['background_options']['image']['background_image']['data'])) {
			$bg_image = 'background-image:url(' . $atts['background_options']['image']['background_image']['data']['icon'] . ');';
			$bg_image .= ' background-repeat: ' . $atts['background_options']['image']['repeat'] . ';';
			$bg_image .= ' background-position: ' . $atts['background_options']['image']['bg_position_x'] . ' ' . $atts['background_options']['image']['bg_position_y'] . ';';
			$bg_image .= ' background-size: ' . $atts['background_options']['image']['bg_size'] . ';';

			$bg = alone_get_color_palette_color_and_class($atts['background_options']['image']['background_color'], array('return_color' => true));
			if (!empty($bg['color'])) {
				$bg_color = 'background-color:' . $bg['color'] . ';';
			}
			$final_styles .= '.tf-sh-' . $atts['unique_id'] . '{' . $bg_image . $bg_color . '}';

			// overlay and opacity
			$type = $atts['background_options']['background'];
			$overlay = $atts['background_options'][$type]['overlay_options']['overlay'];
			if ($overlay == 'yes') {
				$alone_opacity_param = 'overlay_opacity_' . $atts['background_options']['background'];
				$alone_opacity = $atts['background_options'][$type]['overlay_options']['yes'][$alone_opacity_param] / 100;

				$overlay_color = alone_get_color_palette_color_and_class($atts['background_options'][$type]['overlay_options']['yes']['background'], array('return_color' => true));
				if (!empty($overlay_color['color'])) {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' .fw-main-row-overlay {background-color: ' . $overlay_color['color'] . '; opacity: ' . $alone_opacity . ';}';
				}
			}
		} elseif (isset($atts['background_options']['background']) && $atts['background_options']['background'] == 'color') {
			$bg = alone_get_color_palette_color_and_class($atts['background_options']['color']['background_color'], array('return_color' => true));
			if (!empty($bg['color'])) {
				$final_styles .= '.tf-sh-' . $atts['unique_id'] . '{background-color:' . $bg['color'] . ';}';
			}
		}
		elseif( isset($atts['background_options']['background']) && $atts['background_options']['background'] == 'gradient_color' ) {
			// gradient styling
			if( !empty($atts['background_options']['gradient_color']['gradient_bg_color']['primary']) ) {
				$gradient_orientation = $filter = '';
				$gradient = 'linear-gradient';
				$gradient_type = '1';
				if( $atts['background_options']['gradient_color']['gradient_orientation'] == 'horizontal' ) {
					$gradient_orientation = 'left';
					$filter = 'to right';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'vertical' ) {
					$gradient_orientation = 'top';
					$filter = 'to bottom';
					$gradient_type = '0';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'diagonal' ) {
					$gradient_orientation = '-45deg';
					$filter = '135de';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'diagonal_bottom' ) {
					$gradient_orientation = '45deg';
					$filter = '45deg';
				}
				elseif( $atts['background_options']['gradient_color']['gradient_orientation'] == 'radial' ) {
					$gradient = 'radial-gradient';
					$gradient_orientation = 'center, ellipse cover';
					$filter = 'ellipse at center';
				}

				$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' {
					background: '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].';
					background: -moz-'.$gradient.'('.$gradient_orientation.', '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].' 0%, '.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].' 100%);
					background: -webkit-'.$gradient.'('.$gradient_orientation.', '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].' 0%,'.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].' 100%);
					background: '.$gradient.'('.$filter.', '.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].' 0%,'.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].' 100%);
					filter: progid:DXImageTransform.Microsoft.gradient( startColorstr="'.$atts['background_options']['gradient_color']['gradient_bg_color']['primary'].'", endColorstr="'.$atts['background_options']['gradient_color']['gradient_bg_color']['secondary'].'",GradientType='.$gradient_type.' );
				}';
			}
		}

		// shadow settings
		if (isset($atts['shadow_group'])) {
			if ($atts['shadow_group']['selected'] == 'yes') {
				$shadow_horizontal = (int)esc_attr($atts['shadow_group']['yes']['shadow_horiontal']) . 'px';
				$shadow_vertical = (int)esc_attr($atts['shadow_group']['yes']['shadow_vertical']) . 'px';
				$shadow_blur = (int)esc_attr($atts['shadow_group']['yes']['shadow_blur']) . 'px';
				$shadow_size = (int)esc_attr($atts['shadow_group']['yes']['shadow_size']) . 'px';
				$shadow_color = $atts['shadow_group']['yes']['shadow_color'];

				if ($atts['background_options']['background'] != 'none') {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' {
						-webkit-box-shadow: ' . $shadow_horizontal . ' ' . $shadow_vertical . ' ' . $shadow_blur . ' ' . $shadow_size . ' ' . $shadow_color . ';
						-moz-box-shadow: ' . $shadow_horizontal . ' ' . $shadow_vertical . ' ' . $shadow_blur . ' ' . $shadow_size . ' ' . $shadow_color . ';
						box-shadow: ' . $shadow_horizontal . ' ' . $shadow_vertical . ' ' . $shadow_blur . ' ' . $shadow_size . ' ' . $shadow_color . ';
					}';
				} else {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' .fw-col-inner{
						-webkit-box-shadow: ' . $shadow_horizontal . ' ' . $shadow_vertical . ' ' . $shadow_blur . ' ' . $shadow_size . ' ' . $shadow_color . ';
						-moz-box-shadow: ' . $shadow_horizontal . ' ' . $shadow_vertical . ' ' . $shadow_blur . ' ' . $shadow_size . ' ' . $shadow_color . ';
						box-shadow: ' . $shadow_horizontal . ' ' . $shadow_vertical . ' ' . $shadow_blur . ' ' . $shadow_size . ' ' . $shadow_color . ';
					}';
				}
			} else {
				if ($atts['background_options']['background'] != 'none') {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' { box-shadow: none; }';
				} else {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . ' .fw-col-inner{ box-shadow: none; }';
				}
			}
		}

		// for responsive settings
		if (isset($atts['responsive'])) {
			// tablet landscape
			if (isset($atts['responsive']['tablet_landscape_display']['selected']) && $atts['responsive']['tablet_landscape_display']['selected'] == 'yes') {
				// additional spacing

				if ($atts['responsive']['tablet_landscape_display']['yes']['padding_top'] != '') {
					$padding_top = $atts['responsive']['tablet_landscape_display']['yes']['padding_top'];
				} else {
					$padding_top = $atts['padding_top'];
				}

				if ($atts['responsive']['tablet_landscape_display']['yes']['padding_right'] != '') {
					$padding_right = $atts['responsive']['tablet_landscape_display']['yes']['padding_right'];
				} else {
					$padding_right = $atts['padding_right'];
				}

				if ($atts['responsive']['tablet_landscape_display']['yes']['padding_bottom'] != '') {
					$padding_bottom = $atts['responsive']['tablet_landscape_display']['yes']['padding_bottom'];
				} else {
					$padding_bottom = $atts['padding_bottom'];
				}

				if ($atts['responsive']['tablet_landscape_display']['yes']['padding_left'] != '') {
					$padding_left = $atts['responsive']['tablet_landscape_display']['yes']['padding_left'];
				} else {
					$padding_left = $atts['padding_left'];
				}

				if ($padding_top != '' || $padding_right != '' || $padding_bottom != '' || $padding_left != '') {
					$final_styles .= '@media only screen and (min-width: 992px) and (max-width: 1199px) { .tf-sh-' . $atts['unique_id'] . ' .fw-col-inner{padding: ' . (int)$padding_top . 'px ' . $padding_right . 'px ' . $padding_bottom . 'px ' . $padding_left . 'px;} }';
				}
			}

			// tablet portrait
			if ($atts['responsive']['tablet_display']['selected'] == 'yes') {
				// additional spacing

				if ($atts['responsive']['tablet_display']['yes']['padding_top'] != '') {
					$padding_top = $atts['responsive']['tablet_display']['yes']['padding_top'];
				} else {
					$padding_top = $atts['padding_top'];
				}

				if ($atts['responsive']['tablet_display']['yes']['padding_right'] != '') {
					$padding_right = $atts['responsive']['tablet_display']['yes']['padding_right'];
				} else {
					$padding_right = $atts['padding_right'];
				}

				if ($atts['responsive']['tablet_display']['yes']['padding_bottom'] != '') {
					$padding_bottom = $atts['responsive']['tablet_display']['yes']['padding_bottom'];
				} else {
					$padding_bottom = $atts['padding_bottom'];
				}

				if ($atts['responsive']['tablet_display']['yes']['padding_left'] != '') {
					$padding_left = $atts['responsive']['tablet_display']['yes']['padding_left'];
				} else {
					$padding_left = $atts['padding_left'];
				}

				if ($padding_top != '' || $padding_right != '' || $padding_bottom != '' || $padding_left != '') {
					$final_styles .= '@media only screen and (min-width: 768px) and (max-width: 991px) { .tf-sh-' . $atts['unique_id'] . ' .fw-col-inner{padding: ' . (int)$padding_top . 'px ' . $padding_right . 'px ' . $padding_bottom . 'px ' . $padding_left . 'px;} }';
				}
			}

			// smartphone responsive
			if ($atts['responsive']['smartphone_display']['selected'] == 'yes') {
				// additional spacing
				if ($atts['responsive']['smartphone_display']['yes']['padding_top'] != '') {
					$padding_top = $atts['responsive']['smartphone_display']['yes']['padding_top'];
				} else {
					$padding_top = $atts['padding_top'];
				}

				if ($atts['responsive']['smartphone_display']['yes']['padding_right'] != '') {
					$padding_right = $atts['responsive']['smartphone_display']['yes']['padding_right'];
				} else {
					$padding_right = $atts['padding_right'];
				}

				if ($atts['responsive']['smartphone_display']['yes']['padding_bottom'] != '') {
					$padding_bottom = $atts['responsive']['smartphone_display']['yes']['padding_bottom'];
				} else {
					$padding_bottom = $atts['padding_bottom'];
				}

				if ($atts['responsive']['smartphone_display']['yes']['padding_left'] != '') {
					$padding_left = $atts['responsive']['smartphone_display']['yes']['padding_left'];
				} else {
					$padding_left = $atts['padding_left'];
				}

				if ($padding_top != '' || $padding_right != '' || $padding_bottom != '' || $padding_left != '') {
					$final_styles .= '@media only screen and (max-width: 767px) { .tf-sh-' . $atts['unique_id'] . ' .fw-col-inner{padding: ' . $padding_top . 'px ' . $padding_right . 'px ' . $padding_bottom . 'px ' . $padding_left . 'px;} }';
				}
			}
		}

		if (empty($final_styles)) {
			return;
		}

		wp_add_inline_style(
			'fw-theme-style',
			$final_styles
		);
	}

	add_action(
		'fw_ext_shortcodes_enqueue_static:column',
		'_alone_action_shortcode_column_enqueue_dynamic_css'
	);
endif;

if (!function_exists('_alone_action_shortcode_text_block_enqueue_dynamic_css')):
	/**
	 * @internal
	 *
	 * @param array $data
	 */
	function _alone_action_shortcode_text_block_enqueue_dynamic_css($data)
	{
		$shortcode = 'text_block';
		$atts = shortcode_parse_atts($data['atts_string']);

		/**
		 * Decode attributes
		 * ( The below weird code is because of this https://github.com/ThemeFuse/Unyson/issues/469 )
		 */
		$atts = fw_ext_shortcodes_decode_attr($atts, $shortcode, $data['post']->ID);

		$final_styles = '';
		// advanced styling
		if (isset($atts['text_advanced_styling'])) {
			// text advanced styling
			$text_advanced_styling = alone_get_shortcode_advanced_styles($atts['text_advanced_styling']['text'], array('return_color' => true));
			if (!empty($text_advanced_styling['styles'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ' .fw-text-inner {' . $text_advanced_styling['styles'] . '}';
			// responsive text styling
			$responsive_styles = alone_responsive_heading_styles(array('styles' => $atts['text_advanced_styling']['text'], 'selector' => '.tf-sh-' . $atts['unique_id'] . ' .fw-text-inner'));
			if (!empty($responsive_styles)) $final_styles .= '@media(max-width:767px){' . $responsive_styles . '}';
		}

		if (empty($final_styles)) {
			return;
		}

		wp_add_inline_style(
			'fw-theme-style',
			$final_styles
		);
	}

	add_action(
		'fw_ext_shortcodes_enqueue_static:text_block',
		'_alone_action_shortcode_text_block_enqueue_dynamic_css'
	);
endif;

if (!function_exists('_alone_action_shortcode_button_enqueue_dynamic_css')) :
	/**
	 * @internal
	 *
	 * @param array $data
	 */
	function _alone_action_shortcode_button_enqueue_dynamic_css($data)
	{
		$shortcode = 'button';
		$atts = shortcode_parse_atts($data['atts_string']);

		/**
		 * Decode attributes
		 * ( The below weird code is because of this https://github.com/ThemeFuse/Unyson/issues/469 )
		 */
		$atts = fw_ext_shortcodes_decode_attr($atts, $shortcode, $data['post']->ID);

		$final_styles = '';
		if (isset($atts['style']['selected'])) {
			if ($atts['style']['selected'] == 'fw-btn-3') {
				if (isset($atts['style']['fw-btn-3']['border_size']) && !empty($atts['style']['fw-btn-3']['border_size'])) {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . ',.tf-sh-' . $atts['unique_id'] . ':focus{ border-top-width: ' . (int)$atts['style']['fw-btn-3']['border_size'] . 'px; border-bottom-width: ' . (int)$atts['style']['fw-btn-3']['border_size'] . 'px; }';
				}
				// btn color
				if (isset($atts['normal_color'])) {
					$normal_color = alone_get_color_palette_color_and_class($atts['normal_color'], array('return_color' => true));
					if (!empty($normal_color['color'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ',.tf-sh-' . $atts['unique_id'] . ':focus{ border-bottom-color: ' . $normal_color['color'] . '; border-top-color: ' . $normal_color['color'] . ' }';
				}
				// hover color
				if (isset($atts['hover_color'])) {
					$hover_color = alone_get_color_palette_color_and_class($atts['hover_color'], array('return_color' => true));
					if (!empty($hover_color['color'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ':hover { background-color: ' . $hover_color['color'] . '; border-bottom-color: ' . $hover_color['color'] . '; border-top-color: ' . $hover_color['color'] . ' }';
				}
			} elseif ($atts['style']['selected'] == 'fw-btn-2') {
				if (isset($atts['style']['fw-btn-2']['border_size']) && !empty($atts['style']['fw-btn-2']['border_size'])) {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . '{ border-width: ' . (int)$atts['style']['fw-btn-2']['border_size'] . 'px; }';
				}
				if (isset($atts['style']['fw-btn-2']['border_radius']) && !empty($atts['style']['fw-btn-2']['border_radius'])) {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . '{ border-radius: ' . (int)$atts['style']['fw-btn-2']['border_radius'] . 'px; }';
				}

				// btn color
				if (isset($atts['normal_color'])) {
					$normal_color = alone_get_color_palette_color_and_class($atts['normal_color'], array('return_color' => true));
					if (!empty($normal_color['color'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ',.tf-sh-' . $atts['unique_id'] . ':focus{ border-color: ' . $normal_color['color'] . ' }';
				}
				// hover color
				if (isset($atts['hover_color'])) {
					$hover_color = alone_get_color_palette_color_and_class($atts['hover_color'], array('return_color' => true));
					if (!empty($hover_color['color'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ':hover { background-color: ' . $hover_color['color'] . '; border-color: ' . $hover_color['color'] . ' }';
				}
			} elseif ($atts['style']['selected'] == 'fw-btn-1') {
				if (isset($atts['style']['fw-btn-1']['border_radius']) && !empty($atts['style']['fw-btn-1']['border_radius'])) {
					$final_styles .= '.tf-sh-' . $atts['unique_id'] . '{ border-radius: ' . (int)$atts['style']['fw-btn-1']['border_radius'] . 'px; }';
				}

				// btn color
				if (isset($atts['normal_color'])) {
					$normal_color = alone_get_color_palette_color_and_class($atts['normal_color'], array('return_color' => true));
					if (!empty($normal_color['color'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ', .tf-sh-' . $atts['unique_id'] . ':focus{ background-color: ' . $normal_color['color'] . ' }';
				}
				// hover color
				if (isset($atts['hover_color'])) {
					$hover_color = alone_get_color_palette_color_and_class($atts['hover_color'], array('return_color' => true));
					if (!empty($hover_color['color'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ':hover { background-color: ' . $hover_color['color'] . ' }';
				}

			}
		}

		// advanced styling
		if (isset($atts['label_advanced_styling'])) {
			// title advanced styling
			$text_advanced_styling = alone_get_shortcode_advanced_styles($atts['label_advanced_styling']['text'], array('return_color' => true));
			if (!empty($text_advanced_styling['styles'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ', .tf-sh-' . $atts['unique_id'] . ':focus {' . $text_advanced_styling['styles'] . '}';
			// responsive title styling
			$title_responsive_styles = alone_responsive_heading_styles(array('styles' => $atts['label_advanced_styling']['text'], 'selector' => '.tf-sh-' . $atts['unique_id']));
			if (!empty($title_responsive_styles)) $final_styles .= '@media(max-width:767px){' . $title_responsive_styles . '}';

			// hover text color
			if (isset($atts['label_advanced_styling']['hover_text_color'])) {
				$hover_text_color = alone_get_color_palette_color_and_class($atts['label_advanced_styling']['hover_text_color'], array('return_color' => true));
				if (!empty($hover_text_color['color'])) $final_styles .= '.tf-sh-' . $atts['unique_id'] . ':hover { color: ' . $hover_text_color['color'] . ' }';
			}
		}

		if (empty($final_styles)) {
			return;
		}

		wp_add_inline_style(
			'fw-theme-style',
			$final_styles
		);
	}

	add_action(
		'fw_ext_shortcodes_enqueue_static:button',
		'_alone_action_shortcode_button_enqueue_dynamic_css'
	);

endif;

if (!function_exists('_alone_filter_show_404_page')):
	/**
	 * Show custom 404 page
	 */
	function _alone_filter_show_404_page($template404)
	{
		$page_404 = function_exists('fw_get_db_settings_option') ? fw_get_db_settings_option('page_404') : '';

		if (!empty($page_404)) {
			global $wp_query;
			$wp_query = new WP_Query();
			$wp_query->query('page_id=' . $page_404);
			$wp_query->the_post();
			$template404 = get_page_template();
			rewind_posts();
		}

		return $template404;
	}

	add_filter('404_template', '_alone_filter_show_404_page');
endif;

if (!function_exists('_alone_action_coming_soon_page')) :
	/**
	 * Coming soon page
	 */
	function _alone_action_coming_soon_page()
	{
		global $coming_soon;
		$coming_soon = false;
		$enable_coming_soon = function_exists('fw_get_db_settings_option') ? fw_get_db_settings_option('enable_coming_soon') : array();
		if (isset($enable_coming_soon['selected']) && $enable_coming_soon['selected'] == 'yes') {
			// if is enabled coming soon
			if (!is_user_logged_in() && $enable_coming_soon['yes']['coming_soon_page'] != '0') {
				// if user is not logged in and coming soon page is selected
				if (alone_is_page_url_excluded()) {
					return;
				}
				$coming_soon = true;
				global $wp_query;
				$wp_query = new WP_Query();
				$wp_query->query('page_id=' . $enable_coming_soon['yes']['coming_soon_page']);
				$wp_query->the_post();
				rewind_posts();
				nocache_headers();
				//header("HTTP/1.0 503 Service Unavailable");
				include_once get_template_directory() . '/coming-soon-template.php';
				exit();
			}
		}
	}
endif;
add_action('send_headers', '_alone_action_coming_soon_page', 12);

if (!function_exists('_alone_filter_fw_settings_form_header_buttons')) :
	/**
	 * Add an extra options for post event
	 */
	function _alone_filter_fw_settings_form_header_buttons($arr)
	{
		$arr2[] = '<a class="fw-theme-docs-link" target="_blank" href="#">' . esc_html__('Go to Docs', 'alone') . '</a>';
		$arr = array_merge($arr2, $arr);

		return $arr;
	}
endif;
add_filter('fw_settings_form_header_buttons', '_alone_filter_fw_settings_form_header_buttons');

if (!function_exists('_alone_filter_fw_ext_backups_db_restore_keep_options')) :
	/**
	 * Add an extra options for post event
	 */
	function _alone_filter_fw_ext_backups_db_restore_keep_options($options, $is_full)
	{
		if (!$is_full) {
			$options['tfuse_alone_auto_install_state'] = true;
		}

		return $options;
	}
endif;
add_filter('fw_ext_backups_db_restore_keep_options', '_alone_filter_fw_ext_backups_db_restore_keep_options', 10, 2);

if (!function_exists('_alone_filter_update_footer')):
	/**
	 * Theme changelog in footer admin
	 */
	function _alone_filter_update_footer($html)
	{
		$alone_version = ( defined('FW') && function_exists('fw') )
			? fw()->theme->manifest->get_version()
			: '1.0';

		$theme_id = ( defined('FW') && function_exists('fw') )
			? fw()->theme->manifest->get_id()
			: 'alone';

		$html .= ' | <a href="#" target="_blank">' . wp_get_theme() . ' ' . $alone_version . '</a>';
		return $html;
	}
endif;
add_filter('update_footer', '_alone_filter_update_footer', 12);

if(!function_exists('_alone_custom_excerpt_length')) :
	function _alone_custom_excerpt_length( $length ) {
		return 20;
	}
endif;
add_filter( 'excerpt_length', '_alone_custom_excerpt_length', 999 );

// add more link to excerpt
if(!function_exists('_alone_custom_excerpt_more')) :
	function _alone_custom_excerpt_more($more) {
	   global $post;
	   return '...';
	}
endif;
add_filter('excerpt_more', '_alone_custom_excerpt_more');

if(!function_exists('_alone_customizer_live_preview')) :
	/**
	 * _alone_customizer_live_preview
	 * Fix cache file stylesheet mode Customizer Live Preview
 	 */
	function _alone_customizer_live_preview()
	{
		$alone_template_directory = get_template_directory_uri();
		if (defined('FW')) {
			$alone_version = fw()->theme->manifest->get_version();
		} else {
			$alone_version = '1.0';
		}

		// Load font-awesome stylesheet.
		wp_enqueue_style(
			'theme-style',
			$alone_template_directory . '/assets/css/alone.css',
			array('bootstrap'),
			$alone_version . '.' . rand(1, rand(99, 999999999))
		);
	}
endif;
add_action( 'customize_preview_init', '_alone_customizer_live_preview' );

if(!function_exists('_alonewoocommerce_header_add_to_cart_fragment')) :
	/**
	 * _alonewoocommerce_header_add_to_cart_fragment
	 */
	function _alonewoocommerce_header_add_to_cart_fragment( $fragments ) {
    global $woocommerce;

		$fragments['#bearsthemes_minicart_total_quantity_elem'] 		= '<span id="bearsthemes_minicart_total_quantity_elem" class="total-qtt">' . $woocommerce->cart->cart_contents_count . '</span>';
		$fragments['.notification-cart-total-qtt-dk'] 							= '<span class="notification-cart-total-qtt-dk">' . $woocommerce->cart->cart_contents_count . '</span>';

		$fragments['#bearsthemes_minicart_total_price_elem'] 				= '<span id="bearsthemes_minicart_total_price_elem" class="total-price">' . $woocommerce->cart->get_cart_total() . '</span>';
		$fragments['.bearsthemes_minicart_products_container_elem'] = '<div class="minicart-products-container bearsthemes_minicart_products_container_elem">' . $fragments['div.widget_shopping_cart_content'] . '</div>';
		$fragments['#notification-mini-cart'] = '<div id="notification-mini-cart">'. $fragments['div.widget_shopping_cart_content'] .'</div>';

    return $fragments;
	}
endif;
add_filter('add_to_cart_fragments', '_alonewoocommerce_header_add_to_cart_fragment');

if(! function_exists('_alone_related_products_args')) :
	/**
	 * _alone_related_products_args
	 */
	function _alone_related_products_args( $args ) {
		$args['posts_per_page'] = 3; // 4 related products
		return $args;
	}
endif;
add_filter( 'woocommerce_output_related_products_args', '_alone_related_products_args' );

if(! function_exists('_alone_save_gridmap_masonryhybrid')) :
	function _alone_save_gridmap_masonryhybrid() {
		extract($_POST);
		alone_gridmap_masonryhybrid_handle('set', $params['gridname'], $params['gridmap']);
		exit();
	}
endif;
add_action( 'wp_ajax__alone_save_gridmap_masonryhybrid', '_alone_save_gridmap_masonryhybrid' );
add_action( 'wp_ajax_nopriv__alone_save_gridmap_masonryhybrid', '_alone_save_gridmap_masonryhybrid' );

if(! function_exists('_the_bears_filter_fw_ext_backups_demos')) :
	/**
	 * @param FW_Ext_Backups_Demo[] $demos
	 * @return FW_Ext_Backups_Demo[]
	 */
	function _alone_filter_fw_ext_backups_demos($demos)
	{
		$demos_array = array(
			'alone-ngo' => array(
				'title' => esc_html__('Alone - NGO', 'alone'),
				'screenshot' => 'http://theme.bearsthemes.com/import_demo/alone/alone-ngo/screenshot.png',
				'preview_link' => 'http://theme.bearsthemes.com/wordpress/alone-ngo/',
			),
			'alone-5' => array(
				'title' => esc_html__('Alone 5', 'alone'),
				'screenshot' => 'http://theme.bearsthemes.com/import_demo/alone/alone-5/screenshot.png',
				'preview_link' => 'http://theme.bearsthemes.com/wordpress/alone-5/',
			),
		);

		foreach ($demos_array as $id => $data) {
			$demo = new FW_Ext_Backups_Demo($id, 'piecemeal', array(
				'url' => 'http://theme.bearsthemes.com/import_demo/alone/',
				'file_id' => $id,
			));
			$demo->set_title($data['title']);
			$demo->set_screenshot($data['screenshot']);
			$demo->set_preview_link($data['preview_link']);

			$demos[$demo->get_id()] = $demo;

			unset($demo);
		}

		return $demos;
	}
	add_filter('fw:ext:backups-demo:demos', '_alone_filter_fw_ext_backups_demos');
endif;

/* START === filter recipe content */
if(! function_exists('_bearsthemes_wpurp_custom_template')) :
	function _bearsthemes_wpurp_custom_template( $content, $recipe )
	{
		$WPURP_Template_Recipe_Params = array('max_width' => '100%', 'max_height' => 'none', 'desktop' => '', 'template_type' => 'recipe');
		ob_start();
		?>

		<div class="recipe-entry-wrap">
			<div class="row">
				<div class="col-md-4">
					<div class="recipe-ingredients-wrap">
						<h4 class="recipe-title"><?php echo esc_html__('Ingredients', 'alone'); ?></h4>
						<?php
							$ingredient_list = new WPURP_Template_Recipe_Ingredients();
							echo '' . $ingredient_list->output( $recipe, $WPURP_Template_Recipe_Params );
						?>
					</div>
				</div>
				<div class="col-md-8">
					<h4 class="recipe-title"><?php echo sprintf('%s', $recipe->title()); ?></h4>
					<?php echo sprintf('%s', $recipe->description()); ?>
					<div class="recipe-instructions-wrap">
						<h4 class="recipe-title"><?php echo esc_html__('Instructions', 'alone'); ?></h4>
						<?php
						$instructions_list = new WPURP_Template_Recipe_Instructions();
						echo '' . $instructions_list->output( $recipe, $WPURP_Template_Recipe_Params );
						?>
					</div>
					<div class="recipe-notes-wrap">
						<h4 class="recipe-title"><?php echo esc_html__('Notes', 'alone'); ?></h4>
						<?php
						$nodes_list = new WPURP_Template_Recipe_Notes();
						echo '' . $nodes_list->output( $recipe, $WPURP_Template_Recipe_Params );
						?>
					</div>
				</div>
			</div>
		</div>

		<?php
	    $output = ob_get_contents();
	    ob_end_clean();

		return $output;
	}
endif;
// add_filter( 'wpurp_output_recipe', '_bearsthemes_wpurp_custom_template', 10, 2 );
/* END === filter recipe content */

/* START === fix : import data > render image */
if(! function_exists('_bearsthemes_change_graphic_lib')) :
	function _bearsthemes_change_graphic_lib($array) {
		return array( 'WP_Image_Editor_GD', 'WP_Image_Editor_Imagick' );
	}
	add_filter( 'wp_image_editors', '_bearsthemes_change_graphic_lib' );
endif;
/* END === */

if(! function_exists('_bearsthemes_change_excerpt_more')) :
	function _bearsthemes_change_excerpt_more($more) {
	 global $post;
	 return '… <a href="'. get_permalink($post->ID) . '">' . __('more', 'alone') . '</a>';
	}
endif;
// GEWIJZIGD: Extra link uitgeschakeld, is dubbelop met button
// add_filter('excerpt_more', '_bearsthemes_change_excerpt_more');

if(! function_exists('_alone_admin_notice_page_builder_conflict')) :
	function _alone_admin_notice_page_builder_conflict() {
		$cornerstone = class_exists('Cornerstone_Plugin');
		$visual_composer = class_exists('Vc_Manager');

		if ($cornerstone && $visual_composer) :
	    ?>
	    <div class="notice notice-warning is-dismissible theme-custom-notice-ui">
					<img style="width: 80px; margin-top: 10px;" src="<?php echo get_template_directory_uri() . '/assets/images/bears-message-icon.png'; ?>" alt="#"/>
					<div class="entry-content">
						<p><strong><?php _e('Page Builder Conflict !!!', 'alone') ?></strong></p>
						<p><?php _e('We found that you used two page buider plugins at the same time (Cornerstone & Visual Composer). We\'d recommend you use only one of them. Your site will be fine if you use only one of those plugins.', 'alone'); ?></p>
					</div>
			</div>
	    <?php
		endif;
	}
endif;
add_action( 'admin_notices', '_alone_admin_notice_page_builder_conflict' );

if(! function_exists('_alone_admin_notice_theme_message')) :
	function _alone_admin_notice_theme_message() {
		$_fw = defined( 'FW' );

		$current_theme_data = wp_get_theme();
		$themename = $current_theme_data->get( 'Name' );
		$themeversion = $current_theme_data->get( 'Version' );
		$admin_link = get_admin_url();
		$links = array();

		if($_fw) {
			$links['theme_requirements'] = array(
				'text' => __('Theme Requirements', 'alone'),
				'link' => $admin_link . 'themes.php?page=fw-settings',
			);

			$links['install_demo'] = array(
				'text' => __('Install Demo', 'alone'),
				'link' => $admin_link . 'tools.php?page=fw-backups-demo-content',
			);
		}

		$links['install_plugins'] = array(
			'text' => __('Install Plugins', 'alone'),
			'link' => $admin_link . 'themes.php?page=bearsthemes_auto_setup',
		);

		$links['support'] = array(
			'text' => __('Support', 'alone'),
			'link' => 'https://bearsthemes.ticksy.com/',
		);
    ?>
    <div class="notice notice-info is-dismissible theme-custom-notice-ui">
				<img style="width: 80px; margin-top: 10px;" src="<?php echo get_template_directory_uri() . '/assets/images/bears-message-icon.png'; ?>" alt="#"/>
				<div class="entry-content">
					<p><strong><?php _e( sprintf('Hey! Thanks for your used %s theme. (current version %s)', $themename, $themeversion), 'alone') ?></strong></p>
					<div class="theme-custom-button">
						<?php
						foreach($links as $type => $item) :
							echo implode('', array(
								'<a class="theme-custom-button-ui btn-type-'. $type .'" href="'. $item['link'] .'" target="_blank">',
									$item['text'],
								'</a>',
							));
						endforeach;
						?>
					</div>
				</div>
		</div>
    <?php
	}
endif;
// GEWIJZIGD: Nag uitschakelen
// add_action( 'admin_notices', '_alone_admin_notice_theme_message' );

if(! function_exists('_alone_notification_center_action')) :
	/**
	 * _alone_notification_center_action
	 * @since 0.0.7
	 */
	function _alone_notification_center_action() {
		$_FW = defined( 'FW' ); if(! $_FW) return;

		echo fw_render_view(get_template_directory() . '/templates/notification_center/content.php', array(), true);
	}
endif;
add_action( 'wp_footer', '_alone_notification_center_action' );
