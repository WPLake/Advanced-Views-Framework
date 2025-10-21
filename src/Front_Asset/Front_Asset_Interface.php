<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;

defined( 'ABSPATH' ) || exit;

interface Front_Asset_Interface {
	public function enqueue_active(): string;

	public function get_auto_discover_name(): string;

	/**
	 * @return array{css:array<string,string>,js:array<string,string>}
	 */
	public function generate_code( Cpt_Settings $cpt_settings ): array;

	public function maybe_activate( Cpt_Settings $cpt_settings ): void;

	public function is_web_component_required( Cpt_Settings $cpt_settings ): bool;

	public function get_name(): string;
}
