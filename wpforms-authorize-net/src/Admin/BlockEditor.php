<?php

namespace WPFormsAuthorizeNet\Admin;

/**
 * Integration with Block Editor.
 *
 * @since 1.5.0
 */
class BlockEditor {

	/**
	 * Handle name for wp_register_styles handle.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	const HANDLE = 'wpforms-authorize-net';

	/**
	 * Indicate if is allowed to load.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function allow_load() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit';
	}

	/**
	 * Initialize.
	 *
	 * @since 1.5.0
	 */
	public function init() {

		if ( $this->allow_load() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.5.0
	 */
	public function hooks() {

		// Set editor style for block type editor. Must run at 20 in add-ons.
		add_filter( 'register_block_type_args', [ $this, 'register_block_type_args' ], 20, 2 );
	}


	/**
	 * Set editor style for block type editor.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $args       Array of arguments for registering a block type.
	 * @param string $block_type Block type name including namespace.
	 */
	public function register_block_type_args( $args, $block_type ) {

		if ( $block_type !== 'wpforms/form-selector' ) {
			return $args;
		}

		$min = wpforms_get_min_suffix();

		// CSS.
		wp_register_style(
			self::HANDLE,
			WPFORMS_AUTHORIZE_NET_URL . "assets/css/wpforms-authorize-net{$min}.css",
			[ $args['editor_style'] ],
			WPFORMS_AUTHORIZE_NET_VERSION
		);

		$args['editor_style'] = self::HANDLE;

		return $args;
	}

	/**
	 * Load enqueues for the Gutenberg editor.
	 *
	 * @since 1.5.0
	 * @deprecated 1.9.0
	 */
	public function gutenberg_enqueues() {

		_deprecated_function( __METHOD__, '1.9.0 of the WPForms Authorize.Net addon.' );

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			self::HANDLE,
			WPFORMS_AUTHORIZE_NET_URL . "assets/css/wpforms-authorize-net{$min}.css",
			[],
			WPFORMS_AUTHORIZE_NET_VERSION
		);
	}
}
