<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Integration;

defined( 'ABSPATH' ) || exit;

abstract class Template_Integration_Base implements Template_Integration {
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
}
