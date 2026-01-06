<?php

namespace WPFormsAuthorizeNet\Api;

use net\authorize\api\contract\v1 as AnetAPI;

/**
 * Authorize.Net API response manipulations.
 *
 * @since 1.0.0
 */
class Response {

	/**
	 * Transaction API response.
	 *
	 * @since 1.0.0
	 *
	 * @var AnetAPI\CreateTransactionResponse
	 */
	private $transaction_response;

	/**
	 * Transaction part of the transaction API response.
	 *
	 * @since 1.0.0
	 *
	 * @var AnetAPI\TransactionResponseType
	 */
	private $transaction_part;

	/**
	 * Customer profile part of the transaction API response.
	 *
	 * @since 1.0.0
	 *
	 * @var AnetAPI\CreateProfileResponseType
	 */
	private $transaction_profile_part;

	/**
	 * Subscription API response.
	 *
	 * @since 1.0.0
	 *
	 * @var AnetAPI\ARBCreateSubscriptionResponse
	 */
	private $subscription_response;

	/**
	 * Customer profile part of the subscription API response.
	 *
	 * @since 1.0.0
	 *
	 * @var AnetAPI\CustomerProfileIdType
	 */
	private $subscription_profile_part;

	/**
	 * Set transaction response and its parts.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\CreateTransactionResponse $response Transaction API response.
	 */
	public function set_transaction_response( $response ) {

		if ( ! ( $response instanceof AnetAPI\CreateTransactionResponse ) ) {
			return;
		}

		// Set transaction response.
		$this->transaction_response = $response;

		// Set transaction part if available.
		if ( is_callable( [ $response, 'getTransactionResponse' ] ) ) {
			$this->transaction_part = $response->getTransactionResponse();
		}

		// Set transaction profile part if available.
		if ( is_callable( [ $response, 'getProfileResponse' ] ) ) {
			$this->transaction_profile_part = $response->getProfileResponse();
		}
	}

	/**
	 * Set subscription response and its parts.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\ARBCreateSubscriptionResponse $response Subscription API response.
	 */
	public function set_subscription_response( $response ) {

		if ( ! ( $response instanceof AnetAPI\ARBCreateSubscriptionResponse ) ) {
			return;
		}

		// Set subscription response.
		$this->subscription_response = $response;

		// Set subscription profile part if available.
		if ( is_callable( [ $response, 'getProfile' ] ) ) {
			$this->subscription_profile_part = $response->getProfile();
		}
	}

	/**
	 * Get transaction response object.
	 *
	 * @since 1.0.0
	 *
	 * @return AnetAPI\CreateTransactionResponse
	 */
	public function get_transaction_response() {

		return $this->transaction_response;
	}

	/**
	 * Get transaction part object.
	 *
	 * @since 1.0.0
	 *
	 * @return AnetAPI\TransactionResponseType
	 */
	public function get_transaction_part() {

		return $this->transaction_part;
	}

	/**
	 * Get transaction customer profile part object.
	 *
	 * @since 1.0.0
	 *
	 * @return AnetAPI\CreateProfileResponseType
	 */
	public function get_transaction_profile_part() {

		return $this->transaction_profile_part;
	}

	/**
	 * Get subscription response object.
	 *
	 * @since 1.0.0
	 *
	 * @return AnetAPI\ARBCreateSubscriptionResponse
	 */
	public function get_subscription_response() {

		return $this->subscription_response;
	}

	/**
	 * Get subscription customer profile part object.
	 *
	 * @since 1.0.0
	 *
	 * @return AnetAPI\CustomerProfileIdType
	 */
	public function get_subscription_profile_part() {

		return $this->subscription_profile_part;
	}

	/**
	 * Get transaction id.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_transaction_id() {

		if ( is_callable( [ $this->get_transaction_part(), 'getTransId' ] ) ) {
			return $this->get_transaction_part()->getTransId();
		}

		return '';
	}

	/**
	 * Get transaction brand of the card (e.g. "Visa" or "MasterCard").
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_transaction_card_brand() {

		if ( is_callable( [ $this->get_transaction_part(), 'getAccountType' ] ) ) {
			return $this->get_transaction_part()->getAccountType();
		}

		return '';
	}

	/**
	 * Get transaction last 4 digits of the card number (e.g. "XXXX1234").
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_transaction_card_last4() {

		if ( is_callable( [ $this->get_transaction_part(), 'getAccountNumber' ] ) ) {
			return $this->get_transaction_part()->getAccountNumber();
		}

		return '';
	}

	/**
	 * Get transaction customer profile id.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_transaction_profile_id() {

		if ( is_callable( [ $this->get_transaction_profile_part(), 'getCustomerProfileId' ] ) ) {
			return $this->get_transaction_profile_part()->getCustomerProfileId();
		}

		return '';
	}

	/**
	 * Get transaction customer payment profile id (tokenized card saved in Authorize.Net).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_transaction_profile_payment_id() {

		if ( is_callable( [ $this->get_transaction_profile_part(), 'getCustomerPaymentProfileIdList' ] ) ) {
			$payment_ids = $this->get_transaction_profile_part()->getCustomerPaymentProfileIdList();

			return is_array( $payment_ids ) && isset( $payment_ids[0] ) ? $payment_ids[0] : '';
		}

		return '';
	}

	/**
	 * Get subscription id.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_subscription_id() {

		if ( is_callable( [ $this->get_subscription_response(), 'getSubscriptionId' ] ) ) {
			return $this->get_subscription_response()->getSubscriptionId();
		}

		return '';
	}

	/**
	 * Get subscription customer profile id.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_subscription_profile_id() {

		if ( is_callable( [ $this->get_subscription_profile_part(), 'getCustomerProfileId' ] ) ) {
			return $this->get_subscription_profile_part()->getCustomerProfileId();
		}

		return '';
	}

	/**
	 * Check if transaction part is in test mode.
	 * Test mode applies to both sandbox and live environments.
	 * It means that no transaction is recorded and no transaction id is returned.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_transaction_part_in_test_mode() {

		if ( ! is_callable( [ $this->get_transaction_part(), 'getTestRequest' ] ) ) {
			return false;
		}

		return $this->get_transaction_part()->getTestRequest() === '1';
	}

	/**
	 * Check if response result code is ok.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\ANetApiResponseType|AnetAPI\CreateProfileResponseType $response API response or it's part.
	 *
	 * @return bool
	 */
	public function is_result_code_ok( $response ) {

		if ( ! is_callable( [ $response, 'getMessages' ] ) ) {
			return false;
		}

		$messages_type = $response->getMessages();

		if ( ! is_callable( [ $messages_type, 'getResultCode' ] ) ) {
			return false;
		}

		return $messages_type->getResultCode() === 'Ok';
	}

	/**
	 * Check if transaction response is ok.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_transaction_response_ok() {

		return $this->is_result_code_ok( $this->get_transaction_response() );
	}

	/**
	 * Check if transaction part of the transaction response is ok.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_transaction_part_ok() {

		if ( ! is_callable( [ $this->get_transaction_part(), 'getMessages' ] ) ) {
			return false;
		}

		$messages = $this->get_transaction_part()->getMessages();

		// Checking for messages (not errors or result code) is recommended by Authorize.Net (see "Charge a Credit Card" PHP section).
		// https://developer.authorize.net/api/reference/index.html#payment-transactions-charge-a-credit-card.
		return $messages !== null;
	}

	/**
	 * Check if customer profile part of the transaction response is ok.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_transaction_profile_part_ok() {

		return $this->is_result_code_ok( $this->get_transaction_profile_part() );
	}

	/**
	 * Check if subscription response is ok.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_subscription_response_ok() {

		return $this->is_result_code_ok( $this->get_subscription_response() );
	}

	/**
	 * Check if the whole transaction is ok.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_whole_transaction_ok() {

		if ( $this->is_transaction_part_in_test_mode() ) {
			return false;
		}

		if ( ! $this->is_transaction_response_ok() ) {
			return false;
		}

		if ( ! $this->is_transaction_part_ok() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the whole transaction settlement is ok.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_whole_subscription_settlement_ok() {

		if ( $this->is_transaction_part_in_test_mode() ) {
			return false;
		}

		if ( ! $this->is_transaction_response_ok() ) {
			return false;
		}

		if ( ! $this->is_transaction_part_ok() ) {
			return false;
		}

		if ( ! $this->is_transaction_profile_part_ok() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the whole subscription is ok.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_whole_subscription_ok() {

		return $this->is_subscription_response_ok();
	}

	/**
	 * Check if an API response contains a specific error code.
	 *
	 * @since 1.0.0
	 *
	 * @param string                      $code     Error code to look for.
	 * @param AnetAPI\AnetApiResponseType $response API response.
	 *
	 * @return bool
	 */
	public function is_error_code_in_response( $code, $response ) {

		$errors = $this->extract_message_errors( $response );

		if ( ! is_array( $errors ) ) {
			return false;
		}

		return array_key_exists( $code, $errors );
	}

	/**
	 * Extract errors contained in 'message' object.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\ANetApiResponseType|AnetAPI\CreateProfileResponseType $response API response or it's part.
	 *
	 * @return array
	 */
	private function extract_message_errors( $response ) {

		$errors = [];

		if ( ! is_callable( [ $response, 'getMessages' ] ) ) {
			return $errors;
		}

		$messages_type = $response->getMessages();

		if ( ! is_callable( [ $messages_type, 'getMessage' ] ) ) {
			return $errors;
		}

		$messages = $messages_type->getMessage();

		if ( ! is_array( $messages ) ) {
			return $errors;
		}

		foreach ( $messages as $message ) {
			if ( is_callable( [ $message, 'getCode' ] ) && is_callable( [ $message, 'getText' ] ) ) {
				$errors[ $message->getCode() ] = $message->getText();
			}
		}

		return $errors;
	}

	/**
	 * Extract API errors from transaction response.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function extract_errors_from_transaction_response() {

		return $this->extract_message_errors( $this->get_transaction_response() );
	}

	/**
	 * Get an array of error objects from transaction part of the transaction response.
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	private function get_errors_from_transaction_part() {

		if ( ! is_callable( [ $this->get_transaction_part(), 'getErrors' ] ) ) {
			return [];
		}

		$errors = $this->get_transaction_part()->getErrors();

		return is_array( $errors ) ? $errors : [];
	}

	/**
	 * Extract errors from transaction part of the transaction response.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function extract_errors_from_transaction_part() {

		$result = [];
		$errors = $this->get_errors_from_transaction_part();

		foreach ( $errors as $error ) {
			if ( is_callable( [ $error, 'getErrorCode' ] ) && is_callable( [ $error, 'getErrorText' ] ) ) {
				$result[ $error->getErrorCode() ] = $error->getErrorText();
			}
		}

		return $result;
	}

	/**
	 * Extract raw errors from transaction part of the transaction response.
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function extract_errors_from_transaction_part_raw() {

		$result = [];
		$errors = $this->get_errors_from_transaction_part();

		foreach ( $errors as $error ) {
			if ( is_callable( [ $error, 'jsonSerialize' ] ) ) {
				$result[] = $error->jsonSerialize();
			}
		}

		return $result;
	}

	/**
	 * Extract errors from customer profile part of the transaction response.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function extract_errors_from_transaction_profile_part() {

		return $this->extract_message_errors( $this->get_transaction_profile_part() );
	}

	/**
	 * Extract errors from subscription response.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function extract_errors_from_subscription_response() {

		return $this->extract_message_errors( $this->get_subscription_response() );
	}
}
