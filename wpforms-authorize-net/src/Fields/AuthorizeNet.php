<?php

namespace WPFormsAuthorizeNet\Fields;

use WPForms_Field;
use WPFormsAuthorizeNet\Helpers;

/**
 * Authorize.Net credit card field.
 *
 * @since 1.0.0
 */
class AuthorizeNet extends WPForms_Field {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information.
		$this->name     = esc_html__( 'Authorize.Net', 'wpforms-authorize-net' );
		$this->keywords = esc_html__( 'store, ecommerce, credit card, pay, payment, debit card', 'wpforms-authorize-net' );
		$this->type     = 'authorize_net';
		$this->icon     = 'fa-credit-card';
		$this->order    = 95;
		$this->group    = 'payment';

		$this->hooks();
	}

	/**
	 * Field specific hooks.
	 *
	 * @since 1.10.0
	 *
	 * @return void
	 */
	private function hooks(): void {

		add_filter( 'wpforms_field_properties_authorize_net', [ $this, 'field_properties' ], 5, 3 );
		add_filter( 'wpforms_field_new_required', [ $this, 'default_required' ], 10, 2 );
		add_filter( 'wpforms_builder_field_button_attributes', [ $this, 'field_button_attributes' ], 10, 3 );
		add_filter( 'wpforms_field_new_display_duplicate_button', [ $this, 'field_display_duplicate_button' ], 10, 2 );
		add_filter( 'wpforms_field_preview_display_duplicate_button', [ $this, 'field_display_duplicate_button' ], 10, 2 );
		add_filter( 'wpforms_pro_fields_entry_preview_is_field_support_preview_authorize_net_field', [ $this, 'entry_preview_availability' ] );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Remove primary for expanded formats since we have first, middle, last.
		unset( $properties['inputs']['primary'], $properties['label']['attr']['for'] );

		$form_id  = absint( $form_data['id'] );
		$field_id = absint( $field['id'] );

		$props      = [
			'inputs' => [
				'number' => [
					'attr'     => [
						'name'         => '',
						'value'        => '',
						'placeholder'  => ! empty( $field['cardnumber_placeholder'] ) ? $field['cardnumber_placeholder'] : '',
						'autocomplete' => 'off',
					],
					'block'    => [
						'wpforms-field-authorize_net-number',
					],
					'class'    => [
						'wpforms-field-authorize_net-cardnumber',
					],
					'data'     => [
						'rule-creditcard' => 'yes',
					],
					'id'       => "wpforms-{$form_id}-field_{$field_id}",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden'   => ! empty( $field['sublabel_hide'] ),
						'value'    => esc_html__( 'Card Number', 'wpforms-authorize-net' ),
						'position' => 'after',
					],
				],
				'cvc'    => [
					'attr'     => [
						'name'         => '',
						'value'        => '',
						'placeholder'  => ! empty( $field['cardcvc_placeholder'] ) ? $field['cardcvc_placeholder'] : '',
						'maxlength'    => '4',
						'autocomplete' => 'off',
					],
					'block'    => [
						'wpforms-field-authorize_net-code',
					],
					'class'    => [
						'wpforms-field-authorize_net-cardcvc',
					],
					'data'     => [],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-cardcvc",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden'   => ! empty( $field['sublabel_hide'] ),
						'value'    => esc_html__( 'Security Code', 'wpforms-authorize-net' ),
						'position' => 'after',
					],
				],
				'month'  => [
					'attr'     => [
						'aria-label' => esc_html__( 'Expiration month', 'wpforms-authorize-net' ),
					],
					'block'    => [
						'wpforms-field-authorize_net-month',
					],
					'class'    => [
						'wpforms-field-authorize_net-cardmonth',
					],
					'data'     => [],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-cardmonth",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden'   => ! empty( $field['sublabel_hide'] ),
						'value'    => esc_html__( 'Expiration', 'wpforms-authorize-net' ),
						'position' => 'after',
					],
				],
				'year'   => [
					'attr'     => [
						'aria-label' => esc_html__( 'Expiration year', 'wpforms-authorize-net' ),
					],
					'block'    => [
						'wpforms-field-authorize_net-year',
					],
					'class'    => [
						'wpforms-field-authorize_net-cardyear',
					],
					'data'     => [],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-cardyear",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
				],
			],
		];
		$properties = array_merge_recursive( $properties, $props );

		// If this field is required, we need to make some adjustments.
		if ( ! empty( $field['required'] ) ) {

			// Add required class if needed (for multipage validation).
			$properties['inputs']['number']['class'][] = 'wpforms-field-required';
			$properties['inputs']['cvc']['class'][]    = 'wpforms-field-required';
			$properties['inputs']['month']['class'][]  = 'wpforms-field-required';
			$properties['inputs']['year']['class'][]   = 'wpforms-field-required';

			// Below, we add our input special classes if certain fields are required.
			// The jQuery Validation library will not correctly validate fields that do not have a name attribute,
			// So, we use the `wpforms-input-temp-name` class to let jQuery know
			// we should add a temporary name attribute before validation is initialized.
			// Then, remove it before the form submits.
			$properties['inputs']['number']['class'][] = 'wpforms-input-temp-name';
			$properties['inputs']['cvc']['class'][]    = 'wpforms-input-temp-name';
			$properties['inputs']['month']['class'][]  = 'wpforms-input-temp-name';
			$properties['inputs']['year']['class'][]   = 'wpforms-input-temp-name';
		}

		return $properties;
	}

	/**
	 * Disallow dynamic population.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_dynamic_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Disallow fallback population.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_fallback_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Default to the required.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $required Required status, true is required.
	 * @param array $field    Field settings.
	 *
	 * @return bool
	 */
	public function default_required( $required, $field ) {

		if ( $field['type'] === 'authorize_net' ) {
			return true;
		}

		return $required;
	}

	/**
	 * Define additional "Add Field" button attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Add Field button attributes.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_button_attributes( $attributes, $field, $form_data ) {

		if ( $field['type'] !== 'authorize_net' ) {
			return $attributes;
		}

		if ( ! Helpers::has_authorize_net_keys() ) {
			$attributes['class'][] = 'warning-modal';
			$attributes['class'][] = 'authorize_net-keys-required';
		}

		if ( Helpers::has_authorize_net_field( $form_data ) ) {
			$attributes['atts']['disabled'] = 'true';
		}

		return $attributes;
	}

	/**
	 * Disallow field preview "Duplicate" button.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $display Display switch.
	 * @param array $field   Field settings.
	 *
	 * @return bool
	 */
	public function field_display_duplicate_button( $display, $field ) {

		return $field['type'] === 'authorize_net' ? false : $display;
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {
		/*
		 * Basic field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'basic-options', $field, $args );

		// Label.
		$this->field_option( 'label', $field );

		// Description.
		$this->field_option( 'description', $field );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'basic-options', $field, $args );

		/*
		 * Advanced field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'advanced-options', $field, $args );

		// Size.
		$this->field_option( 'size', $field );

		// Card Number.
		$cardnumber_placeholder = ! empty( $field['cardnumber_placeholder'] ) ? esc_attr( $field['cardnumber_placeholder'] ) : '';

		printf( '<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-cardnumber" id="wpforms-field-option-row-%d-cardnumber" data-subfield="cardnumber" data-field-id="%d">', absint( $field['id'] ), absint( $field['id'] ) );
			$this->field_element(
				'label',
				$field,
				[
					'slug'  => 'cardnumber_placeholder',
					'value' => esc_html__( 'Card Number Placeholder Text', 'wpforms-authorize-net' ),
				]
			);
			echo '<div class="placeholder">';
				printf( '<input type="text" class="placeholder-update" id="wpforms-field-option-%d-cardnumber_placeholder" name="fields[%d][cardnumber_placeholder]" value="%s" data-field-id="%d" data-subfield="authorize_net-cardnumber">', absint( $field['id'] ), absint( $field['id'] ), esc_html( $cardnumber_placeholder ), absint( $field['id'] ) );
			echo '</div>';
		echo '</div>';

		// CVC/Security Code.
		$cardcvc_placeholder = ! empty( $field['cardcvc_placeholder'] ) ? esc_attr( $field['cardcvc_placeholder'] ) : '';

		printf( '<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-cvc" id="wpforms-field-option-row-%d-cvc" data-subfield="cvc" data-field-id="%d">', absint( $field['id'] ), absint( $field['id'] ) );
			$this->field_element(
				'label',
				$field,
				[
					'slug'  => 'cardcvc_placeholder',
					'value' => esc_html__( 'Security Code Placeholder Text', 'wpforms-authorize-net' ),
				]
			);
			echo '<div class="placeholder">';
				printf( '<input type="text" class="placeholder-update" id="wpforms-field-option-%d-cardcvc_placeholder" name="fields[%d][cardcvc_placeholder]" value="%s" data-field-id="%d" data-subfield="authorize_net-cardcvc">', absint( $field['id'] ), absint( $field['id'] ), esc_html( $cardcvc_placeholder ), absint( $field['id'] ) );
			echo '</div>';
		echo '</div>';

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Hide Label.
		$this->field_option( 'label_hide', $field );

		// Hide sublabels.
		$this->field_option( 'sublabel_hide', $field );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'advanced-options', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$number_placeholder = ! empty( $field['cardnumber_placeholder'] ) ? esc_attr( $field['cardnumber_placeholder'] ) : '';
		$cvc_placeholder    = ! empty( $field['cardcvc_placeholder'] ) ? esc_attr( $field['cardcvc_placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );
		?>

		<div class="format-selected format-selected-full">

			<div class="wpforms-field-row">
				<div class="wpforms-authorize_net-cardnumber">
					<input type="text" placeholder="<?php echo esc_attr( $number_placeholder ); ?>" readonly>
					<label class="wpforms-sub-label"><?php esc_html_e( 'Card Number', 'wpforms-authorize-net' ); ?></label>
				</div>
			</div>

			<div class="wpforms-field-row">

				<div class="wpforms-field-authorize_net-container">
					<div class="wpforms-authorize_net-expiration">
						<div class="wpforms-authorize_net-cardmonth">
							<select readonly>
								<option>MM</option>
							</select>
							<label class="wpforms-sub-label"><?php esc_html_e( 'Expiration', 'wpforms-authorize-net' ); ?></label>
						</div>
						<span>/</span>
						<div class="wpforms-authorize_net-cardyear">
							<select readonly>
								<option>YY</option>
							</select>
						</div>
					</div>

					<div class="wpforms-authorize_net-cardcvc">
						<input type="text" placeholder="<?php echo esc_attr( $cvc_placeholder ); ?>" readonly>
						<label class="wpforms-sub-label"><?php esc_html_e( 'Security Code', 'wpforms-authorize-net' ); ?></label>
					</div>
				</div>

			</div>

		</div>

		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated field attributes. Use field properties.
	 * @param array $form_data  Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Define data.
		$number = ! empty( $field['properties']['inputs']['number'] ) ? $field['properties']['inputs']['number'] : [];
		$cvc    = ! empty( $field['properties']['inputs']['cvc'] ) ? $field['properties']['inputs']['cvc'] : [];
		$month  = ! empty( $field['properties']['inputs']['month'] ) ? $field['properties']['inputs']['month'] : [];
		$year   = ! empty( $field['properties']['inputs']['year'] ) ? $field['properties']['inputs']['year'] : [];

		// Display warning for non SSL pages.
		if ( ! is_ssl() ) {
			echo '<div class="wpforms-cc-warning wpforms-error-alert">';
			esc_html_e( 'This page is insecure. Credit Card field should be used for testing purposes only.', 'wpforms-authorize-net' );
			echo '</div>';
		}

		if ( ! Helpers::has_authorize_net_keys() ) {
			echo '<div class="wpforms-cc-warning wpforms-error-alert">';
			esc_html_e( 'Credit Card field is disabled, Authorize.Net keys are missing.', 'wpforms-authorize-net' );
			echo '</div>';

			return;
		}

		if ( ! Helpers::is_authorize_net_enabled( $form_data ) ) {
			echo '<div class="wpforms-cc-warning wpforms-error-alert">';
			esc_html_e( 'Credit Card field is disabled, Authorize.Net payments are not enabled in the form settings.', 'wpforms-authorize-net' );
			echo '</div>';

			return;
		}

		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

			// Card number.
			echo '<div ' . wpforms_html_attributes( false, $number['block'] ) . '>';
				$this->field_display_sublabel( 'number', 'before', $field );
				printf(
					'<input type="text" %s %s>',
					wpforms_html_attributes( $number['id'], $number['class'], $number['data'], $number['attr'] ),
					esc_attr( $number['required'] )
				);
				$this->field_display_sublabel( 'number', 'after', $field );
				$this->field_display_error( 'number', $field );
			echo '</div>';

		echo '</div>';

		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

			echo '<div class="wpforms-field-authorize_net-container">';

				// Expiration.
				echo '<div class="wpforms-field-authorize_net-expiration">';

					// Month.
					echo '<div ' . wpforms_html_attributes( false, $month['block'] ) . '>';
						$this->field_display_sublabel( 'month', 'before', $field );
						printf(
							'<select %s %s>',
							wpforms_html_attributes( $month['id'], $month['class'], $month['data'], $month['attr'] ),
							esc_attr( $month['required'] )
						);
						echo '<option class="placeholder" selected disabled value="">MM</option>';

						foreach ( range( 1, 12 ) as $number ) {
							printf( '<option value="%d">%d</option>', absint( $number ), absint( $number ) );
						}

						echo '</select>';
						$this->field_display_sublabel( 'month', 'after', $field );
						$this->field_display_error( 'month', $field );
					echo '</div>';

					// Sep.
					echo '<span>/</span>';

					// Year.
					echo '<div ' . wpforms_html_attributes( false, $year['block'] ) . '>';
						$this->field_display_sublabel( 'year', 'before', $field );
						printf(
							'<select %s %s>',
							wpforms_html_attributes( $year['id'], $year['class'], $year['data'], $year['attr'] ),
							esc_attr( $year['required'] )
						);
						echo '<option class="placeholder" selected disabled value="">YY</option>';

						$year_from = gmdate( 'y' );
						$year_to   = $year_from + 11;

						for ( $i = $year_from; $i < $year_to; $i++ ) {
							printf( '<option value="%d">%d</option>', absint( $i ), absint( $i ) );
						}

						echo '</select>';
						$this->field_display_sublabel( 'year', 'after', $field );
						$this->field_display_error( 'year', $field );
					echo '</div>';

				echo '</div>';

				// CVC.
				echo '<div ' . wpforms_html_attributes( false, $cvc['block'] ) . '>';
					$this->field_display_sublabel( 'cvc', 'before', $field );
					printf(
						'<input type="text" %s %s>',
						wpforms_html_attributes( $cvc['id'], $cvc['class'], $cvc['data'], $cvc['attr'] ),
						esc_attr( $cvc['required'] )
					);
					$this->field_display_sublabel( 'cvc', 'after', $field );
					$this->field_display_error( 'cvc', $field );
				echo '</div>';

			echo '</div>';

		echo '</div>';
	}

	/**
	 * Currently validation happens on the front end. We do not do
	 * generic server-side validation because we do not allow the card
	 * details to POST to the server.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {}

	/**
	 * Format field.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		// Define data.
		$name = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? $form_data['fields'][ $field_id ]['label'] : '';

		// Set final field details.
		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => sanitize_text_field( $name ),
			'value' => '',
			'id'    => absint( $field_id ),
			'type'  => $this->type,
		];
	}

	/**
	 * The field value availability for the entry preview field.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value The submitted Credit Card detail.
	 *
	 * @return bool
	 */
	public function entry_preview_availability( $value ) {

		return ! empty( $value );
	}
}
