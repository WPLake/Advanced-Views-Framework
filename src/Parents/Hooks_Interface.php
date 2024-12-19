<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Org\Wplake\Advanced_Views\Current_Screen;

defined( 'ABSPATH' ) || exit;

interface Hooks_Interface {
	public function set_hooks( Current_Screen $current_screen ): void;
}
