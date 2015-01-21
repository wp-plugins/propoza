<?php

    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }

    if (!class_exists('Propoza_Request')) :

        class Propoza_Request
        {
            /**
             * Constructor
             */
            public function __construct()
            {
                add_action('wp_enqueue_scripts', array($this, 'register_scripts_and_styles'));

                add_action('woocommerce_proceed_to_checkout', array($this, 'add_after_cart'));
            }

            public function add_after_cart()
            {
                require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates/message.php');
            }

            /**
             * Registers and enqueues stylesheets for the administration panel and the
             * public facing site.
             */
            public function register_scripts_and_styles()
            {
                wp_enqueue_script('jquery-ui-dialog');
                wp_enqueue_style("wp-jquery-ui-dialog");

                if (!is_admin()) {
                    $this->load_file(Propoza::slug . '-script', '../assets/js/front.js', true);
                    wp_localize_script(Propoza::slug . '-script', 'ajax_object', $this->get_javascript_variables());
                    $this->load_file(Propoza::slug . '-style', '../assets/css/front.css');
                }
            }

            /**
             * Helper function for registering and enqueueing scripts and styles.
             *
             * @name    The    ID to register with WordPress
             * @file_path        The path to the actual file
             * @is_script        Optional argument for if the incoming file_path is a JavaScript source file.
             */
            private function load_file($name, $file_path, $is_script = false)
            {

                $url = plugins_url($file_path, __FILE__);
                $file = plugin_dir_path(__FILE__) . $file_path;

                if (file_exists($file)) {
                    if ($is_script) {
                        wp_register_script($name, $url, array('jquery')); //depends on jquery
                        wp_enqueue_script($name);
                    } else {
                        wp_register_style($name, $url);
                        wp_enqueue_style($name);
                    }
                }
            }

            private function get_javascript_variables()
            {
                return array('form_quote_request_url' => Propoza::get_form_quote_request_url(), 'basic_auth' => Propoza::get_basic_auth(), 'logged_in_user' => $this->get_prepared_logged_in_user(), 'prepared_quote' => $this->get_prepared_quote());
            }

            public function get_prepared_quote()
            {
                return $this->prepare_quote_request();
            }

            public function get_prepared_logged_in_user()
            {
                return $this->prepare_requester();
            }

            public function prepare_quote_request()
            {
                $to_propoza_quote = array();
                $to_propoza_quote['Quote'] = $this->prepare_quote();
                $to_propoza_quote['Quote']['Requester'] = $this->prepare_requester();
                $to_propoza_quote['Quote']['Product'] = $this->prepare_products();

                return $to_propoza_quote;
            }

            private function prepare_quote()
            {
                $to_propoza_quote = array();
                /*
                 * TODO check if alternative is needed for shop_quote_id
                 */
                $to_propoza_quote['shop_quote_id'] = 0;
                $to_propoza_quote['cart_currency'] = get_woocommerce_currency();
                $to_propoza_quote['include_default_store_tax'] = get_option('woocommerce_prices_include_tax') === 'yes' ? true : false;

                return $to_propoza_quote;
            }

            public function prepare_requester()
            {
                $current_user = wp_get_current_user();
                $requester = array();
                $requester['firstname'] = !$current_user->user_firstname ? '' : $current_user->user_firstname;
                $requester['lastname'] = !$current_user->user_lastname ? '' : $current_user->user_lastname;
                $requester['email'] = !$current_user->user_email ? '' : $current_user->user_email;
                $requester['company'] = !$current_user->billing_company ? !$current_user->shipping_company ? '' : $current_user->shipping_company : $current_user->billing_company;

                return $requester;
            }

            private function prepare_products()
            {
                $products = array();
                $parent_ids = array();
                $count = 0;
                global $woocommerce;
                foreach ($woocommerce->cart->get_cart() as $key => $product) {
                    if ($product['data']->post->post_parent > 0) {
                        if (!in_array($product['data']->post->post_parent, $parent_ids)) {
                            $products[$count] = $parent_product = $this->prepare_parent_product($this->get_parent_product($product['data']->post->post_parent));
                            $products[$count]['Child'] = $this->prepare_children($product['data']->post->post_parent);
                            $parent_ids[] = $product['data']->post->post_parent;
                        }
                    } else {
                        $products[$count] = $this->prepare_product($product);
                    }
                    $count++;
                }

                return $products;
            }

            private function get_parent_product($parent_product_id)
            {
                $product_factory = new WC_Product_Factory();
                $parent_product = $product_factory->get_product($parent_product_id);
                return $parent_product;
            }

            private function prepare_product($product)
            {
                $propoza_product = array();
                $propoza_product['name'] = $product['data']->get_title();
                $propoza_product['original_price'] = $product['data']->get_price();
                $propoza_product['sku'] = $product['data']->get_sku();
                $propoza_product['quantity'] = $product['quantity'];
                $propoza_product['ProductAttribute'] = $this->prepare_product_attributes($product);

                return $propoza_product;
            }

            private function prepare_parent_product($product)
            {
                $propoza_product = array();
                $propoza_product['name'] = $product->get_title();
                $propoza_product['original_price'] = $product->get_price();
                $propoza_product['sku'] = $product->get_sku();
                $propoza_product['quantity'] = 1;
                $propoza_product['ProductAttribute'] = '';

                return $propoza_product;
            }

            private function prepare_child_product($product)
            {
                $propoza_product = array();
                $propoza_product['name'] = $product['data']->post->post_title;
                $propoza_product['original_price'] = $product['data']->get_price();
                $propoza_product['sku'] = $product['data']->get_sku();
                $propoza_product['quantity'] = 1;
                $propoza_product['ProductAttribute'] = '';

                return $propoza_product;
            }

            private function prepare_children($parent_id)
            {
                $children = array();
                global $woocommerce;
                foreach ($woocommerce->cart->get_cart() as $key => $product) {
                    if ($product['data']->post->post_parent === $parent_id) {
                        $children[] = $this->prepare_child_product($product);
                    }
                }
                return $children;
            }

            private function prepare_product_attributes($product)
            {
                $product_attribtues = array();

                $counter = 0;
                if (!empty($product['variation'])) {
                    foreach ($product['variation'] as $key => $attribute_combination) {
                        $product_attribtues[$counter]['name'] = substr($key, strpos($key, 'attribute_') + strlen('attribute_'));
                        $product_attribtues[$counter]['value'] = $attribute_combination;
                        $counter++;
                    }
                }

                return $product_attribtues;
            }
        }

        $Propoza_Request = new Propoza_Request(__FILE__);
    endif;