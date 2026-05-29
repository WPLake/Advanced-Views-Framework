<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Tokens\Function_Tokens;

final class Blade_Function_Tokens implements Function_Tokens {
	public function function_open( string $function_name ): void {
		printf(
			'%s(',
			esc_html( $function_name ),
		);
	}

	public function function_close(): void {
		echo ')';
	}

	public function foreach_open(): void {
		echo '@foreach (';
	}

	public function foreach_close(): void {
		echo ')';
	}

	public function endforeach(): void {
		echo '@endforeach';
	}

	public function filter_raw(): void {
		// Not needed for Blade.
	}

	public function comment_open(): void {
		echo '{{-- ';
	}

	public function comment_close(): void {
		echo ' --}}';
	}
}
