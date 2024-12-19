<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\User;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class User_Author_Link_Field extends Link_Field {
	use Custom_Field;

	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$user = $this->get_user( $variable_field_data->get_value() );

		if ( null === $user ) {
			$variable_field_data->set_value( array() );

			return parent::get_template_variables( $variable_field_data );
		}

		$field_args = array(
			'url'   => get_author_posts_url( $user->ID ),
			// decode to avoid double encoding in Twig.
			'title' => html_entity_decode( $user->display_name, ENT_QUOTES ),
		);

		$variable_field_data->set_value( $field_args );

		return parent::get_template_variables( $variable_field_data );
	}
}
