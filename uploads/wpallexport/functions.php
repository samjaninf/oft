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

	function transform_net_unit( $unit = 'g' ) {
		if ( $unit === 'cl' ) {
			return '€/l';
		} else {
			return '€/kg';
		}
	}

	function get_net_content( $product_id ) {
		if ( get_post_meta( $product_id, '_net_content', true ) and get_post_meta( $product_id, '_net_unit', true ) ) {
			return get_post_meta( $product_id, '_net_content', true ).' '.get_post_meta( $product_id, '_net_unit', true );
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
?>