<?php
	function contains( $allergens ) {
		$parts = explode( '|', $allergens );
		$none = false;
		$other = false;
		foreach ( $parts as $part ) {
			$term = explode( '>', $part );
			if ( $term[0] == 'Geen meldingsplichtige allergenen' ) {
				$none = true;
			} elseif( $term[0] == 'Kan sporen bevatten van' ) {
				$other = true;
			} elseif ( $term[0] == 'Product bevat' ) {
				$contains[] = decode_html($term[1]);
			}
		}
		if ( ! isset($contains) and ( $none or $other ) ) {
			return '/';
		} else {
			return implode( ', ', $contains );	
		}
	}

	function may_contain( $allergens ) {
		$parts = explode( '|', $allergens );
		$none = false;
		$other = false;
		foreach ( $parts as $part ) {
			$term = explode( '>', $part );
			if ( $term[0] == 'Geen meldingsplichtige allergenen' ) {
				$none = true;
			} elseif( $term[0] == 'Product bevat' ) {
				$other = true;
			} elseif ( $term[0] == 'Kan sporen bevatten van' ) {
				$may_contain[] = decode_html($term[1]);
			}
		}
		if ( ! isset($may_contain) and ( $none or $other ) ) {
			return '/';
		} else {
			return implode( ', ', $may_contain );	
		}
	}

	function format_price( $price ) {
		// We gaan ervan uit dat we geen productprijzen boven de 1000 euro hebben
		$price = str_replace( ',', '.', $price );
		if ( $price !== false and floatval($price) !== 0 ) {
			$price = number_format( floatval($price), 2, ',', '' );
		}
		return $price;
	}

	function format_unit_price( $product_id ) {
		$unit_price = format_price( get_post_meta( $product_id, '_unit_price', true ) );
		$unit = get_post_meta( $product_id, '_net_unit', true );
		if ( $unit_price !== false ) {
			if ( $unit === 'cl' ) {
				return '€/l '.$unit_price;
			} else {
				return '€/kg '.$unit_price;
			}
		} else {
			return '';
		}
	}

	function get_net_content( $product_id ) {
		$content = get_post_meta( $product_id, '_net_content', true );
		$unit = get_post_meta( $product_id, '_net_unit', true );
		if ( $content !== false and $unit !== false ) {
			$content = intval( str_replace( ',', '', $content ) );
			if ( $content >= 1000 ) {
				$content = str_replace( '.', ',', $content/1000 );
				$unit = 'k'.$unit;
			}
			return $content.' '.$unit;
		} else {
			return '';
		}
	}

	function get_partner_and_country( $string ) {
		$terms = explode( '|', $string );
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			$partners[] = decode_html( $parts[2].', '.$parts[1] );
		}
		if ( count($partners) > 0 ) {
			sort($partners);
			return implode( ', ', $partners );
		} else {
			return '';
		}
	}

	function get_bio_label( $bio ) {
		$label = mb_strtolower($bio);
		if ( strpos( $label, 'ja' ) !== false or strpos( $label, 'oui' ) !== false or strpos( $label, 'yes' ) !== false ) {
			return ':biobol.psd';
		} else {
			return ':geenbio.psd';
		}
	}

	function get_product_image( $sku ) {
		return ':'.$sku.'.jpg';
	}

	function get_lowest_terms( $string ) {
		$terms = explode( '|', $string );
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			$lowest_terms[] = decode_html( $parts[count($parts)-1] );
		}
		if ( count($lowest_terms) > 0 ) {
			$single_terms = array_unique($lowest_terms);
			sort($single_terms);
			return implode( ', ', $single_terms );
		} else {
			return '';
		}
	}

	function get_second_to_last_terms( $string ) {
		$terms = explode( '|', $string );
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			$second_to_last_terms[] = decode_html( $parts[count($parts)-2] );
		}
		if ( count($second_to_last_terms) > 0 ) {
			$single_terms = array_unique($second_to_last_terms);
			sort($single_terms);
			return implode( ', ', $single_terms );
		} else {
			return '';
		}
	}

	function get_countries_from_partners( $partners ) {
		$terms = explode( '|', $partners );
		$countries = array();
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			// Altijd continent aanwezig op positie 0 dat geknipt kan worden, eventuele partner zit nu op positie 2
			$countries[] = $parts[1];
		}
		if ( count($countries) > 0 ) {
			$single_countries = array_unique($countries);
			sort($single_countries);
			return decode_html( implode( ', ', $single_countries ) );
		} else {
			return '';
		}
	}

	function get_oww_page( $node ) {
		if ( intval($node) > 0 ) {
			return 'https://www.oxfamwereldwinkels.be/node/'.$node;
		} else {
			return '';
		}
	}

	function split_by_paragraph( $text ) {
		$parts = explode( '</p><p>', decode_html($text) );
		$bits = explode( '<br>', $parts[0] );
		$pieces = explode( '<br/>', $bits[0] );
		return wp_strip_all_tags( $pieces[0] );
	}

	// Behouden voor backwards compatibility in talrijke catalogusexports
	function split_after_300_characters( $text ) {
		return split_after_x_characters( $text, 250 );
	}
	
	function split_after_x_characters( $text, $limit = 100 ) {
		$ignored = substr( decode_html($text), 0, $limit );
		if ( substr( $text, $limit ) ) {
			// Zoek het eerste einde van een zin na de minimumhoeveelheid
			$parts = explode( '. ', substr( $text, $limit ) );
			$chopped = $parts[0].'.';
		} else {
			// Tekst is korter dan de limiet, voeg niets meer toe 
			$chopped = '';
		}
		// Verwijder alle line breaks m.b.v. 2de parameter
		return wp_strip_all_tags( $ignored.$chopped, true );
	}

	function decode_html( $value ) {
		return html_entity_decode( $value, ENT_QUOTES );
	}

	function get_stat_uom( $unit ) {
		if ( $unit === 'cl' ) {
			return 'L';
		} else {
			return 'KG';
		}
	}

	function get_fr_name( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'fr' ) );
		if ( $product !== false ) {
			return $product->get_name();
		} else {
			return '';
		}
	}

	function get_fr_description( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'fr' ) );
		if ( $product !== false ) {
			return $product->get_description();
		} else {
			return '';
		}
	}

	function get_fr_short_description( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'fr' ) );
		if ( $product !== false ) {
			return $product->get_short_description();
		} else {
			return '';
		}
	}

	function get_en_name( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'en' ) );
		if ( $product !== false ) {
			return $product->get_name();
		} else {
			return '';
		}
	}

	function get_en_description( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'en' ) );
		if ( $product !== false ) {
			return $product->get_description();
		} else {
			return '';
		}
	}

	function get_en_short_description( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'en' ) );
		if ( $product !== false ) {
			return $product->get_short_description();
		} else {
			return '';
		}
	}

	function get_fr_ingredients( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'fr' ) );
		if ( $product !== false ) {
			return $product->get_meta('_ingredients');
		} else {
			return '';
		}
	}

	function get_en_ingredients( $product_id ) {
		$product = wc_get_product( apply_filters( 'wpml_object_id', $product_id, 'product', false, 'en' ) );
		if ( $product !== false ) {
			return $product->get_meta('_ingredients');
		} else {
			return '';
		}
	}

	function get_products_from_partner( $partner_id ) {
		$products = get_posts( array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'product_partner',
					'field' => 'id',
					'terms' => $partner_id,
				)
			),
		) );

		$product_names = array();
		foreach ( $products as $post ) {
			$product = wc_get_product($post->ID);
			$product_names[$product->get_sku()] = $product->get_sku().' '.$product->get_name();
		}

		asort( $product_names, SORT_NUMERIC );
		return implode( ', ', $product_names );
	}

	function get_visible_product_price( $product_id ) {
		$product = wc_get_product($product_id);
		return html_entity_decode( wp_strip_all_tags( $product->get_price_html() ) );
	}

	// Actiedatums vallen op middernacht, waardoor ze de neiging hebben om een dag vroeger te retourneren wegens tijdzoneverschillen
	function move_date_by_12_hours( $timestamp ) {
		if ( $timestamp > 1000 ) {
			return date( 'Y-m-d', $timestamp + 12*3600 );
		} else {
			return '';
		}
	}

	function price_tags_description( $product_id ) {
		$grape_terms = get_the_terms( $product_id, 'product_grape' );
		
		if ( is_array($grape_terms) ) {
			$grapes = array();
			foreach ( $grape_terms as $term ) {
				$grapes[$term->term_id] = $term->name;
			}
			asort($grapes);
			return 'Druif: '.implode( ', ', $grapes );
		} else {
			$product = wc_get_product($product_id);
			if ( strlen( wp_strip_all_tags( $product->get_short_description(), true ) ) > 0 ) {
				return split_by_paragraph( $product->get_short_description() );
			} else {
				// Val terug op de afgekapte lange beschrijving
				return split_after_x_characters( $product->get_description() );
			}
		}
	}

	function translate_tax( $tax_class ) {
		if ( $tax_class === 'voeding' ) {
			return '6%';
		} elseif ( $tax_class === 'vrijgesteld' ) {
			return '0%';
		} else {
			return '21%';
		}
	}

	function get_woocommerce_single_size( $url ) {
		$image = wp_get_attachment_image_src( attachment_url_to_postid($url), 'woocommerce_single' );
		if ( $image !== false ) {
			// URL staat steeds op 1ste plaats!
			return $image[0];
		} else {
			return $url;
		}
	}
?>