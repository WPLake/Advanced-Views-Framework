<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Rendering;

defined( 'ABSPATH' ) || exit;

interface Template_Renderer {
	/**
	 * @param array<string,mixed> $args
	 */
	public function print( string $unique_id, string $template, array $args, bool $is_validation = false ): void;

	/**
	 * @return array<string,string>
	 */
	public function get_provocative_symbols_map(): array;

	public function mock_provocative_symbols( string $template ): string;

	public function unmock_provocative_symbols( string $template ): string;
}
