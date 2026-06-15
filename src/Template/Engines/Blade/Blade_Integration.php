<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\ACE_Mods;
use Org\Wplake\Advanced_Views\Template\Integration\Template_Integration_Base;

final class Blade_Integration extends Template_Integration_Base {
	public function get_provocative_symbols_map(): array {
		return array();
	}

	public function get_ace_mode(): string {
		return ACE_Mods::TWIG;
	}
}
