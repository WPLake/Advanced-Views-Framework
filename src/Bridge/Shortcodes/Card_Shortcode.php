<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Shortcodes;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\Card_Shortcode_Interface;
use Org\Wplake\Advanced_Views\Cards\Card_Shortcode as InnerCardShortcode;

defined( 'ABSPATH' ) || exit;

class Card_Shortcode extends Shortcode implements Card_Shortcode_Interface {
	protected InnerCardShortcode $inner_card_shortcode;

	public function __construct( InnerCardShortcode $inner_card_shortcode ) {
		$this->inner_card_shortcode = $inner_card_shortcode;
	}

	public function render( array $args = array() ): string {
		$args = array_merge(
			array(
				'card-id'            => $this->get_unique_id(),
				'class'              => $this->get_class(),
				'custom-arguments'   => $this->get_custom_arguments(),
				'user-with-roles'    => $this->get_user_with_roles(),
				'user-without-roles' => $this->get_user_without_roles(),
			),
			$args
		);

		ob_start();

		$this->inner_card_shortcode->render( $args );

		return (string) ob_get_clean();
	}
}
