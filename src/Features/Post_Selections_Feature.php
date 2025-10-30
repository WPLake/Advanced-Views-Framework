<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Features;

defined( 'ABSPATH' ) || exit;

final class Post_Selections_Feature implements Plugin_Feature {
	public static function cpt_name(): string {
		return 'avf-post-selection';
	}

	public static function slug_prefix(): string {
		return 'post-selection-';
	}

	public static function folder_name(): string {
		return 'post-selections';
	}

	public static function shortcode(): string {
		return 'avf-post-selection';
	}

	/**
	 * @return string[]
	 */
	public static function shortcodes(): array {
		return array( self::shortcode(), 'acf_cards', 'avf_card' );
	}

	public static function rest_route_names(): array {
		return array( 'post-selection', 'card' );
	}
}
