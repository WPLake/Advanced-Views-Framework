<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin_Cpt;

use Org\Wplake\Advanced_Views\Plugin_Cpt\Plugin_Cpt;

defined( 'ABSPATH' ) || exit;

final class Post_Selections_Cpt implements Plugin_Cpt {
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
		return self::cpt_name();
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
