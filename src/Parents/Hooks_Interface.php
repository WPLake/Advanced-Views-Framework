<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Org\Wplake\Advanced_Views\Utils\Route_Detector;

defined( 'ABSPATH' ) || exit;

interface Hooks_Interface {
	public function set_hooks( Route_Detector $route_detector ): void;
}
