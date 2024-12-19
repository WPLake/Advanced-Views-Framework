<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Term;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Term_Name_Link_Field extends Link_Field {
	use Custom_Field;

	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$term = $this->get_term( $variable_field_data->get_value() );

		if ( null === $term ) {
			$variable_field_data->set_value( array() );

			return parent::get_template_variables( $variable_field_data );
		}

		$term_link = get_term_link( $term );
		// can be WP_Error.
		$term_link = is_string( $term_link ) ?
			$term_link :
			'';

		$field_args = array(
			'url'   => $term_link,
			// decode to avoid double encoding in Twig.
			'title' => html_entity_decode( $term->name, ENT_QUOTES ),
		);

		$variable_field_data->set_value( $field_args );

		return parent::get_template_variables( $variable_field_data );
	}
}
