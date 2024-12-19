<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Post_Title_Link_Field extends Link_Field {
	use Custom_Field;

	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$post = $this->get_post( $variable_field_data->get_value() );

		if ( null === $post ) {
			$variable_field_data->set_value( array() );

			return parent::get_template_variables( $variable_field_data );
		}

		$field_args = array(
			// @phpstan-ignore-next-line
			'url'   => (string) get_the_permalink( $post ),
			// decode to avoid double encoding in Twig.
			'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
		);

		$variable_field_data->set_value( $field_args );

		return parent::get_template_variables( $variable_field_data );
	}
}
