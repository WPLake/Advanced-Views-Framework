<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt\Pub;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;

interface Public_Plugin_Cpt extends Plugin_Cpt {
	/**
	 * @return string[]
	 */
	public function shortcodes(): array;

	public function shortcode(): string;

	/**
	 * @return string[]
	 */
	public function rest_route_names(): array;
}
