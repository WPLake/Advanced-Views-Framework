<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Integration;

defined( 'ABSPATH' ) || exit;

interface Template_Integration {
	public function get_ace_mode(): string;

	/**
	 * @return array<string,string>
	 */
	public function get_provocative_symbols_map(): array;

	public function mock_provocative_symbols( string $template ): string;

	public function unmock_provocative_symbols( string $template ): string;

	/**
	 * @return array<string,string>
	 */
	public function get_autocomplete_functions(): array;

	/**
	 * @return array<string,string>
	 */
	public function get_autocomplete_filters(): array;

	public function get_file_extension(): string;

	/**
	 * @return array<string,string[]>
	 */
	public function extract_multilingual_strings( string $template ): array;
}
