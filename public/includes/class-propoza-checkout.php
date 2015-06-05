<?php
/**
 * Propoza_Checkout
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Checkout class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-propoza-admin.php`
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Checkout {

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

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.6
	 */
	public function init() {
		add_action( 'init', array( $this, 'propoza_checkout_rewrites_init' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'template_redirect', array( $this, 'propoza_checkout_template_redirect_intercept' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'order_placed' ), 10, 1 );
	}

	public function order_placed( $order_id ) {
		$order = new WC_Order( $order_id );
		foreach ( $order->get_used_coupons() as $coupon_code ) {
			$propoza_coupon = new Propoza_Coupon();
			$propoza_coupon->load_by_id( $coupon_code );
			if ( $propoza_coupon->is_propoza_proposal ) {
				$response = wp_remote_post( Propoza::get_quote_ordered_url(), array(
						'method'  => 'POST',
						'body'    => json_encode( array(
							'Quote' => array(
								'id'             => $propoza_coupon->propoza_quote_id,
								'main_status_id' => 2,
								'sub_status_id'  => 5
							)
						) ),
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => 'Basic ' . WC_Propoza_Integration::option( 'api_key', null )
						)
				) );
				if ( ! is_wp_error( $response ) ) {
					$propoza_quote = new Propoza_Quote( $propoza_coupon->propoza_quote_id );
					$propoza_quote->delete_proposal_quote_clones( $propoza_coupon->propoza_quote_id );
				}
			}
		}
	}

	public function plugins_loaded() {
		require_once( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-quote.php' );
		require_once( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-propoza-coupon.php' );
	}


	public function propoza_checkout_template_redirect_intercept() {
		global $wp_query;
		if ( $wp_query->get( 'propoza' ) && $wp_query->get( 'checkout' ) && $wp_query->get( 'quote_id' ) ) {
			$this->request_checkout( $wp_query->get( 'quote_id' ) );
		}
		if ( $wp_query->get( 'propoza' ) && $wp_query->get( 'add_proposal' ) ) {
			$this->add_proposal_coupon();
		}
	}

	private function request_checkout( $quote_id ) {
		if ( Propoza::is_request_authorized() ) {
			$woocommerce                      = WC();
			$woocommerce->cart->propoza_quote = new Propoza_Quote( $quote_id );
			if ( ! $woocommerce->cart->propoza_quote->get_id() ) {
				return new WP_Error( __( 'No quote found: #' . $quote_id, 'propoza' ) );
			}

			$woocommerce->cart->empty_cart();
			$woocommerce->cart->remove_coupons();

			if ( isset( $woocommerce->cart->propoza_quote ) ) {
				foreach ( $woocommerce->cart->propoza_quote->get_products() as $product ) {
					$woocommerce->cart->add_to_cart( $product['product_id'], $product['quantity'], $product['variation_id'], $product['variation'] );
				}
			}

			$woocommerce->cart->propoza_coupon = new Propoza_Coupon( $woocommerce->cart->propoza_quote->get_propoza_quote_id() );
			if ( $woocommerce->cart->add_discount( sanitize_text_field( $woocommerce->cart->propoza_coupon->code ) ) ) {
				wc_add_notice( __( '<b>Please note:</b> <i>Changing the cart contents will invalidate the proposal price.</i>' ), 'notice' );
			}
			wp_redirect( $woocommerce->cart->get_cart_url() );
			exit;
		} else {
			throw new Exception( __( 'You are not authorized to access this function', 'propoza' ) );
		}
	}

	private function add_proposal_coupon() {
		if ( Propoza::is_request_authorized() ) {
			$post             = json_decode( file_get_contents( "php://input" ), true );
			$propoza_quote_id = $post['id'];
			$quote_id         = $post['shop_quote_id'];
			$proposal_total   = $post['total_proposal_price'];
			$original_total   = $post['total_original_price'];
			$discount         = $original_total - $proposal_total;
			if ( isset( $quote_id ) && isset( $proposal_total ) && isset( $original_total ) && isset( $propoza_quote_id ) ) {

				//Load the original quote by stored id in Propoza
				$quote = new Propoza_Quote( $quote_id );

				if ( $quote->get_id() == null ) {
					throw new Exception( __( 'No quote found with id: #' . $quote_id, 'propoza' ) );
				}

				$quote->set_propoza_quote_id( $propoza_quote_id );
				$quote->save();

				$cloned_quote = $quote->clone_quote();

				if ( $cloned_quote->get_id() == null ) {
					throw new Exception( __( 'Quote could not be cloned: #' . $quote_id, 'propoza' ) );
				}

				$propoza_coupon = new Propoza_Coupon( $propoza_quote_id );
				if ( ! $propoza_coupon->exists ) {
					$coupon_id = $propoza_coupon->create_proposal_coupon( $cloned_quote, $discount );
				} else {
					$cloned_quote->delete_proposal_quote_clones( $propoza_quote_id, array(
						(int) $cloned_quote->get_id(),
						(int) $quote->get_id()
					) );
					$coupon_id = $propoza_coupon->update_propoza_coupon( $cloned_quote, $discount );
				}
				if ( $coupon_id == null ) {
					throw new Exception( __( 'Coupon could not be created/updated', 'propoza' ) );
				}

				header( 'Content-Type: application/json' );
				echo json_encode( array( 'quote_id' => $cloned_quote->get_id() ) );
				die;
			}
		} else {
			throw new Exception( __( 'You are not authorized to access this function', 'propoza' ) );
		}
	}

	public function propoza_checkout_rewrites_init() {
		add_rewrite_tag( '%propoza%', '([0-9]+)' );
		add_rewrite_tag( '%checkout%', '([0-9]+)' );
		add_rewrite_tag( '%quote_id%', '([0-9]+)' );
		add_rewrite_tag( '%add_proposal%', '([0-9]+)' );
	}
}