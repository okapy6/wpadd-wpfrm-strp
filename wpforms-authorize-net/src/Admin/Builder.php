<?php

namespace WPFormsAuthorizeNet\Admin;

use WPForms_Builder_Panel_Settings;

/**
 * Authorize.Net Form Builder related functionality.
 *
 * @since 1.0.0
 */
class Builder {

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
	 * Builder hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {

		add_filter( 'wpforms_builder_strings', [ $this, 'javascript_strings' ] );
		add_action( 'wpforms_builder_enqueues', [ $this, 'enqueues' ] );
		add_action( 'wpforms_form_settings_notifications_single_after', [ $this, 'notification_settings' ], 10, 2 );
	}

	/**
	 * Add our localized strings to be available in the form builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $strings Form builder JS strings.
	 *
	 * @return array
	 */
	public function javascript_strings( $strings ) {

		$strings['authorize_net_recurring_name_email_required'] = wp_kses(
			__( '<p>When recurring subscription payments are enabled, the Customer Email and the Customer Name are required.</p><p>To proceed, please go to <strong>Payments » Authorize.Net</strong> and select <strong>Customer Email</strong> and <strong>Customer Name</strong> fields.</p>', 'wpforms-authorize-net' ),
			[
				'p'      => [],
				'strong' => [],
			]
		);

		$strings['authorize_net_name_format_required'] = wp_kses(
			__( '<p><strong>Name</strong> field <strong>First Last</strong> format is required to be compatible with Authorize.Net.</p><p>To proceed, please change the format or select a different name field as a <strong>Customer Name</strong> in <strong>Payments » Authorize.Net</strong>.</p>', 'wpforms-authorize-net' ),
			[
				'p'      => [],
				'strong' => [],
			]
		);

		$strings['authorize_net_payments_enabled_required'] = wp_kses(
			__( '<p>Authorize.Net Payments must be enabled when using the Authorize.Net field.</p><p>To proceed, please go to <strong>Payments » Authorize.Net</strong> and check <strong>Enable Authorize.Net payments</strong>.</p>', 'wpforms-authorize-net' ),
			[
				'p'      => [],
				'strong' => [],
			]
		);

		$strings['authorize_net_keys_required'] = wp_kses(
			__( '<p>Authorize.Net API credentials are required to use the Authorize.Net field.</p><p>To proceed, please go to <strong>WPForms Settings » Payments » Authorize.Net</strong> and fill in <strong>API Login ID</strong> and <strong>Transaction Key</strong> fields.</p>', 'wpforms-authorize-net' ),
			[
				'p'      => [],
				'strong' => [],
			]
		);

		return $strings;
	}

	/**
	 * Enqueue assets for the builder.
	 *
	 * @since 1.0.0
	 */
	public function enqueues() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-builder-authorize-net',
			WPFORMS_AUTHORIZE_NET_URL . "assets/css/admin/builder-authorize-net{$min}.css",
			[],
			WPFORMS_AUTHORIZE_NET_VERSION
		);

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_enqueue_script(
			'wpforms-builder-authorize-net',
			WPFORMS_AUTHORIZE_NET_URL . "assets/js/admin/builder-authorize-net{$min}.js",
			[ 'jquery' ],
			WPFORMS_AUTHORIZE_NET_VERSION
		);
	}

	/**
	 * Add checkbox to form notification settings.
	 *
	 * @since 1.1.0
	 *
	 * @param WPForms_Builder_Panel_Settings $settings WPForms_Builder_Panel_Settings class instance.
	 * @param int                            $id       Subsection ID.
	 */
	public function notification_settings( $settings, $id ) {

		wpforms_panel_field(
			'toggle',
			'notifications',
			'authorize_net',
			$settings->form_data,
			esc_html__( 'Enable for Authorize.Net completed payments', 'wpforms-authorize-net' ),
			[
				'parent'      => 'settings',
				'class'       => empty( $settings->form_data['payments']['authorize_net']['enable'] ) ? 'wpforms-hidden' : '',
				'input_class' => 'wpforms-radio-group wpforms-radio-group-' . $id . '-notification-by-status wpforms-radio-group-item-authorize_net wpforms-notification-by-status-alert',
				'subsection'  => $id,
				'tooltip'     => wp_kses(
					__( 'When enabled this notification will <em>only</em> be sent when a Authorize.Net payment has been successfully <strong>completed</strong>.', 'wpforms-authorize-net' ),
					[
						'em'     => [],
						'strong' => [],
					]
				),
				'data'        => [
					'radio-group'    => $id . '-notification-by-status',
					'provider-title' => esc_html__( 'Authorize.Net completed payments', 'wpforms-authorize-net' ),
				],
			]
		);
	}
}
