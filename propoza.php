<?php
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }

    /**
     * Check if WooCommerce is active
     **/
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        /*
        Plugin Name: Propoza
        Plugin URI: http://propoza.com
        Description: Propoza adds quotation functionality to your webshop.
        This means more leads & more orders!
        Version: 1.0.0
        Author: Propoza
        Author Email: support@propoza.com
        License:

          Copyright 2014 Propoza (support@propoza.com)

          This program is free software; you can redistribute it and/or modify
          it under the terms of the GNU General Public License, version 2, as
          published by the Free Software Foundation.

          This program is distributed in the hope that it will be useful,
          but WITHOUT ANY WARRANTY; without even the implied warranty of
          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
          GNU General Public License for more details.

          You should have received a copy of the GNU General Public License
          along with this program; if not, write to the Free Software
          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

        */

        class Propoza
        {

            /*--------------------------------------------*
             * Constants
             *--------------------------------------------*/
            const name = 'Propoza';
            const slug = 'propoza';

            /**
             * Constructor
             */
            public function __construct()
            {
                //register an activation hook for the plugin
                register_activation_hook(__FILE__, array(&$this, 'install_propoza'));

                //Hook up to the init action
                add_action('plugins_loaded', array($this, 'init_propoza'));
                add_filter('plugin_action_links_' .plugin_basename( __FILE__ ) , array($this, 'plugin_action_links'));
            }

            /**
             * Runs when the plugin is activated
             */
            public function install_propoza()
            {
                // do not generate any output here
            }

            /**
             * Show action links on the plugin screen.
             *
             * @access    public
             * @param    mixed $links Plugin Action links
             * @return    array
             */
            public function plugin_action_links($links)
            {
                $action_links = array(
                    'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=propoza') . '" title="' . esc_attr(__('View Propoza Settings', 'propoza')) . '">' . __('Settings', 'propoza') . '</a>',
                );
                return array_merge($action_links, $links);
            }

            /**
             * Runs when the plugin is initialized
             */
            public function init_propoza()
            {
                if (class_exists('WC_Integration')) {
                    // Setup localization
                    load_plugin_textdomain(self::slug, false, dirname(plugin_basename(__FILE__)) . '/lang');

                    // Include our settings class.
                    include_once 'includes/admin/settings/class-propoza-settings.php';
                    // Include our request class.
                    include_once 'includes/class-propoza-request.php';

                } else {
                    // throw an admin error if you like
                }
            }

            public static function get_protocol()
            {
                return 'http://';
            }

            public static function get_propoza_url()
            {
                return 'propoza.com';
            }

            public static function get_sign_up_propoza_url()
            {
                return Propoza::get_protocol() . Propoza::get_propoza_url() . '/accounts/create?client=woocommerce';
            }

            public static function get_dashboard_propoza_url($sub_domain = null)
            {
                if (empty($sub_domain)) {
                    $sub_domain = get_option('wc_settings_tab_propoza_web_address', null);
                }

                return Propoza::get_protocol() . $sub_domain . '.' . Propoza::get_propoza_url();
            }

            public static function get_connection_test_url($sub_domain = null)
            {
                return Propoza::get_dashboard_propoza_url($sub_domain) . '/api/WooCommerceQuotes/testConnection';
            }

            public static function get_form_quote_request_url()
            {
                return Propoza::get_dashboard_propoza_url() . '/api/WooCommerceQuotes/requestQuoteForm';
            }

            public static function get_basic_auth($api_key = null, $sub_domain = null)
            {
                if (empty($api_key)) {
                    $api_key = get_option('wc_settings_tab_propoza_api_key', null);
                }

                return base64_encode($api_key . ':' . Propoza::get_dashboard_propoza_url($sub_domain));
            }

        } // end class
        $Propoza = new Propoza(__FILE__);
    }
?>