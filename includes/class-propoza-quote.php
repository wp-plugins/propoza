<?php
/**
 * Propoza_Quote
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */

/**
 * Propoza_Quote class.
 *
 * @package Propoza
 * @author  Propoza <support@propoza.com>
 */
class Propoza_Quote {
	private static $quote_id_prefix = 'quote_';
	private $quote_fields = array( 'products', 'propoza_quote_id' );
	private $id;
	private $propoza_quote_id;
	private $products = array();

	public function __construct( $id = null ) {
		if ( $id != null ) {
			$this->id = $id;
			$this->load();
		}
	}

	private function load() {
		foreach ( $this->quote_fields as $field ) {
			$post_meta    = get_post_meta( $this->id, $field, true );
			$this->$field = empty( $post_meta ) ? $this->$field : $post_meta;
		}
	}

	public function get_prepared_quote() {
		return $this->prepare_quote_request();
	}

	public function prepare_quote_request() {
		$to_propoza_quote                       = array();
		$to_propoza_quote['Quote']              = $this->prepare_quote();
		$to_propoza_quote['Quote']['Requester'] = $this->prepare_requester();
		$to_propoza_quote['Quote']['Product']   = $this->prepare_products();
		$to_propoza_quote['Quote']['shop_url']  = get_site_url();

		return $to_propoza_quote;
	}

	private function prepare_quote() {
		$to_propoza_quote                              = array();
		$to_propoza_quote['shop_quote_id']             = $this->id;
		$to_propoza_quote['cart_currency']             = get_woocommerce_currency();
		$to_propoza_quote['include_default_store_tax'] = get_option( 'woocommerce_prices_include_tax' ) === 'yes' ? true : false;

		return $to_propoza_quote;
	}

	public function prepare_requester() {
		$form_data              = $_POST['form-data'];
		$requester              = array();
		$requester['firstname'] = isset( $form_data['firstname'] ) ? $form_data['firstname'] : '';
		$requester['lastname']  = isset( $form_data['lastname'] ) ? $form_data['lastname'] : '';
		$requester['email']     = isset( $form_data['email'] ) ? $form_data['email'] : '';
		$requester['company']   = isset( $form_data['company'] ) ? $form_data['company'] : '';

		return $requester;
	}

	private function prepare_products() {
		$products   = array();
		$parent_ids = array();
		$count      = 0;
		foreach ( $this->products as $product ) {
			if ( $product['data']->post->post_parent > 0 ) {
				if ( ! in_array( $product['data']->post->post_parent, $parent_ids ) ) {
					$products[ $count ]          = $parent_product = $this->prepare_parent_product( $this->get_parent_product( $product['data']->post->post_parent ) );
					$products[ $count ]['Child'] = $this->prepare_children( $product['data']->post->post_parent );
					$parent_ids[]                = $product['data']->post->post_parent;
				}
			} else {
				$products[ $count ] = $this->prepare_product( $product );
			}
			$count ++;
		}

		return $products;
	}

	private function prepare_parent_product( $product ) {
		$propoza_product                     = array();
		$propoza_product['name']             = $product->get_title();
		$propoza_product['original_price']   = $product->get_price();
		$propoza_product['sku']              = $product->get_sku();
		$propoza_product['quantity']         = 1;
		$propoza_product['ProductAttribute'] = '';

		return $propoza_product;
	}

	private function get_parent_product( $parent_product_id ) {
		$product_factory = new WC_Product_Factory();
		$parent_product  = $product_factory->get_product( $parent_product_id );

		return $parent_product;
	}

	private function prepare_children( $parent_id ) {
		$children = array();

		foreach ( WC()->cart->get_cart() as $key => $product ) {
			if ( $parent_id === $product['data']->post->post_parent ) {
				$children[] = $this->prepare_child_product( $product );
			}
		}

		return $children;
	}

	private function prepare_child_product( $product ) {
		$propoza_product                     = array();
		$propoza_product['name']             = $product['data']->post->post_title;
		$propoza_product['original_price']   = $product['data']->get_price();
		$propoza_product['sku']              = $product['data']->get_sku();
		$propoza_product['quantity']         = 1;
		$propoza_product['ProductAttribute'] = '';

		return $propoza_product;
	}

	private function prepare_product( $product ) {
		$propoza_product                     = array();
		$propoza_product['name']             = $product['data']->get_title();
		$propoza_product['original_price']   = $product['data']->get_price();
		$propoza_product['sku']              = $product['data']->get_sku();
		$propoza_product['quantity']         = $product['quantity'];
		$propoza_product['ProductAttribute'] = $this->prepare_product_attributes( $product );

		return $propoza_product;
	}

	private function prepare_product_attributes( $product ) {
		$product_attribtues = array();

		$counter = 0;
		if ( ! empty( $product['variation'] ) ) {
			foreach ( $product['variation'] as $key => $attribute_combination ) {
				$product_attribtues[ $counter ]['name']  = substr( $key,
					strpos( $key, 'attribute_' ) +
					strlen( 'attribute_' ) );
				$product_attribtues[ $counter ]['value'] = $attribute_combination;
				$counter ++;
			}
		}

		return $product_attribtues;
	}

	public function load_products_from_cart() {
		foreach ( WC()->cart->get_cart() as $key => $product ) {
			$this->add_product( $product );
		}
	}

	public function add_product( $product ) {
		array_push( $this->products, $product );
	}

	public function get_prepared_logged_in_user() {
		$current_user           = wp_get_current_user();
		$requester              = array();
		$requester['firstname'] = ! $current_user->user_firstname ? '' : $current_user->user_firstname;
		$requester['lastname']  = ! $current_user->user_lastname ? '' : $current_user->user_lastname;
		$requester['email']     = ! $current_user->user_email ? '' : $current_user->user_email;
		$requester['company']   = ! $current_user->billing_company ? ! $current_user->shipping_company ? '' : $current_user->shipping_company : $current_user->billing_company;

		return $requester;
	}

	public function get_id() {
		return $this->id;
	}

	public function set_id( $id ) {
		$this->id = $id;
	}

	public function get_products() {
		return $this->products;
	}

	public function set_products( $products ) {
		$this->products = $products;
	}

	public function get_propoza_quote_id() {
		return $this->propoza_quote_id;
	}

	public function set_propoza_quote_id( $propoza_quote_id ) {
		$this->propoza_quote_id = $propoza_quote_id;
	}

	public function custom_post_status() {
		register_post_status( 'quote', array(
			'label'                     => _x( 'Quote', 'post' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
			'label_count'               => _n_noop( 'Quote <span class="count">(%s)</span>', 'Quote <span class="count">(%s)</span>' ),
		) );
	}

	public function get_quote_total() {
		$total = 0;
		foreach ( $this->products as $product ) {
			$total += $product['line_subtotal'];
		}

		return $total;
	}

	public function get_product_ids() {
		$ids = array();
		foreach ( $this->products as $product ) {
			array_push( $ids, $product['product_id'] );
		}

		return $ids;
	}

	public function clone_quote() {
		$propoza_quote = new Propoza_Quote();
		foreach ( $this->quote_fields as $field ) {
			$propoza_quote->$field = $this->$field;
		}
		$propoza_quote->save();

		return $propoza_quote;
	}

	public function save() {
		if ( $this->id ) {
			foreach ( $this->quote_fields as $field ) {
				update_post_meta( $this->id, $field, $this->$field );
			}
		} else {
			$this->id = $this->create();
			$this->save();
		}
	}

	private function create() {
		$quote = array(
			'post_title'   => Propoza_Quote::$quote_id_prefix . time(),
			'post_content' => '',
			'post_status'  => 'quote',
			'post_author'  => 1,
			'post_type'    => 'shop_quote'
		);

		return wp_insert_post( $quote );
	}

	public function delete_proposal_quote_clones( $propoza_quote_id, $exclude_quote_ids = array() ) {
		$data   = new WP_Query( array(
			'post_type'   => 'shop_quote',
			'post_status' => 'quote',
			'meta_key'    => 'propoza_quote_id',
			'meta_value'  => $propoza_quote_id
		) );
		$quotes = $data->get_posts();
		foreach ( $quotes as $quote ) {
			if ( ! in_array( $quote->ID, $exclude_quote_ids ) ) {
				foreach ( $this->quote_fields as $fields ) {
					delete_post_meta( $quote->ID, $fields );
				}
				wp_delete_post( $quote->ID );
			}
		}
	}
}