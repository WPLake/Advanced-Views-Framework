<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\User_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Post_Author_Field extends User_Field {
	use Custom_Field;

	/**
	 * @param mixed $post_id
	 */
	protected function get_post_author_id( $post_id ): ?int {
		$post = $this->get_post( $post_id );

		if ( null === $post ) {
			return null;
		}

		$author_id = get_post_field( 'post_author', $post );
		$author    = '' !== $author_id ?
			get_user_by( 'ID', $author_id ) :
			null;

		return $author->ID ?? null;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		$variable_field_data->set_value( $this->get_post_author_id( $variable_field_data->get_value() ) );

		return parent::get_item_template_args( $variable_field_data );
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Data::FIELD_ACF_VIEW_ID,
			)
		);
	}
}
