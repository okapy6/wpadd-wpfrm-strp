/* global wpforms_builder */

'use strict';

/**
 * WPForms Authorize.Net builder function.
 *
 * @since 1.0.0
 */
const WPFormsBuilderAuthorizeNet = window.WPFormsBuilderAuthorizeNet || ( function( document, window, $ ) {
	/**
	 * Elements holder.
	 *
	 * @since 1.9.0
	 *
	 * @type {Object}
	 */
	const el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.0.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Initialized once the DOM and Providers are fully loaded.
		 *
		 * @since 1.0.0
		 */
		ready() {
			el.$addFieldButton = $( '#wpforms-add-fields-authorize_net' );
			el.$enablePayment = $( '#wpforms-panel-field-authorize_net-enable' );

			app.bindUIActions();
			app.settingsDisplay();
			app.settingsConditions();
		},

		/**
		 * Process various events as a response to UI interactions.
		 *
		 * @since 1.0.0
		 */
		bindUIActions() {
			$( document )
				.on( 'wpformsFieldUpdate', app.settingsDisplay )
				.on( 'wpformsFieldUpdate', app.settingsConditions )

				.on( 'wpformsSaved', app.requiredFieldsCheck )
				.on( 'wpformsSaved', app.nameFormatCheck )
				.on( 'wpformsSaved', app.paymentsEnabledCheck )

				.on( 'mousedown', '#wpforms-add-fields-authorize_net', app.keysCheck )

				.on( 'wpformsFieldAdd', app.fieldAdded )
				.on( 'wpformsFieldDelete', app.enableAddCardButton )
				.on( 'wpformsFieldDelete', app.disableNotifications );
		},

		/**
		 * Toggles visibility of the Authorize.Net addon settings.
		 *
		 * If a credit card field has been added then reveal the settings,
		 * otherwise hide them.
		 *
		 * @since 1.0.0
		 */
		settingsDisplay: function() {

			const $alert   = $( '#authorize_net-credit-card-alert' );
			const $content = $( '#authorize_net-provider' );

			if ( $( '.wpforms-field-option-authorize_net' ).length ) {
				$alert.hide();
				$content.find( '.wpforms-panel-field, .wpforms-conditional-block-panel, h2' ).show();
			} else {
				$alert.show();
				$content.find( '.wpforms-panel-field, .wpforms-conditional-block-panel, h2' ).hide();
				$content.find( '#wpforms-panel-field-authorize_net-enable' ).prop( 'checked', false );
			}
		},

		/**
		 * Toggles the visibility of the related settings.
		 *
		 * @since 1.4.0
		 */
		settingsConditions: function() {

			$( '#wpforms-panel-field-authorize_net-enable' ).conditions( {
				conditions: {
					element: '#wpforms-panel-field-authorize_net-enable',
					type: 'checked',
					operator: 'is',
				},
				actions: {
					if: {
						element: '.wpforms-panel-content-section-authorize_net-body',
						action: 'show',
					},
					else: {
						element: '.wpforms-panel-content-section-authorize_net-body',
						action:  'hide',
					},
				},
				effect: 'appear',
			} );

			$( '#wpforms-panel-field-authorize_net-recurring-enable' ).conditions( {
				conditions: {
					element: '#wpforms-panel-field-authorize_net-recurring-enable',
					type: 'checked',
					operator: 'is',
				},
				actions: {
					if: {
						element: '#wpforms-panel-field-authorize_net-recurring-period-wrap,#wpforms-panel-field-authorize_net-recurring-conditional_logic-wrap,#wpforms-conditional-groups-payments-authorize_net-recurring,#wpforms-panel-field-authorize_net-recurring-email-wrap,#wpforms-panel-field-authorize_net-recurring-name-wrap,#wpforms-panel-field-authorize_net-recurring-customer_name-wrap,#wpforms-panel-field-authorize_net-recurring-customer_billing_address-wrap',
						action: 'show',
					},
					else: {
						element: '#wpforms-panel-field-authorize_net-recurring-period-wrap,#wpforms-panel-field-authorize_net-recurring-conditional_logic-wrap,#wpforms-conditional-groups-payments-authorize_net-recurring,#wpforms-panel-field-authorize_net-recurring-email-wrap,#wpforms-panel-field-authorize_net-recurring-name-wrap,#wpforms-panel-field-authorize_net-recurring-customer_name-wrap,#wpforms-panel-field-authorize_net-recurring-customer_billing_address-wrap',
						action:  'hide',
					},
				},
				effect: 'appear',
			} );
		},

		/**
		 * On form save notify users about required fields.
		 *
		 * @since 1.0.0
		 */
		requiredFieldsCheck: function() {

			if ( ! app.isPaymentsEnabled() ) {
				return;
			}

			if ( ! app.isRecurringPaymentsEnabled() ) {
				return;
			}

			if (
				$( '#wpforms-panel-field-authorize_net-recurring-email' ).val() &&
				$( '#wpforms-panel-field-authorize_net-recurring-customer_name' ).val()
			) {
				return;
			}

			$.alert( {
				title: wpforms_builder.heads_up,
				content: wpforms_builder.authorize_net_recurring_name_email_required,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * On form save notify users about name field having wrong format.
		 *
		 * @since 1.0.0
		 */
		nameFormatCheck: function() {

			if ( ! app.isPaymentsEnabled() ) {
				return;
			}

			let displayWarning = false;

			const $name = $( '#wpforms-field-' + $( '#wpforms-panel-field-authorize_net-customer_name' ).val() );

			if ( $name.length && ! $name.find( '.format-selected-first-last' ).length ) {
				displayWarning = true;
			}

			if ( app.isRecurringPaymentsEnabled() ) {
				const $nameRecurring = $( '#wpforms-field-' + $( '#wpforms-panel-field-authorize_net-recurring-customer_name' ).val() );

				if ( $nameRecurring.length && ! $nameRecurring.find( '.format-selected-first-last' ).length ) {
					displayWarning = true;
				}
			}

			if ( ! displayWarning ) {
				return;
			}

			$.alert( {
				title: wpforms_builder.heads_up,
				content: wpforms_builder.authorize_net_name_format_required,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * On form save notify users if Authorize.Net payments are not enabled.
		 *
		 * @since 1.0.0
		 */
		paymentsEnabledCheck() {
			if ( ! $( '.wpforms-field.wpforms-field-authorize_net' ).length ) {
				return;
			}

			if ( app.isPaymentsEnabled() ) {
				return;
			}

			// Check if the keys are missing.
			// This case is possible if the A.NET keys was deleted/expired after creating the form.
			app.keysCheck();

			$.alert( {
				title  : wpforms_builder.heads_up,
				content: wpforms_builder.authorize_net_payments_enabled_required,
				icon   : 'fa fa-exclamation-circle',
				type   : 'orange',
				buttons: {
					confirm: {
						text    : wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * On adding Card field notify users if Authorize.Net keys are missing.
		 *
		 * @since 1.0.0
		 */
		keysCheck() {
			if ( ! el.$addFieldButton.hasClass( 'authorize_net-keys-required' ) ) {
				return;
			}

			$.alert( {
				title: wpforms_builder.heads_up,
				content: wpforms_builder.authorize_net_keys_required,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * The `wpformsFieldAdd` event handler.
		 *
		 * @since 1.9.0
		 *
		 * @param {Object} e    Event object.
		 * @param {number} id   Field ID.
		 * @param {string} type Field type.
		 */
		fieldAdded( e, id, type ) {
			if ( type !== 'authorize_net' ) {
				return;
			}

			app.paymentsEnabledCheck();
			app.disableAddCardButton( e, id, type );
		},

		/**
		 * Disable "Add Card" button in the fields list.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} e    Event object.
		 * @param {number} id   Field ID.
		 * @param {string} type Field type.
		 */
		disableAddCardButton( e, id, type ) {
			if ( type !== 'authorize_net' ) {
				return;
			}

			el.$addFieldButton.prop( 'disabled', true );
		},

		/**
		 * Enable "Add Card" button in the fields list.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} e    Event object.
		 * @param {number} id   Field ID.
		 * @param {string} type Field type.
		 */
		enableAddCardButton( e, id, type ) {
			if ( type === 'authorize_net' ) {
				el.$addFieldButton.prop( 'disabled', false );
			}
		},

		/**
		 * Disable notifications.
		 *
		 * @since 1.1.0
		 *
		 * @param {Object} e    Event object.
		 * @param {number} id   Field ID.
		 * @param {string} type Field type.
		 */
		disableNotifications( e, id, type ) {
			if ( type === 'authorize_net' ) {
				const $notificationWrap = $( '.wpforms-panel-content-section-notifications [id*="-authorize_net-wrap"]' );

				$notificationWrap.find( 'input[id*="-authorize_net"]' ).prop( 'checked', false );
				$notificationWrap.addClass( 'wpforms-hidden' );
			}
		},

		/**
		 * Check if payments are enabled in the Form Settings.
		 *
		 * @since 1.0.0
		 *
		 * @return {boolean} Payments are enabled.
		 */
		isPaymentsEnabled() {
			return el.$enablePayment.is( ':checked' );
		},

		/**
		 * Check if recurring payments are enabled in the Form Settings.
		 *
		 * @since 1.0.0
		 *
		 * @return {boolean} Recurring payments are enabled.
		 */
		isRecurringPaymentsEnabled() {
			return $( '#wpforms-panel-field-authorize_net-recurring-enable' ).is( ':checked' );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsBuilderAuthorizeNet.init();
