<?php
/**
 * Propoza_Quote_Request
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Quote_Request class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-propoza-admin.php`
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Quote_Request {
	/**
	 * Instance of this class.
	 *
	 * @since    1.0.6
	 *
	 * @var      object
	 */
	protected static $instance = null;

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

	public function init() {
		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_styles'
		) );
		add_action( 'wp_enqueue_scripts', array(
			$this,
			'enqueue_scripts'
		) );

		add_action( 'wp_ajax_get_form_quote_request', array(
			$this,
			'get_form_quote_request'
		) );
		add_action( 'wp_ajax_execute_request_quote', array(
			$this,
			'execute_request_quote'
		) );
		add_action( 'wp_ajax_nopriv_get_form_quote_request', array(
			$this,
			'get_form_quote_request'
		) );
		add_action( 'wp_ajax_nopriv_execute_request_quote', array(
			$this,
			'execute_request_quote'
		) );

		add_action( 'woocommerce_proceed_to_checkout', array(
			$this,
			'add_after_cart'
		), 1000 );

		add_action( 'plugins_loaded', array(
			$this,
			'plugins_loaded'
		) );
	}

	public function plugins_loaded() {
		require_once( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'admin/includes/class-wc-propoza-integration.php' );
		require_once( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-quote.php' );
		require_once( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-coupon.php' );
	}

	public function has_propoza_coupon() {
		$has_propoza_coupon = false;
		foreach ( WC()->cart->get_applied_coupons() as $coupon ) {
			$propoza_coupon = new Propoza_Coupon();
			$propoza_coupon->load_by_id( $coupon );
			$has_propoza_coupon = $propoza_coupon->is_propoza_proposal;
		}

		return $has_propoza_coupon;
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.6
	 */
	public function enqueue_styles() {
		wp_enqueue_style( "wp-jquery-ui-dialog" );
		wp_enqueue_style( Propoza::get_instance()->get_plugin_slug() . '-plugin-styles', plugins_url( 'assets/css/propoza-quote-request.css', dirname( __FILE__ ) ), array(), Propoza::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.6
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( Propoza::get_instance()->get_plugin_slug() . '-plugin-script', plugins_url( 'assets/js/propoza-quote-request.js', dirname( __FILE__ ) ), array( 'jquery' ), Propoza::VERSION );
		wp_localize_script( Propoza::get_instance()->get_plugin_slug() . '-plugin-script', Propoza::get_instance()->get_plugin_slug() . '_' . 'request', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function add_after_cart() {
		require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'views/propoza-quote-request-button.php' );
		require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'views/propoza-quote-request-dialog.php' );
		require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'views/propoza-quote-request-error-message.php' );
	}

	public function get_form_quote_request() {
		$propoza_quote = new Propoza_Quote();
		$response      = wp_remote_post( Propoza::get_form_quote_request_url(), array(
			'method'  => 'POST',
			'body'    => json_encode( $propoza_quote->get_prepared_logged_in_user() ),
			'headers' => array(
				'Content-Type'  => 'text/html',
				'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null )
			)
		) );

		if ( ! is_wp_error( $response ) ) {
			echo $response['body'];
		}
		die;
	}

	public function execute_request_quote() {
		$propoza_quote = new Propoza_Quote();
		$propoza_quote->load_products_from_cart();
		$propoza_quote->save();
		$quote_request = $propoza_quote->prepare_quote_request();

		$quote_request['Quote'] = array_merge( $quote_request['Quote'], $_POST['data'] );
		$response               = wp_remote_post( $_POST['form-action'], array(
			'method'  => 'POST',
			'body'    => json_encode( $quote_request ),
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null )
			)
		) );
		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( $response['body'], true );
			if ( ! isset( $body['response']['validationErrors'] ) ) {
				$woocommerce = WC();
				$woocommerce->cart->empty_cart();
			} else {
				$body['response']['validationErrors'] = $this->array_flat( $body['response']['validationErrors'] );
			}
			echo json_encode( $body, true );
		}
		die;
	}

	private function array_flat( $array, $prefix = '' ) {
		$result = array();

		foreach ( $array as $key => $value ) {
			if ( ! is_numeric( $key ) ) {
				$new_key = $prefix . ucfirst( $key );
			} else {
				$new_key = $prefix;
			}

			if ( is_array( $value ) ) {
				$result = array_merge( $result, $this->array_flat( $value, $new_key ) );
			} else {
				$result[ $this->underscore_to_camel_case( $new_key, true ) ] = $value;
			}
		}

		return $result;
	}

	private function underscore_to_camel_case( $string, $capitalizeFirstCharacter = false ) {

		$str = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $string ) ) );

		if ( ! $capitalizeFirstCharacter ) {
			$str[0] = strtolower( $str[0] );
		}

		return $str;
	}
}
