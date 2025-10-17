<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Shortcodes;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\Card_Shortcode_Interface;

defined( 'ABSPATH' ) || exit;

final class Selection_Shortcode extends Shortcode_Base implements Card_Shortcode_Interface {
	protected function get_args(): array {
		return array_merge(
			parent::get_args(),
			array(
				'card-id' => $this->unique_id,
			)
		);
	}
}
