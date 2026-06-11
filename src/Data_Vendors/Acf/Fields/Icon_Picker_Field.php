<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Acf\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Utils\Safe_Array_Arguments;

defined( 'ABSPATH' ) || exit;

class Icon_Picker_Field extends Markup_Field {
	use Safe_Array_Arguments;

	private Image_Field $image_field;

	public function __construct( Image_Field $image_field ) {
		$this->image_field = $image_field;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory = $markup_field_data->get_token_factory();

		$type_var = $token_factory->variable( $field_id )
									->add_item_path( 'type' );

		$dashicons_condition = $token_factory->comparison()
									->set_left_operand( $type_var )
									->set_comparison_equal()
									->set_right_operand( $token_factory->literal( 'dashicons' ) );
		$dashicons_body      = $token_factory->html( fn() => $this->print_icon_markup( $field_id, $markup_field_data ) );

		$media_library_condition = $token_factory->comparison()
			->set_left_operand( $type_var )
			->set_comparison_equal()
			->set_right_operand( $token_factory->literal( 'media_library' ) );
		$media_library_body      = $token_factory->html( fn() => $this->print_icon_image_markup( $field_id, $markup_field_data ) );

		$custom_image_body = $token_factory->html( fn() => $this->print_custom_image_markup( $field_id, $markup_field_data ) );

		$if = $token_factory->if();

		$if->new_if_branch()
			->set_condition( $dashicons_condition )
			->set_body( $dashicons_body );

		$if->new_elseif_branch()
			->set_condition( $media_library_condition )
			->set_body( $media_library_body );

		$if->new_else_branch()
			->set_body( $custom_image_body );

		$if->print();
	}

	protected function print_icon_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		Format_Token::new_line();
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
		$var = $markup_field_data->get_token_factory()->variable( $field_id )->add_item_path( 'value' );
		$markup_field_data->get_token_factory()->to_echo( $var )->print();

		echo '"></i>';

		Format_Token::new_line();
		$markup_field_data->decrement_and_print_tabs();
	}

	protected function print_icon_image_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		Format_Token::new_line();
		$markup_field_data->increment_and_print_tabs();

		$this->image_field->print_markup( $field_id, $markup_field_data );

		Format_Token::new_line();
		$markup_field_data->decrement_and_print_tabs();
	}

	protected function print_custom_image_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		Format_Token::new_line();
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
		$var = $markup_field_data->get_token_factory()->variable( $field_id )->add_item_path( 'value' );
		$markup_field_data->get_token_factory()->to_echo( $var )->print();

		echo '" loading="lazy" alt="icon">';

		Format_Token::new_line();
		$markup_field_data->decrement_and_print_tabs();
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
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return false;
	}
}
