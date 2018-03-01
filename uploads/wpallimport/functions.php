<?php
	function alter_brand( $raw_brand ) {
		if ( $raw_brand === 'Oxfam Fairtrade' ) {
			$brand = 'Oxfam Fair Trade';
		} else {
			$brand = $raw_brand;
		}
		return $brand;
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

	function translate_stock_status( $flag ) {
		if ( $flag === 'aan' ) {
			return 'instock';
		} else {
			return 'outofstock';
		}
	}
?>