<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Features;

defined( 'ABSPATH' ) || exit;

final class Layouts_Feature implements Plugin_Feature {
	public static function cpt_name(): string {
		return 'acf_views';
	}

	public static function slug_prefix(): string {
		return 'view_';
	}

	public static function folder_name(): string {
		return 'layouts';
	}

	public static function shortcode(): string {
		return 'avf_layout';
	}

	/**
	 * @return string[]
	 */
	public static function shortcodes(): array {
		return array( self::shortcode(), 'acf_views', 'avf_view' );
	}

	public static function rest_route_names(): array {
		return array( 'view' );
	}
}
