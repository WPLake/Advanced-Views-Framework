<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Base;

use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

defined( 'ABSPATH' ) || exit;

interface Hooks_Interface {
	public function set_hooks( Route_Detector $route_detector ): void;
}
