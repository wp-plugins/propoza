<?php
/**
 * Propoza
 *
 * @package   Propoza_Admin
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Admin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Propoza_Admin
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.6
	 *
	 * @var      object
	 */
	protected static $instance = null;
	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.6
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	protected function __construct() {

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.6
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @access    public
	 *
	 * @param    mixed $links Plugin Action links
	 *
	 * @return    array
	 */
	public function plugin_action_links( $links ) {
		$action_links = array( 'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=integration' ) . '" title="' . esc_attr( __( 'View Propoza Settings', 'propoza' ) ) . '">' . __( 'Settings', 'propoza' ) . '</a>', );

		return array_merge( $action_links, $links );
	}


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.6
	 */
	public function init() {
		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$this->plugin_slug = Propoza::get_instance()->get_plugin_slug();

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded() {
		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {
			// Include our integration class.
			include_once 'includes/class-wc-propoza-integration.php';

			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		}
	}

	/**
	 * Add a new integration to WooCommerce.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = 'WC_Propoza_Integration';

		return $integrations;
	}
}
