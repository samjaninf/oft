<?php
	function contains( $allergens ) {
		$parts = explode( '|', $allergens );
		foreach ( $parts as $part ) {
			$term = explode( '>', $part );
			if ( $term[0] == 'Product bevat' ) {
				$contains[] = $term[1];
			}
		}
		return implode( ', ', $contains );
	}

	function may_contain( $allergens ) {
		$parts = explode( '|', $allergens );
		foreach ( $parts as $part ) {
			$term = explode( '>', $part );
			if ( $term[0] == 'Kan sporen bevatten van' ) {
				$may_contain[] = $term[1];
			}
		}
		return implode( ', ', $may_contain );
	}

	function format_price( $price ) {
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

	function get_subcategory( $cats ) {
		switch ( $cats ) {
			case stristr( $cats, 'Koffie' ):
				return 'Koffie & thee';
				break;
			case stristr( $cats, 'Ontbijt' ):
				return 'Ontbijt';
				break;
			case stristr( $cats, 'Snacks' ):
				return 'Snacks & drinks';
				break;
			case stristr( $cats, 'Wereldkeuken' ):
				return 'Wereldkeuken';
				break;
			case stristr( $cats, 'Wijn' ):
				return 'Wijn';
				break;
			default:
				return $cats;
				break;
		}
	}

	function get_subcategory_fr( $cats ) {
		switch ( $cats ) {
			case stristr( $cats, 'Koffie' ):
				return 'Café & thé';
				break;
			case stristr( $cats, 'Ontbijt' ):
				return 'Petit déjeuner';
				break;
			case stristr( $cats, 'Snacks' ):
				return 'Snacks & drinks';
				break;
			case stristr( $cats, 'Wereldkeuken' ):
				return 'Cuisine du monde';
				break;
			case stristr( $cats, 'Wijn' ):
				return 'Vin';
				break;
			default:
				return $cats;
				break;
		}
	}

	function get_subcategory_en( $cats ) {
		switch ( $cats ) {
			case stristr( $cats, 'Koffie' ):
				return 'Coffee & tea';
				break;
			case stristr( $cats, 'Ontbijt' ):
				return 'Breakfast';
				break;
			case stristr( $cats, 'Snacks' ):
				return 'Snacks & drinks';
				break;
			case stristr( $cats, 'Wereldkeuken' ):
				return 'World kitchen';
				break;
			case stristr( $cats, 'Wijn' ):
				return 'Wine';
				break;
			default:
				return $cats;
				break;
		}
	}

	function get_countries_from_partners( $partners ) {
		$terms = explode( '|', $partners );
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			// Altijd continent aanwezig op positie 0 dat geknipt kan worden, eventuele partner zit nu op positie 3
			$countries[] = $parts[1];
		}
		if ( count($countries) > 0 ) {
			$single_countries = array_unique($countries);
			sort($single_countries);
			return implode( ', ', $single_countries );
		} else {
			return '';
		}
	}

	function get_partner_and_country( $string ) {
		$terms = explode( '|', $string );
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			$partners[] = $parts[2].', '.$parts[1];
		}
		if ( count($partners) > 0 ) {
			sort($partners);
			return implode( ', ', $partners );
		} else {
			return '';
		}
	}

	function get_bio_label( $bio ) {
		if ( $bio == 'Ja' ) {
			return ':biobol.psd';
		} else {
			return ':geenbio.psd';
		}
	}

	function split_by_paragraph( $text ) {
		$parts = explode( '</p><p>', $text );
		$bits = explode( '<br>', $parts[0] );
		return $bits[0];
	}

	function get_product_image( $sku ) {
		return ':'.$sku.'.jpg';
	}

	function get_oww_page( $node ) {
		if ( intval($node) > 0 ) {
			return 'https://www.oxfamwereldwinkels.be/node/'.$node;
		} else {
			return '';
		}
	}

	function get_lowest_terms( $string ) {
		$terms = explode( '|', $string );
		foreach ( $terms as $term ) {
			$parts = explode( '>', $term );
			$lowest_terms[] = $parts[count($parts)-1];
		}
		if ( count($lowest_terms) > 0 ) {
			$single_terms = array_unique($lowest_terms);
			sort($single_terms);
			return implode( ', ', $single_terms );
		} else {
			return '';
		}
	}
?>