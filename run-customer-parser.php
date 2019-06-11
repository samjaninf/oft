<html>

<head></head>

<body>
	<?php
		// Laad de WordPress-omgeving (relatief pad geldig vanuit elk thema)
		require_once '../../../wp-load.php';

		// Initialiseer de WC-logger
		$logger = wc_get_logger();
		$context = array( 'source' => 'Customer XML' );
		$start = microtime(true);
		$oostende = array();

		if ( $_GET['import_key'] === IMPORT_KEY ) {

			// Lees het lokale XML-bestand
			$customer_data = simplexml_load_file( WP_CONTENT_DIR.'/odisy/export/customers.xml' );
			
			if ( $customer_data !== false ) {
				echo number_format( microtime(true)-$start, 4, ',', '.' )." s => XML LOADED<br/>";

				$routecodes = array();
				$types = array();
				
				foreach ( $customer_data->Customer as $customer ) {
					$cnt++;
					$client_number = $customer->CustNum->__toString();
					$addresses = $customer->Leveradressen;
					
					// Opties: ???
					if ( array_key_exists( $customer->Routecode->__toString(), $routecodes ) ) {
						$routecodes[$customer->Routecode->__toString()]++;
					} else {
						$routecodes[$customer->Routecode->__toString()] = 1;
					}

					// Opties: ???
					if ( array_key_exists( $customer->Type->__toString(), $types ) ) {
						$types[$customer->Type->__toString()]++;
					} else {
						$types[$customer->Type->__toString()] = 1;
					}

					echo "<b>".$customer->Name.":</b><br/>";
					echo $customer->Lijn1." ".$customer->Huisnr.", ".$customer->Postcode." ".$customer->Gemeente." (".$customer->Land.")<br/>";
					
					$args = array(
						'role' => 'customer',
						'meta_key' => 'billing_client_number',
						'meta_value' => $client_number,
					);
					$user_query = new WP_User_Query($args);
					$matched_users = $user_query->get_results();
					var_dump_pre($matched_users);

					if ( count( $matched_users ) > 0 ) {
						foreach ( $matched_users as $user ) {
							echo "FOUND MATCH WITH ".$user->first_name." ...<br/>";
						}
					}
					
					if ( intval( $client_number ) === 2128 ) {
						foreach ( $addresses->Leveradres as $address ) {
							$oostende[$address->AdrNum] = array( 'name' => $address->Name, 'address_1' => $address->Lijn1." ".$address->Huisnr, 'zipcode' => $address->Postcode, 'city' => $address->Gemeente, 'country' => $address->Land );
						}
					}
				}

				var_dump_pre( $oostende );
				var_dump_pre( $routecodes );
				var_dump_pre( $types );

			} else {
				echo "ERROR LOADING XML<br/>";
			}

			echo number_format( microtime(true)-$start, 4, ',', '.' )." s => ".$cnt." CUSTOMERS LOOPED<br/>";

		} else {
			die("Access prohibited!");
		}
	?>
</body>

</html>