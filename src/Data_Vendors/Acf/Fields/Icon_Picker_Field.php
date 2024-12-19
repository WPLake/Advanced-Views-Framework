<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Acf\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Icon_Picker_Field extends Markup_Field {
	use Safe_Array_Arguments;

	private Image_Field $image_field;

	public function __construct( Image_Field $image_field ) {
		$this->image_field = $image_field;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$markup_field_data->get_template_generator()->print_if_for_array_item( $field_id, 'type', '==', 'dashicons' );

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		printf(
			'<i class="%s dashicons ',
			esc_html(
				$this->get_field_class(
					'icon',
					$markup_field_data
				)
			),
		);
		$markup_field_data->get_template_generator()->print_array_item( $field_id, 'value' );
		echo '"></i>';

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		$markup_field_data->get_template_generator()->print_if_for_array_item(
			$field_id,
			'type',
			'==',
			'media_library',
			false,
			true
		);

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		$this->image_field->print_markup( $field_id, $markup_field_data );

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		$markup_field_data->get_template_generator()->print_else();

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		printf(
			'<img class="%s" src="',
			esc_html(
				$this->get_field_class(
					'icon',
					$markup_field_data
				)
			),
		);
		$markup_field_data->get_template_generator()->print_array_item( $field_id, 'value' );
		echo '" loading="lazy" alt="icon">';

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		$markup_field_data->get_template_generator()->print_end_if();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'type'  => '',
			'value' => '',
		);

		$value = $variable_field_data->get_value();

		if ( false === is_array( $value ) ) {
			return $args;
		}

		$args['type'] = $this->get_string_arg( 'type', $value );

		switch ( $args['type'] ) {
			case 'dashicons':
			case 'url':
				$args['value'] = $this->get_string_arg( 'value', $value );
				break;
			case 'media_library':
				$attachment_id = $this->get_string_arg( 'value', $value );

				$variable_field_data->set_value( $attachment_id );

				$args = array_merge( $args, $this->image_field->get_template_variables( $variable_field_data ) );
				break;
		}

		return $args;
	}

	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'type'  => 'dashicons',
			'value' => 'dashicons-admin-generic',
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return false;
	}
}
