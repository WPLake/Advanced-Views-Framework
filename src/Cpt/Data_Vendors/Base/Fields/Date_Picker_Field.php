<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields;

use DateTime;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;

defined( 'ABSPATH' ) || exit;

class Date_Picker_Field extends Markup_Field {
	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$var = $markup_field_data->get_token_factory()->variable( $field_id )->add_item_path( 'value' );
		$markup_field_data->get_token_factory()->to_echo( $var )->print();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value'     => '',
			'timestamp' => 0,
		);

		$date_time = $variable_field_data->convert_value_to_date_time();

		if ( null === $date_time ) {
			return $args;
		}

		return array_merge(
			$args,
			array(
				// date_i18n() unlike the '$date->format($displayFormat)' supports different languages.
				'value'     => date_i18n(
					$variable_field_data->get_field_meta()->get_display_format(),
					$date_time->getTimestamp()
				),
				'timestamp' => $date_time->getTimestamp(),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$now = new DateTime();

		return array(
			'value'     => $now->format( $variable_field_data->get_field_meta()->get_display_format() ),
			'timestamp' => $now->getTimestamp(),
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
