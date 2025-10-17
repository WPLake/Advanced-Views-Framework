<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\Card_Shortcode_Interface;
use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\View_Shortcode_Interface;
use Org\Wplake\Advanced_Views\Bridge\Shortcodes\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Bridge\Shortcodes\Post_Selection_Shortcode;
use Org\Wplake\Advanced_Views\Bridge\Shortcodes\Shortcode;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode_Renderer;

defined( 'ABSPATH' ) || exit;

class Advanced_Views {
	public static Shortcode_Renderer $layout_renderer;
	public static Shortcode_Renderer $post_selection_renderer;

	/**
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 */
	// @phpcs:ignore
	public static function layout_shortcode( string $unique_id, string $name ): Layout_Shortcode {
		$shortcode = new Layout_Shortcode( self::$layout_renderer );

		$shortcode->set_unique_id( $unique_id );

		return $shortcode;
	}

	/**
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 */
	// @phpcs:ignore
	public static function post_selection_shortcode( string $unique_id, string $name ): Post_Selection_Shortcode {
		$shortcode = new Post_Selection_Shortcode( self::$post_selection_renderer );

		$shortcode->set_unique_id( $unique_id );

		return $shortcode;
	}

	// Deprecated methods.

	/**
	 * @deprecated Use layout_shortcode() instead.
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 */
	// @phpcs:ignore
	public static function view_shortcode( string $unique_id, string $name ): View_Shortcode_Interface {
		return self::layout_shortcode( $unique_id, $name );
	}

	/**
	 * @deprecated Use post_selection_shortcode() instead.
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 */
	// @phpcs:ignore
	public static function card_shortcode( string $unique_id, string $name ): Card_Shortcode_Interface {
		return self::post_selection_shortcode( $unique_id, $name );
	}
}
