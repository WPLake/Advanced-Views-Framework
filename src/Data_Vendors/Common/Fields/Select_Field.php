<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Select_Field extends List_Field {
	const LOOP_ITEM_NAME = 'choice_item';

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
		$twig_name = $markup_data->get_field_meta()->is_multiple() ?
			'choice_item' :
			$field_id;

		$markup_data->set_is_with_field_wrapper(
			$markup_data->get_field_meta()->is_multiple() ||
			$markup_data->is_with_field_wrapper()
		);

		printf(
			'<div class="%s">',
			esc_html(
				$this->get_field_class( 'choice', $markup_data )
			)
		);

		echo "\r\n";
		$markup_data->increment_and_print_tabs();

		$markup_data->get_template_generator()->print_array_item( $twig_name, 'title' );

		echo "\r\n";
		$markup_data->decrement_and_print_tabs();

		echo '</div>';
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		$value = is_string( $variable_field_data->get_value() ) ?
			$variable_field_data->get_value() :
			'';

		return array(
			'title' => $variable_field_data->get_field_meta()->get_choices()[ $value ] ?? '',
			'value' => $value,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array_merge(
			parent::get_template_variables( $variable_field_data ),
			array(
				'choices' => $variable_field_data->get_field_meta()->get_choices(),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array {
		return array(
			'title' => 'Option',
			'value' => 'option',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array_merge(
			parent::get_validation_template_variables( $variable_field_data ),
			array(
				'choices' => array(
					'option' => 'Option',
				),
			)
		);
	}
}
