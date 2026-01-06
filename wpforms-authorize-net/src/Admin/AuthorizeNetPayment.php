<?php

namespace WPFormsAuthorizeNet\Admin;

use WPForms_Payment;
use WPFormsAuthorizeNet\Helpers;

/**
 * Authorize.Net Form Builder payment registration.
 *
 * @since 1.0.0
 */
class AuthorizeNetPayment extends WPForms_Payment {

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->version  = WPFORMS_AUTHORIZE_NET_VERSION;
		$this->name     = 'Authorize.Net';
		$this->slug     = 'authorize_net';
		$this->priority = 15;
		$this->icon     = WPFORMS_AUTHORIZE_NET_URL . 'assets/images/addon-icon-authorize-net.png';
	}

	/**
	 * Display content inside the panel content area.
	 *
	 * @since 1.0.0
	 */
	public function builder_content() {

		if ( ! Helpers::has_authorize_net_keys() ) {
			echo '<p class="wpforms-alert wpforms-alert-info">';
			printf(
				wp_kses(
					/* translators: %s - Admin area Payments settings page URL. */
					__( 'Heads up! Authorize.Net payments can\'t be enabled yet. First, please connect to your Authorize.Net account on the <a href="%s">WPForms Settings</a> page.', 'wpforms-authorize-net' ),
					[
						'a' => [
							'href' => [],
						],
					]
				),
				esc_url( admin_url( 'admin.php?page=wpforms-settings&view=payments#wpforms-setting-row-authorize_net-heading' ) )
			);
			echo '</p>';

			return;
		}

		echo '<p class="wpforms-alert wpforms-alert-info" id="authorize_net-credit-card-alert">';
		esc_html_e( 'To use Authorize.Net payments you need to add an Authorize.Net field to the form', 'wpforms-authorize-net' );
		echo '</p>';

		wpforms_panel_field(
			'toggle',
			'authorize_net',
			'enable',
			$this->form_data,
			esc_html__( 'Enable Authorize.Net payments', 'wpforms-authorize-net' ),
			[
				'parent'  => 'payments',
				'default' => '0',
			]
		);

		echo '<div class="wpforms-panel-content-section-authorize_net-body">';

		wpforms_panel_field(
			'text',
			'authorize_net',
			'payment_description',
			$this->form_data,
			esc_html__( 'Payment Description', 'wpforms-authorize-net' ),
			[
				'parent'  => 'payments',
				'tooltip' => esc_html__( 'Enter your payment description. Eg: Donation for the soccer team. Only used for standard one-time payments.', 'wpforms-authorize-net' ),
			]
		);

		wpforms_panel_field(
			'select',
			'authorize_net',
			'receipt_email',
			$this->form_data,
			esc_html__( 'Authorize.Net Payment Receipt', 'wpforms-authorize-net' ),
			[
				'parent'      => 'payments',
				'field_map'   => [ 'email' ],
				'placeholder' => esc_html__( '--- Select Email ---', 'wpforms-authorize-net' ),
				'tooltip'     => esc_html__( 'If you would like to have Authorize.Net send a receipt after payment, select the email field to use. This is optional but recommended. Only used for standard one-time payments.', 'wpforms-authorize-net' ),
			]
		);

		wpforms_panel_field(
			'select',
			'authorize_net',
			'customer_name',
			$this->form_data,
			esc_html__( 'Customer Name', 'wpforms-authorize-net' ),
			[
				'parent'      => 'payments',
				'field_map'   => [ 'name' ],
				'placeholder' => esc_html__( '--- Select Name ---', 'wpforms-authorize-net' ),
				'tooltip'     => esc_html__( "Select the field that contains the customer's name. This field is optional.", 'wpforms-authorize-net' ),
			]
		);

		wpforms_panel_field(
			'select',
			'authorize_net',
			'customer_billing_address',
			$this->form_data,
			esc_html__( 'Customer Billing Address', 'wpforms-authorize-net' ),
			[
				'parent'      => 'payments',
				'field_map'   => [ 'address' ],
				'placeholder' => esc_html__( '--- Select Billing Address ---', 'wpforms-authorize-net' ),
				'tooltip'     => esc_html__( "Select the field that contains the customer's billing address. This field is optional.", 'wpforms-authorize-net' ),
			]
		);

		wpforms_conditional_logic()->builder_block(
			[
				'form'        => $this->form_data,
				'type'        => 'panel',
				'panel'       => 'authorize_net',
				'parent'      => 'payments',
				'actions'     => [
					'go'   => esc_html__( 'Process', 'wpforms-authorize-net' ),
					'stop' => esc_html__( 'Don\'t process', 'wpforms-authorize-net' ),
				],
				'action_desc' => esc_html__( 'this charge if', 'wpforms-authorize-net' ),
				'reference'   => esc_html__( 'Authorize.Net payment', 'wpforms-authorize-net' ),
			]
		);

		echo '<h2>' . esc_html__( 'Subscriptions', 'wpforms-authorize-net' ) . '</h2>';

		wpforms_panel_field(
			'toggle',
			'authorize_net',
			'enable',
			$this->form_data,
			esc_html__( 'Enable recurring subscription payments', 'wpforms-authorize-net' ),
			[
				'parent'     => 'payments',
				'subsection' => 'recurring',
				'default'    => '0',
			]
		);

		wpforms_panel_field(
			'text',
			'authorize_net',
			'name',
			$this->form_data,
			esc_html__( 'Plan Name', 'wpforms-authorize-net' ),
			[
				'parent'     => 'payments',
				'subsection' => 'recurring',
				'tooltip'    => esc_html__( 'Enter the subscription name. Eg: Email Newsletter. Subscription period and price are automatically appended. If left empty the form name will be used.', 'wpforms-authorize-net' ),
			]
		);

		wpforms_panel_field(
			'select',
			'authorize_net',
			'period',
			$this->form_data,
			esc_html__( 'Recurring Period', 'wpforms-authorize-net' ),
			[
				'parent'     => 'payments',
				'subsection' => 'recurring',
				'default'    => 'yearly',
				'options'    => [
					'weekly'     => esc_html__( 'Weekly', 'wpforms-authorize-net' ),
					'monthly'    => esc_html__( 'Monthly', 'wpforms-authorize-net' ),
					'quarterly'  => esc_html__( 'Quarterly', 'wpforms-authorize-net' ),
					'semiyearly' => esc_html__( 'Semi-Yearly', 'wpforms-authorize-net' ),
					'yearly'     => esc_html__( 'Yearly', 'wpforms-authorize-net' ),
				],
				'tooltip'    => esc_html__( 'How often you would like the charge to recur.', 'wpforms-authorize-net' ),
			]
		);

		wpforms_panel_field(
			'select',
			'authorize_net',
			'email',
			$this->form_data,
			esc_html__( 'Customer Email', 'wpforms-authorize-net' ),
			[
				'parent'      => 'payments',
				'subsection'  => 'recurring',
				'field_map'   => [ 'email' ],
				'placeholder' => esc_html__( '--- Select Email ---', 'wpforms-authorize-net' ),
				'tooltip'     => esc_html__( "Select the field that contains the customer's email address. This field is required.", 'wpforms-authorize-net' ),
			]
		);

		wpforms_panel_field(
			'select',
			'authorize_net',
			'customer_name',
			$this->form_data,
			esc_html__( 'Customer Name', 'wpforms-authorize-net' ),
			[
				'parent'      => 'payments',
				'subsection'  => 'recurring',
				'field_map'   => [ 'name' ],
				'placeholder' => esc_html__( '--- Select Name ---', 'wpforms-authorize-net' ),
				'tooltip'     => esc_html__( "Select the field that contains the customer's name. This field is required.", 'wpforms-authorize-net' ),
			]
		);

		wpforms_panel_field(
			'select',
			'authorize_net',
			'customer_billing_address',
			$this->form_data,
			esc_html__( 'Customer Billing Address', 'wpforms-authorize-net' ),
			[
				'parent'      => 'payments',
				'subsection'  => 'recurring',
				'field_map'   => [ 'address' ],
				'placeholder' => esc_html__( '--- Select Billing Address ---', 'wpforms-authorize-net' ),
				'tooltip'     => esc_html__( "Select the field that contains the customer's billing address. This field is optional.", 'wpforms-authorize-net' ),
			]
		);

		wpforms_conditional_logic()->builder_block(
			[
				'form'        => $this->form_data,
				'type'        => 'panel',
				'panel'       => 'authorize_net',
				'parent'      => 'payments',
				'subsection'  => 'recurring',
				'actions'     => [
					'go'   => esc_html__( 'Process', 'wpforms-authorize-net' ),
					'stop' => esc_html__( 'Don\'t process', 'wpforms-authorize-net' ),
				],
				'action_desc' => esc_html__( 'payment as recurring if', 'wpforms-authorize-net' ),
				'reference'   => esc_html__( 'Authorize.Net Recurring payment', 'wpforms-authorize-net' ),
			]
		);

		echo '</div>';
	}
}
