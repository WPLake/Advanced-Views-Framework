<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Mb_Image_Field extends Image_Field {
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		if ( is_array( $variable_field_data->get_value() ) &&
			key_exists( 'ID', $variable_field_data->get_value() ) ) {
			$id = is_numeric( $variable_field_data->get_value()['ID'] ) ?
				$variable_field_data->get_value()['ID'] :
				0;

			$variable_field_data->set_value( $id );
		}

		return parent::get_template_variables( $variable_field_data );
	}
}
