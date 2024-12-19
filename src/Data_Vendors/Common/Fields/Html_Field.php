<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Html_Field extends Markup_Field {
	private bool $is_formatted_value_in_use;
	private bool $is_with_replace_new_line_to_br;

	/**
	 * @param bool $is_formatted_value_in_use Can be enabled for specific cases, e.g. ACF textarea field (to avoid adding 'p' tags)
	 * @param bool $is_with_replace_new_line_to_br Can be enabled for specific cases, e.g. ACF textarea field
	 */
	public function __construct(
		bool $is_formatted_value_in_use = true,
		bool $is_with_replace_new_line_to_br = false
	) {
		$this->is_formatted_value_in_use      = $is_formatted_value_in_use;
		$this->is_with_replace_new_line_to_br = $is_with_replace_new_line_to_br;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$markup_field_data->get_template_generator()->print_array_item( $field_id, 'value', true );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$twig_args = array(
			'value'     => '',
			'raw_value' => '',
		);

		// by default use the formatted value, as we need html (e.g. for oembed) instead of the pure value.
		$value = true === $this->is_formatted_value_in_use ?
			$variable_field_data->get_formatted_value() :
			$variable_field_data->get_value();

		$value = is_string( $value ) ||
				is_numeric( $value ) ?
			(string) $value :
			'';
		$value = true === $this->is_with_replace_new_line_to_br ?
			str_replace( "\n", '<br/>', $value ) :
			$value;

		return array_merge(
			$twig_args,
			array(
				'value'     => $value,
				// always provide the raw value alongside with the current,
				// e.g. in oEmbed case, it allows to get the url instead of the iframe's html.
				'raw_value' => $variable_field_data->get_value(),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value'     => '1',
			'raw_value' => '1',
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}
}
