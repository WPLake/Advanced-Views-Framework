<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Post_Thumbnail_Field extends Image_Field {
	use Custom_Field;

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$post = $this->get_post( $variable_field_data->get_value() );

		if ( null === $post ) {
			$variable_field_data->set_value( 0 );

			return parent::get_template_variables( $variable_field_data );
		}

		$image_id = 'attachment' !== $post->post_type ?
			(int) get_post_thumbnail_id( $post ) :
			$post->ID;

		$variable_field_data->set_value( $image_id );

		return parent::get_template_variables( $variable_field_data );
	}
}
