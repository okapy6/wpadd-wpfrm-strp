<?php

namespace WPFormsAuthorizeNet\Migrations;

use WPForms\Migrations\Base;

/**
 * Class Migrations handles addon upgrade routines.
 *
 * @since 1.3.0
 */
class Migrations extends Base {

	/**
	 * WP option name to store the migration versions.
	 *
	 * @since 1.3.0
	 */
	const MIGRATED_OPTION_NAME = 'wpforms_authorize_net_versions';

	/**
	 * Current plugin version.
	 *
	 * @since 1.3.0
	 */
	const CURRENT_VERSION = WPFORMS_AUTHORIZE_NET_VERSION;

	/**
	 * Name of plugin used in log messages.
	 *
	 * @since 1.3.0
	 */
	const PLUGIN_NAME = 'WPForms Authorize.Net';

	/**
	 * Upgrade classes.
	 *
	 * @since 1.3.0
	 */
	const UPGRADE_CLASSES = [
		'Upgrade130',
	];
}
