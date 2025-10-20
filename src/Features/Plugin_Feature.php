<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Features;

defined( 'ABSPATH' ) || exit;

interface Plugin_Feature {
	public static function cpt_name(): string;

	public static function slug_prefix(): string;

	public static function folder_name(): string;

	/**
	 * @return string[]
	 */
	public static function shortcodes(): array;

	public static function shortcode(): string;

	/**
	 * @return string[]
	 */
	public static function rest_route_names(): array;
}
