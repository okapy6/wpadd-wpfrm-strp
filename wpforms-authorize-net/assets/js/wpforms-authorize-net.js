/* global Accept, wpforms_authorize_net */

'use strict';

/**
 * WPForms Authorize.Net function.
 *
 * @since 1.0.0
 */
const WPFormsAuthorizeNet = window.WPFormsAuthorizeNet || ( function( document, window, $ ) {

	/**
	 * Holder for original form submit handler.
	 *
	 * @since 1.4.0
	 *
	 * @type {Function}
	 */
	let originalSubmitHandler;

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

			$( document )
				.on( 'wpformsReady', app.updateSubmitHandler )
				.on( 'wpformsBeforePageChange', app.pageChange );

			// Document ready.
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.1.0
		 */
		ready: function() {

			app.loadValidation();
		},

		/**
		 * Update submitHandler for forms containing Authorize.Net.
		 *
		 * @since 1.0.0
		 */
		updateSubmitHandler: function() {

			if ( typeof $.fn.validate === 'undefined' ) {
				return;
			}

			$( '.wpforms-authorize_net form' )
				.filter( ( _, form ) => typeof $( form ).data( 'formid' ) === 'number' ) // filter out forms which are locked (formid changed to 'locked-...').
				.each( function() {

					const validator = $( this ).data( 'validator' );

					if ( ! validator ) {
						return true;
					}

					// Store the original submitHandler.
					originalSubmitHandler = validator.settings.submitHandler;

					// Replace the default submit handler.
					validator.settings.submitHandler = app.submitHandler;
				} );
		},

		/**
		 * Update submitHandler for forms containing Authorize.Net.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} form JS form element.
		 */
		submitHandler: function( form ) {

			const $form = $( form );

			const validator   = $form.data( 'validator' );
			const isValidForm = ! ( validator && validator.numberOfInvalids() > 0 );

			// Omit sending card data to Access.js and call the original SubmitHandler
			// if the form is valid (not required) and card data is incomplete.
			if ( isValidForm && ! app.isFieldFilled( $form ) ) {
				originalSubmitHandler( $form );

				return;
			}

			app.disableSubmitBtn( $form );
			app.removeErrorMessages( $form );

			const cardData = app.getCardData( $form );
			const authData = app.getAuthData();

			Accept.dispatchData( { cardData: cardData, authData: authData }, function( response ) {

				app.apiResponseHandler( $form, response, cardData );
			} );
		},

		/**
		 * Callback for a page changing.
		 * Validate card data before page changing.
		 *
		 * @since 1.5.0
		 *
		 * @param {Event}  event    Event object.
		 * @param {int}    nextPage Next page number.
		 * @param {jQuery} $form    Form element.
		 * @param {string} action   Action name.
		 */
		pageChange: function( event, nextPage, $form, action ) {

			const cardForm = $form.find( '.wpforms-field-authorize_net' );

			// Bail if returning to previous page or current page does not contain Authorize.Net field.
			if ( action === 'prev' || ! cardForm.is( ':visible' ) ) {
				return;
			}

			// Bail if the field is not required and is empty.
			if ( ! cardForm.data( 'required' ) && ! app.isFieldFilled( $form ) ) {
				return;
			}

			app.removeErrorMessages( $form );

			const authData = app.getAuthData();
			const cardData = app.getCardData( $form );

			Accept.dispatchData( { cardData: cardData, authData: authData }, function( response ) {

				if ( response.messages.resultCode === 'Error' ) {
					app.displayApiErrorMessage( $form, response.messages.message );
					event.preventDefault();
				}
			} );
		},

		/**
		 * Determine if card fields is not empty.
		 *
		 * @since 1.5.0
		 *
		 * @param {jQuery} $form Form element.
		 *
		 * @returns {boolean} True if card data is not empty.
		 */
		isFieldFilled: function( $form ) {

			const cardData = app.getCardData( $form );

			return cardData.cardNumber || cardData.cardCode || cardData.year || cardData.month;
		},

		/**
		 * Get card data from form.
		 *
		 * @since 1.5.0
		 *
		 * @param {jQuery} $form Form element.
		 *
		 * @returns {object} Card data.
		 */
		getCardData: function( $form ) {

			return {
				cardNumber: $form.find( '.wpforms-field-authorize_net-cardnumber' ).val().replace( /\s+/g, '' ),
				month:      $form.find( '.wpforms-field-authorize_net-cardmonth' ).val() || '',
				year:       $form.find( '.wpforms-field-authorize_net-cardyear' ).val() || '',
				cardCode:   $form.find( '.wpforms-field-authorize_net-cardcvc' ).val(),
			};
		},

		/**
		 * Get Authorize.Net API auth data.
		 *
		 * @since 1.5.0
		 *
		 * @returns {object} Auth data.
		 */
		getAuthData: function() {

			return {
				clientKey:  wpforms_authorize_net.public_client_key,
				apiLoginID: wpforms_authorize_net.api_login_id,
			};
		},

		/**
		 * Handle Authorize.Net API response.
		 *
		 * @since 1.0.0
		 *
		 * @param {jQuery} $form Form element.
		 * @param {object} response API response.
		 * @param {object} cardData Card data.
		 */
		apiResponseHandler: function( $form, response, cardData ) {

			if ( response.messages.resultCode === 'Error' ) {
				app.displayApiErrorMessage( $form, response.messages.message );
				app.enableSubmitBtn( $form );

				return;
			}

			$form.append( '<input type="hidden" name="wpforms[authorize_net][opaque_data][descriptor]" value="' + app.escapeTextString( response.opaqueData.dataDescriptor ) + '">' );
			$form.append( '<input type="hidden" name="wpforms[authorize_net][opaque_data][value]" value="' + app.escapeTextString( response.opaqueData.dataValue ) + '">' );
			$form.append( '<input type="hidden" name="wpforms[authorize_net][card_data][expire]" value="' + app.escapeTextString( `${cardData.month}/${cardData.year}` ) + '">' );

			app.submitForm( $form );
		},

		/**
		 * Disable submit button for the form.
		 *
		 * @since 1.0.0
		 *
		 * @param {jQuery} $form Form element.
		 */
		disableSubmitBtn: function( $form ) {

			$form.find( '.wpforms-submit' ).prop( 'disabled', true );
		},

		/**
		 * Enable submit button for the form.
		 *
		 * @since 1.0.0
		 *
		 * @param {jQuery} $form Form element.
		 */
		enableSubmitBtn: function( $form ) {

			$form.find( '.wpforms-submit' ).prop( 'disabled', false );
		},

		/**
		 * Display Authorize.Net API error message for the form.
		 *
		 * @since 1.0.0
		 *
		 * @param {jQuery} $form Form element.
		 * @param {object} message API error message.
		 */
		displayApiErrorMessage: function( $form, message ) {

			const $field          = $form.find( '.wpforms-field-authorize_net' );
			const $errorContainer = $( '<div class="wpforms-error-alert wpforms-error-authorize-net"></div>' );
			const messageCount    = message.length;

			for ( let i = 0; i < messageCount; i++ ) {
				$errorContainer.append( '<p>' + app.escapeTextString( message[ i ].text ) + '</p>' );
				console.log( '%cWPForms Authorize.Net Debug: ', 'color: #cd6622;', message[ i ].code + ': ' + message[ i ].text );
			}

			$field.append( $errorContainer );
		},

		/**
		 * Remove Authorize.Net error messages for the form.
		 *
		 * @since 1.5.0
		 *
		 * @param {jQuery} $form Form element.
		 */
		removeErrorMessages: function( $form ) {

			$form.find( '.wpforms-error-authorize-net' ).remove();
			$form.find( '.wpforms-error-container' ).remove();
		},

		/**
		 * Submit the form using the original submitHandler.
		 *
		 * @since 1.0.0
		 *
		 * @param {jQuery} $form Form element.
		 */
		submitForm: function( $form ) {

			const validator = $form.data( 'validator' );

			if ( validator ) {
				originalSubmitHandler( $form );
			}
		},

		/**
		 * Load card validation.
		 *
		 * @since 1.0.0
		 */
		loadValidation: function() {

			// Credit card validation.
			if ( typeof $.fn.payment !== 'undefined' ) {
				$( '.wpforms-field-authorize_net-cardnumber' ).payment( 'formatCardNumber' );
				$( '.wpforms-field-authorize_net-cardcvc' ).payment( 'formatCardCVC' );
			}
		},

		/**
		 * Replaces &, <, >, ", `, and ' with their escaped counterparts.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} string String to escape.
		 *
		 * @returns {string} Escaped string.
		 */
		escapeTextString: function( string ) {

			return $( '<span></span>' ).text( string ).html();
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsAuthorizeNet.init();
