<?php
	if ( ! defined('ABSPATH') ) exit;
?>

<div class="wrap">
	
	<h1>Registratie van nieuwe productfoto's</h1>
	
	<?php
		function list_new_images() {
			$photos = array();

			// Probeer de uploadmap te openen
			if ( $handle = opendir(WP_CONTENT_DIR.'/uploads/') ) {
				// Loop door alle files in de map
				while ( false !== ( $file = readdir($handle) ) ) {
					$filepath = WP_CONTENT_DIR.'/uploads/'.$file;
					// Beschouw enkel de JPG-foto's met een naam van 5 cijfers die sinds de vorige bulksessie geüpload werden
					$parts = explode( '.jpg', $file );
					if ( ends_with( $file, '.jpg' ) and strlen( $parts[0] ) === 5 and is_numeric( $parts[0] ) and filemtime($filepath) > get_option( 'oft_timestamp_last_photo', '1516924800' ) ) {
						// Zet naam, timestamp, datum en pad van de upload in de array
						$photos[] = array(
							"name" => basename($filepath),
							"timestamp" => filemtime($filepath),
							"date" => get_date_from_gmt( date( 'Y-m-d H:i:s', filemtime($filepath) ), 'd/m/Y H:i:s' ),
							"path" => $filepath,
						);
					}
				}
				closedir($handle);
			}
			
			// Orden chronologisch
			if ( count($photos) > 1 ) {
				usort( $photos, 'sort_by_time' );
			}
			return $photos;
		}

		list_new_images();

		add_action( 'admin_footer', 'oxfam_photo_action_javascript' );

		function oxfam_photo_action_javascript() { ?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					var data = <?php echo json_encode(list_new_images()); ?>;

					if ( data !== null ) {
						dump(data);
						var s = "";
						if ( data.length !== 1 ) s = "'s";
						jQuery(".input").prepend("<pre>We vonden "+data.length+" nieuwe of gewijzigde foto"+s+"!</pre>");
						if ( data.length > 0 ) jQuery(".run").prop('disabled', false);
						
						jQuery(".run").on('click', function() {
							jQuery(".run").prop('disabled', true);
							jQuery(".run").text('Ik ben aan het nadenken ...');
							jQuery('#wpcontent').css('background-color', 'orange');
							jQuery(".output").before("<p>&nbsp;</p>");
							ajaxCall(0);
						});
					} else {
						jQuery(".input").prepend("<pre>We vonden geen enkele nieuwe of gewijzigde foto!</pre>");
					}

					jQuery(".input").prepend( "<pre>Uploadtijdstip laatst verwerkte foto: <?php echo get_date_from_gmt( date( 'Y-m-d H:i:s', get_option( 'oft_timestamp_last_photo', '1516924800' ) ), 'd/m/Y H:i:s' ); ?></pre>" );

					var tries = 0;
					var max = 5;

					function ajaxCall(i) {
						if ( i < data.length ) {
							var photo = data[i];

							var input = {
								'action': 'oxfam_photo_action',
								'name': photo['name'],
								'timestamp': photo['timestamp'],
								'path': photo['path'],
							};
							
							jQuery.ajax({
								type: 'POST',
								url: ajaxurl,
								data: input,
								dataType: 'html',
								success: function(msg) {
									tries = 0;
									jQuery(".output").prepend("<p>"+msg+"</p>");
									ajaxCall(i+1);
								},
								error: function(jqXHR, statusText, errorThrown) {
									tries++;
									var str = '<?php _e( 'Asynchroon laden van PHP-file mislukt ... (poging ###CURRENT### van ###MAXIMUM###: ###ERROR###)', 'oftc-admin' ); ?>';
									str = str.replace( '###CURRENT###', tries );
									str = str.replace( '###MAXIMUM###', max );
									str = str.replace( '###ERROR###', errorThrown );
									jQuery(".output").prepend("<p>"+str+"</p>");
									if ( tries < max ) {
										ajaxCall(i);
									} else {
										tries = 0;
										jQuery(".output").prepend("<p>Skip <i>"+photo['name']+"</i>, we schuiven door naar de volgende foto!</p>");
										ajaxCall(i+1);
									}
								},
							});
						} else {
							jQuery("#wpcontent").css("background-color", "limegreen");
							jQuery(".output").prepend("<p>Klaar, we hebben "+i+" foto's verwerkt!</p>");
							jQuery(".run").text("Registreer nieuwe / gewijzigde foto's");
						}
					}
					
					function dump(obj) {
						var out = '';
						for ( var i in obj ) {
							if ( typeof obj[i] === 'object' ){
								dump(obj[i]);
							} else {
								if ( i != 'timestamp' ) out += i + ": " + obj[i] + "<br>";
							}
						}
						jQuery(".input").append('<pre>'+out+'</pre>');
					}
				});
			</script>

			<?php
		}
	?>

	<p>Elke werkdag gaat er om 14 uur een script van start dat nieuwe / gewijzigde beelden in <a href="file:///\\vmfile\data\1-Vormgeving & Publicaties\OFT-sync (LATEN STAAN)\RGB HI-RES\">F:\1-Vormgeving & Publicaties\OFT-sync (LATEN STAAN)\RGB HI-RES\</a> uploadt naar de OFT-site (op voorwaarde dat de layoutserver zijn verbinding met de F-schijf niet kwijtgespeeld is). Hieronder zie je een lijstje met alle originelen die sinds de laatste fotoregistratie in de uploads-map geplaatst werden. De meest recente bestanden staan onderaan. Om de thumbnails aan te maken en de foto's te registreren in de mediabib dient een administrator of category manager daarna nog op onderstaande knop te klikken.</p>

	<p>De packshots worden hierbij automatisch aan het juiste product gekoppeld op basis van het artikelnummer. Zorg er dus voor dat de fotograaf de juiste bestandnaam gebruikt én dat het product reeds (als concept) aangemaakt is. Dankzij het vierkante formaat en de consequente witruimte (+/- 5% bij de langste zijde van het product) creëren we een harmonieus catalogusbeeld. Alleen verpakking waarvan er ook een grotere versie bestaat beelden we iets kleiner af. Raadpleeg <a href="<?php echo get_stylesheet_directory_uri().'/packshots.php'; ?>" target="_blank">deze pagina</a> (laadtijd: +/- 30 seconden!) voor een handig overzicht met alle packshots van actuele producten.</p>

	<p>De thumbnails op het formaat 300 x 300 pixels worden de volgende werkdag automatisch gesynchroniseerd met de map op de Combell-server waar BestelWeb zijn afbeeldingen haalt, zodat de meest recente packshots ook daar verschijnen. De wijnkiezers halen hun beelden rechtstreeks op in deze OFT-site. En zo is iedereen blij!</p>

	<div class="output"></div>

	<p>&nbsp;</p>

	<button class="run" style="float: right; margin: 0 10px; width: 250px;" disabled>Registreer nieuwe / gewijzigde foto's</button>

	<div class="input"></div>

</div>