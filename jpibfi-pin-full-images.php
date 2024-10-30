<?php
/*
Plugin Name: JPIBFI Pin Full Images
Plugin URI: http://mrsztuczkens.me/jpibfi/
Description: Allows the user to pin full images even when they're using thumbnails on their website.
Author: Marcin Skrzypiec
Version: 0.13
Author URI: http://mrsztuczkens.me/
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

class JPIBFI_Pin_Full_Images {

	/* STATIC */
	private static $instance;
	private static $compatibility_error_message = null;

	/* Instance */
	private $plugin_dir;
	private $plugin_url;
	private $plugin_file;

	private function __construct() {
		$this->setup_constants();
		$this->includes();
		$this->update_plugin();
		$this->load_textdomain();

		$jpibfi_extension = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$jpibfi_extension", array( $this, 'plugin_settings_filter' ) );

		add_filter( 'jpibfi_settings_tabs', array( $this, 'add_settings_tab' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'add_plugin_scripts' ) );

		add_filter( 'jpibfi_javascript_parameters', array( $this, 'add_javascript_parameters' ) );

		add_action( 'admin_init', array( $this, 'check_version' ) );
	}

	private static function get_compatibility_error_message() {
		if ( null == self::$compatibility_error_message)
			self::$compatibility_error_message = __( 'JPIBFI Pin Full Images requires JPIBFI version 1.31 or higher', 'jpibfi-pin-full-images' );
		return self::$compatibility_error_message;
	}

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/* Setup plugin constants */
	private function setup_constants() {

		$this->plugin_file = __FILE__;
		$this->plugin_dir = plugin_dir_path( $this->plugin_file );
		$this->plugin_url = plugin_dir_url( $this->plugin_file );

		/* VERSIONING */
		//plugin version
		if ( ! defined( 'JPIBFI_PIN_FULL_IMAGES_VERSION' ) )
			define( 'JPIBFI_PIN_FULL_IMAGES_VERSION', '0.13' );

		//used in versioning css and js files
		if ( ! defined( 'JPIBFI_PIN_FULL_IMAGES_VERSION_MINOR' ) )
			define( 'JPIBFI_PIN_FULL_IMAGES_VERSION_MINOR', 'a' );

		/* OPTIONS IN DATABASE */
		if ( ! defined( 'JPIBFI_PIN_FULL_IMAGES_OPTIONS' ) )
			define( 'JPIBFI_PIN_FULL_IMAGES_OPTIONS', 'jpibfi_pin_full_images_options' );

		if ( ! defined( 'JPIBFI_PIN_FULL_IMAGES_VERSION_OPTION' ) )
			define( 'JPIBFI_PIN_FULL_IMAGES_VERSION_OPTION', 'jpibfi_pin_full_images_version' );

	}

	/* Function updates DB if it detects new version of the plugin */
	public function update_plugin() {
		$version = get_option( JPIBFI_PIN_FULL_IMAGES_VERSION_OPTION );
		//if update is needed
		if ( false == $version || (float)$version < (float)JPIBFI_PIN_FULL_IMAGES_VERSION ) {
			$option = $this->get_options();
			jQuery_Pin_It_Button_For_Images::update_option_fields( $option, JPIBFI_Pin_Full_Images_Options::default_options(), JPIBFI_PIN_FULL_IMAGES_OPTIONS );
			update_option( JPIBFI_PIN_FULL_IMAGES_VERSION_OPTION, JPIBFI_PIN_FULL_IMAGES_VERSION );
		}
	}

	/* Include required files */
	private function includes() {
		global $jpibfi_pin_full_images_options;

		$jpibfi_pin_full_images_options = get_option( JPIBFI_PIN_FULL_IMAGES_OPTIONS );

		require_once $this->plugin_dir . 'class-jpibfi-pin-full-images-options.php';

		if ( is_admin() ) {
			JPIBFI_Pin_Full_Images_Options::get_instance();
		} else {
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'jpibfi-pin-full-images', FALSE, dirname( plugin_basename( $this->plugin_file ) ) . '/languages/' );
	}

	public function plugin_settings_filter( $links ) {
		$settings_link = '<a href="options-general.php?page=jpibfi_settings&tab=pin_full_images_options">' . __( 'Settings', 'jpibfi-extension' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['pin_full_images_options'] =  array(
				'settings_name' => 'jpibfi_pin_full_images_options',
				'tab_label' => __( 'Pin Full Images', 'jpibfi-pin-full-images' ),
				'support_link' => 'http://wordpress.org/support/plugin/jpibfi-pin-full-images',
				'review_link' => 'http://wordpress.org/support/view/plugin-reviews/jpibfi-pin-full-images'
			);

		return $settings_tabs;
	}

	public function add_javascript_parameters( $parameters ) {
		$parameters['pinFullImages'] = $this->get_options();
		return $parameters;
	}

	public function add_plugin_scripts() {
		if ( ! ( JPIBFI_Client_Utilities::add_jpibfi() ) )
			return;
		wp_enqueue_script( 'jpibfi-pin-full-images-script', $this->plugin_url . 'jpibfi-pin-full-images-client.min.js', array( 'jquery-pin-it-button-script' ), JPIBFI_PIN_FULL_IMAGES_VERSION . JPIBFI_PIN_FULL_IMAGES_VERSION_MINOR, false );
	}

	private function get_options(){
		global $jpibfi_pin_full_images_options;
		return $jpibfi_pin_full_images_options;
	}

	/* VERSION CHECK */
	//based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	public function check_version() {
		if ( ! self::compatible_version() ) {
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'disabled_notice' ) );
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}

	function disabled_notice() {
		echo '<div class="error"><strong>' .	self::get_compatibility_error_message()	. '</strong></div>';
	}

	static function activation_check() {
		if ( ! self::compatible_version() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( self::get_compatibility_error_message() );
		}
	}

	static function compatible_version() {
		$jpibfi_version = get_option( JPIBFI_VERSION_OPTION );
		return false != $jpibfi_version && version_compare( $jpibfi_version, '1.31', '>=' );
	}
}

function jpibfi_extension_load() {
	if( !class_exists( 'jQuery_Pin_It_Button_For_Images' ) ) return;
	JPIBFI_Pin_Full_Images::instance();
}
add_action( 'plugins_loaded', 'jpibfi_extension_load' );

register_activation_hook( __FILE__, array( 'JPIBFI_Pin_Full_Images', 'activation_check' ) );