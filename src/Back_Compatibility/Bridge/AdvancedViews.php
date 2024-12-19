<?php

declare( strict_types=1 );

namespace org\wplake\advanced_views\Bridge;

use Org\Wplake\Advanced_Views\Bridge\Advanced_Views as New_Advanced_Views;
use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\Card_Shortcode_Interface;
use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\View_Shortcode_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * @deprecated use Org\Wplake\Advanced_Views\Bridge\Advanced_Views instead
 */
class AdvancedViews extends New_Advanced_Views {
	/**
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 *
	 * @deprecated use view_shortcode() instead
	 */
	public static function viewShortcode( string $unique_id, string $name ): View_Shortcode_Interface {
		return parent::view_shortcode( $unique_id, $name );
	}

	/**
	 * @param string $name unused argument, just to make the method call human-readable in your code
	 *
	 * @deprecated use card_shortcode() instead
	 */
	public static function cardShortcode( string $unique_id, string $name ): Card_Shortcode_Interface {
		return parent::card_shortcode( $unique_id, $name );
	}
}
