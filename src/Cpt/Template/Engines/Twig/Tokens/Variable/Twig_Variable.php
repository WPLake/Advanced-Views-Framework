<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;

final class Twig_Variable extends Variable_Token {
	public function print(): void {
		echo esc_html( $this->name );

		foreach ( $this->item_path as $item_key ) {
			printf(
				'.%s',
				esc_html( $item_key ),
			);
		}
	}
}
