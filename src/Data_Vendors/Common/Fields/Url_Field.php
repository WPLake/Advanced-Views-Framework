<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Url_Field extends Link_Field {
	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$value = is_string( $variable_field_data->get_value() ) ?
			$variable_field_data->get_value() :
			'';

		if ( '' === $value ) {
			$variable_field_data->set_value( array() );

			return parent::get_template_variables( $variable_field_data );
		}

		$field_args = array(
			'url'   => $value,
			'title' => $variable_field_data->get_field_data()->get_label_translation(),
		);

		$variable_field_data->set_value( $field_args );

		return parent::get_template_variables( $variable_field_data );
	}
}
