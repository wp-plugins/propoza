<?php
/**
 * Propoza
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-propoza-admin.php`
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.5';
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;
	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'propoza';

	private function __construct() {

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
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
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		add_action( 'quote', array( 'Propoza_Quote_Request', 'quote_post_status' ) );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {

	}

	public static function get_sign_up_propoza_url() {
		return sprintf( '%s%s/accounts/create?client=woocommerce', Propoza::get_protocol(), Propoza::get_propoza_url() );
	}

	public static function get_protocol() {
		return empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] == "off" ? 'http://' : 'https://';
	}

	public static function get_propoza_url() {
		return 'propoza.com';
	}

	public static function get_connection_test_url( $sub_domain = null ) {
		return sprintf( '%s/api/WooCommerceQuotes/testConnection.json', Propoza::get_dashboard_propoza_url( $sub_domain ) );
	}

	public static function get_dashboard_propoza_url( $sub_domain = null ) {
		if ( empty( $sub_domain ) ) {
			require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/includes/class-wc-propoza-integration.php' );
			$sub_domain = WC_Propoza_Integration::option( 'web_address', null );
		}

		return sprintf( '%s%s.%s', Propoza::get_protocol(), $sub_domain, Propoza::get_propoza_url() );
	}

	public static function get_form_quote_request_url() {
		return sprintf( '%s/api/WooCommerceQuotes/requestQuoteForm', Propoza::get_dashboard_propoza_url() );
	}

	public static function is_valid_api_key( $string ) {
		return preg_match( '/^[A-Za-z0-9+\/]{226}==$/', $string );
	}

	public static function is_request_authorized() {
		$referrer = null;
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$referrer = $_SERVER['HTTP_REFERER'];
		} elseif ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
			$referrer = $_SERVER['HTTP_ORIGIN'];
		}

		$referrer_parts = parse_url( $referrer );
		$referrer_host  = $referrer_parts['host'];

		$dashboard_parts = parse_url( self::get_dashboard_propoza_url() );
		$dashboard_host  = $dashboard_parts['host'];

		return $referrer_host == $dashboard_host;
	}

	public static function get_quote_ordered_url() {
		return sprintf( '%s/api/WooCommerceQuotes/edit.json', Propoza::get_dashboard_propoza_url() );
	}

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function init() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}
}
