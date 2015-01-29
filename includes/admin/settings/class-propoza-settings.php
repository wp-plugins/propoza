<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	if ( ! class_exists( 'Propoza_Settings' ) ) :

		class Propoza_Settings {
			/**
			 * Init and hook in the integration.
			 */
			public function __construct() {
				// Actions.
				add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );
				add_action( 'woocommerce_settings_tabs_propoza', array( __CLASS__, 'settings_tab' ) );
				add_action( 'woocommerce_update_options_propoza', array( __CLASS__, 'update_settings' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );
				add_action( 'wp_ajax_get_basic_auth', array( $this, 'get_basic_auth_ajax' ) );
				add_action( 'wp_ajax_get_test_connection_url', array( $this, 'get_test_connection_url_ajax' ) );
			}

			/**
			 * Add a new settings tab to the WooCommerce settings tabs array.
			 *
			 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
			 *
			 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
			 */
			public static function add_settings_tab( $settings_tabs ) {
				$settings_tabs['propoza'] = __( 'Propoza', 'propoza-settings' );

				return $settings_tabs;
			}

			/**
			 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
			 *
			 * @uses woocommerce_admin_fields()
			 * @uses self::get_settings()
			 */
			public static function settings_tab() {
				woocommerce_admin_fields( self::get_settings() );
			}

			/**
			 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
			 *
			 * @uses woocommerce_update_options()
			 * @uses self::get_settings()
			 */
			public static function update_settings() {
				woocommerce_update_options( self::get_settings() );
			}

			/**
			 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
			 *
			 * @return array Array of settings for @see woocommerce_admin_fields() function.
			 */
			public static function get_settings() {
				add_action( 'woocommerce_admin_field_button', array( __CLASS__, 'generate_button_html' ) );
				add_action( 'woocommerce_admin_field_header', array( __CLASS__, 'generate_header_html' ) );
				add_action( 'woocommerce_admin_field_api_key_textarea', array( __CLASS__, 'generate_api_key_textarea_html' ) );

				$settings = apply_filters( 'wc_settings_tab_propoza', array(
					'section_title'      => array(
						'name'  => __( 'Propoza', 'propoza-settings' ),
						'type'  => 'header',
						'class' => 'propoza-header',
						'id'    => 'wc_settings_tab_propoza_section_title'
					),
					'section_end'        => array(
						'type' => 'sectionend',
						'id'   => 'wc_settings_tab_propoza_section_end'
					),
					'setup_title'        => array( 'title' => __( 'Setup', 'propoza' ), 'type' => 'title', 'desc' => 'Haven\'t setup Propoza yet? Create a free account to start using Propoza to receive quote reuquests from your customers.', 'id' => 'wc_settings_tab_propoza_setup_title' ),
					'setup_free_account' => array(
						'text'              => __( 'Setup your free account', 'propoza' ),
						'type'              => 'button',
						'class'             => 'button',
						'id'                => 'wc_settings_tab_propoza_setup_free_account_button',
						'custom_attributes' => array(
							'onclick' => "window.open('" . Propoza::get_sign_up_propoza_url() . "')",
						),
					),
					'setup_end'          => array(
						'type' => 'sectionend',
						'id'   => 'wc_settings_tab_propoza_section_end'
					),
					'general_title'      => array( 'title' => __( 'General', 'propoza' ), 'type' => 'title', 'desc' => '', 'id' => 'wc_settings_tab_propoza_general_title' ),
					'web_address'        => array(
						'title' => __( 'Sub-domain', 'propoza' ),
						'type'  => 'text',
						'id'    => 'wc_settings_tab_propoza_web_address',
						'desc'  => __( '.propoza.com<p>Please enter the sub-domain that you have registered with your Propoza account.</p>', 'propoza' ),
					),
					'api_key'            => array(
						'title' => __( 'API Key', 'propoza' ),
						'type'  => 'api_key_textarea',
						'id'    => 'wc_settings_tab_propoza_api_key',
						'desc'  => __( '<p>The API key will be send to you in our email after you have setup your Propoza account</p>' ),
						'css'   => 'width:195px; height:150px'
					),
					'test_connection'    => array(
						'text'              => __( 'Test connection', 'propoza' ),
						'type'              => 'button',
						'class'             => 'button',
						'custom_attributes' => array(
							'onclick' => "test_connection();",
						),
						'id'                => 'wc_settings_tab_propoza_test_connection_button',
						'after'             => '<a href="' . Propoza::get_dashboard_propoza_url() . '" target="_blank" class="button"   id="wc_settings_tab_propoza_launch_propoza">' . __( 'Launch Propoza', 'propoza-settings' ) . '</a>'
					),
					'general_end'        => array(
						'type' => 'sectionend',
						'id'   => 'wc_settings_tab_propoza_section_end'
					),
				) );

				return apply_filters( 'wc_settings_tab_propoza', $settings );
			}

			public static function generate_api_key_textarea_html( $value ) {
				// Custom attribute handling
				$custom_attributes = array();

				if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
					foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
						$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
					}
				}

				// Description handling
				if ( true === $value['desc_tip'] ) {
					$description = '';
					$tip         = $value['desc'];
				} elseif ( ! empty( $value['desc_tip'] ) ) {
					$description = $value['desc'];
					$tip         = $value['desc_tip'];
				} elseif ( ! empty( $value['desc'] ) ) {
					$description = $value['desc'];
					$tip         = '';
				} else {
					$description = $tip = '';
				}


				$description  = '<span class="description" style="vertical-align: top">' . wp_kses_post( $description ) . '</span>';
				$option_value = get_option( $value['id'], $value['default'] );

				?>
				<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo $tip; ?>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                    <textarea
	                    name="<?php echo esc_attr( $value['id'] ); ?>"
	                    id="<?php echo esc_attr( $value['id'] ); ?>"
	                    style="<?php echo esc_attr( $value['css'] ); ?>"
	                    class="<?php echo esc_attr( $value['class'] ); ?>"
	                    <?php echo implode( ' ', $custom_attributes ); ?>
	                    ><?php echo esc_textarea( $option_value ); ?></textarea>
					<?php echo $description; ?>
				</td>
				</tr><?php
			}

			public static function generate_header_html( $value ) {
				echo '<h3 id="' . esc_attr( $value['id'] ) . '" class="' . esc_attr( $value['class'] ) . '">' . esc_html( $value['title'] ) . '</h3>';
			}

			public static function generate_button_html( $value ) {
				// Custom attribute handling
				$custom_attributes = array();

				if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
					foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
						$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
					}
				}

				// Description handling
				if ( true === $value['desc_tip'] ) {
					$description = '';
					$tip         = $value['desc'];
				} elseif ( ! empty( $value['desc_tip'] ) ) {
					$description = $value['desc'];
					$tip         = $value['desc_tip'];
				} elseif ( ! empty( $value['desc'] ) ) {
					$description = $value['desc'];
					$tip         = '';
				} else {
					$description = $tip = '';
				}


				$description = '<span class="description">' . wp_kses_post( $description ) . '</span>';

				if ( $tip ) {
					$tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" height="16" width="16" />';
				}

				$defaults = array(
					'class'             => 'button-secondary',
					'css'               => '',
					'custom_attributes' => array(),
					'after'             => '',
					'desc_tip'          => false,
					'description'       => '',
					'title'             => '',
				);

				$data = wp_parse_args( $value, $defaults );

				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label
							for="<?php echo esc_attr( $data['id'] ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
						<?php echo $tip; ?>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
							</legend>
							<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button"
							        name="<?php echo esc_attr( $data['id'] ); ?>"
							        id="<?php echo esc_attr( $data['id'] ); ?>"
							        style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo implode( ' ', $custom_attributes ); ?>><?php echo wp_kses_post( $data['text'] ); ?></button>
							<?php echo $data['after']; ?>
							<?php echo ( $description ) ? $description : ''; ?>
						</fieldset>
					</td>
				</tr>
			<?php
			}

			public function get_basic_auth_ajax() {
				echo Propoza::get_basic_auth( $_POST['api_key'], $_POST['web_address'] );
				die();
			}

			public function get_test_connection_url_ajax() {
				echo Propoza::get_connection_test_url( $_POST['web_address'] );
				die();
			}

			/**
			 * Registers and enqueues stylesheets for the administration panel and the
			 */
			public function register_scripts_and_styles() {
				if ( is_admin() ) {
					$this->load_file( Propoza::slug . '-admin-script', '../../../assets/js/admin.js', true );
					$this->load_file( Propoza::slug . '-admin-style', '../../../assets/css/admin.css' );
				}
			}

			/**
			 * Helper function for registering and enqueueing scripts and styles.
			 *
			 * @name    The    ID to register with WordPress
			 * @file_path        The path to the actual file
			 * @is_script        Optional argument for if the incoming file_path is a JavaScript source file.
			 */
			private function load_file( $name, $file_path, $is_script = false ) {

				$url  = plugins_url( $file_path, __FILE__ );
				$file = plugin_dir_path( __FILE__ ) . $file_path;

				if ( file_exists( $file ) ) {
					if ( $is_script ) {
						wp_register_script( $name, $url, array( 'jquery' ) ); //depends on jquery
						wp_enqueue_script( $name );
						wp_localize_script( $name, Propoza::slug . '_' . 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
					} else {
						wp_register_style( $name, $url );
						wp_enqueue_style( $name );
					}
				}
			}

		}

		$Propoza_Settings = new Propoza_Settings( __FILE__ );

	endif;