<?php

class JPIBFI_Pin_Full_Images_Options {

	protected static $instance = null;

	private function __construct() {
		add_action( 'admin_init', array( $this, 'initialize_options' ) );
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public static function default_options() {
		return array(
			'fileExtensions' => '*',
			'checkDomain' => 'false'
		);
	}

	function initialize_options()	{

		add_settings_section(
			'jpibfi_pin_full_images_options_section',
			__( 'JPIBFI Pin Full Images Settings', 'jpibfi-pin-full-images' ),
			array( $this, 'jpibfi_pin_full_images_callback' ),
			JPIBFI_PIN_FULL_IMAGES_OPTIONS
		);

		//lThen add all necessary fields to the section
		add_settings_field(
			'fileExtensions',
			__( 'Enabled file extensions', 'jpibfi-pin-full-images' ),
			array( $this, 'file_extensions_callback' ),
			JPIBFI_PIN_FULL_IMAGES_OPTIONS,
			'jpibfi_pin_full_images_options_section',
			array(
				__( '* means files of any extension will be used (also those without any extension). If you want to use only files of certain extension(s), type those file extensions here (separate them by commmas).', 'jpibfi-pin-full-images' ),
			)
		);

		add_settings_field(
			'checkDomain',
			__( 'Check domain', 'jpibfi-pin-full-images' ),
			array( $this, 'check_domain_callback' ),
			JPIBFI_PIN_FULL_IMAGES_OPTIONS,
			'jpibfi_pin_full_images_options_section',
			array(
				__( 'When checked, plugin uses URLs only if they are from the same domain as the website.', 'jpibfi-pin-full-images' ),
			)
		);

		register_setting(
			JPIBFI_PIN_FULL_IMAGES_OPTIONS,
			JPIBFI_PIN_FULL_IMAGES_OPTIONS,
			array( $this, 'sanitize_options' )
		);

	}

	function jpibfi_pin_full_images_callback() {
		echo '<p>' . __('JPIBFI Pin Full Images settings', 'jpibfi-pin-full-images') . '</p>';
	}

	function file_extensions_callback( $args ) {
		$options = $this->get_options();
		$val = esc_attr( $options['fileExtensions'] );
		echo '<input type="text" id="fileExtensions" name="jpibfi_pin_full_images_options[fileExtensions]" value="' . $val . '"/>';
		echo JPIBFI_Admin_Utilities::create_description( $args[0] );
	}

	function check_domain_callback( $args ) {
		$options = $this->get_options();
		$checked = $options['checkDomain'];
		echo '<input type="checkbox" id="checkDomain" '. checked( "true", $checked, false ) . 'name="jpibfi_pin_full_images_options[checkDomain]" value="true">';
		echo JPIBFI_Admin_Utilities::create_description( $args[0] );
	}

	function sanitize_options( $input ) {
		$options = $this->default_options();

		foreach( $input as $key => $value ) {
			switch( $key ) {
				case 'fileExtensions':
					$options[ $key ] = esc_attr( $value );
					break;
				default:
					$options[ $key ] = $value;
					break;
			}
		}

		return $options;
	}

	private function get_options(){
		global $jpibfi_pin_full_images_options;
		return $jpibfi_pin_full_images_options;
	}
}