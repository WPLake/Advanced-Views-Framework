<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Pods\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\File_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Post_Object_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Select_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Taxonomy_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\User_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Pods_Pick_Field extends Markup_Field {
	private Select_Field $select_field;
	private User_Field $user_field;
	private Post_Object_Field $post_object_field;
	private Taxonomy_Field $taxonomy_field;
	private File_Field $field_field;

	public function __construct(
		Select_Field $select_field,
		User_Field $user_field,
		Post_Object_Field $post_object_field,
		Taxonomy_Field $taxonomy_field,
		File_Field $file_field
	) {
		$this->select_field      = $select_field;
		$this->user_field        = $user_field;
		$this->post_object_field = $post_object_field;
		$this->taxonomy_field    = $taxonomy_field;
		$this->field_field       = $file_field;
	}

	protected function get_field_instance( Field_Meta_Interface $field_meta ): Markup_Field {
		switch ( $field_meta->get_return_format() ) {
			case 'post_type':
				return $this->post_object_field;
			case 'taxonomy':
				return $this->taxonomy_field;
			case 'user':
				return $this->user_field;
			case 'media':
				return $this->field_field;
		}

		// comment & nav_menu aren't supported (atm there is no single instance for them).

		return $this->select_field;
	}

	/**
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function get_instance_value( Markup_Field $field_instance, $value ) {
		if ( $field_instance instanceof Post_Object_Field ||
			$field_instance instanceof User_Field ||
			$field_instance instanceof File_Field ) {
			if ( false === is_array( $value ) ) {
				return 0;
			}

			$id = $this->get_int_arg( 'ID', $value );

			// in Pod Blocks 'id' is in lower case.
			return 0 !== $id ?
				$id :
				$this->get_int_arg( 'id', $value );
		} elseif ( $field_instance instanceof Taxonomy_Field ) {
			if ( false === is_array( $value ) ) {
				return 0;
			}

			return $this->get_int_arg( 'term_id', $value );
		}

		return $value;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$this->get_field_instance( $markup_field_data->get_field_meta() )
			->print_markup( $field_id, $markup_field_data );
	}

	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$field_instance = $this->get_field_instance( $variable_field_data->get_field_meta() );

		if ( true === $variable_field_data->get_field_meta()->is_multiple() ) {
			if ( true === is_array( $variable_field_data->get_value() ) ) {
				$variable_field_data->set_value(
					array_map(
						function ( $value ) use ( $field_instance ) {
							return $this->get_instance_value( $field_instance, $value );
						},
						$variable_field_data->get_value()
					)
				);
			} else {
				$variable_field_data->set_value( array() );
			}
		} else {
			$variable_field_data->set_value( $this->get_instance_value( $field_instance, $variable_field_data->get_value() ) );
		}

		return $field_instance->get_template_variables( $variable_field_data );
	}

	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return $this->get_field_instance( $variable_field_data->get_field_meta() )->get_validation_template_variables( $variable_field_data );
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return $this->get_field_instance( $field_meta )->is_with_field_wrapper( $view_data, $field, $field_meta );
	}

	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return $this->get_field_instance( $field_meta )->get_conditional_fields( $field_meta );
	}

	public function get_front_assets( Field_Data $field_data ): array {
		return $this->get_field_instance( $field_data->get_field_meta() )->get_front_assets( $field_data );
	}
}
