<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;

final class PHP_Variable extends Variable_Token {
	public function print(): void {
		printf(
			'$%s',
			esc_html( $this->name ),
		);

		foreach ( $this->item_path as $item_key ) {
			if ( $this->is_object ) {
				$this->print_object_key( $item_key );
			} else {
				$this->print_array_key( $item_key );
			}
		}
	}

	protected function print_array_key( string $key ): void {
		printf(
			'["%s"]',
			esc_html( $key ),
		);
	}

	protected function print_object_key( string $key ): void {
		printf(
			'->%s',
			esc_html( $key ),
		);
	}
}
