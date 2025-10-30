<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;

final class Migration_3_8_0 extends Migration {
	public function introduced_version(): string {
		return '3.8.0';
	}

	public function migrate(): void {
		// fixme
		var_dump( '3.8.0 here' );
		exit;
	}
}
