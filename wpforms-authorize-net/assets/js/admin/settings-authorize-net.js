/* global wpforms_admin */

'use strict';

/**
 * WPForms Authorize.Net settings function.
 *
 * @since 1.0.0
 */
const WPFormsSettingsAuthorizeNet = window.WPFormsSettingsAuthorizeNet || ( function( document, window, $ ) {

	/**
	 * Elements.
	 *
	 * @since 1.0.0
	 *
	 * @type {object}
	 */
	const $el = {
		testModeCheckbox: $( '#wpforms-setting-authorize_net-test-mode' ),
		liveConnectionBlock: $( '#wpforms-setting-row-authorize_net-live-api-login-id, #wpforms-setting-row-authorize_net-live-transaction-key' ),
		testConnectionBlock: $( '#wpforms-setting-row-authorize_net-test-api-login-id,#wpforms-setting-row-authorize_net-test-transaction-key' ),
		liveConnectionStatusBlock: $( '#wpforms-setting-row-authorize_net-connection-status-live' ),
		testConnectionStatusBlock: $( '#wpforms-setting-row-authorize_net-connection-status-test' ),
		liveInputs: $( '#wpforms-setting-authorize_net-live-api-login-id, #wpforms-setting-authorize_net-live-transaction-key' ),
		testInputs: $( '#wpforms-setting-authorize_net-test-api-login-id, #wpforms-setting-authorize_net-test-transaction-key' ),
	};

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
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.0.0
		 */
		events: function() {

			$el.testModeCheckbox.change( app.credentialsFieldsDisplay );
		},

		/**
		 * Conditionally show Authorize.Net mode switch warning.
		 *
		 * @since 1.0.0
		 */
		credentialsFieldsDisplay: function() {

			const testModeEnabled = $el.testModeCheckbox.is( ':checked' );

			if ( testModeEnabled ) {
				$el.liveConnectionBlock.hide();
				$el.testConnectionBlock.show();
				$el.liveConnectionStatusBlock.hide();
				$el.testConnectionStatusBlock.show();
			} else {
				$el.liveConnectionBlock.show();
				$el.testConnectionBlock.hide();
				$el.liveConnectionStatusBlock.show();
				$el.testConnectionStatusBlock.hide();
			}

			if ( testModeEnabled && $el.testInputs.val() ) {
				return;
			}

			if ( ! testModeEnabled && $el.liveInputs.val() ) {
				return;
			}

			$.alert( {
				title: wpforms_admin.heads_up,
				content: wpforms_admin.authorize_net_mode_update,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_admin.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsSettingsAuthorizeNet.init();
