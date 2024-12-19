<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

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
		if ( true === $field_meta->is_multiple() ) {
			if ( true === is_array( $value ) ) {
				return $value;
			}

			return array();
		}

		if ( true === is_string( $value ) ||
			true === is_numeric( $value ) ||
			true === is_bool( $value ) ) {
			return $value;
		}

		return '';
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		if ( true === $markup_field_data->get_field_meta()->is_multiple() ) {
			echo "\r\n";
			$markup_field_data->print_tabs();

			$markup_field_data->get_template_generator()->print_for_of_array_item( $field_id, 'value', static::LOOP_ITEM_NAME );

			echo "\r\n";
			$markup_field_data->increment_and_print_tabs();

			if ( '' !== $markup_field_data->get_field_data()->options_delimiter ) {
				echo "\r\n";
				$markup_field_data->print_tabs();

				$markup_field_data->get_template_generator()->print_if_of_not_first_loop_item();

				echo "\r\n";
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

				echo "\r\n";
				$markup_field_data->increment_and_print_tabs();

				$markup_field_data->get_template_generator()->print_array_item( $field_id, 'options_delimiter' );

				echo "\r\n";
				$markup_field_data->decrement_and_print_tabs();

				echo '</span>';

				echo "\r\n";
				$markup_field_data->decrement_and_print_tabs();

				$markup_field_data->get_template_generator()->print_end_if();

				echo "\r\n\r\n";

				$markup_field_data->print_tabs();
			}
		}

		$item_id = $markup_field_data->get_field_meta()->is_multiple() ?
			static::LOOP_ITEM_NAME :
			$field_id;

		$this->print_item( $field_id, $item_id, $markup_field_data );

		if ( $markup_field_data->get_field_meta()->is_multiple() ) {
			echo "\r\n";
			$markup_field_data->decrement_and_print_tabs();
			$markup_field_data->get_template_generator()->print_end_for();
			echo "\r\n";
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => array(),
		);

		if ( true === $variable_field_data->get_field_meta()->is_multiple() ) {
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

		if ( true === $variable_field_data->get_field_meta()->is_multiple() ) {
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
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return $view_data->is_with_unnecessary_wrappers ||
				$field_meta->is_multiple();
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		$conditional_fields = $field_meta->is_multiple() ?
			array(
				Field_Data::FIELD_OPTIONS_DELIMITER,
			) :
			array();

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}
}
