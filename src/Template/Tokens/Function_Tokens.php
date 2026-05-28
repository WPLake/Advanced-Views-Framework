<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Tokens;

defined( 'ABSPATH' ) || exit;

interface Function_Tokens {
	public function function_open( string $function_name ): void;

	public function function_close(): void;

	public function foreach_open(): void;

	public function foreach_close(): void;

	public function endforeach(): void;

	public function filter_raw(): void;
}
