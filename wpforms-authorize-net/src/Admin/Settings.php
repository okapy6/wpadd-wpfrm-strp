<?php

namespace WPFormsAuthorizeNet\Admin;

use WPForms\Admin\Notice;
use WPFormsAuthorizeNet\Helpers;

/**
 * Authorize.Net addon settings.
 *
 * @since 1.0.0
 */
class Settings {

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
	 * Settings hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_action( 'wpforms_settings_enqueue', [ $this, 'enqueue_scripts' ] );
		add_action( 'wpforms_settings_updated', [ $this, 'settings_updated' ] );
		add_filter( 'wpforms_settings_defaults', [ $this, 'register' ], 15 );
		add_filter( 'wpforms_admin_strings', [ $this, 'javascript_strings' ] );
		add_action( 'wpforms_settings_init', [ $this, 'display_notice' ] );
	}

	/**
	 * Enqueue Settings scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-admin-settings-authorize-net',
			WPFORMS_AUTHORIZE_NET_URL . "assets/js/admin/settings-authorize-net{$min}.js",
			[ 'jquery' ],
			WPFORMS_AUTHORIZE_NET_VERSION,
			true
		);
	}

	/**
	 * Register Settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Array of current form settings.
	 *
	 * @return array
	 */
	public function register( $settings ) {

		$settings['payments']['authorize_net-heading'] = [
			'id'       => 'authorize_net-heading',
			'content'  => $this->get_heading_content(),
			'type'     => 'content',
			'no_label' => true,
			'class'    => [ 'section-heading' ],
		];

		$settings['payments']['authorize_net-connection-status-test'] = [
			'id'        => 'authorize_net-connection-status-test',
			'name'      => esc_html__( 'Connection Status', 'wpforms-authorize-net' ),
			'content'   => $this->get_connection_status_content( 'test' ),
			'type'      => 'content',
			'is_hidden' => Helpers::is_live_mode(),
		];

		$settings['payments']['authorize_net-connection-status-live'] = [
			'id'        => 'authorize_net-connection-status-live',
			'name'      => esc_html__( 'Connection Status', 'wpforms-authorize-net' ),
			'content'   => $this->get_connection_status_content( 'live' ),
			'type'      => 'content',
			'is_hidden' => Helpers::is_test_mode(),
		];

		$settings['payments']['authorize_net-test-mode'] = [
			'id'     => 'authorize_net-test-mode',
			'name'   => esc_html__( 'Test Mode', 'wpforms-authorize-net' ),
			'desc'   => esc_html__( 'Prevent Authorize.Net from processing live transactions.', 'wpforms-authorize-net' ),
			'type'   => 'toggle',
			'status' => true,
		];

		$settings['payments']['authorize_net-test-api-login-id'] = [
			'id'        => 'authorize_net-test-api-login-id',
			'name'      => esc_html__( 'Test API Login ID', 'wpforms-authorize-net' ),
			'type'      => 'text',
			'is_hidden' => Helpers::is_live_mode(),
		];

		$settings['payments']['authorize_net-test-transaction-key'] = [
			'id'        => 'authorize_net-test-transaction-key',
			'name'      => esc_html__( 'Test Transaction Key', 'wpforms-authorize-net' ),
			'type'      => 'text',
			'is_hidden' => Helpers::is_live_mode(),
		];

		$settings['payments']['authorize_net-live-api-login-id'] = [
			'id'        => 'authorize_net-live-api-login-id',
			'name'      => esc_html__( 'API Login ID', 'wpforms-authorize-net' ),
			'type'      => 'text',
			'is_hidden' => Helpers::is_test_mode(),
		];

		$settings['payments']['authorize_net-live-transaction-key'] = [
			'id'        => 'authorize_net-live-transaction-key',
			'name'      => esc_html__( 'Transaction Key', 'wpforms-authorize-net' ),
			'type'      => 'text',
			'is_hidden' => Helpers::is_test_mode(),
		];

		return $settings;
	}

	/**
	 * Section header content.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_heading_content() {

		$output =
			'<h4>' . esc_html__( 'Authorize.Net', 'wpforms-authorize-net' ) . '</h4>' .
			'<p>' .
			sprintf(
				wp_kses(
					/* translators: %s - WPForms.com Authorize.Net documentation article URL. */
					__( 'Easily collect credit card payments with Authorize.Net. For getting started and more information, see our <a href="%s" target="_blank" rel="noopener noreferrer">Authorize.Net documentation</a>.', 'wpforms-authorize-net' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				esc_url( wpforms_utm_link( 'https://wpforms.com/docs/how-to-install-and-use-the-authorize-net-addon-with-wpforms/', 'Settings - Payments', 'Authorize.net Documentation' ) )
			) .
			'</p>';

		return $output;
	}

	/**
	 * Section connection status content.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	public function get_connection_status_content( $mode = '' ) {

		if ( wpforms_authorize_net()->api->get_public_client_key( $mode ) ) {
			return $this->get_connected_status_content( $mode );
		}

		return $this->get_disconnected_status_content( $mode );
	}

	/**
	 * Section connection status connected content.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	private function get_connected_status_content( $mode = '' ) {

		$mode = Helpers::validate_authorize_net_mode( $mode );

		$merchant_is_test_mode = wpforms_authorize_net()->api->get_merchant_is_test_mode( $mode );

		$output =
			'<span class="wpforms-success-icon"></span>' .
			sprintf(
				wp_kses(
					/* translators: %1$s - Authorize.Net account name connected; %2$s - Authorize.Net mode connected (live or sandbox); %3$s - indication whether it's a Test or Live mode. */
					__( 'Connected to Authorize.Net as <em>%1$s</em> in <strong>%2$s %3$s</strong> mode.', 'wpforms-authorize-net' ),
					[
						'strong' => [],
						'em'     => [],
					]
				),
				esc_html( wpforms_authorize_net()->api->get_merchant_name( $mode ) ),
				$mode === 'test' ? esc_html__( 'Sandbox', 'wpforms-authorize-net' ) : esc_html__( 'Production', 'wpforms-authorize-net' ),
				$merchant_is_test_mode ? esc_html__( 'Test', 'wpforms-authorize-net' ) : esc_html__( 'Live', 'wpforms-authorize-net' )
			);

		// In case Sandbox account is set into Test mode, it won't work properly with WPForms.
		if ( Helpers::is_test_mode( $mode ) && $merchant_is_test_mode ) {
			$output .= '<p class="desc">' . $this->get_connected_status_sandbox_test_message() . '</p>';
		}

		return $output;
	}

	/**
	 * Section connection status sandbox test message.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	private function get_connected_status_sandbox_test_message() {

		return sprintf(
			wp_kses(
				/* translators: %s - Authorize.Net Sandbox Dashboard URL. */
				__( 'Warning! Please set your <a href="%s" target="_blank" rel="noopener noreferrer">Sandbox account</a> into <strong>Live</strong> mode to work properly with WPForms.', 'wpforms-authorize-net' ),
				[
					'strong' => [],
					'a'      => [
						'href'   => [],
						'target' => [],
						'rel'    => [],
					],
				]
			),
			'https://sandbox.authorize.net'
		);
	}

	/**
	 * Section connection status disconnected content.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	private function get_disconnected_status_content( $mode = '' ) {

		$mode = Helpers::validate_authorize_net_mode( $mode );

		return '<span class="wpforms-error-icon"></span>' .
			sprintf(
				wp_kses(
				/* translators: %s - Authorize.Net mode connected (live or sandbox). */
					__( 'Not connected to Authorize.Net in <strong>%s</strong> mode.', 'wpforms-authorize-net' ),
					[
						'strong' => [],
					]
				),
				$mode === 'test' ? esc_html__( 'Sandbox', 'wpforms-authorize-net' ) : esc_html__( 'Production', 'wpforms-authorize-net' )
			);
	}

	/**
	 * Localize needed strings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $strings JS strings.
	 *
	 * @return array
	 */
	public function javascript_strings( $strings ) {

		$strings['authorize_net_mode_update'] = esc_html__( 'Switching test/live modes requires filling API Login Key and Transaction Key fields.', 'wpforms-authorize-net' );

		return $strings;
	}

	/**
	 * Check client key on settings save.
	 *
	 * @since 1.1.0
	 */
	public function settings_updated() {

		if ( wpforms_authorize_net()->api->get_public_client_key() ) {
			return;
		}

		if ( Helpers::is_live_mode() ) {
			$message = esc_html__( 'You entered invalid Authorize.Net production credentials and won\'t be able to receive live payments.', 'wpforms-authorize-net' );
		} else {
			$message = esc_html__( 'You entered invalid Authorize.Net sandbox credentials and won\'t be able to test the payments.', 'wpforms-authorize-net' );
		}

		Notice::error( $message );
	}

	/**
	 * Display admin error notice if something wrong with the A.Net settings.
	 *
	 * @since 1.9.0
	 *
	 * @param object $settings WPForms_Settings instance.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function display_notice( $settings ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		if ( $this->is_currency_supported() ) {
			return;
		}

		Notice::error(
			sprintf(
				wp_kses( /* translators: %1$s - Selected currency on the WPForms Settings admin page. */
					__( '<strong>Payments Cannot Be Processed</strong><br>The currency you have set (%1$s) is not supported by Authorize.Net. Please choose a different currency, or consider switching your payment gateway to Stripe.', 'wpforms-authorize-net' ),
					[
						'strong' => [],
						'br'     => [],
					]
				),
				esc_html( wpforms_get_currency() )
			)
		);
	}

	/**
	 * Determine if selected currency is supported.
	 *
	 * @since 1.9.0
	 *
	 * @link https://support.authorize.net/knowledgebase/Knowledgearticle/?code=000001351
	 *
	 * @return bool
	 */
	private function is_currency_supported(): bool {

		return in_array( wpforms_get_currency(), [ 'AUD', 'CAD', 'DKK', 'EUR', 'JPY', 'NZD', 'NOK', 'GBP', 'SEK', 'CHF', 'ZAR', 'USD' ], true );
	}
}
