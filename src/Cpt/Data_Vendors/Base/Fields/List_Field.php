<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;

abstract class List_Field extends Markup_Field {
	const LOOP_ITEM_NAME = 'item';

	/**
	 * @return array<string, mixed>
	 */
	abstract protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array;

	/**
	 * @return array<string, mixed>
	 */
	abstract protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array;

	// separate method, so it can be overridden in the child classes.

	/**
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function get_value( Field_Meta_Interface $field_meta, $value ) {
		if ( $field_meta->is_multiple() ) {
			if ( is_array( $value ) ) {
				return $value;
			}

			return array();
		}

		if ( is_string( $value ) ||
			is_numeric( $value ) ||
			is_bool( $value ) ) {
			return $value;
		}

		return '';
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		if ( $markup_field_data->get_field_meta()->is_multiple() ) {
			$this->print_item_loop( $field_id, $markup_field_data );

			return;
		}

		$this->print_inner_item( $field_id, $markup_field_data );
	}

	protected function print_item_loop( string $field_id, Markup_Field_Data $markup_field_data ): void {

		$token_factory = $markup_field_data->get_token_factory();

		$source_var = $token_factory->variable( $field_id )
										->add_item_path( 'value' );
		$item_var   = $token_factory->variable( static::LOOP_ITEM_NAME );
		$loop       = $token_factory->loop()
			->set_source_variable( $source_var )
			->set_item_variable( $item_var );

		$loop_body = $token_factory->html(
			fn() => $this->print_loop_body( $loop->get_index_variable(), $field_id, $markup_field_data )
		);

		$token_factory->format()
						->new_line();
		$markup_field_data->print_tabs();

		$loop
						->set_body( $loop_body )
						->print();

		$token_factory->format()
						->new_line();
	}

	protected function print_loop_body( Variable_Token $index_var, string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory    = $markup_field_data->get_token_factory();
		$is_delimiter_set = strlen( $markup_field_data->get_field_data()->options_delimiter ) > 0;

		$token_factory->format()
			->new_line();
		$markup_field_data->increment_and_print_tabs();

		if ( $is_delimiter_set ) {
			$if = $token_factory->if();

			$secondary_element_comparison = $token_factory->comparison()
				->set_left_operand( $index_var )
				->set_comparison_greater()
				->set_right_operand( $token_factory->literal( 0 ) );

			$if->new_if_branch()
				->set_condition( $secondary_element_comparison )
				->set_body( $token_factory->html( fn() => $this->print_item_delimiter( $field_id, $markup_field_data ) ) );

			$token_factory->format()
						->new_line();
			$markup_field_data->print_tabs();

			$if->print();

			echo "\r\n\r\n";
			$markup_field_data->print_tabs();
		}

		$this->print_inner_item( $field_id, $markup_field_data );

		$token_factory->format()
			->new_line();
		$markup_field_data->decrement_and_print_tabs();
	}

	protected function print_item_delimiter( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory = $markup_field_data->get_token_factory();

		$token_factory->format()
						->new_line();
		$markup_field_data->increment_and_print_tabs();

		printf(
			'<span class="%s">',
			esc_html(
				$this->get_item_class(
					'delimiter',
					$markup_field_data->get_view_data(),
					$markup_field_data->get_field_data()
				)
			)
		);

		$token_factory->format()
						->new_line();
		$markup_field_data->increment_and_print_tabs();

		$delimiter_var = $token_factory->variable( $field_id )
										->add_item_path( 'options_delimiter' );
		$token_factory->to_echo( $delimiter_var )
						->print();

		$token_factory->format()
						->new_line();
		$markup_field_data->decrement_and_print_tabs();

		echo '</span>';

		$token_factory->format()
						->new_line();
		$markup_field_data->decrement_and_print_tabs();
	}

	protected function print_inner_item( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$item_id = $markup_field_data->get_field_meta()->is_multiple() ?
			static::LOOP_ITEM_NAME :
			$field_id;

		$this->print_item( $field_id, $item_id, $markup_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => array(),
		);

		if ( $variable_field_data->get_field_meta()->is_multiple() ) {
			$args['options_delimiter'] = $variable_field_data->get_field_data()->options_delimiter;
		}

		$value = $this->get_value( $variable_field_data->get_field_meta(), $variable_field_data->get_value() );

		if ( array() === $value ||
			'' === $value ) {
			// it's a single item, so merge, not assign to the 'value' key.
			if ( ! $variable_field_data->get_field_meta()->is_multiple() ) {
				$variable_field_data->set_value( null );

				$args = array_merge(
					$args,
					$this->get_item_template_args( $variable_field_data )
				);
			}

			return $args;
		}

		if ( $variable_field_data->get_field_meta()->is_multiple() ) {
			$value = (array) $value;

			foreach ( $value as $item ) {
				$variable_field_data->set_value( $item );

				$args['value'][] = $this->get_item_template_args( $variable_field_data );
			}
		} else {
			$variable_field_data->set_value( $value );

			// it's a single item, so merge, not assign to the 'value' key.
			$args = array_merge(
				$args,
				$this->get_item_template_args( $variable_field_data )
			);
		}

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => array(),
		);

		if ( $variable_field_data->get_field_meta()->is_multiple() ) {
			$args['options_delimiter'] = $variable_field_data->get_field_data()->options_delimiter;
		}

		$item_args = $this->get_validation_item_template_args( $variable_field_data );
		$item      = array();

		if ( $variable_field_data->get_field_meta()->is_multiple() ) {
			$item[] = $item_args;

			return array_merge(
				$args,
				array(
					'value' => $item,
				)
			);
		}

		$item = $item_args;

		return array_merge( $args, $item );
	}

	public function is_with_field_wrapper(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return $layout_settings->is_with_unnecessary_wrappers ||
				$field_meta->is_multiple();
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		$conditional_fields = $field_meta->is_multiple() ?
			array(
				Field_Settings::FIELD_OPTIONS_DELIMITER,
			) :
			array();

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}
}
