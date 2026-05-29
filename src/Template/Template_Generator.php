<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Tokens\Condition_Tokens;
use Org\Wplake\Advanced_Views\Template\Tokens\Function_Tokens;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Echo;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Var;
use Org\Wplake\Advanced_Views\Template\Tokens\Variable_Tokens;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

abstract class Template_Generator {
	protected Token_Generator $generator;
	protected Function_Tokens $function;
	protected Variable_Tokens $variable;

	public function field( string $field_id ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		// fixme is it the best way?
		$var  = $this->generator->var(
			function ( T_Var $var ) use ( $field_id, $sub_field_id ) {
				$var->name = $field_id;

				if ( strlen( $sub_field_id ) > 0 ) {
					$var->sub_item_path = array( $sub_field_id );
				}
			}
		);
		$echo = $this->generator->echo(
			function ( T_Echo $echo ) use ( $var ) {
				$echo->subject = $var;
			}
		);
		$echo();

		$variable = new T_Var();

		$echo = new T_Echo();

		$echo->subject = $some->render( $variable );
		$some->render( $echo );

		$this->variable->expression_open();
		$this->variable->variable( $field_id );

		if ( strlen( $sub_field_id ) > 0 ) {
			$this->variable->inner_item( array( $sub_field_id ) );
		}

		$this->variable->expression_close();
	}

	public function array_item( string $field_id, string $item_key, bool $is_raw_value = false ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$item_keys = array();

		if ( strlen( $sub_field_id ) > 0 ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		$this->variable->expression_open( $is_raw_value );
		$this->variable->variable( $field_id );
		$this->variable->inner_item( $item_keys );

		if ( $is_raw_value ) {
			$this->function->filter_raw();
		}

		$this->variable->expression_close( $is_raw_value );
	}

	public function filled_array_item( string $field_id, string $first_item_key, string $second_item_key ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$first_item_keys  = array();
		$second_item_keys = array();

		if ( strlen( $sub_field_id ) > 0 ) {
			$first_item_keys[]  = $sub_field_id;
			$second_item_keys[] = $sub_field_id;
		}

		$first_item_keys[]  = $first_item_key;
		$second_item_keys[] = $second_item_key;

		$this->variable->expression_open();

		$this->variable->variable( $field_id );
		$this->variable->inner_item( $first_item_keys );

		$this->variable->default_value_open();

		$this->variable->variable( $field_id );
		$this->variable->inner_item( $second_item_keys );

		$this->variable->default_value_close();

		$this->variable->expression_close();
	}

	public function field_attribute( string $attribute_name, string $field_id ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		printf(
			' %s="',
			esc_html( $attribute_name )
		);

		$this->variable->expression_open();
		$this->variable->variable( $field_id );

		if ( strlen( $sub_field_id ) > 0 ) {
			$this->variable->inner_item( array( $sub_field_id ) );
		}

		$this->variable->expression_close();

		echo '"';
	}

	public function array_item_attribute( string $attribute_name, string $field_id, string $item_key ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$item_keys = array();

		if ( strlen( $sub_field_id ) > 0 ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		printf(
			' %s="',
			esc_html( $attribute_name )
		);

		$this->variable->expression_open();
		$this->variable->variable( $field_id );
		$this->variable->inner_item( $item_keys );
		$this->variable->expression_close();

		echo '"';
	}

	/**
	 * @param string|int $value
	 */
	public function if_for_array_item(
		string $field_id,
		string $item_key,
		string $comparison = '',
		$value = '',
		bool $is_with_true_stub = false,
		bool $is_elseif = false
	): void {
		$sub_field_id    = $this->extract_sub_field_id( $field_id );
		$safe_comparison = in_array( $comparison, array( '<', '>', '==' ), true ) ?
			$comparison :
			'';

		$item_keys = array();

		if ( strlen( $sub_field_id ) > 0 ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		if ( false === $is_elseif ) {
			$this->condition->if_open();
		} else {
			$this->condition->elseif_open();
		}

		if ( strlen( $safe_comparison ) > 0 ) {
			if ( is_string( $value ) ) {
				echo '"';
			}

			echo esc_html( (string) $value );

			if ( is_string( $value ) ) {
				echo '"';
			}

			printf(
				' %s ',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$safe_comparison,
			);
		}

		$this->variable->variable( $field_id );
		$this->variable->inner_item( $item_keys );

		if ( $is_with_true_stub ) {
			$this->condition->condition_or_true();
		}

		$this->condition->endif();
	}

	/**
	 * @param array<array{field_id:string,item_key:string}> $conditions
	 */
	public function multiple_if( array $conditions, bool $is_and_comparison = false ): void {
		$this->condition->if_open();

		$conditions_count = count( $conditions );

		for ( $i = 0;$i < $conditions_count;$i++ ) {
			if ( 0 !== $i ) {
				if ( false === $is_and_comparison ) {
					$this->condition->condition_or();
				} else {
					$this->condition->condition_and();
				}
			}

			$field_id = $conditions[ $i ]['field_id'];

			$sub_field_id = $this->extract_sub_field_id( $field_id );

			$item_keys = array();

			if ( '' !== $sub_field_id ) {
				$item_keys[] = $sub_field_id;
			}

			$item_keys[] = $conditions[ $i ]['item_key'];

			$this->variable->variable( $field_id );
			$this->variable->inner_item( $item_keys );
		}

		$this->condition->endif();
	}

	public function for_of_array_item(
		string $field_id,
		string $item_key,
		string $loop_variable_name,
		bool $is_range = false
	): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );

		$item_keys = array();

		if ( strlen( $sub_field_id ) > 0 ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = $item_key;

		$this->function->foreach_open();

		if ( $is_range &&
			! $this->is_twig_engine() ) {
			echo 'range(1, ';
		}

		$this->variable->variable(
			$this->is_twig_engine() ?
				$loop_variable_name :
				$field_id
		);

		if ( ! $this->is_twig_engine() ) {
			$this->variable->inner_item( $item_keys );
		}

		if ( $is_range &&
			! $this->is_twig_engine() ) {
			echo ')';
		}

		echo $this->is_twig_engine() ?
			' in ' :
			' as ';

		if ( $is_range &&
			$this->is_twig_engine() ) {
			echo '1..';
		}

		$this->variable->variable(
			$this->is_twig_engine() ?
				$field_id :
				$loop_variable_name
		);

		if ( $this->is_twig_engine() ) {
			$this->variable->inner_item( $item_keys );
		}

		$this->function->foreach_close();
	}

	public function if_of_not_first_loop_item(): void {
		echo $this->is_twig_engine() ?
			'{% if true != loop.first %}' :
			'@if (true != $loop->first)';
	}

	public function comment( string $comment ): void {
		$this->function->comment_open();

		echo esc_html( $comment );

		$this->function->comment_close();
	}

	public function conditional_variable_string(
		string $variable,
		int $check_value,
		string $comparison,
		string $conditional_variable,
		string $first_value,
		string $second_value
	): void {
		printf(
			$this->is_twig_engine() ?
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

	public function function_include_inner_view( string $field_id, string $data_field_id, string $inner_view_class ): void {
		$sub_field_id      = $this->extract_sub_field_id( $field_id );
		$sub_data_field_id = $this->extract_sub_field_id( $data_field_id );
		$item_keys         = array();

		$this->variable->expression_open( true );

		$this->function->function_open(
			$this->is_twig_engine() ?
				'_include_inner_view' :
				'avf_include_inner_view'
		);

		$this->variable->variable( $field_id );

		if ( strlen( $sub_field_id ) > 0 ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = 'layout_id';

		$this->variable->inner_item( $item_keys );

		echo ', ';

		$this->variable->variable( $data_field_id );

		if ( strlen( $sub_data_field_id ) > 0 ) {
			$this->variable->inner_item( array( $sub_data_field_id ) );
		}

		echo ', ';

		printf(
			$this->is_twig_engine() ?
				'{ class:"%s" }' :
				'["class" => "%s",]',
			esc_html( $inner_view_class )
		);

		$this->function->function_close();
		$this->variable->expression_close( true );
	}

	public function function_include_inner_view_for_flexible( string $field_id, string $inner_view_class ): void {
		$sub_field_id = $this->extract_sub_field_id( $field_id );
		$item_keys    = array();

		$this->variable->expression_open( true );

		$this->function->function_open(
			$this->is_twig_engine() ?
				'_include_inner_view_for_flexible' :
				'avf_include_inner_view_for_flexible'
		);

		$this->variable->variable( $field_id );

		if ( strlen( $sub_field_id ) > 0 ) {
			$item_keys[] = $sub_field_id;
		}

		$item_keys[] = 'layout_views';

		$this->variable->inner_item( $item_keys );

		echo ', ';

		$this->variable->variable( 'item' );

		echo ', ';

		printf(
			$this->is_twig_engine() ?
				'{ class:"%s" }' :
				'["class" => "%s",]',
			esc_html( $inner_view_class )
		);

		$this->function->function_close();
		$this->variable->expression_close( true );
	}

	public function function_paginate_links(): void {
		echo $this->is_twig_engine() ?
			"{{ paginate_links({ 'prev_text': '<', 'next_text': '>', 'total': _card.pages_amount,}) }}" :
			'{!! paginate_links({ "prev_text": "<", "next_text": ">", "total": $_card["pages_amount"],}) !!}';
	}

	protected function extract_sub_field_id( string &$field_id ): string {
		$ids = explode( '.', $field_id );

		$field_id = $ids[0];

		return string( $ids, 1 );
	}
}
