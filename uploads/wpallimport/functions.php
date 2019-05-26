<?php
	function alter_brand( $raw_brand ) {
		switch ( trim( $raw_brand ) ) {
			case 'Oxfam Fairtrade':
				return 'Oxfam Fair Trade';
			case 'Oxfam Wereldwinkels vzw':
				return 'Oxfam-Wereldwinkels';
			case 'Fair Trade Original':
				return 'Fairtrade Original';
			case 'CTM Altromercato':
				return 'Altromercato';
			case 'Not applicable':
				return '';
			default:
				return $raw_brand;
		}
	}

	function only_last_term( $string ) {
		$terms = explode( ', ', $string );
		foreach( $terms as $term ) {
			$parts = explode( '->', $term );
			$last_ones[] = $parts[count($parts)-1];
		}
		return implode( ', ', $last_ones );
	}

	function calculate_net_content_per_kg_l( $stat_conversion, $cu_conversion ) {
		$net_content = 0.0;
		$numerator = floatval( str_replace( ',', '.', $stat_conversion ) );
		$denominator = intval( $cu_conversion );
		// Enkel berekenen indien alle gegevens beschikbaar!
		if ( $numerator > 0.001 and $denominator >= 1 ) {
			$net_content = $numerator / $denominator;
		}
		return $net_content;
	}

	function get_net_content( $stat_conversion, $cu_conversion, $raw_unit ) {
		$net_content = '';
		$fraction = calculate_net_content_per_kg_l( $stat_conversion, $cu_conversion );
		if ( $fraction > 0 ) {	
			if ( $raw_unit == 'L' ) {
				$net_content = number_format( 100*$fraction, 0, '.', '' );
			} elseif ( $raw_unit == 'KG' ) {
				$net_content = number_format( 1000*$fraction, 0, '.', '' );
			}
		}
		return $net_content;
	}

	function get_unit_price( $price, $stat_conversion, $cu_conversion, $raw_unit ) {
		$unit_price = '';
		$numerator = floatval( str_replace( ',', '.', $price ) );
		$denominator = calculate_net_content_per_kg_l( $stat_conversion, $cu_conversion );
		// Enkel berekenen indien alle gegevens beschikbaar!
		if ( $numerator > 0 and $denominator > 0 ) {
			$fraction = $numerator / $denominator;
			$unit_price = number_format( $fraction, 2, '.', '' );
		}
		return $unit_price;
	}

	function ditch_zeros( $value ) {
		if ( $value === '0' or $value === '0,0' or $value === '0,00' or $value === '0,000' ) {
			$value = '';
		}
		return str_replace( ',', '.', $value );
	}

	function transform_decimal_to_point( $string ) {
		$float = floatval( str_replace( ',', '.', $string ) );
		return number_format( $float, 3, '.', '' );
	}

	function get_unit( $raw_unit ) {
		if ( $raw_unit == 'KG' ) {
			$unit = 'g';
		} elseif ( $raw_unit == 'L' ) {
			$unit = 'cl';
		} else {
			$unit = '';
		}
		return $unit;
	}

	// Niet langer nodig, kan automatisch gaan o.b.v. stock en backorderstatus
	function translate_stock_status( $eshop, $can_be_ordered, $stock ) {
		if ( $eshop === 'yes' ) {
			if ( intval($stock) > 0 ) {
				return 'instock';
			} else {
				return 'onbackorder';
			}
		} else {
			return 'outofstock';
		}
	}

	function tweak_stock_quantity( $eshop, $can_be_ordered, $stock ) {
		if ( $can_be_ordered === 'no' ) {
			return 0;
		} else {
			return $stock;
		}
	}

	function skip_creation_untill_forced( $title, $eshop, $can_be_ordered, $main_cat ) {
		if ( $eshop === 'yes' and $can_be_ordered === 'yes' ) {
			return ucfirst( trim( $title ) );
		} else {
			// Een lege titel zorgt ervoor dat een onbestaand product niet automatisch aangemaakt wordt
			return '';
		}
		// Logica toevoegen om titels van niet-voeding toch te updaten? Vergt product-ID!
	}

	function set_non_oft_to_private( $raw_brand, $main_cat, $eshop, $sku ) {
		// Probleem: statusupdate lokt ook datumwijziging uit (ook al blijft de status identiek)
		if ( $eshop === 'yes' ) {
			// Moet zichtbaar zijn, dus forceer status
			if ( alter_brand($raw_brand) === 'Oxfam Fair Trade' and $main_cat === 'FOOD' ) {
				// Verhinder dat nieuw OFT-servicemateriaal zichtbaar wordt voor niet-ingelogde bezoekers
				return 'publish';
			} else {
				return 'private';
			}
		} else {
			// Neem huidige status over
			$product_id = wc_get_product_id_by_sku( $sku );
			if ( $product_id !== false ) {
				return get_post_status( $product_id );
			} else {
				return '';
			}
		}
	}

	function merge_categories( $main_cat, $sub_cat, $group3, $group4 ) {
		$categories = array();
		if ( $main_cat !== 'FOOD' ) {
			$categories[] = $main_cat;
		} else {
			// Laat de catmans de categorie bepalen
			return '';
		}
		if ( $sub_cat !== '' ) {
			$categories[] = $sub_cat;
		}
		// Voorlopig beperken we ons tot de eerste niveaus
		return implode( '>', $categories );
	}

	function merge_assortments( $oww, $mdm, $b2b_nl, $b2b_fr ) {
		$assortments = array();
		if ( $oww === 'yes' ) {
			$assortments[] = 'OWW';
		}
		if ( $mdm === 'yes' ) {
			$assortments[] = 'MDM';
		}
		if ( $b2b_nl === 'yes' ) {
			$assortments[] = 'B2B-NL';
		}
		if ( $b2b_fr === 'yes' ) {
			$assortments[] = 'B2B-FR';
		}
		if ( $oww === 'no' and $mdm === 'no' and $b2b_nl === 'no' and $b2b_fr === 'no' ) {
			// Het product is voor iedereen verboden
			// Keer huidige logica voor blanco's om (voor iedereen beschikbaar => voor niemand beschikbaar)
		}
		return implode( ',', $assortments );
	}

	function translate_yes_no( $value ) {
		if ( $value === 'yes' ) {
			return 'Ja';
		} else {
			return 'Nee';
		}
	}

	function convert_to_array_of_product_ids( $sku ) {
		$list = array();
		if ( $sku !== '' ) {
			$product_id = wc_get_product_id_by_sku( $sku );
			if ( $product_id !== false ) {
				$list[] = $product_id;
			}
		}
		return serialize( $list );
	}

	function convert_price_to_float( $string ) {
		$price = str_replace( ',', '.', str_replace( '.', '', $string ) );
		return number_format( floatval( $price ), 2, '.', '' );
	}
?>