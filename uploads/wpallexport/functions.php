<?php
	function contains( $allergens ) {
		$parts = explode( '|', $allergens );
		foreach ( $parts as $part ) {
			$term = explode( '>', $part );
			if ( $term[0] == 'Product bevat' ) {
				$contains[] = html_entity_decode( $term[1], ENT_QUOTES );
			}
		}
		return implode( ', ', $contains );
	}

	function may_contain( $allergens ) {
		$parts = explode( '|', $allergens );
		foreach ( $parts as $part ) {
			$term = explode( '>', $part );
			if ( $term[0] == 'Kan sporen bevatten van' ) {
				$may_contain[] = html_entity_decode( $term[1], ENT_QUOTES );
			}
		}
		return implode( ', ', $may_contain );
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
				$content = $content/1000;
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
			$partners[] = html_entity_decode( $parts[2].', '.$parts[1], ENT_QUOTES );
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
		if ( $label === 'ja' or $label === 'oui' or $label === 'yes' ) {
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
			$lowest_terms[] = html_entity_decode( $parts[count($parts)-1], ENT_QUOTES );
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
			$second_to_last_terms[] = html_entity_decode( $parts[count($parts)-2], ENT_QUOTES );
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
		$terms = explode( ',', $partners );
		$countries = array();
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			// Altijd continent aanwezig op positie 0 dat geknipt kan worden, eventuele partner zit nu op positie 2
			$countries[] = $parts[1];
		}
		if ( count($countries) > 0 ) {
			$single_countries = array_unique($countries);
			sort($single_countries);
			return html_entity_decode( implode( ', ', $single_countries ), ENT_QUOTES );
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
		$parts = explode( '</p><p>', html_entity_decode( $text, ENT_QUOTES ) );
		$bits = explode( '<br>', $parts[0] );
		$pieces = explode( '<br/>', $bits[0] );
		return wp_strip_all_tags( $pieces[0] );
	}

	function split_after_300_characters( $text ) {
		$text = str_replace( '<br>', ' ', html_entity_decode( $text, ENT_QUOTES ) );
		$text = str_replace( '<br/>', ' ', $text );
		$text = str_replace( '</p><p>', ' ', $text );
		write_log($text);
		$ignored = substr( $text, 0, 300 );
		if ( substr( $text, 300 ) ) {
			$parts = explode( '.', substr( $text, 300 ) );
			$chopped = $parts[0].'.';
		} else {
			$chopped = '';
		}
		return wp_strip_all_tags( $ignored.$chopped );
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
?>