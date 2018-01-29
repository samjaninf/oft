<?php

/**
 * Helper functions and classes with static methods for usage in theme
 */

// TODO: separate functions in specific files

if ( ! isset( $content_width ) ) $content_width = 900;

if( ! function_exists( 'alone_get_the_archive_title' ) ) :
	/**
	 * Get the archive title
	 */
	function alone_get_the_archive_title() {
		if ( is_category() ) {
			$title = single_cat_title( '', false );
		} elseif ( is_tag() ) {
			$title = single_tag_title( '', false );
		} elseif ( is_author() ) {
			$title = get_the_author();
		} elseif ( is_year() ) {
			$title = get_the_date( _x( 'Y', 'yearly archives date format', 'alone' ) );
		} elseif ( is_month() ) {
			$title = get_the_date( _x( 'F Y', 'monthly archives date format', 'alone' ) );
		} elseif ( is_day() ) {
			$title = get_the_date( _x( 'F j, Y', 'daily archives date format', 'alone' ) );
		} elseif ( is_tax( 'post_format' ) ) {
			if ( is_tax( 'post_format', 'post-format-aside' ) ) {
				$title = _x( 'Asides', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) {
				$title = _x( 'Galleries', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-image' ) ) {
				$title = _x( 'Images', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-video' ) ) {
				$title = _x( 'Videos', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-quote' ) ) {
				$title = _x( 'Quotes', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-link' ) ) {
				$title = _x( 'Links', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-status' ) ) {
				$title = _x( 'Statuses', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-audio' ) ) {
				$title = _x( 'Audio', 'post format archive title', 'alone' );
			} elseif ( is_tax( 'post_format', 'post-format-chat' ) ) {
				$title = _x( 'Chats', 'post format archive title', 'alone' );
			}
		} elseif ( is_post_type_archive() ) {
			$title = post_type_archive_title( '', false );
		} elseif ( is_tax() ) {
			$tax = get_taxonomy( get_queried_object()->taxonomy );
			/* translators: 1: Taxonomy singular name, 2: Current taxonomy term */
			$title = sprintf( esc_html__( '%1$s: %2$s', 'alone' ), $tax->labels->singular_name, single_term_title( '', false ) );
		} else {
			$title = esc_html__( 'Archives', 'alone' );
		}

		/**
		 * Filter the archive title.
		 *
		 * @since 4.1.0
		 *
		 * @param string $title Archive title to be displayed.
		 */
		return apply_filters( 'alone_get_the_archive_title', $title );
	}
endif;

if ( ! function_exists( 'alone_load_decentralize_setting' ) ) :

	function alone_load_decentralize_setting( $params = array(), $type = 'parent' ) {
		if( ! is_array( $params ) ) return;

		$result_arr = array();
		switch ($type) {
			case 'parent':

				foreach( $params as $name => $item ) :
					if( isset( $item['parent'] ) )
						$result_arr[$name] = $item['parent'];
				endforeach;
				break;

			case 'children':

				foreach( $params as $name => $item ) :
					if( isset( $item['children'] ) )
						$result_arr[$name] = $item['children'];
				endforeach;
				break;
		}

		return $result_arr;
	}
endif;

if ( ! function_exists( 'alone_list_pages' ) ):
	/**
	 * get an array of all pages
	 */
	function alone_list_pages() {
		$pages = get_pages();
		$result = array();
		$result[0] = esc_html__('None', 'alone');
		foreach ( $pages as $page ) {
			$result[ $page->ID ] = $page->post_title;
		}
		return $result;
	}
endif;

if ( ! function_exists( 'alone_return_memory_size' ) ) :
	/**
	 * print theme requirements page
	 *
	 * @param string $size
	 */
	function alone_return_memory_size( $size ) {
		$symbol = substr( $size, -1 );
		$return = (int)$size;
		switch ( strtoupper( $symbol ) ) {
			case 'P':
				$return *= 1024;
			case 'T':
				$return *= 1024;
			case 'G':
				$return *= 1024;
			case 'M':
				$return *= 1024;
			case 'K':
				$return *= 1024;
		}
		return $return;
	}
endif;

if ( ! function_exists( 'alone_logo' ) ):
	/**
	 * Display theme logo
	 *
	 * @param boolean $wrap
	 */
	function alone_logo( $wrap = false ) {
		$alone_logo_settings['logo']['selected_value']   = 'text';
		$alone_logo_settings['logo']['text']['title']    = get_bloginfo( 'name' );
		$alone_logo_settings['logo']['text']['subtitle'] = esc_html__('', 'alone');
		$TBFW = defined( 'FW' );   if ($TBFW ) {
			$alone_logo_settings = fw_get_db_customizer_option( 'logo_settings' );
		}

		$empty_logo = false;
		if( $alone_logo_settings['logo']['selected_value'] == 'image' ) {
			if( empty($alone_logo_settings['logo']['image']['image_logo']) ) {
				$empty_logo = true;
			}
		} else {
			if( empty($alone_logo_settings['logo']['text']['title']) && empty($alone_logo_settings['logo']['text']['subtitle']) ) {
				$empty_logo = true;
			}
		}

		// logo sticky
		$img_sticky_logo = $alone_logo_src_sticky_header = '';
		$TBFW = defined( 'FW' );   if ($TBFW ) {
			$alone_logo_src_sticky_header = fw_get_db_customizer_option('header_settings/enable_sticky_header/fw-sticky-header/image_logo_sticky/url');
		}

		$trim_alone_logo_src_sticky_header = trim($alone_logo_src_sticky_header);
		if(!empty($trim_alone_logo_src_sticky_header)) {
			$img_sticky_logo = sprintf('<img src="%s" alt="" class="sticky-logo">', $alone_logo_src_sticky_header);
		}
		// echo '<pre>'; print_r($alone_logo_sticky_header); echo '</pre>';

		if( !$empty_logo ) : ?>
			<div class="fw-wrap-logo">
				<?php if ($wrap): ?>
					<div class="fw-container">
				<?php endif; ?>

					<?php if ( $alone_logo_settings['logo']['selected_value'] == 'image' ) :
						$image_logo = $alone_logo_settings['logo']['image']['image_logo'];
						if ( ! empty( $image_logo ) ) : ?>
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fw-site-logo">
								<img src="<?php echo esc_url($image_logo['url']); ?>" alt="<?php esc_url( bloginfo('name') ) ?>" class="main-logo"/>
								<?php echo "{$img_sticky_logo}"; ?>
							</a>
						<?php endif;
					else :
						if ( $alone_logo_settings['logo']['text']['title'] != '' ) : ?>
							<a href="<?php echo esc_url( home_url('/') ); ?>" class="fw-site-logo">
								<strong class="site-title" itemprop="headline"><?php echo "{$alone_logo_settings['logo']['text']['title']}"; ?></strong>
								<?php if ( $alone_logo_settings['logo']['text']['subtitle'] != '' ) : ?>
									<span class="site-description" itemprop="description"><?php echo "{$alone_logo_settings['logo']['text']['subtitle']}"; ?></span>
								<?php endif; ?>
							</a>
						<?php endif;
					endif; ?>

				<?php if ($wrap): ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php }
endif;

if( ! function_exists('alone_header_mobile_menu') ) :
	function alone_header_mobile_menu() {
		$_FW = defined( 'FW' );
		if ($_FW ) {
			$alone_logo_settings = fw_get_db_customizer_option( 'logo_settings' );
			get_template_part( 'templates/headers/header-mobi' );
		} else {
			get_template_part( 'templates/headers/header-mobi' );
		}
	}
endif;

if ( ! function_exists( 'alone_header' ) ) :
	/**
	 * Display theme header
	 */
	function alone_header() {
		$alone_header_settings = defined( 'FW' ) ? fw_get_db_customizer_option( 'header_settings' ) : array();
		$alone_header_type = isset( $alone_header_settings['header_type_picker']['header_type'] ) ? $alone_header_settings['header_type_picker']['header_type'] : 'header-1';

		/* overide version header use $_GET */
		if(isset($_GET['header_ver']) && ! empty($_GET['header_ver'])) $alone_header_type = $_GET['header_ver'];

		/* load header template */
		get_template_part( 'templates/headers/'.$alone_header_type );
	}
endif;

if( !function_exists('alone_scss_handle') ) :

	function alone_scss_handle( $scss_content_string = '' ) {
		global $wp_filesystem;
		if( empty( $wp_filesystem ) ) {
				if(defined('FW')) {
	        fw_render_view(ABSPATH .'/wp-admin/includes/file.php', array(), false);
	      }
		    WP_Filesystem();
		}

		$scss = new scssc();
		$scss_main_content = $wp_filesystem->get_contents( get_template_directory() . '/assets/style-scss/main.scss' );
		$style_path = get_template_directory() . '/assets/css/alone.css';
		$scss->setImportPaths( get_template_directory() . '/assets/style-scss/' );

		$scss_content = $scss->compile( $scss_content_string . $scss_main_content );
		$wp_filesystem->put_contents( $style_path, $scss_content, FS_CHMOD_FILE );
	}
endif;

if(!function_exists('alone_scan_enqueue_google_font')) :
	/*
	 * scan google font from theme setting
	 */
	function alone_scan_enqueue_google_font( $settings = array() ) {
		foreach( $settings as $key => $item ) :
			if( isset( $item['google_font'] ) ) :
				$params_google_font = array(
					'family' 	=> isset( $item['family'] ) ? $item['family'] : '',
					'variation' => isset( $item['variation'] ) ? $item['variation'] : '',
					'subset' 	=> isset( $item['subset'] ) ? $item['subset'] : '',
					'style'		=> array( '300', '300i', '400', '400i', '600', '600i', '700', '700i' ) );

				wp_enqueue_style( 'google-font-' . $params_google_font['family'], "https://fonts.googleapis.com/css?family={$params_google_font['family']}:" . implode( ',', $params_google_font['style'] ) );
			else :
				if( is_array( $item) )
					alone_scan_enqueue_google_font( $item );
			endif;
		endforeach;
	}
endif;

if(! function_exists('alone_general_recipe_options')) :
	function alone_general_recipe_options() {
		// echo '<pre>'; print_r(fw_get_db_customizer_option()); echo '</pre>';
		$TBFW = defined( 'FW' );   if ($TBFW ) {

			return array(
				'recipe_type'    			=> fw_get_db_customizer_option( 'recipe_settings/recipe_type', 'recipe-1' ),
				'posts_per_page' 			=> get_option( 'posts_per_page', 10 ),
				'number_post_in_row' 	=> fw_get_db_customizer_option( 'recipe_settings/number_post_in_row', 2 ),
			);
		}
		else{

			return array(
				'recipe_type'      			=> 'recipe-1',
				'posts_per_page' 			=> get_option( 'posts_per_page', 10 ),
				'number_post_in_row' 	=> 2,
			);
		}
	}
endif;

if( ! function_exists( 'alone_general_posts_options' ) ) :
	/**
	 * return theme general posts options
	 */
	function alone_general_posts_options() {
		$_FW = defined( 'FW' );   if ($_FW ) {

			return array(
				'blog_type'      => fw_get_db_customizer_option( 'post_settings/blog_type', 'blog-1' ),
				'posts_per_page' => get_option( 'posts_per_page', 10 ),
				'number_post_in_row' => fw_get_db_customizer_option( 'post_settings/number_post_in_row', 2 ),
			);
		}
		else{

			return array(
				'blog_type'      => 'blog-1',
				'posts_per_page' => get_option( 'posts_per_page', 10 ),
				'number_post_in_row' => 1,
			);
		}
	}
endif;

if ( ! function_exists( 'alone_get_featured_posts' ) ) :
	/**
	 * Getter function for Featured Content Plugin.
	 *
	 * @param array|bool $posts Array of featured posts, otherwise false.
	 */
	function alone_get_featured_posts() {
		return apply_filters( 'alone_get_featured_posts', array() );
	}
endif;

if(!function_exists('alone_extend')) :
	/**
 	 * extend
 	 */
	function alone_extend($base = array(), $replacements = array()) {
	    $base = ! is_array($base) ? array() : $base;
	    $replacements = ! is_array($replacements) ? array() : $replacements;

	    return array_replace_recursive($base, $replacements);
	}
endif;

if(!function_exists('alone_get_settings_by_post_id')) :
	/*
	 * Get setting post by id
	 */
	function alone_get_settings_by_post_id($post_id) {
		$post_settings = array();

		$_FW = defined( 'FW' );   if ($_FW ) {
			$post_settings = array(
				'post_general_tab' 	=> fw_get_db_post_option($post_id, 'post_general_tab'),
				'post_video_tab' 	=> fw_get_db_post_option($post_id, 'post_video_tab'),
				'post_audio_tab' 	=> fw_get_db_post_option($post_id, 'post_audio_tab'),
				'post_quote_tab' 	=> fw_get_db_post_option($post_id, 'post_quote_tab'),
				'post_link_tab' 	=> fw_get_db_post_option($post_id, 'post_link_tab'),
				'post_gallery_tab' 	=> fw_get_db_post_option($post_id, 'post_gallery_tab'),
			);
		}

		return $post_settings;
	}
endif;

if( ! function_exists( 'alone_listing_post_media' ) ) :
	/**
	 * return listing post options
	 *
	 * @param integer $post_id
	 */
	function alone_listing_post_media($post_id) {
		global $post;
		$wrap_title = 'h2';
		$image_size = $post_format = $layout_creative = '';

		$TBFW = defined( 'FW' );   if ($TBFW ) {
			$post_settings          = alone_get_settings_by_post_id($post_id);
			$layout_creative				= (isset($post_settings['post_general_tab']['blog_layout_style_cretive']) && ! empty($post_settings['post_general_tab']['blog_layout_style_cretive'])) ? $post_settings['post_general_tab']['blog_layout_style_cretive'] : 'default';
			$posts_general_settings = fw_get_db_customizer_option( 'posts_settings', '' );
			$wrap_title				= isset($posts_general_settings['blog_title']['selected']) ? $posts_general_settings['blog_title']['selected'] : 'h2';
			$image_size				= isset($post_settings['post_general_tab']['image_size']) ? $post_settings['post_general_tab']['image_size'] : 'medium-large' ;
		}

		// echo '<pre>'; print_r($post_settings); echo '</pre>';
		// echo '<pre>'; print_r($posts_general_settings); echo '</pre>';
		$taxonomy_names = get_object_taxonomies( $post );
		list($cat_name, $tax_name) = array_merge($taxonomy_names,array('', '')); // (is_array($taxonomy_names) && count($taxonomy_names) > 0) ? : array('', '');
		$result = array(
			'image_size'			=> $image_size,
			'port_format' 		=> get_post_format($post_id),
			'layout_creative'	=> $layout_creative,
			'title' 					=> "<{$wrap_title} class='post-title'>". get_the_title() ."</{$wrap_title}>",
			'title_link' 			=> "<a href='". get_permalink() ."' class='post-title-link'><{$wrap_title} class='post-title'>". get_the_title() ."</{$wrap_title}></a>",
			'featured_image'	=> alone_get_image(get_post_thumbnail_id(), array('size' => $image_size)),
			'date'						=> get_the_date(get_option('date_format'), $post_id),
			'author'					=> get_the_author(),
			'author_link'			=> "<a href=". get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) ) .">". get_the_author() ."</a>",
			'comments'				=> wp_count_comments($post_id)->total_comments,
			'category_list'		=> get_the_term_list( $post_id, $cat_name, '', ', ' ), // get_the_category_list(),
			'tag_list'				=> get_the_term_list( $post_id, $tax_name, '', ', ' ),// get_the_tag_list(),
			'views'						=> alone_get_post_views($post_id),
			'readmore' 				=> "<a href='". get_permalink() ."' class='post-readmore'>". esc_html__('Read More', 'alone') ."</a>",
		);

		// get post type
		$post_format = get_post_format($post_id);
		$post_format_arr  = array('video', 'audio', 'quote', 'link', 'gallery');
		if (in_array($post_format, $post_format_arr)) {
			if(function_exists("alone_get_{$post_format}")) {
				if($post_format == 'gallery') {
					$post_settings["post_{$post_format}_tab"]['img_arg'] = array('size' => $image_size);
				}

				$post_settings["post_{$post_format}_tab"] = isset( $post_settings["post_{$post_format}_tab"] ) ? $post_settings["post_{$post_format}_tab"] : array();
				$result[$post_format] = call_user_func_array("alone_get_{$post_format}", array($post_settings["post_{$post_format}_tab"]));
			}
		}

		return $result;
	}
endif;

if( ! function_exists( 'alone_single_post_options' ) ) :
	/**
	 * return single post options
	 *
	 * @param integer $post_id
	 */
	function alone_single_post_options($post_id) {
		global $post;
		$wrap_title = $image_size = $post_format = '';

		$_FW = defined( 'FW' );   if ($_FW ) {
			$post_settings          = alone_get_settings_by_post_id($post_id);
			$posts_general_settings = fw_get_db_customizer_option( 'posts_settings', '' );
			$wrap_title				= isset($posts_general_settings['blog_title']['selected']) ? $posts_general_settings['blog_title']['selected'] : 'h2';
			$image_size				= isset($post_settings['post_general_tab']['image_size']) ? 'full' : 'full'; // $post_settings['post_general_tab']['image_size'] : 'medium-large' ;
		}

		// echo '<pre>'; print_r($post_settings); echo '</pre>';
		// echo '<pre>'; print_r($posts_general_settings); echo '</pre>';
		$taxonomy_names = get_object_taxonomies( $post ); // print_r($taxonomy_names);
		$result = array(
			'image_size'			=> $image_size,
			'title' 					=> "<{$wrap_title} class='post-title'>". get_the_title() ."</{$wrap_title}>",
			'title_link' 			=> "<a href='". get_permalink() ."' class='post-title-link'><{$wrap_title} class='post-title'>". get_the_title() ."</{$wrap_title}></a>",
			'featured_image'	=> alone_get_image(get_post_thumbnail_id(), array('size' => $image_size)),
			'date'						=> get_the_date(get_option('date_format'), $post_id),
			'author'					=> get_the_author(),
			'author_link'			=> "<a href=". get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) ) .">". get_the_author() ."</a>",
			'comments'				=> wp_count_comments($post_id)->total_comments,
			'category_list'		=> '', // get_the_term_list( $post_id, $taxonomy_names[0], '', ', ' ), // get_the_category_list(),
			'tag_list'				=> '', // get_the_term_list( $post_id, $taxonomy_names[1] ),// get_the_tag_list(),
			'views'						=> alone_get_post_views($post_id),
			'readmore' 				=> "<a href='". get_permalink() ."' class='post-readmore'>". esc_html__('Read More', 'alone') ."</a>",
		);

		if( isset($taxonomy_names[0]) && ! empty($taxonomy_names[0]) ) { $result['category_list'] = get_the_term_list( $post_id, $taxonomy_names[0], '', ', ' ); }
		if( isset($taxonomy_names[1]) && ! empty($taxonomy_names[1]) ) { $result['tag_list'] = get_the_term_list( $post_id, $taxonomy_names[1], '', ' ' ); }

		// get post type
		$post_format = get_post_format($post_id);
		$post_format_arr  = array('video', 'audio', 'quote', 'link', 'gallery');
		if (in_array($post_format, $post_format_arr)) {
			if(function_exists("alone_get_{$post_format}")) {
				if($post_format == 'gallery') {
					$post_settings["post_{$post_format}_tab"]['img_arg'] = array('size' => $image_size);
				}
				if(isset($post_settings["post_{$post_format}_tab"]))
					$result[$post_format] = call_user_func_array("alone_get_{$post_format}", array($post_settings["post_{$post_format}_tab"]));
			}
		}

		return $result;
	}
endif;

if(!function_exists('alone_get_post_views')) :
	/**
 	 * alone_get_post_views
 	 * @param [int] $post_id
 	 */
	function alone_get_post_views($post_id = null) {
		$views = get_post_meta($post_id, 'fw_post_views', true);
		return !empty($views) ? $views : 0;
	}
endif;

if(!function_exists('alone_get_image')) :
	/*
 	 * get image post
 	 */
	function alone_get_image($attachment_id = null, $arg = null){
		if(empty($attachment_id)) return;

		$arg = alone_extend(array(
			'size' => 'medium-large',
			'icon' => false,
			'attr' => array('class' => 'post-single-image'),
		), $arg);

		return wp_get_attachment_image($attachment_id, $arg['size'], $arg['icon'], $arg['attr']);
	}
endif;

if(!function_exists('alone_get_video')) :
	/*
	 * get video post
	 */
	function alone_get_video($data = null){
		if(! isset($data['video_type']) && empty($data['video_type'])) return;

		$output = "";
		// print_r($data);
		switch ($data['video_type']['selected']) {
			case 'embed':
				if(! empty($data['video_type']['embed']['iframe'])) :
					$output = "<div class='post-video-wrap video-type-embed'>
						{$data['video_type']['embed']['iframe']}
					</div>";
				endif;
				break;

			default:
				$video_src = $video_type = "";
				$video_src = $data['video_type']['other']['src'];

				if(!empty($video_src)){
					$exp = explode('.', $video_src);
					$video_type = end($exp);

					$output = "<div class='post-video-wrap video-type-other'>
						<video controls>
						  	<source src='{$video_src}' type='video/{$video_type}'>
							". esc_html__('Your browser does not support the video tag.', 'alone') ."
						</video>
					</div>";
				}
				break;
		}

		return $output;
	}
endif;

if(!function_exists('alone_get_audio')) :
	/**
 	 * ge audio post
 	 */
	function alone_get_audio($data = null){
		if(! isset($data['audio_type']) && empty($data['audio_type'])) return;

		$output = "";
		switch ($data['audio_type']['selected']) {
			case 'embed':
				$output = "<div class='post-video-wrap audio-type-embed'>
					{$data['audio_type']['embed']['iframe']}
				</div>";
				break;

			default:
				$audio_src = $audio_type = '';
				$audio_src = $data['audio_type']['other']['src'];
				if(!empty($audio_src)){
					$exp = explode('.', $audio_src);
					$audio_type = end($exp);
				}

				$output = "<div class='post-video-wrap audio-type-other'>
					<audio controls>
					  	<source src='{$audio_src}' type='video/{$audio_type}'>
						". esc_html__('Your browser does not support the audio tag.', 'alone') ."
					</audio>
				</div>";
				break;
		}

		return $output;
	}
endif;

if(!function_exists('alone_get_quote')) :
	/**
 	 * get quote post
 	 */
	function alone_get_quote($data = null){
		if(! isset($data['quote_text']) && empty($data['quote_text'])) return;

		$quote_text = trim($data['quote_text']);
		$quote_cite = trim($data['quote_cite']);
		if(empty($quote_text)) return;

		$output = "<div class='post-quote-wrap'>
			<blockquote class='post-quote'>{$quote_text}</blockquote>
			<div class='post-cite-wrap'>{$quote_cite}</div>
		</div>";

		return $output;
	}
endif;

if(!function_exists('alone_get_link')) :
	/**
 	 * get link post
 	 */
	function alone_get_link($data = null){
		if(! isset($data['url']) && empty($data['url'])) return;

		$url = trim($data['url']);
		if(empty($url)) return;

		$output = "<div class='post-link-wrap'>
			<a href='{$url}' class='post-link' target='_blank'>{$url}</a>
		</div>";

		return $output;
	}
endif;

if(!function_exists('alone_get_gallery')) :
	function alone_get_gallery($data = null){
		if(! isset($data['gallery_images']) && empty($data['gallery_images'])) return;

		$gallery_images = $data['gallery_images'];
		if(!is_array($gallery_images) || count($gallery_images) <= 0) return;

		$output = $image_item = "";
		foreach($gallery_images as $item) {
			$image_item .= "<a href='{$item['url']}' class='gallery-item item'>". alone_get_image($item['attachment_id'], $data['img_arg']) ."</a>";
		}
		$output = "<div class='post-gallery-wrap'>
				<div class='post-gallery owl-carousel owl-carousel-style-dots-navs-default' data-bears-owl-carousel='{\"autoplay\": true}' data-bears-lightgallery>
					{$image_item}
				</div>
			</div>";

		return $output;
	}
endif;

if ( ! function_exists( 'alone_translate' ) ) :
	/**
	 * Return the content for translations plugins
	 *
	 * @param string $content
	 */
	function alone_translate( $content ) {
		$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );

		if ( function_exists( 'icl_object_id' ) && strpos( $content, 'wpml_translate' ) == true ) {
			$content = do_shortcode( $content );
		} elseif ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$content = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $content );
		} elseif ( function_exists('ppqtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
			$content = ppqtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($content);
		}

		return $content;
	}
endif;

if ( ! function_exists( 'alone_get_shortcode_advanced_styles' ) ) :
	/**
	 * Get shortcode advanced styles
	 *
	 * @param array $style
	 * @param array $atts
	 */
	function alone_get_shortcode_advanced_styles( $style, $atts = array('return_color' => false, 'general_options' => false, 'custom_meta' => '' ) ) {
		$advanced_styles = $title_color = '';

		if($style['is_saved'] !== true && $style['is_saved'] !== 'true') {
			return array('styles' => '', 'classes' => '');
		}

		global $post, $post_google_fonts_list, $google_fonts_list;
		if(isset($style['google_font']) && ($style['google_font'] === true || $style['google_font'] === 'true' ) ){
			$advanced_styles .= isset($style['family']) ? 'font-family:'.esc_attr($style['family']).';' : '';

			if( strpos( $style['variation'], 'italic' ) !== false )
				$advanced_styles  .= 'font-style:italic;';
			elseif( strpos( $style['variation'], 'oblique' ) !== false )
				$advanced_styles  .= 'font-style: oblique;';
			else
				$advanced_styles .= 'font-style: normal;';

			$google_fonts = fw_get_google_fonts();
			$advanced_styles .= (intval( $style['variation'] ) == 0) ? 'font-weight:400;' : 'font-weight:' .intval( $style['variation']) .';';
			if( isset($atts['general_options']) && $atts['general_options'] ) {
				$google_fonts_list[$style['family']]['variation'][ $style['variation'] ] = $style['variation'];
				$google_fonts_list[$style['family']]['subset'][ $style['subset'] ]       = $style['subset'];
				// include and italic variation for font if current font has, because user can use <em> tag
				if ($style['variation'] == 'regular') {
					$italic_variation = "italic";
				}
				else {
					$italic_variation = $style['variation']."italic";
				}
				if( in_array( $italic_variation, $google_fonts[ $style['family'] ]['variants'] ) ) {
					$google_fonts_list[$style['family']]['variation'][ $italic_variation ] = $italic_variation;
				}

				update_option( 'fw_theme_google_fonts_list', $google_fonts_list );
			}
			elseif( isset($atts['custom_meta']) && !empty($atts['custom_meta']) ) {
				$google_fonts_list[$style['family']]['variation'][ $style['variation'] ] = $style['variation'];
				$google_fonts_list[$style['family']]['subset'][ $style['subset'] ]       = $style['subset'];
				// include and italic variation for font if current font has, because user can use <em> tag
				if ($style['variation'] == 'regular') {
					$italic_variation = "italic";
				}
				else {
					$italic_variation = $style['variation']."italic";
				}
				if( in_array( $italic_variation, $google_fonts[ $style['family'] ]['variants'] ) ) {
					$google_fonts_list[$style['family']]['variation'][ $italic_variation ] = $italic_variation;
				}

				update_option( $atts['custom_meta'], $google_fonts_list );
			}
			else {
				$post_google_fonts_list[$style['family']]['variation'][ $style['variation'] ] = $style['variation'];
				$post_google_fonts_list[$style['family']]['subset'][ $style['subset'] ]       = $style['subset'];
				// include and italic variation for font if current font has, because user can use <em> tag
				if ($style['variation'] == 'regular') {
					$italic_variation = "italic";
				}
				else {
					$italic_variation = $style['variation']."italic";
				}
				if( in_array( $italic_variation, $google_fonts[ $style['family'] ]['variants'] ) ) {
					$post_google_fonts_list[$style['family']]['variation'][ $italic_variation ] = $italic_variation;
				}

				if( !is_singular() && function_exists('update_term_meta') ) {
					if ( is_category() ) {
						$term = get_category( get_query_var( 'cat' ), false );
					} else {
						$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
					}
					if( isset($term->term_id) ) {
						$term_id = $term->term_id;
						update_term_meta( $term_id, 'fw_theme_post_google_fonts', $post_google_fonts_list );
					}
				}
				else{
					update_post_meta( @$post->ID, 'fw_theme_post_google_fonts', $post_google_fonts_list );
				}
			}
		}
		else {
			$advanced_styles .= isset($style['family']) ? 'font-family:'.esc_attr($style['family']).';' : '';
			$advanced_styles .= isset($style['style']) ? 'font-style:'.esc_attr($style['style']).';' : '';
			$advanced_styles .= isset($style['weight']) ? 'font-weight:'.esc_attr($style['weight']).';' : '';
			if( !isset($atts['general_options']) || !$atts['general_options'] ) {
				update_post_meta( @$post->ID, 'fw_theme_post_google_fonts', array() );
			}
		}

		$advanced_styles .= !empty($style['line-height']) ? is_numeric($style['line-height']) ?  'line-height:' . esc_attr($style['line-height']) .'px;' : 'line-height:' . esc_attr($style['line-height']) .';' : '';
		$advanced_styles .= !empty($style['size']) ? is_numeric($style['size']) ?  'font-size:'. esc_attr($style['size']) .'px;' : 'font-size:'. esc_attr($style['size']) .';' : '';
		$advanced_styles .= is_numeric($style['letter-spacing']) ?  'letter-spacing:'. esc_attr($style['letter-spacing']).'px;' : '';

		// text color
		if( isset($style['color-palette']['id']) && $style['color-palette']['id'] !== 'fw-custom'){
			if( $atts['return_color'] ){
				// get colors from db
				global $alone_color_settings;
				$advanced_styles .= 'color:'.$alone_color_settings[ $style['color-palette']['id'] ].';';
			}
			else {
				$title_color .= 'fw_theme_text_' . $style['color-palette']['id'];
			}
		}
		elseif( isset($style['color-palette']['color']) && !empty($style['color-palette']['color']) ){
			$advanced_styles .= 'color:'.$style['color-palette']['color'].';';
		}

		if(!empty($advanced_styles))
			$advanced_style = $advanced_styles;
		else
			$advanced_style = '';


		return array('styles' => $advanced_style, 'classes' => $title_color);
	}
endif;

if( ! function_exists( 'alone_responsive_heading_styles' ) ) :
	/**
	 * return text size styles
	 *
	 * @param array $atts
	 */
	function alone_responsive_heading_styles( $atts = array( 'styles' => array(), 'selector' => '', 'important' => false ) ) {
		$return_html = '';
		if($atts['styles']['is_saved'] !== true && $atts['styles']['is_saved'] !== 'true') {
			return $return_html;
		}

		$important = '';
		if( isset($atts['important']) && $atts['important'] ){
			$important = ' !important';
		}

		if( !empty($atts['styles']) && !empty($atts['selector']) ) {
			$atts['styles']['size'] = (int) $atts['styles']['size'];
			$atts['styles']['line-height'] = (int) $atts['styles']['line-height'];
			if ( $atts['styles']['size'] >= 20 && $atts['styles']['size'] <= 25 ) {
				$return_html .= $atts['selector'] . '{font-size: ' . round( $atts['styles']['size'] * 0.9, 0 ) . 'px ' . $important . '; line-height: ' . round( $atts['styles']['line-height'] * 0.9, 0 ) . 'px ' . $important . ';}';
			} elseif ( $atts['styles']['size'] >= 26 && $atts['styles']['size'] <= 30 ) {
				$return_html .= $atts['selector'] . '{font-size: ' . round( $atts['styles']['size'] * 0.8, 0 ) . 'px ' . $important . '; line-height: ' . round( $atts['styles']['line-height'] * 0.8, 0 ) . 'px ' . $important . ';}';
			} elseif ( $atts['styles']['size'] >= 31 && $atts['styles']['size'] <= 45 ) {
				$return_html .= $atts['selector'] . '{font-size: ' . round( $atts['styles']['size'] * 0.7, 0 ) . 'px ' . $important . '; line-height: ' . round( $atts['styles']['line-height'] * 0.7, 0 ) . 'px ' . $important . ';}';
			} elseif ( $atts['styles']['size'] >= 46 && $atts['styles']['size'] <= 65 ) {
				$return_html .= $atts['selector'] . '{font-size: ' . round( $atts['styles']['size'] * 0.6, 0 ) . 'px ' . $important . '; line-height: ' . round( $atts['styles']['line-height'] * 0.6, 0 ) . 'px ' . $important . ';}';
			} elseif ( $atts['styles']['size'] >= 66 && $atts['styles']['size'] <= 80 ) {
				$return_html .= $atts['selector'] . '{font-size: ' . round( $atts['styles']['size'] * 0.5, 0 ) . 'px ' . $important . '; line-height: ' . round( $atts['styles']['line-height'] * 0.5, 0 ) . 'px ' . $important . ';}';
			} elseif ( $atts['styles']['size'] >= 81 && $atts['styles']['size'] <= 100 ) {
				$return_html .= $atts['selector'] . '{font-size: ' . round( $atts['styles']['size'] * 0.4, 0 ) . 'px ' . $important . '; line-height: ' . round( $atts['styles']['line-height'] * 0.4, 0 ) . 'px ' . $important . ';}';
			} elseif ( $atts['styles']['size'] > 100 ) {
				$return_html .= $atts['selector'] . '{font-size: ' . round( $atts['styles']['size'] * 0.3, 0 ) . 'px ' . $important . '; line-height: ' . round( $atts['styles']['line-height'] * 0.3, 0 ) . 'px ' . $important . ';}';
			}
		}
		return $return_html;
	}
endif;

if ( ! function_exists( 'alone_get_font_array' ) ) :
	/**
	 * get an array of fonts
	 *
	 * @param array $font_array
	 * @param array $alone_color_settings
	 */
	function alone_get_font_array( $font_array, $alone_color_settings ) {
		global $google_fonts_list;
		$return['font-family'] = "'".$font_array['family']."'";
		$return['font-size'] = $font_array['size'].'px';
		$return['line-height'] = $font_array['line-height'].'px';
		$return['letter-spacing'] = $font_array['letter-spacing'].'px';
		$return['color'] = '';
		if( isset($font_array['color-palette']['id']) && $font_array['color-palette']['id'] == 'fw-custom'){
			if( !empty($font_array['color-palette']['color']) ){
				$return['color'] = $font_array['color-palette']['color'];
			}
		}
		elseif( isset($font_array['color-palette']['id']) && isset($alone_color_settings[$font_array['color-palette']['id']]) ){
            $return['color'] = $alone_color_settings[$font_array['color-palette']['id']];
		}

		// if is google font
		$return['font-weight'] = $return['font-style'] = '';
		if(isset($font_array['google_font']) && $font_array['google_font']){
			if( strpos( $font_array['variation'], 'italic' ) !== false ) {
				$return['font-style'] = 'italic';
			}
			elseif( strpos( $font_array['variation'], 'oblique' ) !== false ) {
				$return['font-style'] = 'oblique';
			}
			else {
				$return['font-style'] = 'normal';
			}
			$return['font-weight'] = (intval( $font_array['variation'] ) == 0) ? 400 : intval( $font_array['variation']);
			$google_fonts_list[$font_array['family']]['variation'][ $font_array['variation'] ] = $font_array['variation'];
			$google_fonts_list[$font_array['family']]['subset'][ $font_array['subset'] ] = $font_array['subset'];
			update_option( 'fw_theme_google_fonts_list', $google_fonts_list );
		}
		elseif(isset($font_array['style'])){
			$return['font-style']  = $font_array['style'];
			$return['font-weight'] = $font_array['weight'];
		}

		return $return;
	}
endif;

if ( ! function_exists( 'alone_get_remote_fonts' ) ) :
	/**
	 * Get remote fonts
	 *
	 * @param array $include_from_google
	 */
	function alone_get_remote_fonts( $include_from_google ) {
		if ( ! sizeof( $include_from_google ) || !defined('FW') ) {
			return '';
		}

        $html = "//fonts.googleapis.com/css?family=";
        foreach ( $include_from_google as $font => $styles ) {
            if( array_key_exists('false', fw_akg( 'variation', $styles ) ) ) {
                unset($styles['variation']['false']);
            }

            $html .= str_replace(' ', '+', $font) . ':' . implode(',', fw_akg( 'variation', $styles ) ). '|';

            if( array_key_exists('false', fw_akg( 'subset', $styles ) ) ) {
                unset($styles['subset']['false']);
            }

            if( isset( $styles['subset'] ) && count($styles['subset']) > 1 ) {
                // if font have more than 1 subset
                foreach( $styles['subset'] as $subset_item ) {
                    $subset_key = $subset_item;
	                if( !empty($subset_key) ) {
		                $subset[ $subset_key ] = $subset_key;
	                }
                }
            }
            else{
                $subset_key = implode( '', fw_akg( 'subset', $styles ) );
				if( !empty($subset_key) ) {
					$subset[ $subset_key ] = $subset_key;
				}
            }
        }
        $html = substr( $html, 0, - 1 );
        $html .= '&subset='.implode( ',', $subset );

		return $html;
	}
endif;

if( ! function_exists( 'alone_array_merge_recursive' ) ) :
	/**
	 * Merge array recursive
	 *
	 * @param array $a
	 * @param array $b
	 */
	function alone_array_merge_recursive($a, $b) {
		if (!is_array($a) || !is_array($b)) {
			return $a;
		}

		foreach (array_merge(array_keys($a), array_keys($b)) as $k) {
			if (
				isset($b[$k]) && isset($a[$k])
				&&
				is_array($a[$k]) && is_array($b[$k])
			) {
				$a[$k] = alone_array_merge_recursive($a[$k], $b[$k]);
			} elseif (isset($b[$k])) {
				$a[$k] = $b[$k];
			}
		}

		return $a;
	}
endif;

if ( ! function_exists( 'alone_is_page_url_excluded' ) ) :
	/**
	 * Check if is page is from excluded pages
	 */
	function alone_is_page_url_excluded() {
		$exception_urls = array( 'wp-login.php', 'async-upload.php', '/plugins/', 'wp-admin/', 'upgrade.php', 'trackback/', 'feed/' );
		foreach ( $exception_urls as $url ){
			if ( strstr( $_SERVER['PHP_SELF'], $url) ) return true;
		}

		if ( strstr($_SERVER['QUERY_STRING'], 'feed=') ) return true;

		return false;
	}
endif;

if(!function_exists( 'alone_get_color_palette_color_and_class')) :
	/**
	 * Get colors
	 *
	 * @param array $color_array
	 * @param array $atts
	 */
	function alone_get_color_palette_color_and_class( $color_array, $atts = array('return_color' => false ) ) {
		$return['color'] = $return['class'] = '';
		if(empty($color_array)){
			return $return;
		}
		if ( $color_array['id'] == 'fw-custom' ) {
			if ( ! empty( $color_array['color'] ) ) {
				$return['color'] = $color_array['color'];
			}
		} else {
			if( $atts['return_color'] ){
				// get colors from db
				global $alone_color_settings;
				$return['color'] = $alone_color_settings[ $color_array['id'] ];
			}
			else {
				$return['class'] = $color_array['id'];
			}
		}

		return $return;
	}
endif;

if(!function_exists('alone_scss_variables_handle')) :
	/**
 	 * build variable scss
 	 */
	function alone_scss_variables_handle($data = array()) {
		if(!$data || !is_array($data) || count($data) <= 0) return array();

		return array_map(function($value, $key) {
			return str_replace(array('{key}', '{value}'), array($key, $value), '${key}: {value}');
		}, array_values($data), array_keys($data));
	}
endif;

if( !function_exists('alone_replace_http') ) :
    /**
     * Replace http with empty string in a URL
     * @param string $url
     */
    function alone_replace_http($url) {
        $url = trim($url);
        $url = trim($url, "/");
        if( !preg_match("/https\:\/\//", $url) ) {
            return preg_replace("/http(s?)\:\/\/(www\.)?/i", "", $url);
        }
        else {
            return $url;
        }
    }
endif;

if( !function_exists('alone_change_original_link_with_cdn') ) :
    /**
     * Replace original link with a CDN link
     * @param string $url
     */
    function alone_change_original_link_with_cdn($url) {
        $final_url = $url;
        if( class_exists('WpFastestCache') ) {
            // for Wp Fastest Cache
            $cdn_values = get_option("WpFastestCacheCDN");
            if ($cdn_values) {
                $std = json_decode($cdn_values);

                $std->originurl = trim($std->originurl);
                $std->originurl = trim($std->originurl, "/");
                $std->originurl = preg_replace("/http(s?)\:\/\/(www\.)?/i", "", $std->originurl);

                $std->cdnurl = trim($std->cdnurl);
                $std->cdnurl = trim($std->cdnurl, "/");

                // remove http from CDN url
                $std->cdnurl = alone_replace_http($std->cdnurl);

                // remove http from original url
                $url = alone_replace_http($url);

                $final_url = '//' . str_replace($std->originurl, $std->cdnurl, $url);
            }
        }
        elseif( function_exists('wp_cache_add_pages') ) {
            global $ossdlcdn;
            if( $ossdlcdn ) {
                $siteurl = alone_replace_http(get_option('siteurl'));

                $ossdl_off_cdn_url = get_option('ossdl_off_cdn_url');
                // remove http from CDN url
                $ossdl_off_cdn_url = trim($ossdl_off_cdn_url);
                $ossdl_off_cdn_url = trim($ossdl_off_cdn_url, "/");
                $ossdl_off_cdn_url = alone_replace_http($ossdl_off_cdn_url);

                // remove http from original url
                $url = alone_replace_http($url);

                $final_url = '//' . str_replace($siteurl, $ossdl_off_cdn_url, $url);
            }
        }

        return $final_url;
    }
endif;

if ( ! function_exists( 'alone_button_class' ) ) :
	/**
	 * Display specific class for buttons - depends on theme
	 *
	 * @param string $style
	 */
	function alone_button_class( $style ) {
		if ( $style == 'fw-btn-1' ) {
			echo 'fw-btn-1';
		} elseif ( $style == 'fw-btn-2' ) {
			echo 'fw-btn-2';
		} elseif ( $style == 'fw-btn-4' ) {
			echo 'fw-btn-4';
		} elseif ( $style == 'more' ) {
			echo 'fw-btn-post-read-more';
		} elseif ( $style == 'load-more-portfolio' ) {
			echo 'fw-btn fw-btn-3 fw-btn-md';
		} elseif ( $style == 'send_contact' ) {
			echo 'fw-btn-form';
		} elseif ( $style == 'join-discussion' ) {
			echo 'fw-btn fw-btn-3 fw-btn-md fw-join-discussion';
		} elseif ( $style == 'fw-btn-instagram' ) {
			echo 'fw-btn-instagram fw-btn fw-btn-1 fw-btn-sm';
		} else {
			echo 'fw-btn-3';
		}
	}
endif;

if(!function_exists('alone_get_all_wordpress_menus')) :
	function alone_get_all_wordpress_menus(){
	    return get_terms( 'nav_menu', array( 'hide_empty' => true ) );
	}
endif;

if(!function_exists('alone_build_select_option_wordpress_menu')) :
	function alone_build_select_option_wordpress_menu() {
		$menus = alone_get_all_wordpress_menus();

		if(!is_array($menus) || count($menus) <= 0) return array();

		$menu_arr = array();
		foreach($menus as $menu) {
			$menu_arr[$menu->slug] = $menu->name;
		}

		return $menu_arr;
	}
endif;

if ( ! function_exists( 'alone_get_content_class' ) ) :
	/**
	 * Get content class when is full width or width sidebar
	 *
	 * @param string $parameter
	 * @param string $alone_sidebar_position
	 */
	function alone_get_content_class( $parameter, $alone_sidebar_position ) {
		$class = '';
		if ( $parameter == 'content' ) {
			if ( $alone_sidebar_position == 'left' || $alone_sidebar_position == 'right' ) {
				$class = 'col-md-8 col-sm-12';
			} else {
				$class = 'col-md-12';
			}
		} elseif( $parameter == 'fully' ) {
			if ( $alone_sidebar_position == 'left' || $alone_sidebar_position == 'right' ) {
				$class = 'col-md-9 col-sm-12';
			} else {
				$class = 'col-md-12 container-fully';
			}
		} elseif ( $parameter == 'main' ) {
			if ( $alone_sidebar_position == 'left' ) {
				$class = 'sidebar-left';
			} elseif ( $alone_sidebar_position == 'right' ) {
				$class = 'sidebar-right';
			}
		}
		echo esc_attr($class);
	}
endif;

if ( ! function_exists( 'alone_paging_navigation' ) ) :
	/**
	 * Display archive/category pagination
	 *
	 * @param object $wp_query
	 */
	function alone_paging_navigation( $wp_query = null ) {
		if ( ! $wp_query ) {
			$wp_query = $GLOBALS['wp_query'];
		}

		// Don't print empty markup if there's only one page.
		if ( $wp_query->max_num_pages < 2 ) {
			return;
		}

		$paged        = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;
		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$query_args   = array();
		$url_parts    = explode( '?', $pagenum_link );

		if ( isset( $url_parts[1] ) ) {
			wp_parse_str( $url_parts[1], $query_args );
		}

		$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
		$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

		$format = $GLOBALS['wp_rewrite']->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= $GLOBALS['wp_rewrite']->using_permalinks() ? user_trailingslashit( 'page/%#%', 'paged' ) : '?paged=%#%';

        $alone_pagination_type = function_exists('fw_get_db_customizer_option') ? fw_get_db_customizer_option('post_settings/blog_pagination', 'paging-navigation-type-1') : 'paging-navigation-type-1';
        if( $alone_pagination_type == 'paging-navigation-type-2' ) {
            $prev_text = esc_html__( 'Prev Page', 'alone' );
            $next_text = esc_html__( 'Next Page', 'alone' );
            $prev_icon = '<i class="ion-ios-arrow-thin-left"></i>';
            $next_icon = '<i class="ion-ios-arrow-thin-right"></i>';
        }
        else {
            $prev_text = esc_html__( 'Newer', 'alone' );
            $next_text = esc_html__( 'Older', 'alone' );
            $prev_icon = '<i class="fa fa-angle-left"></i>';
            $next_icon = '<i class="fa fa-angle-right"></i>';
        }

		// Set up paginated links.
		$links = paginate_links( array(
			'base'      => $pagenum_link,
			'format'    => $format,
			'total'     => $wp_query->max_num_pages,
			'current'   => $paged,
			'mid_size'  => 1,
			'type'      => 'array',
			'add_args'  => array_map( 'urlencode', $query_args ),
			'prev_text' => $prev_icon.'<strong>' . $prev_text . '</strong>',
			'next_text' => '<strong>' . $next_text . '</strong>'.$next_icon,
		) );

		if ( $links ) : ?>
			<nav class="navigation paging-navigation <?php echo esc_attr($alone_pagination_type); ?>" role="navigation">
				<div class="pagination loop-pagination">
					<?php
					$next = get_next_posts_link('', $wp_query->max_num_pages);
					$prev = get_previous_posts_link();
					if ( empty( $prev ) ) {
						echo '<a href="javascript:void(0)" class="prev page-numbers disabled">'.$prev_icon.'<strong>' . $prev_text . '</strong></a>';
                        $begin_for = 0;
					}
            else {
                $begin_for = 1;
            }

            if ( empty( $next ) ) {
                $end_for = count($links) - 1;
            }
            else {
                $end_for = count($links) - 2;
            }

            // parse link in foreach for make a wrap only for numbers
            foreach( $links as $key => $value ) {
                if( $key == $begin_for ) {
                    echo '<div class="before-hr"></div>';
                    echo '<div class="pagination-numbers-wrap">';
                }
                echo "{$value}";
                if( $key == $end_for ) {
                    echo '</div>';
                    echo '<div class="after-hr"></div>';
                }
            }

					if ( empty( $next ) ) {
						echo '<a href="javascript:void(0)" class="next page-numbers disabled"><strong>' . $next_text . '</strong>'.$next_icon.'</a>';
					}
					?>
				</div><!-- .pagination -->
			</nav><!-- .navigation -->
		<?php endif;
	}
endif;

if(!function_exists('alone_get_footer_class')) :
	function alone_get_footer_class($echo = false) {
		$result = '';

		if($echo == true)
			echo esc_attr($result);
		else
			return $result;
	}
endif;

if ( ! function_exists( 'alone_footer' ) ) :
	/**
	 * Display theme footer
	 */
	function alone_footer() {
		$alone_footer_settings = defined( 'FW' ) ? fw_get_db_customizer_option( 'footer_settings' ) : array();
		$bearsthemes_link	 = 'http://bearsthemes.com/';
		$show_footer_widgets = isset( $alone_footer_settings['show_footer_widgets']['selected_value'] ) ? $alone_footer_settings['show_footer_widgets']['selected_value'] : 'no';
		$copyright_position  = isset( $alone_footer_settings['copyright_position'] ) ? $alone_footer_settings['copyright_position'] : 'fw-copyright-center text-center';
		$copyright           = isset( $alone_footer_settings['copyright'] ) ? $alone_footer_settings['copyright'] : 'Copyright &copy;'. date("Y") .' <a href="'.$bearsthemes_link.'" rel="nofollow">Bearsthemes</a>. All Rights Reserved';
		?>
		<?php if ( $show_footer_widgets == 'yes' ) :
			get_template_part( 'templates/footers/footer-widgets' );
		endif; ?>

		<div class="bt-footer-bar <?php echo esc_attr($copyright_position); ?>">
			<div class="container">
				<div class="bt-copyright"><?php echo "{$copyright}"; ?></div>
			</div>
		</div>
	<?php }
endif;

if ( ! function_exists( 'alone_twitter_formating' ) ) :
	/**
	 * Return the twitter formatted text
	 *
	 * @param string $text
	 * @param string $user
	 */
	function alone_twitter_formating( $text, $user ) {
		$pattern = array(
			'/[^(:\/\/)](www\.[^ \n\r]+)/',
			'/(https?:\/\/[^ \n\r]+)/',
			'/@(\w+)/',
			'/^' . $user . ':\s*/i'
		);
		$replace = array(
			'<a href="http://$1" rel="nofollow"  target="_blank">$1</a>',
			'<a href="$1" rel="nofollow" target="_blank">$1</a>',
			'<a href="http://twitter.com/$1" rel="nofollow"  target="_blank">@$1</a>' .
			''
		);

		return preg_replace( $pattern, $replace, $text );
	}
endif;

if ( ! function_exists( 'alone_get_instagram_photos' ) ):
	/**
	 * Get instagram photos
	 *
	 * @param string $username - instagram username
	 * @param integer $items - number of photos
	 */
	function alone_get_instagram_photos( $username, $items = 9 ) {
		if ( false === ( $instagram = get_transient( 'instagram-photos-' . sanitize_title_with_dashes( $username ) . '-'.$items ) ) ) {
			$connect = wp_remote_get( 'http://instagram.com/' . trim( $username ) );

			if ( is_wp_error( $connect ) ) {
				return new WP_Error( 'site_down', esc_html__( 'Unable to communicate with Instagram.', 'alone' ) );
			}

			if ( 200 != wp_remote_retrieve_response_code( $connect ) ) {
				return new WP_Error( 'invalid_response', esc_html__( 'Instagram did not return a 200.', 'alone' ) );
			}

			$shared_data     = explode( 'window._sharedData = ', $connect['body'] );
			$instagram_json  = explode( ';</script>', $shared_data[1] );
			$instagram_array = json_decode( $instagram_json[0], true );

			if ( ! $instagram_array ) {
				return new WP_Error( 'bad_json', esc_html__( 'Instagram has returned invalid data.', 'alone' ) );
			}

			// attention on this array !
			if(isset($instagram_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'])) {
				$images = $instagram_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'];
			}
			else{
				return;
			}

			$instagram = array();
			$alone_count     = 0;
			foreach ( $images as $image ) {
				if ( !$image['is_video']) {
					$instagram[] = array(
						'code'        => $image['code'],
						'link'        => $image['display_src'],
						'likes'       => $image['likes']['count'],
					);
					$alone_count ++;
				}
				if ( $alone_count == $items ) {
					break;
				}
			}

			$instagram = serialize( $instagram );
			set_transient( 'instagram-photos-' . sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'null_instagram_cache_time', HOUR_IN_SECONDS * 2 ) );
		}
		$instagram = unserialize( $instagram );

		return array_slice( $instagram, 0, $items );
	}
endif;

if ( ! function_exists( 'alone_get_posts' ) ):
	/**
	 *  Generate array with: recent/popular/commented posts
	 *
	 * @param string $sort Sort type (recent/popular/most commented)
	 * @param integer $items Number of items to be extracted
	 * @param boolean $image_post Extract or no post image
	 * @param boolean $return_image_tag Return with tag <img
	 * @param boolean $return_for_alone_image Return for alone_image function
	 * @param integer $image_width Set width of post image
	 * @param integer $image_height Set height of post image
	 * @param string $image_class Set class of post image
	 * @param boolean $date_post Extract or no post date
	 * @param string $date_format Set date format
	 * @param string $post_type Set post type
	 * @param string $category Set category from where posts would be extracted
	 */
	function alone_get_posts( $args = null ) {
		$defaults = array(
			'sort'                => 'recent',
			'items'               => 5,
			'post_by_id'					=> array(),
			'image_post'          => true,
			'return_image_tag'    => true,
			'return_for_alone_image' => false,
			'image_size'					=> 'large',
			'image_width'         => 54,
			'image_height'        => 54,
			'image_class'         => 'thumbnail',
			'date_post'           => true,
			'date_format'         => 'F jS, Y',
			'date_query'          => array(),
			'post_type'           => 'post',
			'category'            => '',
			'excerpt_length'      => 40,
			'offset'							=> 0,
		);

		extract( wp_parse_args( $args, $defaults ) );
		global $post;
		$fw_cat_ID = ( ! empty( $category ) ) ? $category : '';

		if ( $sort == 'recent' ) {
			$query = new WP_Query( array(
				'post_type'      => $post_type,
				'orderby'        => 'post_date',
				'order '         => 'DESC',
				'cat'            => $fw_cat_ID,
				'posts_per_page' => $items,
				'date_query'     => $date_query,
				'offset'				 => $offset,
			) );
			$posts = $query->get_posts();
		} elseif ( $sort == 'popular' ) {
			$query = new WP_Query( array(
				'post_type'      => $post_type,
				'orderby'        => 'meta_value',
				'meta_key'       => 'fw_post_views',
				'order '         => 'DESC',
				'cat'            => $fw_cat_ID,
				'posts_per_page' => $items,
				'date_query'     => $date_query,
				'offset'				 => $offset,
			) );
			$posts = $query->get_posts();
		} elseif ( $sort == 'commented' ) {
			$query = new WP_Query( array(
				'post_type'      => $post_type,
				'orderby'        => 'comment_count',
				'order '         => 'DESC',
				'cat'            => $fw_cat_ID,
				'posts_per_page' => $items,
				'date_query'     => $date_query,
				'offset'				 => $offset,
			) );
			$posts = $query->get_posts();
		} elseif ( $sort == 'by_id' ) {
			$query = new WP_Query( array(
				'post_type'      => $post_type,
				'orderby'        => 'post_date',
				'order '         => 'DESC',
				'cat'            => $fw_cat_ID,
				'posts_per_page' => $items,
				'date_query'     => $date_query,
				'offset'				 => $offset,
				'post__in'       => $post_by_id,
			) );
			$posts = $query->get_posts();
		} else {
			return false;
		}
		// echo '<pre>'; print_r($query); echo '</pre>';
		$fw_post_option = array();
		$alone_count          = 0;
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post_elm ) {
				setup_postdata( $post_elm );
				$img = '';

				if ( $image_post == true ) {
					$post_thumbnail_id 	= get_post_thumbnail_id( $post_elm->ID );
					$post_thumbnail    	= wp_get_attachment_image_src( $post_thumbnail_id, $image_size );
					$image 							= $post_thumbnail[0];

					if ( ! empty( $post_thumbnail ) ) {
						$img = function_exists('fw_resize') ? fw_resize( $post_thumbnail[0], $image_width, $image_height, true ) : $post_thumbnail[0];
						if ( $return_for_alone_image ) {
							$img = array(
								'attachment_id' => $post_thumbnail_id,
								'url'           => $post_thumbnail[0],
							);
						} elseif ( $return_image_tag ) {
							$img = '<img src="' . $image . '" class="' . $image_class . '" alt="' . get_the_title( $post_thumbnail_id ) . '" width="' . $image_width . '" height="' . $image_height . '" />';
						}
					}
				}

				if ( ! empty( $img ) ) {
					$fw_post_option[ $alone_count ]['post_img'] = $img;
				} else {
					$fw_post_option[ $alone_count ]['post_img'] = '';
				}

				if ( $date_post ) {
					$time_format                                = apply_filters( '_filter_widget_time_format', $date_format );
					$fw_post_option[ $alone_count ]['post_date_post'] = get_the_time( $time_format, $post_elm->ID );
				} else {
					$fw_post_option[ $alone_count ]['post_date_post'] = '';
				}

				$fw_post_option[ $alone_count ]['post_id']        		= $post_elm->ID;
				$fw_post_option[ $alone_count ]['post_class']        = ( is_singular() && $post->ID == $post_elm->ID ) ? 'current-menu-item post_' . $sort : 'post_' . $sort;
				$fw_post_option[ $alone_count ]['post_title']        = get_the_title( $post_elm->ID );
				$fw_post_option[ $alone_count ]['post_link']         = get_permalink( $post_elm->ID );
				$fw_post_option[ $alone_count ]['post_author_link']  = get_author_posts_url( get_the_author_meta( 'ID' ) );
				$fw_post_option[ $alone_count ]['post_author_name']  = get_the_author();
				$fw_post_option[ $alone_count ]['post_comment_numb'] = get_comments_number( $post_elm->ID );
				$fw_post_option[ $alone_count ]['post_excerpt']      = ( isset( $post ) ) ? get_the_excerpt() : '';
				$alone_count ++;
			}
			wp_reset_postdata();
		}

		return $fw_post_option;
	}
endif;

if ( ! function_exists( 'alone_single_post_title' ) ) :
	/**
	 * Display single post/page title
	 *
	 * @param integer $post_id
	 * @param string $post_type
	 */
	function alone_single_post_title( $post_id, $post_type = 'post' ) {
		if ( ! defined( 'FW' ) ) {
			if($post_type == 'fw-event'){
				echo '<h2 class="entry-title" itemprop="name">' . get_the_title() . '</h2>';
			}
			else{
				echo '<h2 class="entry-title" itemprop="headline">' . get_the_title() . '</h2>';
			}
			return;
		}
		elseif( function_exists('fw_ext_page_builder_is_builder_post') && fw_ext_page_builder_is_builder_post($post_id) && $post_type == 'fw-portfolio' ){
			return;
		}

		$overlay = '';
		$image   = fw_get_db_post_option( $post_id, 'header_image', array() );

		// get general header options
		if ( $post_type == 'page' ) {
			$general_header_options = fw_get_db_settings_option( 'general_page_header', '' );
		} elseif ( $post_type == 'fw-portfolio' ) {
			$general_header_options = fw_get_db_settings_option( 'general_portfolio_header', '' );
		}

		if ( $post_type == 'page' ) {
			// for default page template
			$posts_header_title = array();
		} else {
			$posts_header_title = isset( $general_header_options['posts_header_title'] ) ? $general_header_options['posts_header_title'] : array();
		}

		if ( isset( $general_header_options['posts_header_overlay_options']['posts_header_overlay'] ) && $general_header_options['posts_header_overlay_options']['posts_header_overlay'] == 'yes' ) {
			$overlay = $general_header_options['posts_header_overlay_options']['posts_header_overlay'];
		}

		if ( $image == '' ) {
			// if image from post or category is empty - get image from general theme settings
			$image = isset( $general_header_options['posts_header_image'] ) ? $general_header_options['posts_header_image'] : array();
		}

		if ( ( empty( $image ) && $overlay != 'yes' ) || ( isset( $posts_header_title['posts_title'] ) && $posts_header_title['posts_title'] != 'post_title' ) ) {
			$alone_blog_title = fw_get_db_settings_option('posts_settings/blog_title/selected', 'h2');
			$itemprop = 'headline';
			?>
			<<?php echo "{$alone_blog_title}"; ?> class="entry-title" itemprop="<?php echo esc_attr($itemprop); ?>"><?php the_title(); ?></<?php echo "{$alone_blog_title}"; ?>>
		<?php }
	}
endif;

if(!function_exists('alone_get_sidebars')) :
	function alone_get_sidebars($data = array()) {
		global $wp_registered_sidebars;

		$result = array();
		foreach ( $wp_registered_sidebars as $sidebar ) {
			$result[ $sidebar['id'] ] = $sidebar['name'];
		}

		//
		if(count($data) > 0) { $result = array_merge($data, $result); }

		return $result;
	}
endif;

if(!function_exists('alone_top_bar')) :
	function alone_top_bar() {
		$alone_header_settings = defined( 'FW' ) ? fw_get_db_customizer_option( 'header_settings' ) : array();
		if(
			isset($alone_header_settings['enable_header_top_bar']) &&
			$alone_header_settings['enable_header_top_bar']['selected_value'] == 'yes'
		) {
			$sidebar_list = $alone_header_settings['enable_header_top_bar']['yes']['header_top_sidebar'];
			$count = count($sidebar_list);
			$col = array(
				1 => 'col-md-12 col-sm-12 col-sx-12',
				2 => 'col-md-6 col-sm-12 col-sx-12',
				3 => 'col-md-4 col-sm-12 col-sx-12' );

			if( $count > 0 ) {
				foreach($sidebar_list as $item) {
					$class = array(
						'header-top-sidebar-item',
						$col[$count],
						$item['content_align'],
						$item['custom_class']);
				?>
				<div class="<?php echo esc_attr( implode(' ', $class) ); ?>">
					<?php dynamic_sidebar($item['sidebar_id']); ?>
				</div>
				<?php
				}
			}
		}
	}
endif;

if(! function_exists('alone_title_bar_default') ) :
	function alone_title_bar_default() {
		if(is_single()) return;

		$archive   = false;
		$post_type = ''; // make post_type empty for categories because is used in section as class

		if( is_category() ){
			$term = get_category( get_query_var('cat'), false );
		}
		else{
			$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
		}

		if( isset($term->taxonomy) ){
			$taxonomy = $term->taxonomy;
			$term_id = $term->term_id;
			$title = $term->name;
			$description = $term->description;
		}
		else{
			$archive = true;
			if( is_post_type_archive('product')){
				$title = esc_html__('Products', 'alone');
			}
			elseif( is_search() ){
				$title = esc_html__( 'Search results', 'alone' );
			}
			elseif( is_page() || is_single() ) {
				$title = get_the_title();
			}
			else{
				$title = alone_get_the_archive_title();
				// for blog page
				if( is_home() ) {
						$page_for_posts = get_option( 'page_for_posts' );
						$title = ($page_for_posts != 0) ? get_the_title($page_for_posts) : esc_html__('Blog', 'alone');
				}
			}
		}
		?>
		<section class="title-bar-default text-center">
			<div class="container">
				<div class="row">
					<h1 class="title-bar-text"><?php echo "{$title}"; ?></h1>
				</div>
			</div>
		</section>
		<?php
	}
endif;

if(!function_exists('alone_title_bar')) :
	function alone_title_bar() {
		if (!defined('FW')) { alone_title_bar_default(); return; }

		global $post;
		$post_type = get_post_type( $post );

		// title bar options
		$general_title_bar_options = fw_get_db_customizer_option( 'general_title_bar', '' );
		// echo '<pre>'; print_r($general_title_bar_options); echo '</pre>';

		// color options
		$general_color_settings = fw_get_db_customizer_option('color_settings');

		// echo '<pre>'; print_r($general_title_bar_options); echo '</pre>';

		/* variables */
		$posts_header_height = $description = $alone_overlay_style = $title =  $taxonomy = $term_id = '';

		if( is_page() ){
			// for page (default template)
			$post_id = $post->ID;
			$image   = fw_get_db_post_option($post_id, 'header_image', '');
			if($image == ''){
				// if image from page is empty - get image from general theme settings
				$image = isset($general_title_bar_options['title_bar_image']) ? $general_title_bar_options['title_bar_image'] : array();
				// GEWIJZIGD: Geen title bar tonen indien header image niet expliciet ingevuld
				return;
			}
			$title = get_the_title($post_id);

			// overlay
			$title_bar_overlay_options = isset( $general_title_bar_options['title_bar_overlay_options'] ) ? $general_title_bar_options['title_bar_overlay_options'] : array();
			if(isset($title_bar_overlay_options['title_bar_overlay']) && $title_bar_overlay_options['title_bar_overlay'] == 'yes') {
				$alone_overlay_bg = $title_bar_overlay_options['yes']['title_bar_overlay_color']['id'];
				$alone_opacity    = $title_bar_overlay_options['yes']['title_bar_overlay_opacity_image'] / 100;
				if ( $alone_overlay_bg == 'fw-custom' ) {
					if ( ! empty( $title_bar_overlay_options['yes']['title_bar_overlay_color']['color'] ) ) {
						$alone_overlay_style = '<div class="fw-main-row-overlay" style="background-color: ' . $title_bar_overlay_options['yes']['title_bar_overlay_color']['color'] . '; opacity: ' . $alone_opacity . ';"></div>';
					}
				} else {
					$alone_overlay_style = '<div class="fw-main-row-overlay fw_theme_bg_' . $alone_overlay_bg . '" style="opacity: ' . $alone_opacity . ';"></div>';
				}
			}

		} elseif(! is_single()) {
			//
			$archive   = false;
			$post_type = ''; // make post_type empty for categories because is used in section as class

			if( is_category() ){
				$term = get_category( get_query_var('cat'), false );
			}
			else{
				$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
			}

			if( isset($term->taxonomy) ){
				$taxonomy = $term->taxonomy;
				$term_id = $term->term_id;
				$title = $term->name;
				$description = $term->description;
			}
			else{
				$archive = true;
				if( is_post_type_archive('product')){
					$title = esc_html__('Products', 'alone');
				}
				elseif( is_search() ){
					$title = esc_html__( 'Search results', 'alone' );
				}
				else{
					$title = alone_get_the_archive_title();
          // for blog page
          if( is_home() ) {
              $page_for_posts = get_option( 'page_for_posts' );
              $title = ($page_for_posts != 0) ? get_the_title($page_for_posts) : esc_html__('Blog', 'alone');
          }
				}
			}

			// overlay
			$title_bar_overlay_options = isset( $general_title_bar_options['title_bar_overlay_options'] ) ? $general_title_bar_options['title_bar_overlay_options'] : array();
			if(isset($title_bar_overlay_options['title_bar_overlay']) && $title_bar_overlay_options['title_bar_overlay'] == 'yes') {
				$alone_overlay_bg = $title_bar_overlay_options['yes']['title_bar_overlay_color']['id'];
				$alone_opacity    = $title_bar_overlay_options['yes']['title_bar_overlay_opacity_image'] / 100;
				if ( $alone_overlay_bg == 'fw-custom' ) {
					if ( ! empty( $title_bar_overlay_options['yes']['title_bar_overlay_color']['color'] ) ) {
						$alone_overlay_style = '<div class="fw-main-row-overlay" style="background-color: ' . $title_bar_overlay_options['yes']['title_bar_overlay_color']['color'] . '; opacity: ' . $alone_opacity . ';"></div>';
					}
				} else {
					$alone_overlay_style = '<div class="fw-main-row-overlay fw_theme_bg_' . $alone_overlay_bg . '" style="opacity: ' . $alone_opacity . ';"></div>';
				}
			}

			if( is_post_type_archive('product') ){
				// if is product archive page
				$shop_page_id = get_option( 'woocommerce_shop_page_id' );
				$image = fw_get_db_post_option($shop_page_id, 'header_image', '');
				$title = get_the_title($shop_page_id);
			}
			/* elseif( $archive ){
				// if is archive
				$post_type = get_post_type( $post );
				$image = isset($general_title_bar_options['title_bar_image']) ? $general_title_bar_options['title_bar_image'] : array();
			} */
			else{
				$post_type = get_post_type( $post );
				$image = isset($general_title_bar_options['title_bar_image']) ? $general_title_bar_options['title_bar_image'] : array();

				/* check is product cat page */
				if( function_exists('is_product_category') && is_product_category() ) {
					$category_options = fw_get_db_term_option($term_id, $taxonomy, '', '');

					$header_image_data = fw_akg('custom_header_image', $category_options);
					$header_title = fw_akg('custom_category_title', $category_options);

					// GEWIJZIGD: PROBEER HET OOK NOG EENS MET DE MOEDERCATEGORIE
					if ( empty($header_image_data) ) {
						$parent_term_id = absint( get_term( $term_id, 'product_cat' )->parent );
						$category_options = fw_get_db_term_option( $parent_term_id, $taxonomy, '', '' );
						$header_image_data = fw_akg( 'custom_header_image', $category_options );
					}

					if(! empty($header_image_data)) { $image = $header_image_data; }
					if(! empty($header_title)) { $title = $header_title; }
				}
			}

		} else {
			// for single post
			$post_id       = $post->ID;
			$image         = fw_get_db_post_option($post_id, 'header_image', '');
			if($image == ''){
				// if image from post is empty - get image from general theme settings
				$image = isset($general_title_bar_options['title_bar_image']) ? $general_title_bar_options['title_bar_image'] : array();
				// GEWIJZIGD: Geen title bar tonen indien header image niet expliciet ingevuld
				return;
			}

			$title = get_the_title($post_id);

			// overlay
			$title_bar_overlay_options = isset( $general_title_bar_options['title_bar_overlay_options'] ) ? $general_title_bar_options['title_bar_overlay_options'] : array();
			if(isset($title_bar_overlay_options['title_bar_overlay']) && $title_bar_overlay_options['title_bar_overlay'] == 'yes') {
				$alone_overlay_bg = $title_bar_overlay_options['yes']['title_bar_overlay_color']['id'];
				$alone_opacity    = $title_bar_overlay_options['yes']['title_bar_overlay_opacity_image'] / 100;
				if ( $alone_overlay_bg == 'fw-custom' ) {
					if ( ! empty( $title_bar_overlay_options['yes']['title_bar_overlay_color']['color'] ) ) {
						$alone_overlay_style = '<div class="fw-main-row-overlay" style="background-color: ' . $title_bar_overlay_options['yes']['title_bar_overlay_color']['color'] . '; opacity: ' . $alone_opacity . ';"></div>';
					}
				} else {
					$alone_overlay_style = '<div class="fw-main-row-overlay fw_theme_bg_' . $alone_overlay_bg . '" style="opacity: ' . $alone_opacity . ';"></div>';
				}
			}
		}

		if(! empty($image) ||
			(
				isset($title_bar_overlay_options['title_bar_overlay_options']) &&
				$title_bar_overlay_options['title_bar_overlay_options'] == 'yes'
			)
		){
			// echo '<pre>'; print_r($general_title_bar_options); echo '</pre>';
			$extra_classes = $bg_style = $bg_color = $content_align = $space_top_bottom = $data_parallax = '';

			// content align
			$content_align = $general_title_bar_options['content_align'];

			// background color
			if(isset($general_title_bar_options['background_color'])) {
				if( $general_title_bar_options['background_color']['id'] == 'fw-custom' ){
					$bg_color = $general_title_bar_options['background_color']['color'];
				} else {
					$bg_color = $general_color_settings[$general_title_bar_options['background_color']['id']];
				}
			}

			// space top
			if(isset($general_title_bar_options['title_bar_top']) && !empty($general_title_bar_options['title_bar_top']))
				$space_top_bottom .= 'padding-top: '. (int) $general_title_bar_options['title_bar_top'] .'px;';

			// space bottom
			if(isset($general_title_bar_options['title_bar_bottom']) && !empty($general_title_bar_options['title_bar_bottom']))
				$space_top_bottom .= 'padding-bottom: '. (int) $general_title_bar_options['title_bar_bottom'] .'px;';

			$replace_arr = array(
				'{bg_image}' 			=> $image['url'],
				'{bg_repeat}' 		=> $general_title_bar_options['title_bar_image_repeat'],
				'{bg_position_x}' => $general_title_bar_options['title_bar_image_position_x'],
				'{bg_position_y}' => $general_title_bar_options['title_bar_image_position_y'],
				'{bg_size}' 			=> $general_title_bar_options['title_bar_image_size'],
				'{bg_color}' 			=> $bg_color,
			);

			$bg_style = str_replace(
				array_keys($replace_arr),
				array_values($replace_arr),
				"background: url({bg_image}) {bg_repeat} {bg_position_x} {bg_position_y} / {bg_size}, {bg_color};"
				// "background: url({bg_image}) {bg_repeat} {bg_position_x} {bg_position_y} / {bg_size}, {bg_color};"
			);

			if ( isset($general_title_bar_options['parallax']) && $general_title_bar_options['parallax']['selected'] == 'yes' ) :
				$extra_classes .= ' parallax-section';
				$data_parallax = 'data-stellar-background-ratio='.( (int) $general_title_bar_options['parallax']['yes']['parallax_speed']/10).'';
			endif;

			?>
			<section
				class="fw-title-bar fw-main-row-custom fw-main-row-top fw-content-vertical-align-middle fw-section-image fw-section-default-page <?php echo esc_attr($post_type); ?> <?php echo esc_attr($extra_classes); ?>" <?php echo esc_attr($data_parallax); ?>
				style="<?php echo esc_attr($bg_style); ?>">
				<?php echo "{$alone_overlay_style}"; ?>
				<div class="container" style="<?php echo esc_attr($space_top_bottom); ?>">
					<div class="row">
						<div class="col-sm-12">
							<div class="fw-heading <?php echo esc_attr($content_align); ?>">
								<h1 class="fw-special-title"><?php echo alone_translate($title); ?></h1>
								<?php if($description != '' ) : ?>
									<div class="fw-special-subtitle"><?php echo alone_translate($description); ?></div>
								<?php endif; ?>
								<!-- GEWIJZIGD: Breadcrumb nooit tonen in header -->
								<?php if( function_exists('fw_ext_breadcrumbs') && bearsthemes_check_is_bbpress() == 'DISABLE' ) fw_ext_breadcrumbs('<span class="ion-ios-arrow-right"></span>'); ?>
							</div>
						</div>
					</div>
				</div>
			</section>
		<?php
		}
		else{ ?>
			<div class="no-header-image"></div>
		<?php }
	}
endif;

if(!function_exists('alone_fw_ext_page_builder_is_builder_post')) :
	function alone_fw_ext_page_builder_is_builder_post($post_id = '')
	{
		if ( ! defined( 'FW' ) ) return false;
		return false;
		//return fw()->extensions->get('page-builder')->is_builder_post($post_id);
	}
endif;

if(!function_exists('alone_get_extra_typography')) :
	/**
	 * alone_get_extra_typography
	 * @param [string] $type : element_select_option | get_style_font_by_name | build_class_css
	 * @param [string] $name
	 * @return [array]
	 */
	function alone_get_extra_typography($type = null, $name = null) {
		if ( ! defined( 'FW' ) ) return false;

		$customizer_option = (function_exists('fw_get_db_customizer_option')) ? fw_get_db_customizer_option('typography_settings/extra_typography') : array();
		$alone_color_settings = (function_exists('fw_get_db_customizer_option')) ? fw_get_db_customizer_option('color_settings') : array();
		$result = null;

		switch($type) {
			case 'element_select_option' :
				if(count($customizer_option > 0)) {
					$result = array();
					/* default */
					array_push($result, array('value' => '', 'label' => esc_html__('Default', 'alone')));

					foreach($customizer_option as $index => $item) {
						$label = !empty($item['name']) ? $item['name'] : 'Custom Form';
						$value = "{$index}-" . str_replace(' ', '-', $label);
						array_push($result, array('value' => $value, 'label' => $label));
					}
				}
				break;

			case 'get_style_font_by_name' :
				if(count($customizer_option > 0) && !empty($name)) {
					foreach($customizer_option as $index => $item) {
						$label = !empty($item['name']) ? $item['name'] : 'Custom Form';
						$value = "{$index}-" . str_replace(' ', '-', $label);

						$value_trim = trim($value);
						$name_trim = trim($name);
						if($value_trim == $name_trim){
							$result = alone_get_font_array($item['typography'], $alone_color_settings);
							break;
						}
					}
				}
				break;

			case 'build_class_css' :
				if(count($customizer_option > 0)) {
					$result = '';
					foreach($customizer_option as $index => $item) {
						$font_data = alone_get_font_array($item['typography'], $alone_color_settings);
						if(!empty($item['class'])) {
							$result = sprintf(
								'%s {%s}',
								$item['class'],
								alone_css_build_font_style($font_data)
							);
						}
					}
				}
				break;
		}

		// echo '<pre>'; print_r($result); echo '</pre>';
		return $result;
	}
endif;

if(!function_exists('alone_css_build_font_style')) :
	/**
	 * alone_css_build_font_style
	 * @param [array] $font_data
	 * @return [string] css
	 */
	function alone_css_build_font_style($font_data = array()) {
		$style = array();
		foreach($font_data as $name => $value) {
			if(!empty($value))
				$style[] = "{$name}: {$value}";
		}

		return implode('; ', $style);
	}
endif;

if(!function_exists('alone_generate_random_key')) :
	/**
	 * alone_generate_random_key
	 * @param [int] $length
	 */
	function alone_generate_random_key($length = 10) {
	    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}
endif;

if(! function_exists('alone_product_add_to_cart_button')) :
	function alone_product_add_to_cart_button($pid = null, $data = array()) {
		$output = '';
		$product = wc_get_product( $pid );

		$text = isset($data['text']) ? $data['text'] : __('Add To Cart', 'alone');
		$option_text = isset($data['text']) ? $data['text'] : __('Select Options', 'alone');

		$button_text_default = array(
			'external' 	=> __( 'Buy product', 'alone' ),
			'grouped' 	=> __( 'View products', 'alone' ),
			'simple' 		=> __( 'Add to cart', 'alone' ),
			'variable' 	=> __( 'Select options', 'alone' ),
		);

		$product_type = $product->get_type();
		switch ( trim($product_type) ) {
			case 'external':
				$add_to_cart_text = isset($data['external_text']) 	? $data['external_text'] 	: $button_text_default['external'];
				break;

			case 'grouped':
				$add_to_cart_text = isset($data['grouped_text']) 		? $data['grouped_text'] 	: $button_text_default['grouped'];
				break;

			case 'simple':
				$add_to_cart_text = isset($data['simple_text']) 		? $data['simple_text'] 		: $button_text_default['simple'];
				break;

			case 'variable':
				$add_to_cart_text= isset($data['variable_text']) 		? $data['variable_text'] 	: $button_text_default['variable'];
				break;

			default:
				$add_to_cart_text = __( 'Read more', 'alone' );
		}

		$add_to_cart_class = implode( ' ', array(
						'button',
						'product_type_' . $product_type,
						$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
						$product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : ''
		) );

		$variables = array(
			'{add_to_cart_url}' 	=> $product->add_to_cart_url(),
			'{quantity}' 					=> isset($data['quantity']) ? $data['quantity'] : 1,
			'{product_id}' 				=> $product->get_id(),
			'{product_sku}' 			=> $product->get_sku(),
			'{add_to_cart_class}' => $add_to_cart_class,
			'{add_to_cart_text}' 	=> $add_to_cart_text,
			'{extra_class}'				=> isset($data['extra_class']) ? $data['extra_class'] : '',
			'{title}'							=> isset($button_text_default[$product_type]) ? $button_text_default[$product_type] : $add_to_cart_text,
		);

		$_temp = '<a rel="nofollow" href="{add_to_cart_url}" data-quantity="{quantity}" data-product_id="{product_id}" data-product_sku="{product_sku}" class="{add_to_cart_class} {extra_class}" title="{title}">{add_to_cart_text}</a>';

		return str_replace(array_keys($variables), array_values($variables), $_temp);
	}
endif;

if(!function_exists('alone_get_products')) :
	/**
	 * WooCommerce - get Products function
	 * alone_get_products
	 * @param [array] $data
	 */
	function alone_get_products( $data = array() ) {
		$number  = ! empty( $data['number'] ) ? (int) $data['number'] : -1;
		$show    = ! empty( $data['show'] ) ? $data['show'] : '';
		$orderby = ! empty( $data['orderby'] ) ? $data['orderby'] : 'date';
		$order   = ! empty( $data['order'] ) ? $data['order'] : 'desc';
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();

		$query_args = array(
			'posts_per_page' => $number,
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'no_found_rows'  => 1,
			'order'          => $order,
			'meta_query'     => array()
		);

		if ( empty( $instance['show_hidden'] ) ) {
			$query_args['meta_query'][] = WC()->query->visibility_meta_query();
			$query_args['post_parent']  = 0;
		}

		if ( ! empty( $instance['hide_free'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'DECIMAL',
			);
		}

		$query_args['meta_query'][] = WC()->query->stock_status_meta_query();
		$query_args['meta_query']   = array_filter( $query_args['meta_query'] );

		switch ( $show ) {
			case 'featured' :
				/* Woo 2x
				$query_args['meta_query'][] = array(
					'key'   => '_featured',
					'value' => true
				);
				*/

				/* Woo 3x */
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_term_ids['featured'],
				);
				break;
			case 'onsale' :
				$product_ids_on_sale    = wc_get_product_ids_on_sale();
				$product_ids_on_sale[]  = 0;
				$query_args['post__in'] = $product_ids_on_sale;
				break;
			case 'by-category' :
				if(isset($data['category']) && ! empty($data['category'])) {
					$category_trim = trim($data['category']);
					$slug_cat_arr = explode(',', $category_trim);
					if(count($slug_cat_arr) > 0) {
						$query_args['tax_query'] = array('relation' => 'OR');
						foreach($slug_cat_arr as $slug_item) {
							array_push($query_args['tax_query'], array(
								'taxonomy' => 'product_cat',
		            'field' => 'slug',
		            'terms' => trim($slug_item),
							));
						}
					}
				}
				break;
			case 'by-category-id' :
				if(isset($data['category']) && ! empty($data['category'])) {
					$category_trim = trim($data['category']);
					$term_cat_arr = explode(',', $category_trim);
					if(count($term_cat_arr) > 0) {
						$query_args['tax_query'] = array('relation' => 'OR');
						foreach($term_cat_arr as $term_id) {
							array_push($query_args['tax_query'], array(
								'taxonomy' => 'product_cat',
		            'field' => 'term_id',
		            'terms' => trim($term_id),
							));
						}
					}
				}
				break;
		}

		switch ( $orderby ) {
			case 'price' :
				$query_args['meta_key'] = '_price';
				$query_args['orderby']  = 'meta_value_num';
				break;
			case 'rand' :
				$query_args['orderby']  = 'rand';
				break;
			case 'sales' :
				$query_args['meta_key'] = 'total_sales';
				$query_args['orderby']  = 'meta_value_num';
				break;
			default :
				$query_args['orderby']  = 'date';
		}

		return new WP_Query( apply_filters( 'bearsthemes_woocommerce_products_element_query_args', $query_args ) );
	}
endif;

if(! function_exists('alone_gridmap_masonryhybrid_handle')) :
	/**
	 * alone_gridmap_masonryhybrid_handle
	 * @param $type [string] get | set
	 * @param $grid_name [string]
	 * @param $data [array]
	 */
	function alone_gridmap_masonryhybrid_handle($type = 'get', $grid_name = '', $data = array()) {
		if(empty($grid_name)) return;

		$option_name = "bearsthemes_gridmap_masonryhybrid";
		$option_exists = (get_option($option_name, null) !== null);

		if ($option_exists) { add_option($option_name, json_encode(array())); }

		$options = get_option($option_name);
		$options = json_decode($options, true);
		switch ($type) {
			case 'get':
				return isset($options[$grid_name]) ? $options[$grid_name] : '';
				break;

			case 'set':
				$options[$grid_name] = $data;
				update_option($option_name, json_encode($options));
				break;
		}
	}
endif;

if ( ! function_exists( 'alone_related_articles' ) ) :
	/**
	 * Return post related articles
	 */
	function alone_related_articles($post_count = 2) {
		global $post;
		$taxonomy   = 'post_tag';
		$post_terms = array();
		$terms      = wp_get_post_terms( $post->ID, $taxonomy );
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$post_terms[] = $term->term_id;
			}
		} else {
			// if post have 0 tags
			$taxonomy = 'category';
			$terms    = wp_get_post_terms( $post->ID, $taxonomy );
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$post_terms[] = $term->term_id;
				}
			}
		}

		$args = array(
			'posts_per_page' => 2,
			'orderby'        => 'date',
			'post_status'    => 'publish',
			'post_type'      => 'post',
			'post_type' 		 => get_post_type($post->ID),
			'post__not_in'   => array( $post->ID ),
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'id',
					'terms'    => $post_terms
				),
			)
		);

		$all_posts = new WP_Query( $args );
		// echo '<pre>'; print_r($all_posts->posts); echo '</pre>';
		return $all_posts->posts;
	}
endif;

if(! function_exists('alone_get_header_2_options')) :
	function alone_get_header_2_options() {
		$alone_header_settings = defined( 'FW' ) ? fw_get_db_customizer_option( 'header_settings' ) : array('header_type_picker' => array());
		// header 3 option defaults
		$header_2_options = array_merge(array(
			'header-2' => array(
				'custom_position_logo_menu' => array(
		      'select' => 'no',
		      'yes' => array(
		        'position_logo_sidebar' => array(
							array(
								'name' => esc_html__('Primary Menu', 'alone'),
								'width' => 40,
								'type' => array(
									'select' => 'menu',
									'menu'=> array(
										'menu_type' => 'primary',
									)
								),
								'content_align' => 'text-left',
								'custom_class'	=> '',
							),
							array(
								'name' => esc_html__('Logo', 'alone'),
								'width' => 20,
								'type' => array(
									'select' => 'logo',
								),
								'content_align' => 'text-center',
								'custom_class'	=> '',
							),
							array(
								'name' => esc_html__('Secondary Menu', 'alone'),
								'width' => 40,
								'type' => array(
									'select' => 'menu',
									'menu'=> array(
										'menu_type' => 'secondary',
									)
								),
								'content_align' => 'text-right',
								'custom_class'	=> '',
							),
		        )
		      )
		    ),
			),
		), $alone_header_settings['header_type_picker']);
		return $header_2_options;
	}
endif;

if(! function_exists('alone_load_header_2')) :
	function alone_load_header_2() {
		$header_2_options = alone_get_header_2_options();
		$data = $header_2_options['header-2']['custom_position_logo_menu']['yes']['position_logo_menu'];
		// echo '<pre>'; print_r($data); echo '</pre>';
		/* check $data not empty */
		if(count($data) <= 0) return;

		foreach($data as $item) :
			$style_inline = "width: {$item['width']}%";

			switch ($item['type']['select']) {
				case 'menu':
					if($item['type']['menu']['menu_type'] == 'primary') {
						?>
						<div class="bt-container-menu bt-menu-primary bt-icell bt-icell-align-middle <?php echo esc_attr($item['content_align']); ?> <?php echo esc_attr($item['custom_class']); ?>" style="<?php echo esc_attr($style_inline); ?>">
	  					<div class="bt-nav-wrap" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement" role="navigation">
	  						<?php fw_theme_nav_menu( 'primary' ); ?>
	  					</div>
						</div>
						<?php
					} elseif($item['type']['menu']['menu_type'] == 'secondary') {
						?>
						<div class="bt-container-menu bt-menu-secondary bt-icell bt-icell-align-middle <?php echo esc_attr($item['content_align']); ?> <?php echo esc_attr($item['custom_class']); ?>" style="<?php echo esc_attr($style_inline); ?>">
	  					<div class="bt-nav-wrap" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement" role="navigation">
	  						<?php fw_theme_nav_menu( 'secondary' ); ?>
	  					</div>
	  				</div>
						<?php
					}
					break;

				case 'logo':
					?>
					<div class="bt-container-logo bt-icell bt-icell-align-middle <?php echo esc_attr($item['content_align']); ?> <?php echo esc_attr($item['custom_class']); ?>" style="<?php echo esc_attr($style_inline); ?>">
  					<?php alone_logo(); ?>
  				</div>
					<?php
					break;
			}
		endforeach;
	}
endif;

if(! function_exists('alone_get_header_3_options')) :
	/**
	 * alone_get_header_3_options
	 *
	 */
	function alone_get_header_3_options() {
		$alone_header_settings = defined( 'FW' ) ? fw_get_db_customizer_option( 'header_settings' ) : array('header_type_picker' => array());
		// header 3 option defaults
		$header_3_options = array_merge(array(
			'header-3' => array(
				'custom_position_logo_sidebar' => array(
		      'select' => 'no',
		      'yes' => array(
		        'position_logo_sidebar' => array(
							array(
								'name' => esc_html__('Sidebar Left', 'alone'),
								'width' => 40,
								'type' => array(
									'select' => 'sidebar',
									'sidebar'=> array(
										'sidebar_id' => 'blank',
									)
								),
								'content_align' => 'text-left',
								'custom_class'	=> '',
							),
							array(
								'name' => esc_html__('Logo', 'alone'),
								'width' => 20,
								'type' => array(
									'select' => 'logo',
								),
								'content_align' => 'text-center',
								'custom_class'	=> '',
							),
							array(
								'name' => esc_html__('Sidebar Right', 'alone'),
								'width' => 40,
								'type' => array(
									'select' => 'sidebar',
									'sidebar'=> array(
										'sidebar_id' => 'blank',
									)
								),
								'content_align' => 'text-right',
								'custom_class'	=> '',
							),
		        )
		      )
		    ),
				'logo_sidebar_padding_top' => 15,
				'logo_sidebar_padding_bottom' => 15,
				'logo_sidebar_bg_color'	=> '#ffffff',
				'logo_sidebar_shadow_effect' => array(
					'select' => 'yes',
					'yes' => array(
						'shadow_color' => '#222222',
					)
				)
			),
		), $alone_header_settings['header_type_picker']);
		return $header_3_options;
	}
endif;

if(! function_exists('alone_load_logo_sidebar_header_3')) :
	/*
	 * alone_load_logo_sidebar_header_3
	 */
	function alone_load_logo_sidebar_header_3() {
		$header_3_options = alone_get_header_3_options();
		$data = $header_3_options['header-3']['custom_position_logo_sidebar']['yes']['position_logo_sidebar'];

		/* check $data not empty */
		if(count($data) <= 0) return;

		/* each item */
		foreach($data as $item) {
			$classes = implode(' ', array($item['content_align'], $item['custom_class']));
		?>
			<div class="bt-icell bt-icell-align-middle" style="width: <?php echo esc_attr($item['width'] . '%'); ?>">
				<div class="<?php echo esc_attr($classes); ?>">
					<?php
					switch ($item['type']['select']) {
						/* logo */
						case 'logo': ?>
							<div class="bt-container-logo <?php echo esc_attr($item['custom_class']); ?>"><?php alone_logo(); ?></div>
							<?php break;

						/* sidebar */
						case 'sidebar':
							$sidebar_id = (isset($item['type']['sidebar']['sidebar_id']) && ! empty($item['type']['sidebar']['sidebar_id'])) ? $item['type']['sidebar']['sidebar_id'] : 'blank';
							if($sidebar_id != 'blank') { ?>
								<div class="header-sidebar-item <?php echo esc_attr($item['custom_class']); ?>">
									<?php dynamic_sidebar( $sidebar_id ); ?>
								</div>
							<?php }
							break;
					}
					?>
				</div>
			</div>
		<?php }
	}
endif;

if(! function_exists('alone_load_menu_header_3')) :
	function alone_load_menu_header_3() {
		$header_3_options = alone_get_header_3_options();
		$data = $header_3_options['header-3']['custom_position_menu']['yes']['position_menu'];
		// echo '<pre>'; print_r($data); echo '</pre>';
		if(count($data) <= 0) return;

		foreach($data as $item) :
			$style_inline = "width: {$item['width']}%";
			if($item['menu_type'] == 'primary') {
				?>
				<div class="bt-container-menu bt-menu-primary bt-icell bt-icell-align-middle <?php echo esc_attr($item['content_align']); ?> <?php echo esc_attr($item['custom_class']); ?>" style="<?php echo esc_attr($style_inline); ?>">
					<div class="bt-nav-wrap" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement" role="navigation">
						<?php fw_theme_nav_menu( 'primary' ); ?>
					</div>
				</div>
				<?php
			} elseif($item['menu_type'] == 'secondary') {
				?>
				<div class="bt-container-menu bt-menu-secondary bt-icell bt-icell-align-middle <?php echo esc_attr($item['content_align']); ?> <?php echo esc_attr($item['custom_class']); ?>" style="<?php echo esc_attr($style_inline); ?>">
					<div class="bt-nav-wrap" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement" role="navigation">
						<?php fw_theme_nav_menu( 'secondary' ); ?>
					</div>
				</div>
				<?php
			}
		endforeach;
	}
endif;

if(! function_exists('alone_builder_options_header')) :
	function alone_builder_options_header() {
		$alone_header_settings = defined( 'FW' ) ? fw_get_db_customizer_option( 'header_settings' ) : array();
		$alone_logo_settings = defined( 'FW' ) ? fw_get_db_customizer_option( 'logo_settings' ) : array();

		/* Result options */
		$result = array(
			'alone_enable_header_top_bar' 	=> isset( $alone_header_settings['enable_header_top_bar']['selected_value'] ) 				? $alone_header_settings['enable_header_top_bar']['selected_value'] 					: 'no',
			'alone_header_logo_align' 			=> isset( $alone_header_settings['header_type_picker']['header-1']['logo_align'] ) 		? $alone_header_settings['header_type_picker']['header-1']['logo_align'] 			: '',
			'alone_logo_retina' 						=> isset( $alone_logo_settings['logo']['image']['retina_logo'] ) 											? $alone_logo_settings['logo']['image']['retina_logo'] 												: '',
			'alone_header_full_content' 		=> isset( $alone_header_settings['enable_header_full_content'] ) 											? $alone_header_settings['enable_header_full_content'] 												: '',
			'alone_header_menu_position' 		=> isset( $alone_header_settings['header_menu_position'] ) 														? $alone_header_settings['header_menu_position'] 															: '', // fw-menu-position-right | fw-menu-position-left | fw-menu-position-center
			'alone_absolute_header' 				=> isset( $alone_header_settings['enable_absolute_header'] ) 													? $alone_header_settings['enable_absolute_header']['selected_value'] 					: '',
			'alone_sticky_header' 					=> isset( $alone_header_settings['enable_sticky_header'] ) 														? $alone_header_settings['enable_sticky_header']['selected_value'] 						: '',
			'alone_header_2_options' 				=> alone_get_header_2_options(),
			'alone_header_3_options' 				=> alone_get_header_3_options(),
		);

		/* START - Overide options use $_GET */
		$opts_overide = array('alone_enable_header_top_bar', 'alone_header_full_content', 'alone_header_menu_position', 'alone_absolute_header', 'alone_sticky_header');
		if(isset($_GET) && ! empty($_GET) && is_array($_GET) && count($_GET) > 0) {
			foreach($_GET as $name => $value) {
				/* check $name in opts overide then overide */
				if(in_array($name, $opts_overide)) $result[$name] = $value;
			}
		}
		/* END - Overide options use $_GET */

		return $result;
	}
endif;

if(! function_exists('alone_get_options_portfolio')) :
function alone_get_options_portfolio() {
	// echo '<pre>'; print_r(fw_get_db_customizer_option()); echo '</pre>';
	$default_settings = array(
		'portfolio_type' => 'default',
		'number_portfolio_per_page' => get_option('posts_per_page'),
		'number_portfolio_in_row' => 3,
		'show_filter' => 'no',
		'portfolio_single' => array(
			'show_comment' => 'no'
		)
	);
	$alone_portfolio_settings = defined('FW') ? fw_get_db_customizer_option('portfolio_settings') : $default_settings;

	return $alone_portfolio_settings;
}
endif;

if(! function_exists('alone_get_category_by_id')) :
	/**
	 * alone_get_category_by_id
	 *
	 * @param [int] $product_id
	 * @return [array]
	 */
	function alone_get_category_by_id($product_id = 0) {
		$term_list = wp_get_post_terms($product_id, 'product_cat');
		return $term_list;
	}
endif;

if( ! function_exists('alone_get_all_product_cat') ) :
	function alone_get_all_product_cat($params = array()) {
		$taxonomy = 'product_cat';

		$args = array();
		if(isset ($params['hide_empty'])) {
			$args['hide_empty'] = $params['hide_empty'];
		}

		$terms = get_terms($taxonomy, $args);
		// echo '<pre>'; print_r($terms); echo '</pre>';
		return $terms;
	}
endif;

if(! function_exists('alone_build_template_by_product_term')) :
	/**
	 * @param [array] $term_arr
	 * @param [string] $layout (classes, masonry_filter)
	 * @param [array] $params
	 */
	function alone_build_template_by_product_term($term_arr = array(), $layout = 'classes', $params = array()) {
		if(empty($term_arr) && count($term_arr) <= 0) return;
		$output = "";
		switch ($layout) {
			case 'classes':
				foreach($term_arr as $item) {
					$output .= " {$item->slug}";
				}
				break;

			case 'filter_masonry':
				$classes = array('product-filter-wrap', 'product-filter-button-group');
				if(isset ($params['custom_class'])) $classes[] = $params['custom_class'];

				$output .= '<div class="'. implode(' ', $classes) .'">';
				$output .= '<a href="#" data-filter="*" class="product-filter-item is-active">'. esc_html__('All', 'alone') .'</a>';
				foreach($term_arr as $term) {
					if(isset($params['in_slug']) && ! empty($params['in_slug'])) {
						$slug_arr = explode(',', $params['in_slug']);
						if(! in_array($term->slug, $slug_arr)) continue;
					}

					$classes = array('product-filter-item');
					$output .= '<a href="'. esc_attr(get_term_link($term)) .'" class="'. implode(' ', $classes) .'" data-filter=".'. $term->slug .'">'. $term->name .'</a>';
				}
				$output .= '</div>';
				break;
		}

		return $output;
	}
endif;

/**
 * WooCommerce Product Thumbnail
 **/
if ( ! function_exists( 'alone_woocommerce_get_product_thumbnail' ) ) :
	function alone_woocommerce_get_product_thumbnail( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0  ) {
		global $post, $woocommerce;

		ob_start();
		?>
		<div class="woocommerce-imagewrapper">
			<?php echo '<div class="woocommerce_before_thumbnail_loop">'; do_action( 'bearsthemes_woocommerce_before_thumbnail_loop' ); echo '</div>'; ?>
			<?php
			$thumbnail_html = "<img src='". get_template_directory_uri() . '/assets/images/image-default.jpg' ."' alt=''>";
			if ( has_post_thumbnail() ) {
		    // echo get_the_post_thumbnail( $post->ID, $size );
				$thumbnail_html = get_the_post_thumbnail( $post->ID, $size );
		  }

			echo sprintf('<a class="woocommerce-product-link" href="%s" title="%s">%s</a>', get_permalink(), get_the_title(), $thumbnail_html);
			?>
			<?php echo '<div class="woocommerce_after_thumbnail_loop">'; do_action( 'bearsthemes_woocommerce_after_thumbnail_loop' ); echo '</div>'; ?>
		</div>
		<?php
		$output = ob_get_clean();
	  return $output;
	}
endif;

if( ! function_exists( 'bearsthemes_check_is_bbpress' ) ) :
	// is_bbpress()
	function bearsthemes_check_is_bbpress() {
		if(function_exists('is_bbpress') && is_bbpress() == true)
			return true;
		else
			return false;
	}
endif;

if(! function_exists('bearsthemes_build_rating_star')) :
	function bearsthemes_build_rating_star($number_star = 5, $number_active = 0) {
		$output = "";
		for($i = 1; $i <= $number_star; $i++) {
			$class_star = 'fa fa-star-o';
			if($number_active >= $i) { $class_star = 'fa fa-star'; }
			$output .= "<span><i class='{$class_star}'></i></span>";
		}

		return '<div class="rating-star-ui">' . $output . '</div>';
	}
endif;

if(! function_exists('bearsthemes_get_gallery_grid_by_post_id')) :
	function bearsthemes_get_gallery_by_post_id($post_id, $params = array()) {
		$TBFW = defined( 'FW' );
		if ($TBFW ) {
			$post_settings    = alone_get_settings_by_post_id($post_id);
			$gallery_data			= $post_settings['post_gallery_tab']['gallery_images'];
			$image_size				= isset($post_settings['post_general_tab']['image_size']) ? $post_settings['post_general_tab']['image_size'] : 'medium-large'; // $post_settings['post_general_tab']['image_size'] : 'medium-large' ;
		} else {
			return;
		}

		//
		if(isset($params['gallery_data'])) 	$gallery_data = $params['gallery_data'];

		if(empty($gallery_data) || count($gallery_data) <= 0) return;

		$current_user = wp_get_current_user();

		/* masonryhybrid opts */
		$masonryhybrid_opts = array(
			'itemSelector'=> '.grid-item_',
			'columnWidth' => '.grid-sizer_',
			'gutter'			=> '.gutter-sizer_',
		  'col'         => 3,
		  'space'       => 1,
		  'responsive'  => false, //json_decode('{"420":{"col":3},"860":{"col":3}}', true),
		);

		$masonryhybrid_resize_opts = array(
	    'celHeight' => 120,
	    'grid_name' => 'gallery-layout-grid-post-' . $post_id,
	  );

		// START overide params
		if(isset($params['image_size'])) 		$image_size = $params['image_size'];
		if(isset($params['col'])) 					$masonryhybrid_opts['col'] = $params['col'];
		if(isset($params['space'])) 				$masonryhybrid_opts['space'] = $params['space'];
		if(isset($params['responsive'])) 		$masonryhybrid_opts['responsive'] = $params['responsive'];
		if(isset($params['grid_name'])) 		$masonryhybrid_resize_opts['grid_name'] = $params['grid_name'];
		if(isset($params['celHeight'])) 		$masonryhybrid_resize_opts['celHeight'] = $params['celHeight'];
		// END overide params

		/* load grid map */
		$grid_map = alone_gridmap_masonryhybrid_handle('get', $masonryhybrid_resize_opts['grid_name']);
		if(! empty($grid_map)) {
			$masonryhybrid_resize_opts['sizeMap'] = $grid_map;
		}

		/* check admin login can resize item */
		if(user_can( $current_user, 'administrator' )) {
			$masonryhybrid_resize_opts['resize'] = true;
		}

		$masonryhybrid_resize_attr = "data-bears-masonryhybrid-resize='". json_encode($masonryhybrid_resize_opts, true) ."'";

		/* lightgallery opts */
		$lightgallery_opts = array(
		  'selector'  => '.icon-zoom',
		);

		$array_variable = array(
		  '{masonryhybrid_opts}'        => json_encode($masonryhybrid_opts),
		  '{lightgallery_opts}'         => json_encode($lightgallery_opts),
		  '{content}'                   => '',
		  '{masonryhybrid_resize_attr}' => $masonryhybrid_resize_attr,
		);

		$gallery_temp = '
    <div class="masonry-hybrid-wrap" data-bears-masonryhybrid=\'{masonryhybrid_opts}\' {masonryhybrid_resize_attr} data-bears-lightgallery=\'{lightgallery_opts}\'>
      <div class="grid-sizer_"></div>
      <div class="gutter-sizer_"></div>
      {content}
    </div>';

		foreach($gallery_data as $item) {
			$thumb_src = wp_get_attachment_image_src($item['attachment_id'], $image_size);
			$array_variable['{content}'] .= "
			<div class='grid-item_'>
				<div class='grid-item-inner_'>
					<a href='{$item['url']}' class='icon-zoom' style='background: url({$thumb_src[0]}) no-repeat center center / cover;'>

					</a>
				</div>
			</div>";
		}

		return str_replace(array_keys($array_variable), array_values($array_variable), $gallery_temp);;
	}
endif;

if(! function_exists('alone_render_recipe_media_single')) :
  function alone_render_recipe_media_single($post_id = null, $template = null) {
    global $post;
    $post_id = ! empty($post_id) ? $post_id : $post->ID;
		$recipe_post_data = array(
			'gallery_data' 	=> array(),
			'video' 				=> '',
			'class_wrap'		=> '',
			'class_item'		=> '',
		);

		$TBFW = defined( 'FW' );
		if ($TBFW ) {
		  $post_settings    = fw_get_db_post_option($post_id);
		  $recipe_post_data['gallery_data']    = (isset($post_settings['gallery_images']) && count($post_settings['gallery_images']) > 0) ? $post_settings['gallery_images'] : array();
		  $recipe_post_data['video']           = (isset($post_settings['video']) && ! empty($post_settings['video'])) ? $post_settings['video'] : '';
		}

		$gallery_params = array(
		  'gallery_data'  => $recipe_post_data['gallery_data'],
		  'image_size'    => 'large',
		  'grid_name'     => 'recipe-single-gallery-id-' . $post_id,
		  'col'           => 3,
		  'celHeight'     => 110,
		  'space'         => 5,
		  'responsive'    => json_decode('{"420":{"col":2},"860":{"col":3}}', true)
		);

		$image_background_elem = '';
		if ( has_post_thumbnail($post_id) ) { // check if the post has a Post Thumbnail assigned to it.
		  $style_inline = "background: url(". get_the_post_thumbnail_url($post_id, 'large') .") center center / cover;";
		  $image_background_elem = "<div class='background-overlay' style='{$style_inline}' data-stellar-background-ratio='0.8'></div>";
		}

    switch ($template) {
      case 'recipe_fully_template':
				$recipe_post_data['class_wrap'] = 'col-md-4';
				$recipe_post_data['class_item'] = '';
        break;

      default:
				$recipe_post_data['class_wrap'] = 'row';
				$recipe_post_data['class_item'] = 'col-md-4';
				$gallery_params['celHeight'] = 80;
				$gallery_params['responsive'] = json_decode('{"420":{"col":3},"860":{"col":3}}', true);
        break;
    }
		// echo '<pre>'; print_r($recipe_post_data); echo '</pre>';
		?>
		<div class="<?php echo esc_attr($recipe_post_data['class_wrap'] . ' recipe-media-template-' . $template); ?>">
			<div class="recipe-media-content">
				<?php if(! empty($recipe_post_data['video'])) : ?>
					<div class="<?php echo esc_attr($recipe_post_data['class_item']); ?>">
						<h4 class="title"><?php echo esc_html__('Video', 'alone') ?></h4>
						<div class="recipe-video-wrap" data-bears-lightgallery='{"selector": ".handle-play-wrap"}'>
							<?php echo "{$image_background_elem}"; ?>
							<a class="handle-play-wrap" href="<?php echo esc_attr($recipe_post_data['video']); ?>"><span class="icon-play-video"><i class="ion-ios-play-outline"></i></span><span>Play video</span></a>
						</div>
					</div>
				<?php endif; ?>

				<?php if(count($recipe_post_data['gallery_data']) > 0) : ?>
					<div class="<?php echo esc_attr($recipe_post_data['class_item']); ?>">
						<h4 class="title"><?php echo esc_html__('Gallery', 'alone') ?></h4>
						<?php
						echo  '<div class="recipe-gallery-wrap">' . bearsthemes_get_gallery_by_post_id($post_id, $gallery_params) . '</div>'; ?>
					</div>
				<?php endif; ?>

				<div class="<?php echo esc_attr($recipe_post_data['class_item']); ?>">
					<h4 class="title"><?php echo esc_html__('Sharing', 'alone') ?></h4>
					<?php echo do_shortcode('[x_share title="'. esc_html__(' ', 'alone') .'" facebook="true" twitter="true" google_plus="true" linkedin="true" pinterest="true"]'); ?>
				</div>
			</div>
		</div>
		<?php
  }
endif;

if(! function_exists('alone_share_post')) :
	function alone_share_post($params = array('facebook' => false, 'twitter' => false, 'google_plus' => false, 'linkedin' => false, 'pinterest' => faslse)) {
		global $post;

		$output = '';
		extract($params);

		$post_data = array(
			'{post_link}' => get_permalink($post->ID),
			'{post_title}' => get_the_title($post->ID),
		);

		$share_icon = apply_filters('share_post_filter_icon', array(
			'facebook' 			=> '<span class="fa fa-facebook"></span>',
			'twitter' 			=> '<span class="fa fa-twitter"></span>',
			'google_plus' 	=> '<span class="fa fa-google-plus"></span>',
			'linkedin' 			=> '<span class="fa fa-linkedin"></span>',
			'pinterest' 		=> '<span class="fa fa-pinterest-p"></span>',
		));

		$output = apply_filters('share_post_before_output', $output);

		/* facebook */
		if($facebook == true) $output .= '<a class="share-post-item s-facebook" href="http://www.facebook.com/sharer.php?u={post_link}" target="_blank" data-toggle="tooltip" title="'. __('Share on Facebook', 'alone') .'" data-share-post>'. $share_icon['facebook'] .'</a>';
		/* twitter */
		if($twitter == true) $output .= '<a class="share-post-item s-twitter" href="https://twitter.com/share?url={post_link}&text={post_title}" target="_blank" data-toggle="tooltip" title="'. __('Share on Twitter', 'alone') .'" data-share-post>'. $share_icon['twitter'] .'</a>';
		/* google plus */
		if($google_plus == true) $output .= '<a class="share-post-item s-google-plus" href="https://plus.google.com/share?url={post_link}" target="_blank" data-toggle="tooltip" title="'. __('Share on Google+', 'alone') .'" data-share-post>'. $share_icon['google_plus'] .'</a>';
		/* linkedin */
		if($linkedin == true) $output .= '<a class="share-post-item s-linkedin" href="http://www.linkedin.com/shareArticle?mini=true&url={post_link}" target="_blank" data-toggle="tooltip" title="'. __('Share on LinkedIn', 'alone') .'" data-share-post>'. $share_icon['linkedin'] .'</a>';
		/* pinterest */
		if($pinterest == true) $output .= '<a class="share-post-item s-pinterest" href="https://pinterest.com/pin/create/bookmarklet/?url={post_link}&description={post_title}" target="_blank" data-toggle="tooltip" title="'. __('Share on Pinterest', 'alone') .'" data-share-post>'. $share_icon['pinterest'] .'</a>';

		$output = apply_filters('share_post_after_output', $output);

		return ! empty( $output ) ? implode('', array(
			'<div class="share-post-wrap">',
				str_replace(array_keys($post_data), array_values($post_data), $output),
			'</div>'
		)) : '';
	}
endif;

if(! function_exists('alone_give_get_donor')) :
	function alone_give_get_donor($args = array()) {
		if (! class_exists( 'Give' ) ) return;
		$donors = array();

		if( $args['type'] == 'latest' ) {

			$_args = array(
				'number' => $args['number'],
			);
			$donors = Give()->customers->get_customers( $_args );

		} elseif ( $args['type'] == 'by_ID' ) {

			$_args = array(
				'give_forms' => $args['give_forms'],
			);
			$donations = give_get_payments( $_args );

			if(! empty($donations) && count($donations) > 0) :
				foreach($donations as $donation) :

					//Now get donor information from this donation ("customer" aka "donor")
					$customer_id = give_get_payment_donor_id( $donation->ID );
					$customer    = new Give_Customer( $customer_id );

					$donors[] = $customer;
				endforeach;
			endif;
		}

		return $donors;
	}
endif;

if(! function_exists('alone_give_donors_slide')) :
	function alone_give_donors_slide($donors = array()) {
		if(empty($donors) && count($donors) <= 0) return;
		$output = '<div {owl_atts}>{content}</div>';

		$items = array();
		foreach($donors as $item) :
			$avatar_url = get_avatar_url($item->email, array('size' => 120));
			$items[] = implode('', array(
				'<div class="item">',
					'<img src="'. $avatar_url .'" alt="#">',
				'</div>',
			));
		endforeach;

		$owl_atts = html_build_attributes(array(
			'class' => 'owl-carousel',
			'data-bears-owl-carousel' => json_encode(array(
				'items' => 1,
				'nav' => false,
				'autoplay' => true,
				'margin' => 4,
				'autoplaySpeed' => 500,
			))
		));

		$variable = array(
			'{content}' => implode('', $items),
			'{owl_atts}' => $owl_atts,
		);

		return str_replace(array_keys($variable), array_values($variable), $output);
	}
endif;

if(! function_exists('alone_load_icon_v2')) :
	/**
	 * alone_load_icon_v2
	 * @since 0.0.7
	 */
	function alone_load_icon_v2($data = array(), $custom_class = '') {
		$type = fw_akg('type', $data);
		$output = '';
		switch ($type) {
			case 'custom-upload':
				$output = "<img src='". fw_akg('url', $data) ."' alt='#' class='icon-type-v2 {$custom_class}'>";
				break;

			case 'icon-font':
				$output = "<span class='icon-type-v2 {$custom_class}'><i class='". fw_akg('icon-class', $data) ."'></i></span>";
				break;
		}

		return $output;
	}
endif;
