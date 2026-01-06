<?php

namespace WPFormsAuthorizeNet\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Tasks\Actions\Migration175Task;

/**
 * Class Authorize.Net addon v1.3.0 upgrade.
 *
 * @since 1.4.0
 *
 * @noinspection PhpUnused
 */
class Upgrade130 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 1.4.0
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run() {

		return $this->run_async( Migration175Task::class );
	}
}
