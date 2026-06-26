<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields;

use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;

defined( 'ABSPATH' ) || exit;

class Plain_Field extends Markup_Field {
	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$var = $markup_field_data->get_token_factory()->variable( $field_id )->add_item_path( 'value' );
		$markup_field_data->get_token_factory()->to_echo( $var )->print();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$twig_args = array(
			'value' => '',
		);

		$value = is_string( $variable_field_data->get_value() ) ||
				is_numeric( $variable_field_data->get_value() ) ?
			(string) $variable_field_data->get_value() :
			'';

		return array_merge(
			$twig_args,
			array(
				'value' => $value,
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value' => '1',
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
