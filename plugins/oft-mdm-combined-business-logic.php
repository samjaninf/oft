<?php
	/*
	Plugin Name: OFT/OMDM Combined Business Logic
	Description: Deze plug-in groepeert alle functies die gedeeld kunnen worden tussen fairtradecrafts.be en oxfamfairtrade.be.
	Version:     0.1.0
	Author:      Full Stack Ahead
	Author URI:  https://www.fullstackahead.be
	Text Domain: oft
	*/

	defined('ABSPATH') or die('Access prohibited!');

	class Custom_Business_Logic {
		public static $company;

		public function __construct( $param = 'oft' ) {
			self::$company = $param;

			// Verberg bepaalde tabbladen onder 'Mijn account'
			add_filter( 'woocommerce_account_menu_items', array( $this, 'remove_my_account_menu_items' ), 10, 1 );

			// Verhinder het permanent verwijderen van producten (maar na 1 jaar wel automatische clean-up door Wordpress, zie wp-config.php!)
			add_action( 'before_delete_post', array( $this, 'disable_manual_product_removal' ), 10, 1 );
		}

		public function remove_my_account_menu_items( $items ) {
			if ( self::$company === 'oft' ) {
				unset($items['dashboard']);
			}
			unset($items['downloads']);
			return $items;
		}

		public function disable_manual_product_removal( $post_id ) {
			if ( get_post_type($post_id) == 'product' ) {
				wp_die( sprintf( __( 'Uit veiligheidsoverwegingen is het verwijderen van producten niet toegestaan, voor geen enkele gebruikersrol! Vraag &ndash; indien nodig &ndash; dat de hogere machten op %s deze beperking tijdelijk opheffen, zodat je je vuile zaakjes kunt opknappen.', 'oft' ), '<a href="mailto:'.get_option('admin_email').'">'.get_option('admin_email').'</a>' ) );
			}
		}
	}

	new Custom_Business_Logic('oft');
	
	// register_activation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
	// register_deactivation_hook( __FILE__, array( 'Custom_Business_Logic', 'install' ) );
?>