<?php

namespace WPFormsAuthorizeNet;

/**
 * Authorize.Net related helper methods.
 *
 * @since 1.0.0
 */
class Helpers {

	/**
	 * Check if Authorize.Net is enabled for the form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form data and settings.
	 *
	 * @return bool
	 */
	public static function is_authorize_net_enabled( $form_data ) {

		return ! empty( $form_data['payments']['authorize_net']['enable'] );
	}

	/**
	 * Check if Authorize.Net is in use on the page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms Forms data (e.g. forms on a current page).
	 *
	 * @return bool
	 */
	public static function has_authorize_net_enabled( $forms ) {

		foreach ( $forms as $form_data ) {
			if ( self::is_authorize_net_enabled( $form_data ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if Authorize.Net field is in the form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms    Form data (e.g. forms on a current page).
	 * @param bool  $multiple Must be 'true' if $forms contain multiple forms.
	 *
	 * @return bool
	 */
	public static function has_authorize_net_field( $forms, $multiple = false ) {

		return wpforms_has_field_type( 'authorize_net', $forms, $multiple );
	}

	/**
	 * Validate Authorize.Net mode name to ensure it's either 'live' or 'test'.
	 * If given mode is invalid, fetches current Authorize.Net mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode to validate.
	 *
	 * @return string
	 */
	public static function validate_authorize_net_mode( $mode ) {

		if ( empty( $mode ) || ! in_array( $mode, [ 'live', 'test' ], true ) ) {
			$mode = self::get_authorize_net_mode();
		}

		return $mode;
	}

	/**
	 * Get Authorize.Net mode from WPForms settings.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_authorize_net_mode() {

		return wpforms_setting( 'authorize_net-test-mode' ) ? 'test' : 'live';
	}

	/**
	 * Check if Authorize.Net is in test mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return bool
	 */
	public static function is_test_mode( $mode = '' ) {

		$mode = self::validate_authorize_net_mode( $mode );

		return $mode === 'test';
	}

	/**
	 * Check if Authorize.Net is in live mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return bool
	 */
	public static function is_live_mode( $mode = '' ) {

		$mode = self::validate_authorize_net_mode( $mode );

		return $mode === 'live';
	}

	/**
	 * Get Authorize.Net API login ID from WPForms settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	public static function get_login_id( $mode = '' ) {

		$mode = self::validate_authorize_net_mode( $mode );

		return sanitize_text_field( wpforms_setting( "authorize_net-{$mode}-api-login-id" ) );
	}

	/**
	 * Get Authorize.Net transaction key from WPForms settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	public static function get_transaction_key( $mode = '' ) {

		$mode = self::validate_authorize_net_mode( $mode );

		$key = wpforms_setting( "authorize_net-{$mode}-transaction-key" );

		return ( ! empty( $key ) && is_string( $key ) ) ? sanitize_text_field( $key ) : '';
	}

	/**
	 * Check if Authorize.Net keys have been configured in the plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode to check the keys for.
	 *
	 * @return bool
	 */
	public static function has_authorize_net_keys( $mode = '' ) {

		return (bool) self::get_login_id( $mode ) && (bool) self::get_transaction_key( $mode );
	}

	/**
	 * Get data for every subscription period.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_subscription_period_data() {

		return [
			'weekly'     => [
				'name'  => 'weekly',
				'unit'  => 'days',
				'count' => 7,
				'desc'  => esc_html__( 'Weekly', 'wpforms-authorize-net' ),
			],
			'monthly'    => [
				'name'  => 'monthly',
				'unit'  => 'months',
				'count' => 1,
				'desc'  => esc_html__( 'Monthly', 'wpforms-authorize-net' ),
			],
			'quarterly'  => [
				'name'  => 'quarterly',
				'unit'  => 'months',
				'count' => 3,
				'desc'  => esc_html__( 'Quarterly', 'wpforms-authorize-net' ),
			],
			'semiyearly' => [
				'name'  => 'semiyearly',
				'unit'  => 'months',
				'count' => 6,
				'desc'  => esc_html__( 'Semi-Yearly', 'wpforms-authorize-net' ),
			],
			'yearly'     => [
				'name'  => 'yearly',
				'unit'  => 'months',
				'count' => 12,
				'desc'  => esc_html__( 'Yearly', 'wpforms-authorize-net' ),
			],
		];
	}
}
