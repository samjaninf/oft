<html>

<head></head>

<body>
	<?php
		// Laad de WordPress-omgeving (relatief pad geldig vanuit elk thema)
		require_once '../../../wp-load.php';

		// Initialiseer de WC-logger
		$logger = wc_get_logger();
		$context = array( 'source' => 'Order XML' );
		$start = microtime(true);
		$client_number = 2128;
		$update = false;

		if ( $_GET['import_key'] === IMPORT_KEY ) {

			// Lees het lokale XML-bestand
			$order_data = simplexml_load_file( WP_CONTENT_DIR.'/odisy/export/orders.xml' );
			
			if ( $order_data !== false ) {
				// var_dump_pre( $order_data );
				echo number_format( microtime(true)-$start, 4, ',', '.' )." s => XML LOADED<br/>";

				$statusses = array();
				$types = array();
				if ( false === ( $oostende = get_transient('invoice_data_client_'.$client_number) ) ) {
					$update = true;
					$oostende = array();
				}
				
				foreach ( $order_data->Order as $order ) {
					$cnt++;
					$header = $order->OrderHeader;
					$lines = $order->OrderLines;
					$parts = explode( ' ', $header->OrderCreditRef->__toString() );
					$order_number = $parts[0];
					$order_type = $parts[1];

					// Magic method __toString() wel nodig in array keys!
					// Opties: 'in behandeling', 'verzonden', 'gefactureerd', 'te crediteren' of 'gecrediteerd'
					if ( array_key_exists( $header->OrderCreditStatus->__toString(), $statusses ) ) {
						$statusses[$header->OrderCreditStatus->__toString()]++;
					} else {
						$statusses[$header->OrderCreditStatus->__toString()] = 1;
					}

					// Opties: 'N', 'CH', 'B', 'RL' of '' (bij crediteringen?)
					if ( array_key_exists( $order_type, $types ) ) {
						$types[$order_type]++;
					} else {
						$types[$order_type] = 1;
					}
					
					// var_dump_pre($header->BestelwebRef);
					if ( ! empty( $header->BestelwebRef ) ) {
						echo $header->BestelwebRef."<br/>";

						// Zoek het order op
						$args = array(
							// Of staan we ook nog correcties toe na het sluiten van een order?
							'status' => 'processing',
							'type' => 'shop_order',
							'_order_number_formatted' => $header->BestelwebRef,
						);
						$matched_orders = wc_get_orders($args);
						var_dump_pre($matched_orders);

						if ( count($matched_orders) === 1 ) {
							$order = reset($matched_orders);
							$order->update_meta_data( 'odisy_order_number', $order_number );
							$order->update_meta_data( 'odisy_order_type', $order_number );
							$order->update_meta_data( 'odisy_routecode', $header->Routecode );
							$order->save();

							if ( $header->OrderCreditStatus->__toString() === 'verzonden' ) {
								$order->update_status('completed');
							}
							if ( $header->OrderCreditStatus->__toString() === 'gefactureerd' ) {
								// Custom orderstatus nog te definiëren
								$order->update_status('invoiced');
							}
						}
					}
					
					if ( $header->OrderCreditStatus->__toString() === 'in behandeling' and ( $order_type === 'N' or $order_type === 'CH' ) ) {
						echo "<br/>".number_format( microtime(true)-$start, 4, ',', '.' )." s => ORDER ".$header->OrderCreditRef." WITH STATUS ".$header->OrderCreditStatus." LOADED<br/>";
						foreach ( $lines->OrderLine as $line ) {
							// $line->AantalGeleverdGecrediteerd is in deze fase nog steeds nul!
							echo $line->Artikel.": ".$line->AantalBesteldTeCrediteren."x ".$line->Eenheid." besteld à ".$line->BestelCreditBedrag." euro<br/>";
						}
					}

					if ( intval( $header->KlantNr ) === $client_number and intval( $header->LeverNr ) === 0 and $update ) {
						if ( $header->OrderCreditStatus->__toString() === 'gefactureerd' and ( $order_type === 'N' or $order_type === 'CH' ) ) {
							foreach ( $lines->OrderLine as $line ) {
								if ( $line->AantalBesteldTeCrediteren->__toString() !== $line->AantalGeleverdGecrediteerd->__toString() ) {
									// Prijs uit de effectieve factuurlijn halen!
									$oostende[$order_number][] = $line->Artikel.": ".$line->AantalBesteldTeCrediteren."x ".$line->Eenheid." besteld, ".$line->AantalGeleverdGecrediteerd."x ".$line->Eenheid." geleverd à ".$line->ShipnoteInvoiceLines->ShipnoteInvoiceLine->Factuurbedrag." euro";
								} else {
									$oostende[$order_number][] = $line->Artikel.": ".$line->AantalBesteldTeCrediteren."x ".$line->Eenheid." geleverd à ".$line->ShipnoteInvoiceLines->ShipnoteInvoiceLine->Factuurbedrag." euro";
								}
							}
						}
					}
				}

				if ( $update ) {
					set_transient( 'invoice_data_client_'.$client_number, $oostende, HOUR_IN_SECONDS );
				}
				foreach ( $oostende as $order_number => $lines ) {
					echo "<br/>".$order_number." => ".implode( ', ', $lines )."<br/>";
				}

				var_dump_pre( $statusses );
				var_dump_pre( $types );

			} else {
				echo "ERROR LOADING XML<br/>";
			}

			echo number_format( microtime(true)-$start, 4, ',', '.' )." s => ".$cnt." ORDERS LOOPED<br/>";

		} else {
			die("Access prohibited!");
		}

		function process_picking_feedback( $local_path, $filename ) {
			global $sitepress, $logger, $context_bpost, $context_customer, $context_refund;

			// Zet de status voorlopig op mislukt
			$status = 'ERR';

			// Lees het lokale bestand
			if ( ( $stream = fopen( $local_path, 'r' ) ) !== false ) {
				$headers = fgetcsv( $stream, 0, ';' );
				// Knip de laatste lege kolom uit de header
				unset($headers[3]);
				$picked_items = array();
				while ( ( $row = fgetcsv( $stream, 0, ';' ) ) !== false ) {
					// Gebruik de SKU als key voor de items
					$picked_items[$row[0]] = array_combine( $headers, $row );
				}
				fclose($stream);
			}

			$parts = explode( '-', $filename );
			
			$nav_ref = false;
			if ( strpos( $parts[0], 'NO' ) === 0 ) {
				$nav_ref = str_replace( 'NO', 'NO/', $parts[0] );
			} elseif ( strpos( $parts[0], 'EA' ) === 0 ) {
				$nav_ref = str_replace( 'EA', 'EA/', $parts[0] );
			}

			$order_ref = $parts[1];
			// Indien het een voorafbestelling was, belandt de 'EA'-suffix in $parts[2], dus neem het LAATSTE element van $parts
			$nav_boxes = intval( str_replace( '.csv', '', $parts[count($parts)-1] ) );
			
			$limit_boxes = 25;
			if ( $nav_boxes < 1 ) {
				// Vang nulletjes op
				$nav_boxes = 1;
			} elseif ( $nav_boxes > $limit_boxes ) {
				// Zet limiet op onwaarschijnlijke aantallen
				$logger->alert( $order->get_order_number().": limiting number of boxes (".$nav_boxes.") to ".$limit_boxes, $context_customer );
				$nav_boxes = $limit_boxes;
			}
			
			if ( strpos( $order_ref, get_option('woocommerce_order_number_prefix') ) === 0 ) {
				$args = array(
					'status' => 'processing',
					'type' => 'shop_order',
					'_order_number_formatted' => $order_ref,
				);
				$matched_orders = wc_get_orders($args);
				
				if ( count($matched_orders) > 0 ) {
					// Check of we inderdaad slechts één order terugvinden
					if ( count($matched_orders) === 1 ) {
						$order = reset($matched_orders);
						
						if ( $nav_ref ) {
							$order->update_meta_data( 'order_number_navision', $nav_ref );
						}
						if ( $nav_boxes ) {
							$order->update_meta_data( 'number_of_boxes', $nav_boxes );
						}
						$order->save();

						if ( oftc_is_bpost_delivery($order) ) {
							if ( $order->get_meta('_bpost_order_reference') !== '' ) {
								// Er is al een zending aangemeld bij Bpost, spreek nu met Shipping Manager
								$retrieve_response = retrieve_bpost_shipment( $order, $order->get_meta('_bpost_order_reference') );
								$retrieve_body = wp_remote_retrieve_body($retrieve_response);

								if ( check_bpost_remote_response( $retrieve_response, $order ) !== 200 ) {
									$logger->error( "Unexpected retrieve status response: ".$retrieve_body, $context_bpost );
								} else {
									$current_shipment = simplexml_load_string( $retrieve_body );
									if ( count($current_shipment->box) === 1 and $current_shipment->box->status->__toString() !== 'PENDING' ) {
										$logger->warning( $order->get_meta('_bpost_order_reference').": already processed", $context_bpost );
									} elseif ( $nav_boxes > 1 and count($current_shipment->box) >= $nav_boxes ) {
										$logger->warning( $order->get_meta('_bpost_order_reference').": already has sufficient boxes", $context_bpost );
									} else {
										$replace_response = replace_bpost_shipment( $retrieve_body, $order, $nav_boxes );
										if ( is_wp_error( $replace_response ) ) {
											$logger->error( $replace_response->get_error_message(), $context_bpost );
										} else {
											// Annuleer de oorspronkelijke zending
											$cancel_response = update_bpost_shipment( $order->get_meta('_bpost_order_reference'), 'CANCELLED' );
											if ( check_bpost_remote_response( $cancel_response, $order ) !== 200 ) {
												$logger->error( "Unexpected cancel status response: ".wp_remote_retrieve_body($cancel_response), $context_bpost );
											} else {
												$new_bpost_ref = $order->get_order_number().'-'.$parts[0];
												$logger->info( $new_bpost_ref.": processing update", $context_bpost );
												// Creëer onmiddellijk alle barcodes horende bij de definitieve zending
												$print_response = create_bpost_labels($new_bpost_ref);

												if ( check_bpost_remote_response( $print_response, $order ) !== 200 ) {
													$logger->error( "Unexpected print status response: ".wp_remote_retrieve_body($print_response), $context_bpost );
												} else {
													// Alles is in orde, haal definitieve zending op ter controle
													$retrieve_response = retrieve_bpost_shipment( $order, $new_bpost_ref );
													$retrieve_body = wp_remote_retrieve_body($retrieve_response);

													if ( check_bpost_remote_response( $retrieve_response, $order ) !== 200 ) {
														$logger->error( "Unexpected retrieve status response: ".$retrieve_body, $context_bpost );
													} else {
														$printed_shipment = simplexml_load_string($retrieve_body);
														if ( $printed_shipment === false ) {
															$logger->error( $new_bpost_ref.": XML could not be parsed", $context_bpost );
														} else {
															// Voeg barcodes en status toe aan weborder
															$barcodes = array();
															$bpost_status = 'OPEN';
															foreach ( $printed_shipment->box as $box ) {
																// Converteer het (weliswaar platte) SimpleXML-object naar een échte string
																$bpost_status = $box->status->__toString();
																// Bpack gaat na het printen meteen naar de 'announced'-status
																if ( $bpost_status === 'PRINTED' or $bpost_status === 'ANNOUNCED' ) {
																	// Barcode is nu beschikbaar in XML (anders fatale error!)
																	$barcodes[] = $box->barcode->__toString();
																}
															}
															$order->update_meta_data( '_bpost_barcode', implode( ',', $barcodes ) );
															$order->update_meta_data( '_bpost_status', $bpost_status );
															$order->update_meta_data( '_bpost_order_reference', $printed_shipment->reference->__toString() );
															$order->save();
															$logger->info( $order->get_order_number().": new Bpost shipment reference saved", $context_bpost );
														}
													}
												}
											}
										}
									}
								}
							} else {
								$logger->error( $order->get_order_number().": initial Bpost shipment reference unknown", $context_bpost );
							}
						}

						// Switch naar taal van order
						$lang = $order->get_meta('wpml_language');
						$locale = ( $lang === 'fr' ) ? 'fr_FR' : 'nl_NL';
						$sitepress->switch_lang( $lang, true );
						unload_textdomain('oftc');
						load_textdomain( 'oftc', WP_CONTENT_DIR.'/languages/themes/oftc-'.$locale.'.mo' );

						// Voer refund uit en registreer het resultaat
						if ( refund_undelivered_products( $order, $picked_items, $nav_ref ) ) {
							// Alles was in orde, zet de status op goedgekeurd!
							$status = 'OK';

							// Voeg de kosten voor bijkomende dozen toe indien betalende levering
							if ( $nav_boxes > 1 ) {
								add_extra_shipping_costs( $order, $nav_boxes - 1 );
							}
							
							// Update de status naar 'Afgerond'
							$order->update_status('completed');
						}

						// Switch voor alle zekerheid terug naar default
						$lang = apply_filters( 'wpml_default_language', NULL );
						$locale = ( $lang === 'fr' ) ? 'fr_FR' : 'nl_NL';
						$sitepress->switch_lang( $lang, true );
						unload_textdomain('oftc');
						load_textdomain( 'oftc', WP_CONTENT_DIR.'/languages/themes/oftc-'.$locale.'.mo' );
					} else {
						$logger->error( $order_ref.": reference not singular", $context_refund );
					}
				} else {
					$logger->error( $order_ref.": no order found in processing status", $context_refund );
				}
			} else {
				$logger->error( $order_ref.": reference format not valid", $context_refund );
			}

			return $status;
		}

		function refund_undelivered_products( $order, $picked_items, $nav_ref, $refund_reason = false ) {
			global $logger, $context_customer, $context_picking, $context_refund;

			if ( $refund_reason === false ) {
				$refund_reason = __( 'Onbeschikbaar:', 'oftc' );
			}

			if ( $order->get_status() === 'refunded' or count( $order->get_refunds() ) > 0 ) {
				
				$logger->error( $order->get_order_number().":  order was already refunded", $context_refund );
				return false;

			} elseif ( $order->get_status() === 'processing' ) {
				
				// Vraagt per default enkel bestellijnen van het type 'line_item' op!
				$ordered_items = $order->get_items();
				$refund_amount = 0;
				$items_to_refund = array();
				$refunded_skus = array();

				if ( $ordered_items ) {
					
					if ( $order->get_meta('_order_has_preorder') === 'yesDISABLE' ) {

						// NIETS DOEN OF HANDMATIG BEPAALDE PRODUCTEN UIT ZENDLIJST HALEN INDIEN CSV VAN ROLLIJST NIET CORRECT IS

					} else {

						if ( count($ordered_items) > count($picked_items) ) {
							$logger->critical( $order->get_order_number().": surplus of order lines in webshop", $context_customer );
						}

						foreach ( $ordered_items as $item_id => $item ) {
							$product = $item->get_product();
							if ( array_key_exists( $product->get_sku(), $picked_items ) ) {
								$picked_item = $picked_items[$product->get_sku()];
								$old_qty = $item->get_quantity();
								$new_qty = intval( $picked_item['Invoice Qty'] );

								if ( $new_qty < $old_qty ) {
									$refunded_skus[] = ( $old_qty - $new_qty ).'x '.$product->get_sku();

									// Reken terug o.b.v. de oorspronkelijk getoonde prijs (i.p.v. huidige prijs)
									$refund_total = calc_for_refunded_qty( $item->get_total(), $old_qty, $new_qty );
									$tax_data = $item->get_taxes();

									// echo "TAX DATA BEFORE";
									// var_dump_pre($tax_data);
									
									// Moet floats bevatten, en dus geen strings zoals wc_format_decimal()
									// Omdat array_map() de keys niet bewaart, loopen we met een foreach()
									foreach ( $tax_data['total'] as $key => $value ) {
										$tax_data['total'][$key] = calc_for_refunded_qty( $value, $old_qty, $new_qty );
									}

									// De subarray 'subtotal' bevat in principe exact hetzelfde als 'total'
									// BEHALVE INDIEN NADIEN KORTINGEN TOEGEPAST WERDEN? TE CHECKEN, ZIE OMDM42931
									// foreach ( $tax_data['subtotal'] as $key => $value ) {
									// 	$tax_data['subtotal'][$key] = calc_for_refunded_qty( $value, $old_qty, $new_qty );
									// }

									// echo "TAX DATA AFTER";
									// var_dump_pre($tax_data);

									$refund_tax = calc_for_refunded_qty( $item->get_total_tax(), $old_qty, $new_qty );
									$refund_amount += $refund_total + $refund_tax;

									$items_to_refund[$item_id] = array( 
										'qty'			=> $old_qty - $new_qty, 
										'refund_total'	=> $refund_total, 
										'refund_tax'	=> $tax_data['total'],
									);

									$helper = '';
									if ( $new_qty > 0 ) {
										$helper = ' fully'; 
									}

									if ( $product->get_stock_quantity() > 0 ) {
										$logger->warning( "SKU ".$product->get_sku().": not".$helper." picked in ".$order->get_order_number()." but ".$product->get_stock_quantity()." pieces left in stock", $context_picking );
									} else {
										$logger->info( "SKU ".$product->get_sku().": not".$helper." picked in ".$order->get_order_number()." (stock depleted)", $context_picking );
									}
								} elseif ( $new_qty > $old_qty ) {
									// Voor de veiligheid alle originele metadata capteren ('Opmerking', '_ywpo_item_preorder', ...)
									$original_meta_data = $item->get_meta_data();

									// Reken terug o.b.v. de oorspronkelijk getoonde prijs (i.p.v. huidige prijs)
									$calc = calc_for_new_qty( $item->get_total(), $old_qty, $new_qty );
									$price_args = array(
										'subtotal' => $calc,
										'total' => $calc,
									);
									
									// Aangezien $order->update_product() deprecated is, doen we een remove/add
									$order->remove_item($item_id);
									
									// Nadien moet er nog een calculate_totals() volgen maar dit gebeurt sowieso al in register_items_added_in_navision()
									$new_item_id = $order->add_product( $product, $new_qty, $price_args );
									
									// Originele metadata opnieuw toevoegen
									foreach ( $original_meta_data as $meta_key => $meta_value ) {
										write_log($meta_key);
										wc_add_order_item_meta(	$new_item_id, $meta_key, $meta_value );
									}
									wc_add_order_item_meta( $new_item_id, __( 'Opmerking', 'oftc' ), sprintf( __( '%d extra via Klantendienst', 'oftc' ), $new_qty - $old_qty ) );
									
									$logger->info( $order->get_order_number().": SKU ".$product->get_sku()." quantity upped", $context_customer );
								}
							} else {

								// Enkel doen als we zeker zijn dat er nooit halve / lege CSV's in de map belanden! WACHTEN OP WEBSERVICE
								// refund_item_removed_in_navision( $order, $item );
								$logger->warning( $order->get_order_number().": SKU ".$product->get_sku()." removed in Navision but not in webshop (for security)", $context_customer );
							}
						}

						// Voeg in Navision toegevoegde regels ook toe aan weborder (indien product online bestaat)
						register_items_added_in_navision( $order, $ordered_items, $picked_items );

					}
						
				}

				if ( count($items_to_refund) > 0 ) {
					
					$new_refund = wc_create_refund( array(
						'amount' => wc_format_decimal($refund_amount),
						'reason' => $refund_reason.' '.implode( ', ', $refunded_skus ),
						'order_id' => $order->get_id(),
						'line_items' => $items_to_refund,
						// Gebruik dezelfde datum als het oorspronkelijke order, zodat de maandelijkse rapporten correct zijn
						'date_created' => $order->get_date_created()->__toString(),
						'refund_payment' => false,
						'restock_items' => false,
					) );
				
				}

				$str = count($ordered_items)." order lines";
				if ( count($items_to_refund) > 0 ) {
					$str .= " of which ".count($items_to_refund)." (partially) refunded, totalling ".number_format( $refund_amount, 2, ',', '.' )." euros";
				} else {
					$str .= ", all fulfilled";
				}
				
				file_put_contents( WP_CONTENT_DIR.'/refunds-week-'.intval( date_i18n('W') ).'.csv', date_i18n('d/m/Y H:i:s') . "\t<a href='".admin_url('post.php?post='.$order->get_id().'&action=edit')."'>" . $order->get_order_number() . "</a>\t" . $nav_ref . "\t" . $str . "\n", FILE_APPEND );
				return true;
			}
		}

		function register_items_added_in_navision( $order, $ordered_items, $picked_items ) {
			global $logger, $context_customer;

			foreach ( $picked_items as $picked_item ) {
				$product_id_dirty = wc_get_product_id_by_sku($picked_item['Item No']);
				// Bovenstaande functie is ondanks switchen van taal niet language proof, dus vraag expliciet op in taal van order 
				$product_id = apply_filters( 'wpml_object_id', $product_id_dirty, 'product', false, $order->get_meta('wpml_language') );
				
				if ( $product_id > 0 ) {
					$found = false;
					foreach ( $ordered_items as $item ) {
						if ( $item->get_product_id() === $product_id ) {
							$found = true;
							break;
						}
					}

					if ( ! $found ) {
						$qty = intval( $picked_item['Invoice Qty'] );
						if ( $qty > 0 ) {
							// Is een check op publicatiestatus nodig? WERKT OOK INDIEN PRODUCT NOG OP DRAFT STAAT, ZIE OMDM45443
							$product = wc_get_product($product_id);
							
							// Gebruik de specifieke prijs voor deze klant
							$calc = $qty * get_price_by_client_type( $product, $order->get_meta('client_type') );
							$price_args = array(
								'subtotal' => $calc,
								'total' => $calc,
							);
							$new_item_id = $order->add_product( $product, $qty, $price_args );
							wc_add_order_item_meta( $new_item_id, __( 'Opmerking', 'oftc' ), __( 'toegevoegd door Klantendienst', 'oftc' ) );

							$logger->info( $order->get_order_number().": SKU ".$product->get_sku()." added", $context_customer );
						} else {
							$logger->info( $order->get_order_number().": SKU ".$picked_item['Item No']." not added because zero picked", $context_customer );
						}
					}
				} else {
					// Eventueel een 'anonieme' bestellijn toevoegen maar geen naam/prijs beschikbaar!
					$logger->warning( $order->get_order_number().": SKU ".$picked_item['Item No']." does not exist in webshop yet", $context_customer );
				}
			}

			return $order->calculate_totals();
		}

		function add_extra_shipping_costs( $order, $extra_boxes = 1 ) {
			global $logger, $context_customer;
			$shipping_method = oftc_get_shipping_method($order);

			// TO DO: VERALGEMENEN TOT ALLE BETALENDE VERZENDMETHODES
			$tariff = 0;
			if ( oftc_is_bpost_delivery($order) ) {
				// Neem het huidige tarief van de methode als prijs per doos
				$tariff = $shipping_method->get_total();
			}
			
			if ( $tariff > 0 ) {
				$shipping_item = new WC_Order_Item_Shipping();
				$shipping_item->set_method_title( sprintf( _n( '%s extra doos', '%s extra dozen', $extra_boxes, 'oftc' ), $extra_boxes ) );

				// We nemen dezelfde ID als bij de originele bestelling, om dubbelzinnigheden door onze truc met reset() te vermijden
				$shipping_item->set_method_id( $shipping_method->get_method_id() );
				// TO DO: INSTANCE_ID TOEVOEGEN NA UPGRADE NAAR WC3.4+ ???
				// $shipping_item->set_instance_id( $shipping_method->get_instance_id() );
				$shipping_item->set_total( $extra_boxes * $tariff );

				// Key 1 => BE 21% BTW
				// Key 2 => BE 6% BTW
				// Key 3/7/9 => ES/LU/NL BTW VERLEGD
				// Key 4/8/10 => ES/LU/NL BTW VERLEGD REDUCED
				$shipping_item->set_taxes( array( 1 => 0.21 * $shipping_item->get_total() ) );

				$order->add_item($shipping_item);
				$logger->info( $order->get_order_number().": shipping costs for ".$extra_boxes." extra boxes added", $context_customer );
			}

			// Herbereken het totaalbedrag (doet automatisch eerst een belastingberekening) OOK COUPONS HERBEREKENEN, MOGELIJK VANAF WC 3.2?
			return $order->calculate_totals();
		}

		function replace_bpost_shipment( $body, $order, $number_of_boxes ) {
			global $logger, $context_bpost;

			// Nu het nog een string is kunnen we nodes makkelijk herbenoemen! 
			$announced_shipment = simplexml_load_string( str_replace( 'orderInfo', 'order', $body ) );
			
			if ( $announced_shipment === false ) {
				return new WP_Error( '666', $order->get_order_number().': XML could not be parsed' );
			} else {
				// Bpack Integration Manual p. 12: orderReference uniek (zoniet overwrite), max. 50 tekens, case sensitive, geen slashes
				$announced_shipment->reference = $order->get_order_number().'-'.str_replace( '/', '', $order->get_meta('order_number_navision') );
				// Probleem: deze node moet blijkbaar boven alle <box> toegevoegd worden!
				$announced_shipment->addChild( 'costCenter', $order->get_meta('client_type') );
				
				// Bij switchen van SimpleXML naar DOM blijft de instance dezelfde, dus wijzigingen worden meteen ook zichtbaar in SimpleXML
				$dom_shipment = dom_import_simplexml($announced_shipment);
				$dom_box = dom_import_simplexml($announced_shipment->box[0]);
				
				// Voorbeeld van hoe we box nodes kunnen kopiëren, maar wij zullen gewoon meerdere keren POST doen
				// $dom_shipment->appendChild($dom_box->cloneNode(true));
				
				// Voeg de kostnode nogmaals toe op de juiste plaats
				$dom_cost = dom_import_simplexml($announced_shipment->costCenter);
				$dom_shipment->insertBefore( $dom_cost, $dom_box );
				// En verwijder de 2de vermelding verderop in de XML
				unset($announced_shipment->costCenter[1]);
				
				// Ga ervan uit dat er slechts één doos in de reeds aangemelde zending zit (geen loop)
				unset($announced_shipment->box->status);
				// Indien de node bestaat is het label al aangemaakt en stoppen we misschien beter ...
				unset($announced_shipment->box->barcode);

				// Opgelet: volgorde moet exact gerespecteerd blijven!
				unset($announced_shipment->box->remark);
				$announced_shipment->box->addChild( 'remark', $order->get_order_number().' - '.$order->get_meta('order_number_navision').' - Box 1 / '.$number_of_boxes );
				unset($announced_shipment->box->additionalCustomerReference);
				$announced_shipment->box->addChild( 'additionalCustomerReference', 'Webshop' );
				
				// Pas de gewenste leverdatum aan indien de deadline gemist werd UITGESCHAKELD
				// if ( date_i18n('Y-m-d+02:00') >= $announced_shipment->box->nationalBox->children( 'ns3', true )->atHome->requestedDeliveryDate ) {
				// 	$new_date = date_i18n( 'Y-m-d+02:00', strtotime('+1 weekday') );
				// 	// Verzet de leverdatum naar volgende werkdag
				// 	$announced_shipment->box->nationalBox->children( 'ns3', true )->atHome->requestedDeliveryDate = $new_date;
				// 	// Sla dit ook op in de webshop
				// 	$order->update_meta_data( '_bpost_delivery_date', $new_date );
				// }

				// Openingsuren enkel doorgeven bij thuislevering via 'bpack 24h business'
				if ( count($announced_shipment->box->nationalBox->children( 'ns3', true )->atHome) > 0 and strpos( $announced_shipment->box->nationalBox->children( 'ns3', true )->atHome->product->__toString(), 'business' ) !== false ) {
					// Nodes met namespace prefix moeten via children() opgevraagd worden!
					$bpost_address = $announced_shipment->box->nationalBox->children( 'ns3', true )->atHome->receiver->children( 'ns2', true )->address->streetName->__toString();
					$bpost_address .= ' '.$announced_shipment->box->nationalBox->children( 'ns3', true )->atHome->receiver->children( 'ns2', true )->address->number->__toString();
					$wc_default_address = get_user_meta( $order->get_customer_id(), 'shipping_address_1', true );
					
					// Openingsuren enkel doorgeven bij default winkeladres (hoofdletterongevoelig)
					if ( strcasecmp( $bpost_address, $wc_default_address ) === 0 ) {
						$node = get_user_meta( $order->get_customer_id(), 'website_node_oww', true );
						if ( $node !== '' ) {
							$webshop_response = wp_remote_get( 'https://shop.oxfamwereldwinkels.be/wp-content/themes/oxfam-webshop/get-shop-data.php?node='.$node );
							if ( wp_remote_retrieve_response_code($webshop_response) === 200 ) {
								$opening_hours = json_decode( wp_remote_retrieve_body($webshop_response) );
								for ( $i = 0; $i < 6; $i++ ) {
									// Juiste formaat bij 2 dagdelen: <Tuesday>09:00-12:00/13:00-17:30</Tuesday>
									$key = date( 'l', strtotime('next Monday + '.$i.' days') );
									$value = str_replace( ' ', '', str_replace( ' en ', '/', str_replace( 'Gesloten', '-', $opening_hours[$i] ) ) );
									// Verwijder eventuele reeds ingegeven uren
									unset($announced_shipment->box->nationalBox->children( 'ns3', true )->atHome->openingHours->{$key});
									$announced_shipment->box->nationalBox->children( 'ns3', true )->atHome->openingHours->addChild( $key, $value );
								}
								$logger->info( $announced_shipment->reference->__toString().": openings hours set", $context_bpost );
								// write_log( $announced_shipment->asXML() );
							}
						} else {
							$logger->warning( $announced_shipment->reference->__toString().": openings hours not available", $context_bpost );
						}
					} else {
						$logger->info( $announced_shipment->reference->__toString().": delivery address not equal to default", $context_bpost );
					}
				} else {
					$logger->info( $announced_shipment->reference->__toString().": not a home delivery", $context_bpost );
				}

				// Maak de definitieve zending aan met onze eigen referentie, herhaal voor elke bijkomende doos
				for ( $i = 1; $i <= $number_of_boxes; $i++ ) {
					if ( $i > 1 ) {
						$announced_shipment->box->remark = $order->get_order_number().' - '.$order->get_meta('order_number_navision').' - Box '.$i.' / '.$number_of_boxes;
					}
					
					// Indien de referentie al bestaat wordt er een extra doos toegevoegd
					$create_response = create_bpost_shipment( $announced_shipment->asXML() );

					if ( check_bpost_remote_response( $create_response, $order ) !== 201 ) {
						$logger->error( "Unexpected create status response: ".wp_remote_retrieve_body($create_response), $context_bpost );
						// Doen we hierna nog verder of breken we volledig met de uitvoering?
						// return new WP_Error( '666', $order->get_order_number().': new shipment could not be created' );
					}
				}

				// Doe eventueel nog eens een retrieve om het aantal dozen te checken
				return true;
			}
		}

		function calc_for_refunded_qty( $value, $old_qty, $new_qty ) {
			return floatval($value) / $old_qty * ( $old_qty - $new_qty );
		}

		function calc_for_new_qty( $value, $old_qty, $new_qty ) {
			return floatval($value) / $old_qty * $new_qty;
		}
	?>
</body>

</html>