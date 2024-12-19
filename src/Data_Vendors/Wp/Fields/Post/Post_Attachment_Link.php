<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Post_Attachment_Link extends Link_Field {
	use Custom_Field;

	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$post = $this->get_post( $variable_field_data->get_value() );

		if ( null === $post ||
		'attachment' !== $post->post_type ) {
			$variable_field_data->set_value( array() );
		} else {
			$title = get_the_title( $post );

			// decode to avoid double encoding in Twig.
			$title = '' !== $title ?
				html_entity_decode( $title, ENT_QUOTES ) :
				$title;

			$variable_field_data->set_value(
				array(
					'title' => $title,
					'url'   => (string) wp_get_attachment_url( $post->ID ),
				)
			);
		}

		return parent::get_template_variables( $variable_field_data );
	}
}
