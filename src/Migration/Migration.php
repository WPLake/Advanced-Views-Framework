<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Migration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Parents\Hookable;

abstract class Migration extends Hookable {
	abstract public function introduced_at_version(): string;

	abstract public function migrate(): void;
}
