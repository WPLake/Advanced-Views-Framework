<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Taxonomy_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class Mb_Taxonomy_Field extends Taxonomy_Field {
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		if ( false === $variable_field_data->get_field_meta()->is_multiple() ) {
			$variable_field_data->set_value(
				true === ( $variable_field_data->get_value() instanceof WP_Term ) ?
					$variable_field_data->get_value()->term_id :
					0
			);
		} else {
			$variable_field_data->set_value(
				true === is_array( $variable_field_data->get_value() ) ?
					array_map(
						function ( $term ) {
							return true === $term instanceof WP_Term ?
								$term->term_id :
								0;
						},
						$variable_field_data->get_value()
					) :
					array()
			);
		}

		return parent::get_template_variables( $variable_field_data );
	}
}
