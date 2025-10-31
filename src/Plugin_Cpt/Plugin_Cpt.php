<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin_Cpt;

defined( 'ABSPATH' ) || exit;

interface Plugin_Cpt {
	public function cpt_name(): string;

	public function slug_prefix(): string;

	public function folder_name(): string;

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
