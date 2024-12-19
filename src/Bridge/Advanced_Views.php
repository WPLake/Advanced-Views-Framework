<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\Card_Shortcode_Interface;
use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\View_Shortcode_Interface;
use Org\Wplake\Advanced_Views\Bridge\Shortcodes\Card_Shortcode;
use Org\Wplake\Advanced_Views\Bridge\Shortcodes\View_Shortcode;
use Org\Wplake\Advanced_Views\Cards\Card_Shortcode as InnerCardShortcode;
use Org\Wplake\Advanced_Views\Views\View_Shortcode as InnerViewShortcode;

defined( 'ABSPATH' ) || exit;

class Advanced_Views {
	public static InnerViewShortcode $inner_view_shortcode;
	public static InnerCardShortcode $inner_card_shortcode;

	/**
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 */
	// @phpcs:ignore
	public static function view_shortcode( string $unique_id, string $name ): View_Shortcode_Interface {
		$view_shortcode = new View_Shortcode( static::$inner_view_shortcode );

		$view_shortcode->set_unique_id( $unique_id );

		return $view_shortcode;
	}

	/**
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 */
	// @phpcs:ignore
	public static function card_shortcode( string $unique_id, string $name ): Card_Shortcode_Interface {
		$card_shortcode = new Card_Shortcode( static::$inner_card_shortcode );

		$card_shortcode->set_unique_id( $unique_id );

		return $card_shortcode;
	}
}
