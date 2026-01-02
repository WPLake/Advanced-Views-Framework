<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\User_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Layouts\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Post_Author_Field extends User_Field {
	use Custom_Field;

	// todo remove as it's already handled by User_Field.
	/*
		protected function print_external_item_layout( string $field_id, string $item_id, Markup_Field_Data $markup_field_data ): void {
		printf( '[%s', esc_html( Hard_Layout_Cpt::cpt_name() ) );
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'view-id', $field_id, 'view_id' );
		echo ' object-id="user"';
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'user-id', $field_id, 'value' );
		echo ']';
	}*/

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

		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value' => $variable_field_data->get_value(),
			);
		}

		return parent::get_item_template_args( $variable_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array {
		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value' => $variable_field_data->get_value(),
			);
		}

		return parent::get_validation_item_template_args( $variable_field_data );
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Settings::FIELD_ACF_VIEW_ID,
			)
		);
	}

	public function is_with_field_wrapper(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		if ( $field_settings->has_external_layout() ) {
			return true;
		}

		return parent::is_with_field_wrapper( $layout_settings, $field_settings, $field_meta );
	}
}
