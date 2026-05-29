<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Tokens;

defined( 'ABSPATH' ) || exit;

interface Variable_Tokens {
	public function expression_open( bool $is_raw = false ): void;

	public function expression_close( bool $is_raw = false ): void;

	public function variable( string $variable ): void;

	/**
	 * @param string[] $item_keys
	 */
	public function inner_item( array $item_keys ): void;

	public function default_value_open(): void;

	public function default_value_close(): void;
}
