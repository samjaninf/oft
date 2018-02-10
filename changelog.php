<?php
	if ( ! defined('ABSPATH') ) exit;
?>

<div class="wrap">
	<h1>Changelog</h1>

	<p>Hieronder kun je de aangepaste productinfo raadplegen van deze week.</p>

	<p>&nbsp;</p>

	<h2>Productwijzigingen WK<?php echo intval( date_i18n('W') ); ?></h2>

	<?php
		$file_path = WP_CONTENT_DIR.'/changelog-week-'.intval( date_i18n('W') ).'.csv';
		if ( ( $handle = fopen( $file_path,'r' ) ) !== false ) {
			echo parse_csv_to_table( $handle );
		} else {
			echo '<p>'.__( 'Nog geen wijzingen geregistreerd.', 'oftc-admin' ).'</p>';
		}
	?>
</div>