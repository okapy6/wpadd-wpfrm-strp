<?php

namespace WPFormsAuthorizeNet\Admin;

use WPFormsAuthorizeNet\Helpers;

/**
 * Authorize.Net admin entries.
 *
 * @since 1.0.0
 */
class Entries {

	/**
	 * Init the class.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();

		return $this;
	}

	/**
	 * Entries hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {

		add_filter( 'wpforms_has_payment_gateway', [ $this, 'has_payment_gateway' ], 10, 2 );
	}

	/**
	 * Make Authorize.Net payment gateway work on the admin entries page.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $result    Initial value.
	 * @param array $form_data Form data and settings.
	 *
	 * @return bool
	 */
	public function has_payment_gateway( $result, $form_data ) {

		if ( ! empty( $form_data['payments']['authorize_net']['enable'] ) ) {
			return true;
		}

		return $result;
	}

	/**
	 * Change gateway name in Entry Payment Details metabox.
	 *
	 * @since 1.0.0
	 * @deprecated 1.6.0
	 *
	 * @param string $gateway    Initial gateway name.
	 * @param array  $entry_meta Entry meta data.
	 *
	 * @return string
	 */
	public function entry_details_payment_gateway( $gateway, $entry_meta ) {

		_deprecated_function( __METHOD__, '1.6.0 of the Authorize.Net addon.' );

		if ( ! $this->is_authorize_net_entry_meta_payment_type( $entry_meta ) ) {
			return $gateway;
		}

		return sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( Helpers::is_test_mode() ? 'https://sandbox.authorize.net' : 'https://account.authorize.net' ),
			esc_html__( 'Authorize.Net', 'wpforms-authorize-net' )
		);
	}

	/**
	 * Change transaction ID in Entry Payment Details metabox.
	 *
	 * @since 1.0.0
	 * @deprecated 1.6.0
	 *
	 * @param string $transaction Initial transaction ID.
	 * @param array  $entry_meta  Entry meta data.
	 *
	 * @return string
	 */
	public function entry_details_payment_transaction( $transaction, $entry_meta ) {

		_deprecated_function( __METHOD__, '1.6.0 of the Authorize.Net addon.' );

		if ( ! $this->is_authorize_net_entry_meta_payment_type( $entry_meta ) ) {
			return $transaction;
		}

		if ( ! empty( $entry_meta['payment_transaction'] ) ) {
			$transaction = esc_html( $entry_meta['payment_transaction'] );
		}

		return $transaction;
	}

	/**
	 * Change subscription ID in Entry Payment Details metabox.
	 *
	 * @since 1.0.0
	 * @deprecated 1.6.0
	 *
	 * @param string $subscription Initial subscription ID.
	 * @param array  $entry_meta   Entry meta data.
	 *
	 * @return string
	 */
	public function entry_details_payment_subscription( $subscription, $entry_meta ) {

		_deprecated_function( __METHOD__, '1.6.0 of the Authorize.Net addon.' );

		if ( ! $this->is_authorize_net_entry_meta_payment_type( $entry_meta ) ) {
			return $subscription;
		}

		if ( ! empty( $entry_meta['payment_subscription'] ) ) {
			$subscription = esc_html( $entry_meta['payment_subscription'] );
		}

		return $subscription;
	}

	/**
	 * Change customer ID in Entry Payment Details metabox.
	 *
	 * @since 1.0.0
	 * @deprecated 1.6.0
	 *
	 * @param string $customer   Initial customer ID.
	 * @param array  $entry_meta Entry meta data.
	 *
	 * @return string
	 */
	public function entry_details_payment_customer( $customer, $entry_meta ) {

		_deprecated_function( __METHOD__, '1.6.0 of the Authorize.Net addon.' );

		if ( ! $this->is_authorize_net_entry_meta_payment_type( $entry_meta ) ) {
			return $customer;
		}

		if ( ! empty( $entry_meta['payment_customer'] ) ) {
			$customer = esc_html( $entry_meta['payment_customer'] );
		}

		return $customer;
	}

	/**
	 * Change transaction total in Entry Payment Details metabox.
	 *
	 * @since 1.0.0
	 * @deprecated 1.6.0
	 *
	 * @param string $total      Initial transaction total.
	 * @param array  $entry_meta Entry meta data.
	 *
	 * @return string
	 */
	public function entry_details_payment_total( $total, $entry_meta ) {

		_deprecated_function( __METHOD__, '1.6.0 of the Authorize.Net addon.' );

		if ( ! $this->is_authorize_net_entry_meta_payment_type( $entry_meta ) ) {
			return $total;
		}

		if ( ! empty( $entry_meta['payment_period'] ) ) {
			$total .= ' <span style="font-weight:400; color:#999; display:inline-block;margin-left:4px;"><i class="fa fa-refresh" aria-hidden="true"></i> ' . $entry_meta['payment_period'] . '</span>';
		}

		return $total;
	}

	/**
	 * Check if Authorize.Net is set as a payment type inside entry meta.
	 *
	 * @since 1.0.0
	 *
	 * @param array $entry_meta Entry meta data.
	 *
	 * @return bool
	 */
	private function is_authorize_net_entry_meta_payment_type( $entry_meta ) {

		if ( empty( $entry_meta['payment_type'] ) ) {
			return false;
		}

		if ( $entry_meta['payment_type'] !== 'authorize_net' ) {
			return false;
		}

		return true;
	}
}
