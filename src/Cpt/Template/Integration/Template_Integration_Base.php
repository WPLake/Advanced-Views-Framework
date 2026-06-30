<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Integration;

use Org\Wplake\Advanced_Views\Plugin\Plugin;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;

defined( 'ABSPATH' ) || exit;

abstract class Template_Integration_Base implements Template_Integration {
	/**
	 * This code is common for all the template engines.
	 *
	 * @return array<string,string[]>
	 */
	public static function parse_translation_calls( string $template, string $default_domain ): array {
		// extract ml string data from: __("Some data") or __("Some data", "my-theme").
		preg_match_all(
			'/__\([ ]*["]([^"]+)["]([, ]+["]([^"]+)["])*[ ]*\)/',
			$template,
			$functions_with_double_quotes,
			PREG_SET_ORDER
		);

		// extract ml string data from: __('Some data') or __('Some data', 'my-theme').
		preg_match_all(
			"/__\([ ]*[']([^']+)[']([, ]+[']([^']+)['])*[ ]*\)/",
			$template,
			$functions_with_single_quotes,
			PREG_SET_ORDER
		);

		$functions = array_merge( $functions_with_double_quotes, $functions_with_single_quotes );
		$strings   = array();

		foreach ( $functions as $match ) {
			$label       = $match[1];
			$text_domain = $match[3] ?? $default_domain;

			/**
			 * @var string[] $labels
			 */
			$labels   = arr( $strings, $text_domain );
			$labels[] = $label;

			$strings[ $text_domain ] = $labels;
		}

		foreach ( $strings as $text_domain => $labels ) {
			$strings[ $text_domain ] = array_unique( $labels );
		}

		return $strings;
	}

	public function mock_provocative_symbols( string $template ): string {
		$provocative_symbols_map = $this->get_provocative_symbols_map();

		return str_replace(
			array_keys( $provocative_symbols_map ),
			array_values( $provocative_symbols_map ),
			$template
		);
	}

	public function unmock_provocative_symbols( string $template ): string {
		$provocative_symbols_map = $this->get_provocative_symbols_map();

		return str_replace(
			array_values( $provocative_symbols_map ),
			array_keys( $provocative_symbols_map ),
			$template
		);
	}

	public function extract_multilingual_strings( string $template ): array {
		$default_domain = Plugin::get_theme_text_domain();

		return self::parse_translation_calls( $template, $default_domain );
	}
}
