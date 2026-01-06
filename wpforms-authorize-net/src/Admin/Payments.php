<?php

namespace WPFormsAuthorizeNet\Admin;

/**
 * Extending Payments class for Authorize.Net.
 *
 * @since 1.6.0
 */
class Payments {

	/**
	 * Authorize.Net URL.
	 * Test (sandbox) mode.
	 *
	 * @since 1.6.0
	 */
	const TEST_ENV_URL = 'https://sandbox.authorize.net/';

	/**
	 * Authorize.Net URL.
	 * Live (production) mode.
	 *
	 * @since 1.6.0
	 */
	const LIVE_ENV_URL = 'https://account.authorize.net/';

	/**
	 * Register hooks.
	 *
	 * @since 1.6.0
	 */
	public function hooks() {

		add_filter( 'wpforms_admin_payments_views_single_gateway_dashboard_link', [ $this, 'gateway_dashboard_link' ], 10, 2 );
		add_filter( 'wpforms_admin_payments_views_single_gateway_transaction_link', [ $this, 'gateway_transaction_link' ], 10, 2 );
		add_filter( 'wpforms_admin_payments_views_single_gateway_subscription_link', [ $this, 'gateway_subscription_link' ], 10, 2 );
		add_filter( 'wpforms_admin_payments_views_single_gateway_customer_link', [ $this, 'gateway_customer_link' ], 10, 2 );
		add_filter( 'wpforms_admin_payments_views_single_gateway_action_link', [ $this, 'gateway_action_link' ], 10, 3 );
	}

	/**
	 * Return gateway dashboard link.
	 *
	 * @since 1.6.0
	 *
	 * @param string $link    Dashboard link.
	 * @param object $payment Payment object.
	 *
	 * @return string
	 */
	public function gateway_dashboard_link( $link, $payment ) {

		// Return the original link if the payment is not for this gateway.
		if ( ! $this->is_this_gateway( $payment ) ) {
			return $link;
		}

		return $this->get_env_url( $payment );
	}

	/**
	 * Return gateway transaction link.
	 *
	 * @since 1.6.0
	 *
	 * @param string $link    Dashboard link.
	 * @param object $payment Payment object.
	 *
	 * @return string
	 */
	public function gateway_transaction_link( $link, $payment ) {

		// Return the original link if the payment is not for this gateway.
		if ( ! $this->is_this_gateway( $payment ) ) {
			return $link;
		}

		return $this->gateway_dashboard_link( $link, $payment );
	}

	/**
	 * Return gateway subscription link.
	 *
	 * @since 1.6.0
	 *
	 * @param string $link    Dashboard link.
	 * @param object $payment Payment object.
	 *
	 * @return string
	 */
	public function gateway_subscription_link( $link, $payment ) {

		// Return the original link if the payment is not for this gateway.
		if ( ! $this->is_this_gateway( $payment ) ) {
			return $link;
		}

		return $this->gateway_dashboard_link( $link, $payment );
	}

	/**
	 * Return the gateway customer link.
	 *
	 * @since 1.6.0
	 *
	 * @param string $link    Dashboard link.
	 * @param object $payment Payment object.
	 *
	 * @return string
	 */
	public function gateway_customer_link( $link, $payment ) {

		// Return the original link if the payment is not for this gateway.
		if ( ! $this->is_this_gateway( $payment ) ) {
			return $link;
		}

		return $this->gateway_dashboard_link( $link, $payment );
	}

	/**
	 * Return the gateway action link.
	 *
	 * @since 1.6.0
	 *
	 * @param string $link    Dashboard link.
	 * @param string $action  Action.
	 * @param object $payment Payment object.
	 *
	 * @return string
	 */
	public function gateway_action_link( $link, $action, $payment ) {

		// Return the original link if the payment is not for this gateway.
		if ( ! $this->is_this_gateway( $payment ) ) {
			return $link;
		}

		return $this->gateway_dashboard_link( $link, $payment );
	}

	/**
	 * Return an environment URL according to a payment mode.
	 *
	 * @since 1.6.0
	 *
	 * @param object $payment Payment object.
	 *
	 * @return string
	 */
	private function get_env_url( $payment ) {

		return isset( $payment->mode ) && $payment->mode === 'test' ? self::TEST_ENV_URL : self::LIVE_ENV_URL;
	}

	/**
	 * Check if the payment is for this gateway.
	 *
	 * @since 1.6.0
	 *
	 * @param object $payment Payment object.
	 *
	 * @return bool
	 */
	private function is_this_gateway( $payment ) {

		return isset( $payment->gateway ) && $payment->gateway === 'authorize_net';
	}
}
