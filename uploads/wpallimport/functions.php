<?php
	function alter_brand( $brand ) {
		if ( $brand == 'Oxfam Fairtrade' or $brand == 'EZA' ) {
			$brand = 'Oxfam Fair Trade';
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

	function calc_content_per_kg_l( $stat, $ompak ) {
		$calc = 0.0;
		$numerator = floatval( str_replace( ',', '.', $stat ) );
		$denominator = intval($ompak);
		// Veiligheid inbouwen voor onvolledige gegevens
		if ( $numerator > 0.001 and $denominator >= 1) {
			$calc = $numerator / $denominator;
		}
		return $calc;
	}

	function get_content( $stat, $ompak, $unit ) {
		$cont = '';
		$calc = calc_content_per_kg_l( $stat, $ompak );
		if ( $calc > 0 ) {	
			if ( mb_strtoupper($unit) === 'L' ) {
				$cont = number_format( 100*$calc, 0 );
			} elseif ( mb_strtoupper($unit) === 'KG' ) {
				$cont = number_format( 1000*$calc, 0 );
			}
		}
		return $cont;
	}

	function get_eprice( $cp, $stat, $ompak, $unit ) {
		$eprice = '';
		$denominator = calc_content_per_kg_l( $stat, $ompak );
		if ( $denominator > 0 ) {
			$numerator = floatval( str_replace( ',', '.', $cp ) );
			if ( $numerator > 0 ) {
				$calc = $numerator / $denominator;
				$eprice = number_format( $calc, 2, '.', '' );
			}
		}
		return $eprice;
	}

	function ditch_zeros( $value ) {
		if ( $value === '0' or $value === '0,0' or $value === '0,000' ) {
			$value = '';
		}
		return $value;
	}

	function seperator_comma_to_point( $string ) {
		$float = floatval( str_replace( ',', '.', $string ) );
		return number_format( $float, 3, '.', '' );
	}

	function get_unit( $value ) {
		if ( mb_strtoupper($unit) === 'KG' ) {
			$unit = 'g';
		} elseif ( mb_strtoupper($unit) === 'L' ) {
			$unit = 'cl';
		} else {
			$unit = '';
		}
		return $unit;
	}
?>