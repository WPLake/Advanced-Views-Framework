<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Gallery_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Mb_Gallery_Field extends Gallery_Field {
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		// ids are array keys.
		$variable_field_data->set_value(
			is_array( $variable_field_data->get_value() ) ?
				array_keys( $variable_field_data->get_value() ) :
				array()
		);

		return parent::get_template_variables( $variable_field_data );
	}
}
