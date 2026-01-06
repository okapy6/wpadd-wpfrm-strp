<?php

namespace WPFormsAuthorizeNet;

/**
 * Authorize.Net form frontend related functionality.
 *
 * @since 1.0.0
 */
class Frontend {

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();

		return $this;
	}

	/**
	 * Frontend hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {

		add_action( 'wpforms_frontend_container_class', [ $this, 'form_container_class' ], 10, 2 );
		add_action( 'wpforms_wp_footer', [ $this, 'enqueues' ] );
	}

	/**
	 * Add class to form container if Authorize.Net is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param array $class     Array of form classes.
	 * @param array $form_data Form data of current form.
	 *
	 * @return array
	 */
	public function form_container_class( $class, $form_data ) {

		if ( ! Helpers::has_authorize_net_field( $form_data ) ) {
			return $class;
		}

		if ( ! Helpers::has_authorize_net_keys() ) {
			return $class;
		}

		if ( Helpers::is_authorize_net_enabled( $form_data ) ) {
			$class[] = 'wpforms-authorize_net';
		}

		return $class;
	}

	/**
	 * Enqueue assets in the frontend if Authorize.Net is in use on the page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms Form data of forms on current page.
	 */
	public function enqueues( $forms ) {

		if ( ! $this->allow_loading_assets( $forms ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-authorize-net',
			WPFORMS_AUTHORIZE_NET_URL . "assets/css/wpforms-authorize-net{$min}.css",
			[],
			WPFORMS_AUTHORIZE_NET_VERSION
		);

		// Load CC payment library - https://github.com/stripe/jquery.payment/.
		wp_enqueue_script(
			'wpforms-payment',
			WPFORMS_PLUGIN_URL . 'assets/pro/lib/jquery.payment.min.js',
			[ 'jquery' ],
			WPFORMS_VERSION,
			true
		);

		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script(
			'authorize-net-accept-js',
			Helpers::is_test_mode() ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js',
			[],
			null // "Null" version is important to avoid "E_WC_01: Please include Accept.js library from cdn." error.
		);
		// phpcs:enable WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_enqueue_script(
			'wpforms-authorize-net',
			WPFORMS_AUTHORIZE_NET_URL . "assets/js/wpforms-authorize-net{$min}.js",
			[ 'jquery', 'wpforms-payment', 'authorize-net-accept-js' ],
			WPFORMS_AUTHORIZE_NET_VERSION
		);

		wp_localize_script(
			'wpforms-authorize-net',
			'wpforms_authorize_net',
			[
				'api_login_id'      => Helpers::get_login_id(),
				'public_client_key' => wpforms_authorize_net()->api->get_public_client_key(),
			]
		);
	}

	/**
	 * Determine if assets should be loaded on the frontend.
	 *
	 * @since 1.7.0
	 *
	 * @param array $forms Form data of forms on current page.
	 */
	private function allow_loading_assets( $forms ) {

		if ( wpforms()->obj( 'frontend' )->assets_global() ) {
			return true;
		}

		if ( ! Helpers::has_authorize_net_field( $forms, true ) ) {
			return false;
		}

		if ( ! Helpers::has_authorize_net_enabled( $forms ) ) {
			return false;
		}

		return Helpers::has_authorize_net_keys();
	}
}
