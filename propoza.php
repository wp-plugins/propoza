<?php
/**
 * Propoza
 *
 * An awesome plugin that does awesome things
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Propoza
 * Plugin URI:        https://propoza.com
 * Description:       Propoza adds quotation functionality to your webshop. This means more leads & more orders!
 * Version:           1.0.7
 * Author:            Propoza
 * Text Domain:       propoza
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI:
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	/*----------------------------------------------------------------------------*
	 * Public-Facing Functionality
	 *----------------------------------------------------------------------------*/
	require_once( plugin_dir_path( __FILE__ ) . 'public/class-propoza.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-propoza-admin.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'public/class-propoza-frontend.php' );

	Propoza::get_instance()->init();

	/*
	 * Register hooks that are fired when the plugin is activated or deactivated.
	 * When the plugin is deleted, the uninstall.php file is loaded.
	 */
	register_activation_hook( __FILE__, array(
		'Propoza',
		'activate'
	) );
	register_deactivation_hook( __FILE__, array(
		'Propoza',
		'deactivate'
	) );

	/*----------------------------------------------------------------------------*
	 * Dashboard and Administrative Functionality
	 *----------------------------------------------------------------------------*/

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		Propoza_Admin::get_instance()->init();
		Propoza_Frontend::get_instance()->init();
	}

	if ( is_admin() ) {
		Propoza_Admin::get_instance()->init();
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
			Propoza_Admin::get_instance(),
			'plugin_action_links'
		) );
	} else {
		Propoza_Frontend::get_instance()->init();
	}
} else {
	function woocommerce_not_active_notice() {
		echo '<div class="error"><p><b>WooCommerce</b> is not active/installed. Please activate/install <b>WooCommerce</b> to make use of <b>Propoza</b>!</p></div>';
	}

	add_action( 'admin_notices', 'woocommerce_not_active_notice' );
}