<?php

namespace WPFormsAuthorizeNet\Api;

use net\authorize\api\constants\ANetEnvironment;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use WPFormsAuthorizeNet\Helpers;

/**
 * Authorize.Net API.
 *
 * @since 1.0.0
 */
class Api {

	/**
	 * Opaque data (card token) received from Accept.js.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $opaque_data;

	/**
	 * API response handling.
	 *
	 * @since 1.0.0
	 *
	 * @var \WPFormsAuthorizeNet\Api\Response
	 */
	public $response;

	/**
	 * API errors.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $errors;

	/**
	 * Merchant details.
	 *
	 * @since 1.1.0
	 *
	 * @var array
	 */
	private $merchant_details;

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 *
	 * @return \WPFormsAuthorizeNet\Api\Api
	 */
	public function init() {

		$this->response = new Response();

		return $this;
	}

	/**
	 * Get API errors.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_errors() {

		return $this->errors;
	}

	/**
	 * Get API endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	public function get_api_endpoint( $mode = '' ) {

		return Helpers::is_test_mode( $mode ) ? ANetEnvironment::SANDBOX : ANetEnvironment::PRODUCTION;
	}

	/**
	 * Get merchant details.
	 *
	 * Fetches merchant details from API if `$this->merchant_details[ $mode ]` is empty.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return AnetAPI\GetMerchantDetailsResponse|null
	 */
	public function get_merchant_details( $mode = '' ) {

		if ( empty( $this->merchant_details[ $mode ] ) ) {
			$this->merchant_details[ $mode ] = $this->fetch_merchant_details( $mode );
		}

		return $this->merchant_details[ $mode ];
	}

	/**
	 * Get public client key to use with Accept.js.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	public function get_public_client_key( $mode = '' ) {

		$merchant_details = $this->get_merchant_details( $mode );

		if ( $merchant_details === null ) {
			return '';
		}

		return is_callable( [ $merchant_details, 'getPublicClientKey' ] ) ? $merchant_details->getPublicClientKey() : '';
	}

	/**
	 * Get merchant name.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return string
	 */
	public function get_merchant_name( $mode = '' ) {

		$merchant_details = $this->get_merchant_details( $mode );

		if ( $merchant_details === null ) {
			return '';
		}

		return is_callable( [ $merchant_details, 'getMerchantName' ] ) ? $merchant_details->getMerchantName() : '';
	}

	/**
	 * Fetch merchant account mode.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return bool|null
	 */
	public function get_merchant_is_test_mode( $mode = '' ) {

		$merchant_details = $this->get_merchant_details( $mode );

		if ( $merchant_details === null ) {
			return null;
		}

		return is_callable( [ $merchant_details, 'getIsTestMode' ] ) ? $merchant_details->getIsTestMode() : null;
	}

	/**
	 * Fetch public client key to use with Accept.js.
	 *
	 * @since 1.0.0
	 * @deprecated 1.1.0
	 *
	 * @return string
	 */
	public function fetch_public_client_key() {

		_deprecated_function( __METHOD__, '1.1.0 of the WPForms Authorize.Net addon' );

		return $this->get_public_client_key();
	}

	/**
	 * Fetch merchant details.
	 *
	 * @since 1.1.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return AnetAPI\GetMerchantDetailsResponse|null
	 */
	private function fetch_merchant_details( $mode = '' ) {

		if ( ! Helpers::has_authorize_net_keys( $mode ) ) {
			return null;
		}

		$request = new AnetAPI\GetMerchantDetailsRequest();

		$request->setMerchantAuthentication( $this->get_auth_object( $mode ) );

		$controller = new AnetController\GetMerchantDetailsController( $request );

		/* @var AnetAPI\GetMerchantDetailsResponse $response Enforce correct return type hinting. */
		$response = $controller->executeWithApiResponse( $this->get_api_endpoint( $mode ) );

		if ( ! $this->response->is_result_code_ok( $response ) ) {
			return null;
		}

		return $response;
	}

	/**
	 * Set opaque data (token) from a submitted form data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $entry Copy of original $_POST.
	 */
	public function set_opaque_data( $entry ) {

		if ( ! empty( $entry['authorize_net']['opaque_data']['descriptor'] ) ) {
			$this->opaque_data['descriptor'] = $entry['authorize_net']['opaque_data']['descriptor'];
		}

		if ( ! empty( $entry['authorize_net']['opaque_data']['value'] ) ) {
			$this->opaque_data['value'] = $entry['authorize_net']['opaque_data']['value'];
		}
	}

	/**
	 * Check if API keys are present.
	 *
	 * @since 1.0.0
	 */
	private function check_keys() {

		if ( ! Helpers::has_authorize_net_keys() ) {
			$this->errors[] = esc_html__( 'Missing Authorize.Net keys.', 'wpforms-authorize-net' );
		}
	}

	/**
	 * Check if opaque data (card token) is present.
	 *
	 * @since 1.0.0
	 */
	private function check_opaque_data() {

		if ( empty( $this->opaque_data['descriptor'] ) || empty( $this->opaque_data['value'] ) ) {
			$this->errors[] = esc_html__( 'Authorize.Net payment stopped, missing opaque data.', 'wpforms-authorize-net' );
		}
	}

	/**
	 * Check if all required single transaction arguments are present.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments to check.
	 */
	private function check_required_args_transaction( $args ) {

		if ( empty( $args['amount'] ) ) {
			$this->errors[] = esc_html__( 'Missing amount.', 'wpforms-authorize-net' );
		}

		if ( empty( $args['currency'] ) ) {
			$this->errors[] = esc_html__( 'Missing currency.', 'wpforms-authorize-net' );
		}
	}

	/**
	 * Check if all required subscription arguments are present.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments to check.
	 */
	private function check_required_args_subscription( $args ) {

		$this->check_required_args_subscription_settings( $args );
		$this->check_required_args_subscription_customer( $args );
		$this->check_required_args_subscription_descriptions( $args );
	}

	/**
	 * Check if all required subscription settings arguments are present.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments to check.
	 */
	private function check_required_args_subscription_settings( $args ) {

		if ( empty( $args['subscription']['name'] ) ) {
			$this->errors[] = esc_html__( 'Missing subscription name.', 'wpforms-authorize-net' );
		}

		if ( empty( $args['subscription']['period']['count'] ) || empty( $args['subscription']['period']['unit'] ) ) {
			$this->errors[] = esc_html__( 'Missing subscription period.', 'wpforms-authorize-net' );
		}

		if ( empty( $args['subscription']['start_date'] ) ) {
			$this->errors[] = esc_html__( 'Missing subscription start date.', 'wpforms-authorize-net' );
		}
	}

	/**
	 * Check if all required subscription customer arguments are present.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments to check.
	 */
	private function check_required_args_subscription_customer( $args ) {

		if ( empty( $args['subscription']['customer_name']['first'] ) ) {
			$this->errors[] = esc_html__( 'Missing customer first name.', 'wpforms-authorize-net' );
		}

		if ( empty( $args['subscription']['customer_name']['last'] ) ) {
			$this->errors[] = esc_html__( 'Missing customer last name.', 'wpforms-authorize-net' );
		}

		if ( empty( $args['subscription']['email'] ) ) {
			$this->errors[] = esc_html__( 'Missing customer email.', 'wpforms-authorize-net' );
		}
	}

	/**
	 * Check if all required subscription descriptions arguments are present.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments to check.
	 */
	private function check_required_args_subscription_descriptions( $args ) {

		if ( empty( $args['subscription']['payment_desc'] ) ) {
			$this->errors[] = esc_html__( 'Missing subscription payment description.', 'wpforms-authorize-net' );
		}

		if ( empty( $args['subscription']['settlement_desc'] ) ) {
			$this->errors[] = esc_html__( 'Missing subscription settlement payment description.', 'wpforms-authorize-net' );
		}
	}

	/**
	 * Process single transaction.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Single transaction arguments.
	 */
	public function process_transaction( $args ) {

		$this->check_keys();
		$this->check_opaque_data();
		$this->check_required_args_transaction( $args );

		if ( $this->errors ) {
			return;
		}

		$transaction = $this->prepare_transaction( $args );
		$transaction = apply_filters( 'wpforms_authorize_net_process_transaction', $transaction, $args, $this );
		$response    = $this->send_request_transaction( $transaction );

		$this->handle_response_transaction( $response );
	}

	/**
	 * Process subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Subscription arguments.
	 */
	public function process_subscription( $args ) {

		$this->check_keys();
		$this->check_opaque_data();
		$this->check_required_args_transaction( $args );
		$this->check_required_args_subscription( $args );

		if ( $this->errors ) {
			return;
		}

		$this->process_subscription_settlement( $args );

		if ( $this->errors ) {
			return;
		}

		$subscription = $this->prepare_subscription( $args );
		$subscription = apply_filters( 'wpforms_authorize_net_process_subscription', $subscription, $args, $this );

		$response = $this->send_request_subscription( $subscription );

		// Authorize.Net API might be slow to create a customer profile from a settlement transaction.
		// In this case "E00040 The record cannot be found." error appears.
		// We are detecting this error and giving API some time to create a profile before retrying the subscription.
		// Authorize.Net support says it only happens on a sandbox environment and shouldn't affect the live mode.
		if ( $this->response->is_error_code_in_response( 'E00040', $response ) ) {
			sleep( 15 );
			$response = $this->send_request_subscription( $subscription );
		}

		$this->handle_response_subscription( $response );
	}

	/**
	 * Process subscription settlement transaction.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Subscription settlement arguments.
	 */
	private function process_subscription_settlement( $args ) {

		$settlement = $this->prepare_subscription_settlement( $args );
		$settlement = apply_filters( 'wpforms_authorize_net_process_subscription_settlement', $settlement, $args, $this );
		$response   = $this->send_request_transaction( $settlement );

		$this->handle_response_subscription_settlement( $response );
	}

	/**
	 * Process transaction void.
	 *
	 * @since 1.0.0
	 */
	private function process_transaction_void() {

		$transaction_void = $this->prepare_transaction_void();

		// No response handling here, cleaning up quietly.
		$this->send_request_transaction( $transaction_void );
	}

	/**
	 * Process customer profile deletion.
	 *
	 * @since 1.0.0
	 */
	private function process_profile_delete() {

		// No response handling here, cleaning up quietly.
		$this->send_request_profile_delete();
	}

	/**
	 * Get merchant authorization object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Authorize.Net mode (e.g. 'live' or 'test').
	 *
	 * @return AnetAPI\MerchantAuthenticationType
	 */
	private function get_auth_object( $mode = '' ) {

		$auth = new AnetAPI\MerchantAuthenticationType();

		$auth->setName( Helpers::get_login_id( $mode ) );
		$auth->setTransactionKey( Helpers::get_transaction_key( $mode ) );

		return $auth;
	}

	/**
	 * Get single transaction object.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Single transaction arguments.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	private function get_transaction_request_object( $args ) {

		$opaque_data = new AnetAPI\OpaqueDataType();

		$opaque_data->setDataDescriptor( $this->opaque_data['descriptor'] );
		$opaque_data->setDataValue( $this->opaque_data['value'] );

		$payment = new AnetAPI\PaymentType();

		$payment->setOpaqueData( $opaque_data );

		$transaction = new AnetAPI\TransactionRequestType();

		$transaction->setTransactionType( 'authCaptureTransaction' );
		$transaction->setAmount( $args['amount'] );
		$transaction->setCurrencyCode( $args['currency'] );
		$transaction->setPayment( $payment );

		return $transaction;
	}

	/**
	 * Set a billing name in a single transaction object.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\TransactionRequestType $transaction Single transaction object.
	 * @param array                          $name        Billing first/last name.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	public function transaction_set_billing_name( $transaction, $name ) {

		if ( empty( $name['first'] ) && empty( $name['last'] ) ) {
			return $transaction;
		}

		$bill_to = new AnetAPI\CustomerAddressType();

		if ( ! empty( $name['first'] ) ) {
			$bill_to->setFirstName( $name['first'] );
		}

		if ( ! empty( $name['last'] ) ) {
			$bill_to->setLastName( $name['last'] );
		}

		$transaction->setBillTo( $bill_to );

		return $transaction;
	}

	/**
	 * Set a customer email in a single transaction object.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\TransactionRequestType $transaction Single transaction object.
	 * @param string                         $email       Customer email.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	public function transaction_set_customer_email( $transaction, $email ) {

		if ( empty( $email ) ) {
			return $transaction;
		}

		$customer = new AnetAPI\CustomerDataType();

		$customer->setEmail( $email );

		$transaction->setCustomer( $customer );

		return $transaction;
	}

	/**
	 * Set a customer billing address in transaction object.
	 *
	 * @since 1.1.0
	 *
	 * @param AnetAPI\TransactionRequestType $transaction Single transaction object.
	 * @param array                          $address     Customer address.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	public function transaction_set_customer_billing_address( $transaction, $address ) {

		if ( empty( $address ) ) {
			return $transaction;
		}

		$bill_to = $transaction->getBillTo();

		if ( is_null( $bill_to ) ) {
			$bill_to = new AnetAPI\CustomerAddressType();
		}

		$full_address = $address['address1'];

		if ( ! empty( $address['address2'] ) ) {
			$full_address .= ' ' . $address['address2'];
		}

		$bill_to->setAddress( $full_address );
		$bill_to->setCity( $address['city'] );
		$bill_to->setState( $address['state'] );
		$bill_to->setZip( $address['postal'] );
		$bill_to->setCountry( $address['country'] );

		$transaction->setBillTo( $bill_to );

		return $transaction;
	}

	/**
	 * Set an order description in a single transaction object.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\TransactionRequestType $transaction Single transaction object.
	 * @param string                         $desc        Customer email.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	public function transaction_set_order_desc( $transaction, $desc ) {

		if ( empty( $desc ) ) {
			return $transaction;
		}

		$order = new AnetAPI\OrderType();

		$order->setDescription( $desc );

		$transaction->setOrder( $order );

		return $transaction;
	}

	/**
	 * Add a setting to a single transaction object.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\TransactionRequestType $transaction Single transaction object.
	 * @param string                         $name        Setting name.
	 * @param string|bool                    $value       Setting value.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	public function transaction_add_to_settings( $transaction, $name, $value ) {

		$allowed_names = [
			'allowPartialAuth',
			'duplicateWindow',
			'emailCustomer',
			'headerEmailReceipt',
			'footerEmailReceipt',
			'recurringBilling',
		];

		if ( ! in_array( $name, $allowed_names, true ) ) {
			return $transaction;
		}

		$setting = new AnetAPI\SettingType();

		$setting->setSettingName( $name );
		$setting->setSettingValue( $value );

		$transaction->addToTransactionSettings( $setting );

		return $transaction;
	}

	/**
	 * Prepare a single transaction object for sending to API.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Single transaction arguments.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	private function prepare_transaction( $args ) {

		$transaction = $this->get_transaction_request_object( $args );

		if ( ! empty( $args['customer_name'] ) ) {
			$transaction = $this->transaction_set_billing_name( $transaction, $args['customer_name'] );
		}

		if ( ! empty( $args['receipt_email'] ) ) {
			$transaction = $this->transaction_set_customer_email( $transaction, $args['receipt_email'] );
			$transaction = $this->transaction_add_to_settings( $transaction, 'emailCustomer', true );
		}

		if ( ! empty( $args['description'] ) ) {
			$transaction = $this->transaction_set_order_desc( $transaction, $args['description'] );
		}

		if ( ! empty( $args['customer_billing_address'] ) ) {
			$transaction = $this->transaction_set_customer_billing_address( $transaction, $args['customer_billing_address'] );
		}

		return $transaction;
	}

	/**
	 * Prepare a subscription settlement transaction object for sending to API.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Subscription settlement arguments.
	 *
	 * @return AnetAPI\TransactionRequestType
	 */
	private function prepare_subscription_settlement( $args ) {

		$transaction = $this->get_transaction_request_object( $args );

		$transaction = $this->transaction_set_billing_name( $transaction, $args['subscription']['customer_name'] );
		$transaction = $this->transaction_set_customer_email( $transaction, $args['subscription']['email'] );
		$transaction = $this->transaction_set_order_desc( $transaction, $args['subscription']['settlement_desc'] );

		// Send Authorize.Net notification to a customer about subscription settlement payment.
		$transaction = $this->transaction_add_to_settings( $transaction, 'emailCustomer', true );

		if ( ! empty( $args['subscription']['customer_billing_address'] ) ) {
			$transaction = $this->transaction_set_customer_billing_address( $transaction, $args['subscription']['customer_billing_address'] );
		}

		// Create a customer profile out of a settlement payment data to use for a subscription creation.
		$profile = new AnetAPI\CustomerProfilePaymentType();

		$profile->setCreateProfile( true );
		$transaction->setProfile( $profile );

		return $transaction;
	}

	/**
	 * Prepare a subscription object for sending to API.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Subscription arguments.
	 *
	 * @return AnetAPI\ARBSubscriptionType
	 */
	private function prepare_subscription( $args ) {

		$interval = new AnetAPI\PaymentScheduleType\IntervalAType();

		$interval->setLength( $args['subscription']['period']['count'] );
		$interval->setUnit( $args['subscription']['period']['unit'] );

		$schedule = new AnetAPI\PaymentScheduleType();

		$schedule->setInterval( $interval );
		$schedule->setStartDate( $args['subscription']['start_date'] );
		$schedule->setTotalOccurrences( 9999 ); // 9999 means an ongoing subscription.

		$order = new AnetAPI\OrderType();

		$order->setDescription( $args['subscription']['payment_desc'] );

		$profile_id = $this->response->get_transaction_profile_id();
		$payment_id = $this->response->get_transaction_profile_payment_id();

		// Using data from a profile created on a settlement payment step.
		$profile = new AnetAPI\CustomerProfileIdType();

		$profile->setCustomerProfileId( $profile_id );
		$profile->setCustomerPaymentProfileId( $payment_id );

		$subscription = new AnetAPI\ARBSubscriptionType();

		$subscription->setName( $args['subscription']['name'] );
		$subscription->setPaymentSchedule( $schedule );
		$subscription->setOrder( $order );
		$subscription->setAmount( $args['amount'] );
		$subscription->setProfile( $profile );

		return $subscription;
	}

	/**
	 * Prepare a transaction void object for sending to API.
	 *
	 * @since 1.0.0
	 */
	private function prepare_transaction_void() {

		$transaction_void = new AnetAPI\TransactionRequestType();

		$transaction_void->setTransactionType( 'voidTransaction' );
		$transaction_void->setRefTransId( $this->response->get_transaction_id() );

		return $transaction_void;
	}

	/**
	 * Send a single transaction request to API.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\TransactionRequestType $transaction Single transaction object.
	 *
	 * @return AnetAPI\CreateTransactionResponse
	 */
	private function send_request_transaction( $transaction ) {

		$request = new AnetAPI\CreateTransactionRequest();

		$request->setMerchantAuthentication( $this->get_auth_object() );
		$request->setRefId( 'ref' . time() );
		$request->setTransactionRequest( $transaction );

		$controller = new AnetController\CreateTransactionController( $request );

		/* @var AnetAPI\CreateTransactionResponse $response Enforce correct return type hinting. */
		$response = $controller->executeWithApiResponse( $this->get_api_endpoint() );

		return $response;
	}

	/**
	 * Send a subscription request to API.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\ARBSubscriptionType $subscription Subscription object.
	 *
	 * @return AnetAPI\ARBCreateSubscriptionResponse
	 */
	private function send_request_subscription( $subscription ) {

		$request = new AnetAPI\ARBCreateSubscriptionRequest();

		$request->setmerchantAuthentication( $this->get_auth_object() );
		$request->setRefId( 'ref' . time() );
		$request->setSubscription( $subscription );

		$controller = new AnetController\ARBCreateSubscriptionController( $request );

		/* @var AnetAPI\ARBCreateSubscriptionResponse $response Enforce correct return type hinting. */
		$response = $controller->executeWithApiResponse( $this->get_api_endpoint() );

		return $response;
	}

	/**
	 * Send a customer profile deletion request to API.
	 *
	 * @since 1.0.0
	 *
	 * @return AnetAPI\DeleteCustomerProfileResponse
	 */
	private function send_request_profile_delete() {

		$request = new AnetAPI\DeleteCustomerProfileRequest();

		$request->setMerchantAuthentication( $this->get_auth_object() );
		$request->setRefId( 'ref' . time() );
		$request->setCustomerProfileId( $this->response->get_transaction_profile_id() );

		$controller = new AnetController\DeleteCustomerProfileController( $request );

		/* @var AnetAPI\DeleteCustomerProfileResponse $response Enforce correct return type hinting. */
		$response = $controller->executeWithApiResponse( $this->get_api_endpoint() );

		return $response;
	}

	/**
	 * Handle a single transaction response.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\CreateTransactionResponse $response Single transaction response.
	 */
	private function handle_response_transaction( $response ) {

		$this->response->set_transaction_response( $response );

		if ( ! $this->response->is_whole_transaction_ok() ) {
			$this->save_transaction_errors();
		}
	}

	/**
	 * Handle a subscription settlement response.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\CreateTransactionResponse $response Subscription settlement response.
	 */
	private function handle_response_subscription_settlement( $response ) {

		$this->response->set_transaction_response( $response );

		if ( ! $this->response->is_whole_subscription_settlement_ok() ) {
			$this->save_subscription_settlement_errors();
			$this->subscription_settlement_cleanup();
		}
	}

	/**
	 * Handle a subscription response.
	 *
	 * @since 1.0.0
	 *
	 * @param AnetAPI\ARBCreateSubscriptionResponse $response Subscription response.
	 */
	private function handle_response_subscription( $response ) {

		$this->response->set_subscription_response( $response );

		if ( ! $this->response->is_whole_subscription_ok() ) {
			$this->save_subscription_errors();
			$this->subscription_cleanup();
		}
	}

	/**
	 * Save single transaction errors.
	 *
	 * @since 1.0.0
	 */
	private function save_transaction_errors() {

		if ( $this->response->is_transaction_part_in_test_mode() ) {
			$this->errors[] = esc_html__( 'Authorize.Net is in test mode, empty transaction id is returned.', 'wpforms-authorize-net' );

			return;
		}

		do_action( 'wpforms_authorize_net_api_set_rate_limit', $this->response->get_transaction_response() );

		$this->errors[] = esc_html__( 'Payment was declined by Authorize.Net.', 'wpforms-authorize-net' );

		if ( ! $this->response->is_transaction_response_ok() ) {
			$errors = $this->response->extract_errors_from_transaction_response();

			$this->save_extracted_errors( $errors );

			return;
		}

		if ( ! $this->response->is_transaction_part_ok() ) {
			$errors = $this->response->extract_errors_from_transaction_part();

			$this->save_extracted_errors( $errors );

			return;
		}
	}

	/**
	 * Save subscription settlement errors.
	 *
	 * @since 1.0.0
	 */
	private function save_subscription_settlement_errors() {

		if ( $this->response->is_transaction_part_in_test_mode() ) {
			$this->errors[] = esc_html__( 'Authorize.Net is in test mode, empty transaction id is returned.', 'wpforms-authorize-net' );

			return;
		}

		do_action( 'wpforms_authorize_net_api_set_rate_limit', $this->response->get_transaction_response() );

		$this->errors[] = esc_html__( 'Subscription settlement payment was declined by Authorize.Net.', 'wpforms-authorize-net' );

		if ( ! $this->response->is_transaction_response_ok() ) {
			$errors = $this->response->extract_errors_from_transaction_response();

			$this->save_extracted_errors( $errors );

			return;
		}

		if ( ! $this->response->is_transaction_part_ok() ) {
			$errors = $this->response->extract_errors_from_transaction_part();

			$this->save_extracted_errors( $errors );

			return;
		}

		if ( ! $this->response->is_transaction_profile_part_ok() ) {
			$errors = $this->response->extract_errors_from_transaction_profile_part();

			$this->save_extracted_errors( $errors );

			return;
		}
	}

	/**
	 * Save subscription errors.
	 *
	 * @since 1.0.0
	 */
	private function save_subscription_errors() {

		do_action( 'wpforms_authorize_net_api_set_rate_limit', $this->response->get_subscription_response() );

		$this->errors[] = esc_html__( 'Subscription creation was declined by Authorize.Net.', 'wpforms-authorize-net' );
		$errors         = $this->response->extract_errors_from_subscription_response();

		$this->save_extracted_errors( $errors );
	}

	/**
	 * Save errors extracted from API response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $errors Extracted errors to set.
	 */
	private function save_extracted_errors( $errors ) {

		if ( ! is_array( $errors ) ) {
			return;
		}

		foreach ( $errors as $code => $error ) {
			$this->errors[] = esc_html__( 'API', 'wpforms-authorize-net' ) . ': (' . $code . ')  ' . $error;
		}
	}

	/**
	 * Cleanup after unsuccessful subscription settlement.
	 *
	 * @since 1.0.0
	 */
	private function subscription_settlement_cleanup() {

		// If transaction was unsuccessful, the profile never gets created, no need to clean up.
		// If the transaction was successful, but profile creation wasn't, need to void the transaction.
		if (
			$this->response->is_transaction_part_ok() &&
			! $this->response->is_transaction_profile_part_ok()
		) {
			$this->process_transaction_void();
		}
	}

	/**
	 * Cleanup after unsuccessful subscription.
	 *
	 * @since 1.0.0
	 */
	private function subscription_cleanup() {

		// If subscription had a creation attempt, both transaction and profile creation were successful.
		// Need to void the transaction and remove the profile.
		$this->process_transaction_void();
		$this->process_profile_delete();
	}
}
