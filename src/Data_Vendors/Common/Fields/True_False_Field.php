<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class True_False_Field extends Markup_Field {
	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$safe_classes = esc_html( $this->get_item_class( 'true-false--state--:', $markup_field_data->get_view_data(), $markup_field_data->get_field_data() ) );

		ob_start();
		$markup_field_data->get_template_generator()->print_array_item( $field_id, 'state' );
		$code = (string) ob_get_clean();

		$safe_classes = str_replace( ':', $code, $safe_classes );

		printf(
			'<div class="%s %s"></div>',
			esc_html(
				$this->get_field_class(
					'true-false',
					$markup_field_data
				)
			),
			// @phpcs:ignore WordPress.Security.EscapeOutput
			$safe_classes
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$is_checked = in_array( $variable_field_data->get_value(), array( 1, '1', true ), true );

		$args = array(
			'value' => $is_checked,
			'state' => $is_checked ?
				'checked' :
				'unchecked',
		);

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$is_checked = in_array( $variable_field_data->get_value(), array( 1, '1', true ), true );

		$args = array(
			'value' => $is_checked,
			'state' => $is_checked ?
				'checked' :
				'unchecked',
		);

		return $args;
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return $view_data->is_with_unnecessary_wrappers;
	}

	// mark that false value still is supported in markup.
	public function is_empty_value_supported_in_markup(): bool {
		return true;
	}
}
