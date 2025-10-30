<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Hookable;

abstract class Migration extends Hookable {
	public static function future_version(): string {
		return sprintf( '%1$s.%1$s.%1$s', PHP_INT_MIN );
	}

	abstract public function introduced_version(): string;

	abstract public function migrate(): void;

	public function migrate_cpt_settings( Cpt_Settings $cpt_settings ): void {
	}
}
