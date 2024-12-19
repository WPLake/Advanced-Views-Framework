<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template_Engines;

defined( 'ABSPATH' ) || exit;

class Template_Generator extends Template_Tokenizer {
	public function print_field( string $field_id ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$this->print_token_begin_expression();
		$this->print_token_variable( $field_id );

		if ( '' !== $sub_field_id ) {
			$this->print_token_items( array( $sub_field_id ) );
		}

		$this->print_token_end_expression();
	}

	public function print_array_item( string $field_id, string $item_key, bool $is_raw_value = false ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$item_keys = array();

		if ( '' !== $sub_field_id ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		$this->print_token_begin_expression( $is_raw_value );
		$this->print_token_variable( $field_id );
		$this->print_token_items( $item_keys );

		if ( true === $is_raw_value ) {
			$this->print_token_filter_raw();
		}

		$this->print_token_end_expression( $is_raw_value );
	}

	public function print_filled_array_item( string $field_id, string $first_item_key, string $second_item_key ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$first_item_keys  = array();
		$second_item_keys = array();

		if ( '' !== $sub_field_id ) {
			$first_item_keys[]  = $sub_field_id;
			$second_item_keys[] = $sub_field_id;
		}

		$first_item_keys[]  = $first_item_key;
		$second_item_keys[] = $second_item_key;

		$this->print_token_begin_expression();
		$this->print_token_variable( $field_id );
		$this->print_token_items( $first_item_keys );

		echo true === $this->is_twig_engine() ?
			'|default(' :
			' ?: ';

		$this->print_token_variable( $field_id );
		$this->print_token_items( $second_item_keys );

		echo true === $this->is_twig_engine() ?
			')' :
			'';

		$this->print_token_end_expression();
	}

	public function print_field_attribute( string $attribute_name, string $field_id ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		printf(
			' %s="',
			esc_html( $attribute_name )
		);

		$this->print_token_begin_expression();
		$this->print_token_variable( $field_id );

		if ( '' !== $sub_field_id ) {
			$this->print_token_items( array( $sub_field_id ) );
		}

		$this->print_token_end_expression();

		echo '"';
	}

	public function print_array_item_attribute( string $attribute_name, string $field_id, string $item_key ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$item_keys = array();

		if ( '' !== $sub_field_id ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		printf(
			' %s="',
			esc_html( $attribute_name )
		);

		$this->print_token_begin_expression();
		$this->print_token_variable( $field_id );
		$this->print_token_items( $item_keys );
		$this->print_token_end_expression();

		echo '"';
	}

	/**
	 * @param string|int $value
	 */
	public function print_if_for_array_item(
		string $field_id,
		string $item_key,
		string $comparison = '',
		$value = '',
		bool $is_with_true_stub = false,
		bool $is_elseif = false
	): void {
		$sub_field_id    = $this->extract_sub_field_id( $field_id );
		$safe_comparison = true === in_array( $comparison, array( '<', '>', '==' ), true ) ?
			$comparison :
			'';

		$item_keys = array();

		if ( '' !== $sub_field_id ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		if ( false === $is_elseif ) {
			$this->print_token_begin_if();
		} else {
			$this->print_token_begin_elseif();
		}

		if ( '' !== $safe_comparison ) {
			if ( true === is_string( $value ) ) {
				echo '"';
			}

			echo esc_html( (string) $value );

			if ( true === is_string( $value ) ) {
				echo '"';
			}

			printf(
				' %s ',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$safe_comparison,
			);
		}

		$this->print_token_variable( $field_id );
		$this->print_token_items( $item_keys );

		if ( true === $is_with_true_stub ) {
			echo true === $this->is_twig_engine() ?
				' or true' :
				' || true';
		}

		$this->print_token_end_if();
	}

	/**
	 * @param array<array{field_id:string,item_key:string}> $conditions
	 */
	public function print_multiple_if( array $conditions, bool $is_and_comparison = false ): void {
		$this->print_token_begin_if();

		$conditions_count = count( $conditions );

		for ( $i = 0;$i < $conditions_count;$i++ ) {
			if ( 0 !== $i ) {
				if ( false === $is_and_comparison ) {
					$this->print_token_condition_or();
				} else {
					$this->print_token_condition_and();
				}
			}

			$field_id = $conditions[ $i ]['field_id'];

			$sub_field_id = $this->extract_sub_field_id( $field_id );

			$item_keys = array();

			if ( '' !== $sub_field_id ) {
				$item_keys[] = $sub_field_id;
			}

			$item_keys[] = $conditions[ $i ]['item_key'];

			$this->print_token_variable( $field_id );
			$this->print_token_items( $item_keys );
		}

		$this->print_token_end_if();
	}

	public function print_else(): void {
		echo true === $this->is_twig_engine() ?
			'{% else %}' :
			'@else';
	}

	public function print_end_if(): void {
		echo true === $this->is_twig_engine() ?
			'{% endif %}' :
			'@endif';
	}

	public function print_for_of_array_item(
		string $field_id,
		string $item_key,
		string $loop_variable_name,
		bool $is_range = false
	): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$item_keys = array();

		if ( '' !== $sub_field_id ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		$this->print_token_begin_foreach();

		if ( true === $is_range &&
			false === $this->is_twig_engine() ) {
			echo 'range(1, ';
		}

		$this->print_token_variable(
			true === $this->is_twig_engine() ?
			$loop_variable_name :
			$field_id
		);

		if ( false === $this->is_twig_engine() ) {
			$this->print_token_items( $item_keys );
		}

		if ( true === $is_range &&
			false === $this->is_twig_engine() ) {
			echo ')';
		}

		echo true === $this->is_twig_engine() ?
			' in ' :
			' as ';

		if ( true === $is_range &&
			true === $this->is_twig_engine() ) {
			echo '1..';
		}

		$this->print_token_variable(
			true === $this->is_twig_engine() ?
				$field_id :
				$loop_variable_name
		);

		if ( true === $this->is_twig_engine() ) {
			$this->print_token_items( $item_keys );
		}

		$this->print_token_end_foreach();
	}

	public function print_if_of_not_first_loop_item(): void {
		echo true === $this->is_twig_engine() ?
			'{% if true != loop.first %}' :
			'@if (true != $loop->first)';
	}

	public function print_end_for(): void {
		echo true === $this->is_twig_engine() ?
			'{% endfor %}' :
			'@endforeach';
	}

	public function print_comment( string $comment ): void {
		printf(
			true === $this->is_twig_engine() ?
			'{# %s #}' :
			'{{-- %s --}}',
			esc_html( $comment )
		);
	}

	public function print_conditional_variable_string(
		string $variable,
		int $check_value,
		string $comparison,
		string $conditional_variable,
		string $first_value,
		string $second_value
	): void {
		printf(
			true === $this->is_twig_engine() ?
				'{%% set %s = %s %s %s ? "%s" : "%s" %%}' :
				'@php $%s = %s %s $%s ? "%s" : "%s" @endphp',
			esc_html( $variable ),
			esc_html( (string) $check_value ),
			esc_html( $comparison ),
			esc_html( $conditional_variable ),
			esc_html( $first_value ),
			esc_html( $second_value )
		);
	}

	public function print_function_include_inner_view( string $field_id, string $data_field_id, string $inner_view_class ): void {
		$sub_field_id      = $this->extract_sub_field_id( $field_id );
		$sub_data_field_id = $this->extract_sub_field_id( $data_field_id );
		$item_keys         = array();

		$this->print_token_begin_expression( true );

		$this->print_token_begin_function(
			true === $this->is_twig_engine() ?
				'_include_inner_view' :
				'avf_include_inner_view'
		);

		$this->print_token_variable( $field_id );

		if ( '' !== $sub_field_id ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = 'view_id';

		$this->print_token_items( $item_keys );

		echo ', ';

		$this->print_token_variable( $data_field_id );

		if ( '' !== $sub_data_field_id ) {
			$this->print_token_items( array( $sub_data_field_id ) );
		}

		echo ', ';

		printf(
			true === $this->is_twig_engine() ?
			'{ class:"%s" }' :
			'["class" => "%s",]',
			esc_html( $inner_view_class )
		);

		$this->print_token_end_function();
		$this->print_token_end_expression( true );
	}

	public function print_function_include_inner_view_for_flexible( string $field_id, string $inner_view_class ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );
		$item_keys    = array();

		$this->print_token_begin_expression( true );

		$this->print_token_begin_function(
			true === $this->is_twig_engine() ?
				'_include_inner_view_for_flexible' :
				'avf_include_inner_view_for_flexible'
		);

		$this->print_token_variable( $field_id );

		if ( '' !== $sub_field_id ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = 'layout_views';

		$this->print_token_items( $item_keys );

		echo ', ';

		$this->print_token_variable( 'item' );

		echo ', ';

		printf(
			true === $this->is_twig_engine() ?
				'{ class:"%s" }' :
				'["class" => "%s",]',
			esc_html( $inner_view_class )
		);

		$this->print_token_end_function();
		$this->print_token_end_expression( true );
	}

	public function print_function_paginate_links(): void {
		echo true === $this->is_twig_engine() ?
			"{{ paginate_links({ 'prev_text': '<', 'next_text': '>', 'total': _card.pages_amount,}) }}" :
			'{!! paginate_links({ "prev_text": "<", "next_text": ">", "total": $_card["pages_amount"],}) !!}';
	}
}
