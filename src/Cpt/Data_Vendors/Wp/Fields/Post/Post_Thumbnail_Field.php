<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Wp\Fields\Post;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Front_Asset\Acf_Views_Lightbox_Front_Asset;
use Org\Wplake\Advanced_Views\Front_Asset\Light_Gallery_Front_Asset;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;

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

	public function get_front_assets( Field_Settings $field_settings ): array {
		$front_assets = array();

		switch ( $field_settings->lightbox_type ) {
			case 'simple':
				$front_assets[] = Acf_Views_Lightbox_Front_Asset::NAME;
				break;
			case 'lightgallery_v2':
				$front_assets[] = Light_Gallery_Front_Asset::NAME;
				break;
		}

		return array_merge( parent::get_front_assets( $field_settings ), $front_assets );
	}
}
