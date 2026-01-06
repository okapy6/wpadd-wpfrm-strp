<?php

namespace WPFormsAuthorizeNet;

use WPForms_Updater;
use WPFormsAuthorizeNet\Api\Api;
use WPFormsAuthorizeNet\Fields\AuthorizeNet;
use WPFormsAuthorizeNet\Migrations\Migrations;

/**
 * WPForms Authorize.Net loader class.
 *
 * @since 1.0.0
 */
final class Loader {

	/**
	 * Authorize.Net credit card field.
	 *
	 * @since 1.0.0
	 *
	 * @var AuthorizeNet
	 */
	public $field;

	/**
	 * Authorize.Net API.
	 *
	 * @since 1.0.0
	 *
	 * @var Api
	 */
	public $api;

	/**
	 * Magic getter.
	 *
	 * @since 1.1.0
	 *
	 * @param string $name Property name.
	 *
	 * @return string|null
	 */
	public function __get( $name ) {

		if ( $name === 'url' ) {
			_deprecated_argument( __CLASS__ . '->url', '1.1.0', 'Use WPFORMS_AUTHORIZE_NET_URL constant.' );

			return WPFORMS_AUTHORIZE_NET_URL;
		}

		return null;
	}

	/**
	 * Initiate main plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Loader
	 */
	public static function get_instance() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();

			$instance->init();
		}

		return $instance;
	}

	/**
	 * Init class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();

		return $this;
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {

		add_action( 'wpforms_loaded', [ $this, 'setup' ], 20 );
	}

	/**
	 * All the actual plugin loading is done here.
	 *
	 * @since 1.0.0
	 */
	public function setup() {

		( new Migrations() )->init();

		$this->load_admin_entries();
		$this->load_settings();
		$this->load_field();
		$this->load_builder();
		$this->load_block_editor();
		$this->load_frontend();
		$this->load_api();
		$this->load_processing();
		$this->load_admin_payments();
	}

	/**
	 * Load admin entries functionality.
	 *
	 * @since 1.0.0
	 */
	private function load_admin_entries() {

		if ( wpforms_is_admin_page( 'entries' ) ) {
			( new Admin\Entries() )->init();
		}
	}

	/**
	 * Load Authorize.Net settings.
	 *
	 * @since 1.0.0
	 */
	private function load_settings() {

		if ( wpforms_is_admin_page( 'settings', 'payments' ) ) {
			( new Admin\Settings() )->init();
		}
	}

	/**
	 * Load Authorize.Net field.
	 *
	 * @since 1.0.0
	 */
	private function load_field() {

		//phpcs:disable WordPress.Security.NonceVerification
		$is_elementor =
			( ! empty( $_POST['action'] ) && $_POST['action'] === 'elementor_ajax' ) ||
			( ! empty( $_GET['action'] ) && $_GET['action'] === 'elementor' );
		// phpcs:enable WordPress.Security.NonceVerification

		if ( $is_elementor || ! is_admin() || wp_doing_ajax() || wpforms_is_admin_page( 'builder' ) ) {
			$this->field = new Fields\AuthorizeNet();
		}
	}

	/**
	 * Load block editor class.
	 *
	 * @since 1.5.0
	 */
	private function load_block_editor() {

		( new Admin\BlockEditor() )->init();
	}

	/**
	 * Load builder functionality.
	 *
	 * @since 1.0.0
	 */
	private function load_builder() {

		if ( wpforms_is_admin_page( 'builder' ) ) {
			new Admin\AuthorizeNetPayment();
			( new Admin\Builder() )->init();
		}
	}

	/**
	 * Load frontend functionality.
	 *
	 * @since 1.0.0
	 */
	private function load_frontend() {

		if ( ! is_admin() ) {
			( new Frontend() )->init();
		}
	}

	/**
	 * Load Authorize.Net API.
	 *
	 * @since 1.0.0
	 */
	private function load_api() {

		if ( ! is_admin() || wpforms_is_frontend_ajax() || wpforms_is_admin_page( 'settings', 'payments' ) ) {
			$this->api = ( new Api() )->init();
		}
	}

	/**
	 * Load payment form processing.
	 *
	 * @since 1.0.0
	 */
	private function load_processing() {

		if ( ! is_admin() || wpforms_is_frontend_ajax() ) {
			( new Process() )->init();
		}
	}

	/**
	 * Initialize class for extending the Payments functionality.
	 *
	 * @since 1.6.0
	 */
	private function load_admin_payments() {

		if ( ! wpforms_is_admin_page( 'payments' ) ) {
			return;
		}

		( new Admin\Payments() )->hooks();
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 * @deprecated 1.9.0
	 *
	 * @todo Remove with core 1.9.2
	 *
	 * @param string $key License key.
	 */
	public function updater( $key ) {

		_deprecated_function( __METHOD__, '1.9.0 of the WPForms Authorize.Net plugin' );

		new WPForms_Updater(
			[
				'plugin_name' => 'WPForms Authorize.Net',
				'plugin_slug' => 'wpforms-authorize-net',
				'plugin_path' => plugin_basename( WPFORMS_AUTHORIZE_NET_FILE ),
				'plugin_url'  => trailingslashit( WPFORMS_AUTHORIZE_NET_URL ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_AUTHORIZE_NET_VERSION,
				'key'         => $key,
			]
		);
	}
}
