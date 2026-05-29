<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Tokens\Variable_Tokens;

final class Blade_Variable_Tokens implements Variable_Tokens {
	public function expression_open( bool $is_raw = false ): void {
		echo $is_raw ?
			'{!! ' :
			'{{ ';
	}

	public function expression_close( bool $is_raw = false ): void {
		echo $is_raw ?
			' !!}' :
			' }}';
	}

	public function variable( string $variable ): void {
		printf(
			'$%s',
			esc_html( $variable ),
		);
	}

	public function inner_item( array $item_keys ): void {
		foreach ( $item_keys as $item_key ) {
			printf(
				'["%s"]',
				esc_html( $item_key ),
			);
		}
	}

	public function default_value_open(): void {
		echo ' ?: ';
	}

	public function default_value_close(): void {
		// nothing in Blade.
	}
}
