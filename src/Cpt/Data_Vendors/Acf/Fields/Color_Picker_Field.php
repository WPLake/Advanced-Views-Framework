<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Acf\Fields;

use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

class Color_Picker_Field extends Markup_Field {
	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory = $markup_field_data->get_token_factory();

		if ( 'string' === $markup_field_data->get_field_meta()->get_return_format() ) {
			$var = $token_factory->variable( $field_id )->add_item_path( 'value' );
			$token_factory->to_echo( $var )->print();

			return;
		}

		$parts       = array( 'red', 'green', 'blue', 'alpha' );
		$items_count = count( $parts );

		echo 'rgba(';

		for ( $i = 0;$i < $items_count;$i++ ) {
			if ( $i > 0 ) {
				echo ';';
			}

			$var = $token_factory->variable( $field_id )->add_item_path( $parts[ $i ] );
			$token_factory->to_echo( $var )->print();
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
			$red = string( $value, 'red' );
			// value is just bool, as 'red' can be zero, but still be a value.
			$args['value'] = (bool) $red;
			$args['red']   = $red;
			$args['green'] = string( $value, 'green' );
			$args['blue']  = string( $value, 'blue' );
			$args['alpha'] = string( $value, 'alpha' );
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
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}

	public function get_custom_field_wrapper_tag(): string {
		return 'p';
	}
}
