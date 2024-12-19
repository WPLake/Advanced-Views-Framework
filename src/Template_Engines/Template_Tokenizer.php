<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template_Engines;

defined( 'ABSPATH' ) || exit;

class Template_Tokenizer {
	private string $template_engine;

	public function __construct( string $template_engine ) {
		$this->template_engine = $template_engine;
	}

	protected function is_twig_engine(): bool {
		return true === in_array( $this->template_engine, array( Template_Engines::TWIG, '' ), true );
	}

	protected function print_token_begin_expression( bool $is_raw = false ): void {
		echo ( true === $this->is_twig_engine() ||
				false === $is_raw ) ?
			'{{ ' :
			'{!! ';
	}

	protected function print_token_end_expression( bool $is_raw = false ): void {
		if ( true === $is_raw ) {
			echo true === $this->is_twig_engine() ?
				' }}' :
				' !!}';

			return;
		}

		echo ' }}';
	}

	protected function print_token_begin_if(): void {
		if ( true === $this->is_twig_engine() ) {
			echo '{% ';
		}

		echo true === $this->is_twig_engine() ?
			'if ' :
			'@if (';
	}

	protected function print_token_begin_elseif(): void {
		if ( true === $this->is_twig_engine() ) {
			echo '{% ';
		}

		echo true === $this->is_twig_engine() ?
			'elseif ' :
			'@elseif (';
	}

	protected function print_token_end_if(): void {
		if ( true === $this->is_twig_engine() ) {
			echo ' %}';
		} else {
			echo ')';
		}
	}

	protected function print_token_begin_foreach(): void {
		if ( true === $this->is_twig_engine() ) {
			echo '{% ';
		}

		echo true === $this->is_twig_engine() ?
			'for ' :
			'@foreach (';
	}

	protected function print_token_end_foreach(): void {
		if ( true === $this->is_twig_engine() ) {
			echo ' %}';
		} else {
			echo ')';
		}
	}

	protected function print_token_condition_or(): void {
		echo true === $this->is_twig_engine() ?
			' or ' :
			' || ';
	}

	protected function print_token_condition_and(): void {
		echo true === $this->is_twig_engine() ?
			' and ' :
			' && ';
	}

	protected function print_token_variable( string $variable ): void {
		printf(
			true === $this->is_twig_engine() ?
				'%s' :
				'$%s',
			esc_html( $variable ),
		);
	}

	/**
	 * @param string[] $item_keys
	 */
	protected function print_token_items( array $item_keys ): void {
		foreach ( $item_keys as $item_key ) {
			printf(
				true === $this->is_twig_engine() ?
					'.%s' :
					'["%s"]',
				esc_html( $item_key ),
			);
		}
	}

	protected function print_token_filter_raw(): void {
		if ( false === $this->is_twig_engine() ) {
			return;
		}

		echo '|raw';
	}

	protected function print_token_begin_function( string $function_name ): void {
		printf(
			'%s(',
			esc_html( $function_name ),
		);
	}

	protected function print_token_end_function(): void {
		echo ')';
	}

	protected function extract_sub_field_id( string &$field_id ): string {
		$field_id = explode( '.', $field_id );

		$sub_field_id = count( $field_id ) > 1 ?
			$field_id[1] :
			'';

		$field_id = $field_id[0];

		return $sub_field_id;
	}
}
