<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;

interface Version_Migration extends Migration {
	public function introduced_version(): string;

	public function get_order(): int;
}
