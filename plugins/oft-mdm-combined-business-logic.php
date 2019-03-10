<?php
	/*
	Plugin Name: OFT/OMDM Combined Business Logic
	Description: Deze plug-in groepeert alle functies die gedeeld kunnen worden tussen fairtradecrafts.be en oxfamfairtrade.be.
	Version:     0.1.1
	Author:      Full Stack Ahead
	Author URI:  https://www.fullstackahead.be
	Text Domain: oft
	*/

	defined('ABSPATH') or die('Access prohibited!');

	class Custom_Business_Logic {
		public static $company;

		public function __construct( $param = 'oft' ) {
			self::$company = $param;
			// Voeg kleine afwijkingen toe door if ( self::$company === 'oft' ) te checken

			// Verberg bepaalde tabbladen onder 'Mijn account'
			add_filter( 'woocommerce_account_menu_items', array( $this, 'remove_my_account_menu_items' ), 10, 1 );

			// Verhinder het permanent verwijderen van producten (maar na 1 jaar wel automatische clean-up door Wordpress, zie wp-config.php!)
			add_action( 'before_delete_post', array( $this, 'disable_manual_product_removal' ), 10, 1 );

			// Maak de adresvelden voor klanten onbewerkbaar en verduidelijk de labels en layout
			add_filter( 'woocommerce_default_address_fields', array( $this, 'make_addresses_readonly' ), 10, 1 );
		}

		public function remove_my_account_menu_items( $items ) {
			unset($items['dashboard']);
			unset($items['downloads']);
			return $items;
		}

		public function disable_manual_product_removal( $post_id ) {
			if ( get_post_type($post_id) == 'product' ) {
				wp_die( sprintf( __( 'Uit veiligheidsoverwegingen is het verwijderen van producten niet toegestaan, voor geen enkele gebruikersrol! Vraag &ndash; indien nodig &ndash; dat de hogere machten op %s deze beperking tijdelijk opheffen, zodat je je vuile zaakjes kunt opknappen.', 'oft' ), '<a href="mailto:'.get_option('admin_email').'">'.get_option('admin_email').'</a>' ) );
			}
		}

		public function make_addresses_readonly( $address_fields ) {
			$address_fields['company']['custom_attributes'] = array( 'readonly' => 'readonly' );
			
			$address_fields['address_1']['label'] = __( 'Straat en nummer', 'oft' );
			$address_fields['address_1']['placeholder'] = '';
			$address_fields['address_1']['required'] = true;
			$address_fields['address_1']['custom_attributes'] = array( 'readonly' => 'readonly' );
			
			$address_fields['postcode']['label'] = __( 'Postcode', 'oft' );
			$address_fields['postcode']['placeholder'] = '';
			$address_fields['postcode']['required'] = true;
			$address_fields['postcode']['custom_attributes'] = array( 'readonly' => 'readonly' );
			$address_fields['postcode']['class'] = array('row-first');
			$address_fields['postcode']['clear'] = false;

			$address_fields['city']['label'] = __( 'Gemeente', 'oft' );
			$address_fields['city']['placeholder'] = '';
			$address_fields['city']['required'] = true;
			$address_fields['city']['custom_attributes'] = array( 'readonly' => 'readonly' );
			$address_fields['city']['class'] = array('row-last');
			$address_fields['city']['clear'] = true;

			$address_fields['number_omdm']['label'] = __( 'Klantnummer OMDM', 'oft' );
			$address_fields['number_omdm']['placeholder'] = '';
			$address_fields['number_omdm']['required'] = true;
			$address_fields['number_omdm']['custom_attributes'] = array( 'readonly' => 'readonly' );

			$address_fields['country']['class'] = array('hidden');
			$address_fields['state']['class'] = array('hidden');
			
			return $address_fields;
		}
	}

	new Custom_Business_Logic('oft');
	
	// register_activation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
	// register_deactivation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
?>