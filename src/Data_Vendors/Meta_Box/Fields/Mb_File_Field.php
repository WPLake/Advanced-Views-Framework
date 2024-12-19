<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\File_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Mb_File_Field extends File_Field {
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		if ( $variable_field_data->get_field_meta()->is_multiple() ) {
			// ids are array keys.
			$variable_field_data->set_value(
				is_array( $variable_field_data->get_value() ) ?
					array_keys( $variable_field_data->get_value() ) :
					array()
			);
		} else {
			// in this case we've only the url, without any extra info
			// try to get the id from the url.
			$url = is_string( $variable_field_data->get_value() ) ?
				$variable_field_data->get_value() :
				'';
			$variable_field_data->set_value(
				'' !== $url ?
					attachment_url_to_postid( $url ) :
					''
			);
		}

		return parent::get_template_variables( $variable_field_data );
	}
}
