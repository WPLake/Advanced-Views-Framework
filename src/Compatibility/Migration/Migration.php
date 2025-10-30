<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Hookable;

abstract class Migration extends Hookable {
	abstract public function introduced_version(): string;

	abstract public function migrate(): void;

	public function migrate_cpt_settings( Cpt_Settings $cpt_settings ): void {
	}
}
