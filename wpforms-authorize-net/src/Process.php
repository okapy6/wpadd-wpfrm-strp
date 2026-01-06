<?php

namespace WPFormsAuthorizeNet;

/**
 * Authorize.Net payment processing.
 *
 * @since 1.0.0
 */
class Process {

	/**
	 * Payment amount.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $amount = '';

	/**
	 * Form ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	public $form_id = 0;

	/**
	 * Form Authorize.Net payment settings.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $settings = [];

	/**
	 * Sanitized submitted field values and data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $fields = [];

	/**
	 * Form data and settings.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $form_data = [];

	/**
	 * Authorize.Net form errors.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $errors;

	/**
	 * Credit card expiration date.
	 *
	 * @since 1.6.0
	 *
	 * @var string
	 */
	private $cc_expire = 'xx/xx';

	/**
	 * Whether the payment has been processed.
	 *
	 * @since 1.7.0
	 *
	 * @var bool
	 */
	private $is_payment_processed = false;

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
	 * Process hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_action( 'wpforms_process', [ $this, 'process_entry' ], 10, 3 );
		add_action( 'wpforms_authorize_net_api_set_rate_limit', [ $this, 'set_rate_limit' ] );
		add_action( 'wpforms_process_complete', [ $this, 'process_entry_meta' ], 10, 4 );
		add_filter( 'wpforms_entry_email_process', [ $this, 'process_email' ], 70, 4 );
		add_filter( 'wpforms_forms_submission_prepare_payment_data', [ $this, 'prepare_payment_data' ], 10, 3 );
		add_filter( 'wpforms_forms_submission_prepare_payment_meta', [ $this, 'prepare_payment_meta' ], 10, 3 );
		add_action( 'wpforms_process_payment_saved', [ $this, 'process_payment_saved' ], 10, 3 );
		add_filter( 'wpforms_process_bypass_captcha', [ $this, 'bypass_captcha' ] );
	}

	/**
	 * Check if a payment exists with an entry, if so validate and process.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    Final/sanitized submitted field data.
	 * @param array $entry     Copy of original $_POST.
	 * @param array $form_data Form data and settings.
	 */
	public function process_entry( $fields, $entry, $form_data ) {

		// Check if payment method exists and is enabled.
		if ( empty( $form_data['payments']['authorize_net']['enable'] ) ) {
			return;
		}

		$this->form_id   = (int) $form_data['id'];
		$this->fields    = $fields;
		$this->form_data = $form_data;
		$this->settings  = $form_data['payments']['authorize_net'];
		$this->amount    = wpforms_get_total_payment( $this->fields );

		// Before proceeding, check if any basic errors were detected.
		if ( ! $this->is_form_ok() ) {
			$this->display_errors();

			return;
		}

		// Set a card token provided by Accept.js.
		wpforms_authorize_net()->api->set_opaque_data( $entry );

		// Set the card expiration date.
		$this->set_credit_card_expire( $entry );

		// Proceed to executing the purchase.
		$this->process_payment();

		// Set payment processing flag.
		$this->is_payment_processed = true;

		// Update the card field value to contain basic details.
		$this->update_card_field_value();
	}

	/**
	 * Bypass captcha if payment has been processed.
	 *
	 * @since 1.7.0
	 *
	 * @param bool $bypass_captcha Whether to bypass captcha.
	 *
	 * @return bool
	 */
	public function bypass_captcha( $bypass_captcha ) {

		if ( $bypass_captcha ) {
			return $bypass_captcha;
		}

		return $this->is_payment_processed;
	}

	/**
	 * Get form errors before payment processing.
	 *
	 * @since 1.0.0
	 */
	private function is_form_ok() {

		// Bail in case there are form processing errors.
		if ( ! empty( wpforms()->obj( 'process' )->errors[ $this->form_id ] ) ) {
			return false;
		}

		if ( ! $this->is_card_field_visibility_ok() ) {
			return false;
		}

		// Check rate limit.
		if ( ! $this->is_rate_limit_ok() ) {
			$this->errors[] = esc_html__( 'Unable to process Authorize.Net payment, please try again later.', 'wpforms-authorize-net' );

			return false;
		}

		// Check for conditional logic.
		if ( ! $this->is_conditional_logic_ok( $this->settings ) ) {
			$title = esc_html__( 'Authorize.Net payment stopped by conditional logic.', 'wpforms-authorize-net' );

			$this->log_errors( $title, $this->fields, 'conditional_logic' );

			return false;
		}

		// Check for Authorize.Net keys.
		if ( ! Helpers::has_authorize_net_keys() ) {
			$this->errors[] = esc_html__( 'Authorize.Net payment stopped, missing API keys.', 'wpforms-authorize-net' );

			return false;
		}

		// Check total charge amount.
		// Authorize.Net has no minimum amount limit.
		if ( empty( $this->amount ) || wpforms_sanitize_amount( 0 ) === $this->amount ) {
			$this->errors[] = esc_html__( 'Authorize.Net payment stopped, invalid/empty amount.', 'wpforms-authorize-net' );

			return false;
		}

		// Check that, despite how the form is configured, the form and
		// entry actually contain payment fields, otherwise no need to proceed.
		if ( ! wpforms_has_payment( 'form', $this->form_data ) || ! wpforms_has_payment( 'entry', $this->fields ) ) {
			$this->errors[] = esc_html__( 'Authorize.Net payment stopped, missing payment fields.', 'wpforms-authorize-net' );

			return false;
		}

		// Check subscription settings are provided.
		if ( ! $this->is_subscription_settings_ok() ) {
			$this->errors[] = esc_html__( 'Authorize.Net payment stopped, missing required subscription settings.', 'wpforms-authorize-net' );

			return false;
		}

		return true;
	}

	/**
	 * Check if there is at least one visible (not hidden by conditional logic) card field in the form.
	 *
	 * @since 1.1.0
	 */
	private function is_card_field_visibility_ok() {

		// If the form contains no fields with conditional logic the card field is visible by default.
		if ( empty( $this->form_data['conditional_fields'] ) ) {
			return true;
		}

		foreach ( $this->fields as $field ) {

			if ( empty( $field['type'] ) || $field['type'] !== 'authorize_net' ) {
				continue;
			}

			// if the field is NOT in array of conditional fields, it's visible.
			if ( ! in_array( $field['id'], $this->form_data['conditional_fields'], true ) ) {
				return true;
			}

			// if the field IS in array of conditional fields and marked as visible, it's visible.
			if ( ! empty( $field['visible'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if rate limit is under threshold and passes.
	 *
	 * @since 1.0.0
	 */
	private function is_rate_limit_ok() {

		$ip       = wpforms_get_ip();
		$ip_hash  = wp_hash( $ip );
		$attempts = get_transient( 'wpforms_authorize_net_attempt_' . $ip_hash );

		if ( ! empty( $attempts ) && $attempts >= 4 ) {
			$attempts++;
			set_transient( 'wpforms_authorize_net_attempt_' . $ip_hash, $attempts, HOUR_IN_SECONDS * 2 );

			return false;
		}

		return true;
	}

	/**
	 * Check if conditional logic check passes for the given settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Conditional logic settings to process.
	 *
	 * @return bool
	 */
	private function is_conditional_logic_ok( $settings ) {

		// Check conditional logic settings.
		if (
			empty( $settings['conditional_logic'] ) ||
			empty( $settings['conditional_type'] ) ||
			empty( $settings['conditionals'] )
		) {
			return true;
		}

		// All conditional logic checks passed, continue with processing.
		$process = wpforms_conditional_logic()->process( $this->fields, $this->form_data, $settings['conditionals'] );

		if ( $settings['conditional_type'] === 'stop' ) {
			$process = ! $process;
		}

		return $process;
	}

	/**
	 * Check if subscription settings are provided.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_subscription_settings_ok() {

		if ( empty( $this->settings['recurring']['enable'] ) ) {
			return true;
		}

		if ( empty( $this->settings['recurring']['period'] ) ) {
			return false;
		}

		if ( empty( $this->settings['recurring']['email'] ) && $this->settings['recurring']['email'] !== '0' ) {
			return false;
		}

		if ( empty( $this->settings['recurring']['customer_name'] ) && $this->settings['recurring']['customer_name'] !== '0' ) {
			return false;
		}

		return true;
	}

	/**
	 * Process a payment.
	 *
	 * @since 1.0.0
	 */
	private function process_payment() {

		if ( empty( $this->settings['recurring']['enable'] ) ) {
			$this->process_payment_single();
		} else {
			$this->process_payment_subscription();
		}
	}

	/**
	 * Get args for any type of payment.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_payment_args_general() {

		return [
			'amount'   => $this->amount,
			'currency' => strtolower( wpforms_get_currency() ),
		];
	}

	/**
	 * Get single payment args.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_payment_args_single() {

		$args = $this->get_payment_args_general();

		// Customer first name.
		if ( ! empty( $this->fields[ $this->settings['customer_name'] ]['first'] ) ) {
			$args['customer_name']['first'] = sanitize_text_field( $this->fields[ $this->settings['customer_name'] ]['first'] );
		}

		// Customer last name.
		if ( ! empty( $this->fields[ $this->settings['customer_name'] ]['last'] ) ) {
			$args['customer_name']['last'] = sanitize_text_field( $this->fields[ $this->settings['customer_name'] ]['last'] );
		}

		// Receipt email.
		if ( ! empty( $this->fields[ $this->settings['receipt_email'] ]['value'] ) ) {
			$args['receipt_email'] = sanitize_email( $this->fields[ $this->settings['receipt_email'] ]['value'] );
		}

		// Payment description.
		if ( ! empty( $this->settings['payment_description'] ) ) {
			$args['description'] = html_entity_decode( $this->settings['payment_description'], ENT_COMPAT, 'UTF-8' );
		}

		// Customer billing address.
		if ( ! empty( $this->fields[ $this->settings['customer_billing_address'] ] ) ) {
			$args['customer_billing_address'] = $this->fields[ $this->settings['customer_billing_address'] ];
		}

		return $args;
	}

	/**
	 * Get subscription payment args.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_payment_args_subscription() {

		$args     = $this->get_payment_args_general();
		$args_sub = [];

		// Receipt email.
		if ( ! empty( $this->fields[ $this->settings['recurring']['email'] ]['value'] ) ) {
			$args_sub['email'] = sanitize_email( $this->fields[ $this->settings['recurring']['email'] ]['value'] );
		}

		// Customer first name.
		if ( ! empty( $this->fields[ $this->settings['recurring']['customer_name'] ]['first'] ) ) {
			$args_sub['customer_name']['first'] = sanitize_text_field( $this->fields[ $this->settings['recurring']['customer_name'] ]['first'] );
		}

		// Customer last name.
		if ( ! empty( $this->fields[ $this->settings['recurring']['customer_name'] ]['last'] ) ) {
			$args_sub['customer_name']['last'] = sanitize_text_field( $this->fields[ $this->settings['recurring']['customer_name'] ]['last'] );
		}

		$period_data = Helpers::get_subscription_period_data();

		// Subscription period.
		$args_sub['period'] = array_key_exists( $this->settings['recurring']['period'], $period_data ) ? $period_data[ $this->settings['recurring']['period'] ] : $period_data['yearly'];

		// Subscription start date.
		$args_sub['start_date'] = date_create( '+' . $args_sub['period']['count'] . $args_sub['period']['unit'] );

		$title = ! empty( $this->settings['recurring']['name'] ) ? $this->settings['recurring']['name'] : sanitize_text_field( $this->form_data['settings']['form_title'] );
		$title = trim( $title );

		// Subscription name suffix.
		$title_suffix = sprintf( '(%1$s %2$s)', $args['amount'], $args_sub['period']['desc'] );

		// Subscription name truncated.
		// The max length of the subscription name is 50 characters.
		// So, we need to truncate the title to fit the max length, given the suffix length into consideration.
		$title_short = trim( mb_substr( $title, 0, 50 - mb_strlen( $title_suffix ) - 2 ) );

		// Add ellipsis if the title was truncated.
		$title_short = $title_short === $title ? $title_short : $title_short . '…';

		// Subscription name.
		$args_sub['name'] = sprintf( '%1$s %2$s', $title_short, $title_suffix );

		// Customer billing address.
		if ( ! empty( $this->fields[ $this->settings['recurring']['customer_billing_address'] ] ) ) {
			$args_sub['customer_billing_address'] = $this->fields[ $this->settings['recurring']['customer_billing_address'] ];
		}

		// Subscription regular payment description.
		/* translators: %s - Name of the subscription plan. */
		$args_sub['payment_desc'] = sprintf( __( 'Payment for %s', 'wpforms-authorize-net' ), esc_html( $title ) );

		// Subscription settlement payment description.
		/* translators: %s - Name of the subscription plan. */
		$args_sub['settlement_desc'] = sprintf( __( 'Settlement Payment for %s', 'wpforms-authorize-net' ), esc_html( $title ) );

		// Put subscription arguments into its own key.
		$args['subscription'] = $args_sub;

		return $args;
	}

	/**
	 * Process a single payment.
	 *
	 * @since 1.0.0
	 */
	private function process_payment_single() {

		$args = $this->get_payment_args_single();
		$args = apply_filters( 'wpforms_authorize_net_process_payment_single_args', $args, $this );

		wpforms_authorize_net()->api->process_transaction( $args );

		$this->process_api_errors( 'single' );
	}

	/**
	 * Process a subscription payment.
	 *
	 * @since 1.0.0
	 */
	private function process_payment_subscription() {

		// Check for conditional logic.
		if ( ! $this->is_conditional_logic_ok( $this->settings['recurring'] ) ) {
			$this->process_payment_single();

			return;
		}

		$args = $this->get_payment_args_subscription();
		$args = apply_filters( 'wpforms_authorize_net_process_payment_subscription_args', $args, $this );

		wpforms_authorize_net()->api->process_subscription( $args );

		$this->process_api_errors( 'subscription' );
	}

	/**
	 * Display form errors.
	 *
	 * @since 1.0.0
	 *
	 * @param array $errors Errors to display.
	 */
	private function display_errors( $errors = [] ) {

		if ( ! $errors ) {
			$errors = $this->errors;
		}

		if ( ! $errors || ! is_array( $errors ) ) {
			return;
		}

		// Check if the form contains a required credit card. If it does
		// and there was an error, return the error to the user and prevent
		// the form from being submitted. This should not occur under normal
		// circumstances.
		foreach ( $this->form_data['fields'] as $field ) {

			if ( $field['type'] !== 'authorize_net' ) {
				continue;
			}

			if ( ! empty( $field['required'] ) ) {
				wpforms()->obj( 'process' )->errors[ $this->form_id ]['footer'] = implode( '<br>', $errors );
			}
		}
	}

	/**
	 * Collect errors from API and turn it into form errors.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Payment time (e.g. 'single' or 'subscription').
	 */
	private function process_api_errors( $type ) {

		$errors = wpforms_authorize_net()->api->get_errors();

		if ( empty( $errors ) || ! is_array( $errors ) ) {
			return;
		}

		$this->display_errors( $errors );

		if ( $type === 'subscription' ) {
			$title = esc_html__( 'Authorize.Net subscription payment stopped by error', 'wpforms-authorize-net' );
		} else {
			$title = esc_html__( 'Authorize.Net payment stopped by error', 'wpforms-authorize-net' );
		}

		// Log transaction specific errors to make payment issues identification easier.
		$_errors = wpforms_authorize_net()->api->response->extract_errors_from_transaction_part_raw();

		if ( $_errors ) {
			$errors[] = $_errors;
		}

		$this->log_errors( $title, $errors );
	}

	/**
	 * Set rate limit for API errors.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response Authorize.Net response.
	 */
	public function set_rate_limit( $response ) {

		if ( Helpers::is_test_mode() ) {
			return;
		}

		if ( ! apply_filters( 'wpforms_authorize_net_process_set_rate_limit', true ) ) {
			return;
		}

		$ip       = wpforms_get_ip();
		$ip_hash  = wp_hash( $ip );
		$attempts = get_transient( 'wpforms_authorize_net_attempt_' . $ip_hash );

		if ( $attempts === false ) {
			$attempts = 1;
		} else {
			$attempts++;
		}

		set_transient( 'wpforms_authorize_net_attempt_' . $ip_hash, $attempts, HOUR_IN_SECONDS * 2 );
	}

	/**
	 * Log payment errors.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $title    Error title.
	 * @param array|string $messages Error messages.
	 * @param string       $level    Error level to add to 'payment' error level.
	 */
	private function log_errors( $title, $messages = [], $level = 'error' ) {

		wpforms_log(
			$title,
			$messages,
			[
				'type'    => [ 'payment', $level ],
				'form_id' => $this->form_id,
			]
		);
	}

	/**
	 * Update the card field value to contain basic details.
	 *
	 * @since 1.0.0
	 */
	private function update_card_field_value() {

		if ( $this->errors ) {
			return;
		}

		foreach ( $this->fields as $field_id => $field ) {

			if ( empty( $field['type'] ) || $field['type'] !== 'authorize_net' ) {
				continue;
			}

			$details = [
				'brand' => wpforms_authorize_net()->api->response->get_transaction_card_brand(),
				'last4' => wpforms_authorize_net()->api->response->get_transaction_card_last4(),
			];

			$details = implode( "\n", array_filter( $details ) );
			$details = apply_filters(
				'wpforms_authorize_net_process_update_card_field_value',
				$details,
				wpforms_authorize_net()->api->response->get_transaction_response()
			);

			wpforms()->obj( 'process' )->fields[ $field_id ]['value'] = $details;

			return;
		}
	}

	/**
	 * Update entry details and add meta for a successful payment.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $fields    Final/sanitized submitted field data.
	 * @param array  $entry     Copy of original $_POST.
	 * @param array  $form_data Form data and settings.
	 * @param string $entry_id  Entry ID.
	 */
	public function process_entry_meta( $fields, $entry, $form_data, $entry_id ) {

		if ( $this->errors || empty( $entry_id ) ) {
			return;
		}

		$transaction_id = wpforms_authorize_net()->api->response->get_transaction_id();

		if ( empty( $transaction_id ) ) {
			return;
		}

		wpforms()->obj( 'entry' )->update(
			$entry_id,
			[
				'type' => 'payment',
			],
			'',
			'',
			[ 'cap' => false ]
		);

		$transaction  = wpforms_authorize_net()->api->response->get_transaction_response();
		$subscription = wpforms_authorize_net()->api->response->get_subscription_response();
		$customer     = wpforms_authorize_net()->api->response->get_subscription_profile_part();

		// Processing complete.
		do_action( 'wpforms_authorize_net_process_entry_meta', $fields, $form_data, $entry_id, $transaction, $subscription, $customer );
	}

	/**
	 * Logic that helps decide if we should send completed payments notifications.
	 *
	 * @since 1.1.0
	 *
	 * @param bool  $process         Whether to process or not.
	 * @param array $fields          Form fields.
	 * @param array $form_data       Form data.
	 * @param int   $notification_id Notification ID.
	 *
	 * @return bool
	 */
	public function process_email( $process, $fields, $form_data, $notification_id ) {

		if ( ! $process ) {
			return false;
		}

		if ( empty( $form_data['payments']['authorize_net']['enable'] ) ) {
			return $process;
		}

		if ( empty( $form_data['settings']['notifications'][ $notification_id ]['authorize_net'] ) ) {
			return $process;
		}

		if ( ! $this->is_conditional_logic_ok( $this->settings ) ) {
			return false;
		}

		if ( ! wpforms_authorize_net()->api->response->is_whole_transaction_ok() && ! wpforms_authorize_net()->api->response->is_whole_subscription_ok() ) {
			return false;
		}

		return empty( $this->errors ) && empty( wpforms_authorize_net()->api->get_errors() );
	}

	/**
	 * Add details to payment data.
	 *
	 * @since 1.6.0
	 *
	 * @param array $payment_data Payment data args.
	 * @param array $fields       Final/sanitized submitted field data.
	 * @param array $form_data    Form data and settings.
	 *
	 * @return array
	 */
	public function prepare_payment_data( $payment_data, $fields, $form_data ) {

		$response       = wpforms_authorize_net()->api->response;
		$transaction_id = $response->get_transaction_id();

		// If the transaction ID is empty, we don't have a successful transaction.
		if ( empty( $transaction_id ) ) {
			return $payment_data;
		}

		$is_subscription                = false;
		$payment_data['status']         = 'processed';
		$payment_data['gateway']        = 'authorize_net';
		$payment_data['mode']           = Helpers::get_authorize_net_mode();
		$payment_data['transaction_id'] = sanitize_text_field( $transaction_id );

		if ( ! empty( $response->get_subscription_id() ) ) {
			$is_subscription                     = true;
			$payment_data['subscription_status'] = 'not-synced';
			$payment_data['subscription_id']     = sanitize_text_field( $response->get_subscription_id() );
			$payment_data['customer_id']         = sanitize_text_field( $response->get_subscription_profile_id() );
		}

		// Fetch the data according to the transaction type.
		// i.e. One-time or subscription could have different customer name and email.
		$payment_data['title'] = $this->get_payment_title( $is_subscription );

		return $payment_data;
	}

	/**
	 * Add payment meta for a successful one-time or subscription payment.
	 *
	 * @since 1.6.0
	 *
	 * @param array $payment_meta Payment meta.
	 * @param array $fields       Final/sanitized submitted field data.
	 * @param array $form_data    Form data and settings.
	 *
	 * @return array
	 */
	public function prepare_payment_meta( $payment_meta, $fields, $form_data ) {

		$response       = wpforms_authorize_net()->api->response;
		$transaction_id = $response->get_transaction_id();

		// If the transaction ID is empty, we don't have a successful transaction.
		if ( empty( $transaction_id ) ) {
			return $payment_meta;
		}

		$payment_type                        = 'Order';
		$payment_meta['method_type']         = 'card';
		$payment_meta['credit_card_expires'] = sanitize_text_field( $this->cc_expire );
		$payment_meta['credit_card_last4']   = sanitize_text_field( preg_replace( '/\D/', '', $response->get_transaction_card_last4() ) );
		$payment_meta['credit_card_method']  = sanitize_text_field( strtolower( $response->get_transaction_card_brand() ) );

		if ( ! empty( $response->get_subscription_id() ) ) {
			$payment_type                        = 'Subscription';
			$payment_meta['subscription_period'] = sanitize_text_field( $this->settings['recurring']['period'] );
		}

		// Add a log indicating that the charge was successful.
		$payment_meta['log'] = $this->format_payment_log(
			sprintf(
				'Authorize.Net %s created.',
				$payment_type
			)
		);

		return $payment_meta;
	}

	/**
	 * Add payment info for successful payment.
	 *
	 * @since 1.6.0
	 *
	 * @param string $payment_id Payment ID.
	 * @param array  $fields     Final/sanitized submitted field data.
	 * @param array  $form_data  Form data and settings.
	 */
	public function process_payment_saved( $payment_id, $fields, $form_data ) {

		$response       = wpforms_authorize_net()->api->response;
		$transaction_id = $response->get_transaction_id();

		// If the transaction ID is empty, we don't have a successful transaction.
		if ( empty( $transaction_id ) ) {
			return;
		}

		wpforms()->obj( 'payment_meta' )->add_log(
			$payment_id,
			sprintf(
				'Authorize.Net payment completed. (Transaction ID: %s)',
				$transaction_id
			)
		);
	}

	/**
	 * Return payment log value.
	 *
	 * @since 1.6.0
	 *
	 * @param string $value Log value.
	 *
	 * @return string
	 */
	private function format_payment_log( $value ) {

		return wp_json_encode(
			[
				'value' => sanitize_text_field( $value ),
				'date'  => gmdate( 'Y-m-d H:i:s' ),
			]
		);
	}

	/**
	 * Get Payment title.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $is_subscription Determine if the payment is a subscription.
	 *
	 * @return string Payment title.
	 */
	private function get_payment_title( $is_subscription ) {

		$customer_name = $this->get_customer_name( $is_subscription );

		if ( $customer_name ) {
			return sanitize_text_field( implode( ' ', array_values( $customer_name ) ) );
		}

		$customer_email = $this->get_customer_email( $is_subscription );

		if ( $customer_email ) {
			return sanitize_email( $customer_email );
		}

		return '';
	}

	/**
	 * Get Customer name.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $is_subscription Determine if the payment is a subscription.
	 *
	 * @return array
	 */
	private function get_customer_name( $is_subscription ) {

		$customer_name = [];
		$settings      = $is_subscription ? $this->settings['recurring'] : $this->settings;

		// Billing first name.
		if ( ! empty( $this->fields[ $settings['customer_name'] ]['first'] ) ) {
			$customer_name['first_name'] = $this->fields[ $settings['customer_name'] ]['first'];
		}

		// Billing last name.
		if ( ! empty( $this->fields[ $settings['customer_name'] ]['last'] ) ) {
			$customer_name['last_name'] = $this->fields[ $settings['customer_name'] ]['last'];
		}

		// If a Name field has the `Simple` format.
		if (
			empty( $customer_name['first_name'] ) &&
			empty( $customer_name['last_name'] ) &&
			! empty( $this->fields[ $settings['customer_name'] ]['value'] )
		) {
			$customer_name['first_name'] = $this->fields[ $settings['customer_name'] ]['value'];
		}

		return $customer_name;
	}

	/**
	 * Get Customer email.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $is_subscription Determine if the payment is a subscription.
	 *
	 * @return string
	 */
	private function get_customer_email( $is_subscription ) {

		if ( $is_subscription ) {
			return ! empty( $this->fields[ $this->settings['recurring']['email'] ]['value'] ) ? $this->fields[ $this->settings['recurring']['email'] ]['value'] : '';
		}

		return ! empty( $this->fields[ $this->settings['receipt_email'] ]['value'] ) ? $this->fields[ $this->settings['receipt_email'] ]['value'] : '';
	}

	/**
	 * Set the credit card expire date.
	 *
	 * @since 1.6.0
	 *
	 * @param array $entry Copy of the original $_POST.
	 */
	private function set_credit_card_expire( &$entry ) {

		if ( empty( $entry['authorize_net']['card_data']['expire'] ) ) {
			return;
		}

		// Set the expiry date.
		$this->cc_expire = $entry['authorize_net']['card_data']['expire'];

		// Remove the expiry date from the entry.
		unset( $entry['authorize_net']['card_data'] );
	}
}
