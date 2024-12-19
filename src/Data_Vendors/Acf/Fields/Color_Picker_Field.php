<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Acf\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Color_Picker_Field extends Markup_Field {
	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		if ( 'string' === $markup_field_data->get_field_meta()->get_return_format() ) {
			$markup_field_data->get_template_generator()->print_array_item( $field_id, 'value' );

			return;
		}

		$parts       = array( 'red', 'green', 'blue', 'alpha' );
		$items_count = count( $parts );

		echo 'rgba(';

		for ( $i = 0;$i < $items_count;$i++ ) {
			if ( $i > 0 ) {
				echo ';';
			}

			$markup_field_data->get_template_generator()->print_array_item( $field_id, $parts[ $i ] );
		}

		echo ')';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => '',
			'red'   => '',
			'green' => '',
			'blue'  => '',
			'alpha' => '',
		);

		$value = null;

		if ( 'string' === $variable_field_data->get_field_meta()->get_return_format() &&
			is_string( $variable_field_data->get_value() ) ) {
			$value = $variable_field_data->get_value();
		} elseif ( is_array( $variable_field_data->get_value() ) ) {
			$value = $variable_field_data->get_value();
		}

		if ( null === $value ) {
			return $args;
		}

		if ( 'string' === $variable_field_data->get_field_meta()->get_return_format() ) {
			$args['value'] = $value;
		} else {
			// value is just bool, as 'red' can be zero, but still be a value.
			$args['value'] = ! ! ( $value['red'] ?? '' );
			$args['red']   = (string) ( $value['red'] ?? '' );
			$args['green'] = (string) ( $value['green'] ?? '' );
			$args['blue']  = (string) ( $value['blue'] ?? '' );
			$args['alpha'] = (string) ( $value['alpha'] ?? '' );
		}

		return $args;
	}

	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value' => true,
			'red'   => '1',
			'green' => '1',
			'blue'  => '1',
			'alpha' => '1',
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}

	public function get_custom_field_wrapper_tag(): string {
		return 'p';
	}
}
