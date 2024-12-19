<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Pods\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\File_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Gallery_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Pods_Upload_Field extends Markup_Field {
	const LOOP_ITEM_NAME = 'file_item';

	private Image_Field $image_field;
	private File_Field $file_field;
	private Gallery_Field $gallery_field;

	public function __construct( Image_Field $image_field, File_Field $file_field, Gallery_Field $gallery_field ) {
		$this->image_field   = $image_field;
		$this->file_field    = $file_field;
		$this->gallery_field = $gallery_field;
	}

	protected function get_field_instance( Field_Meta_Interface $field_meta ): Markup_Field {
		if ( false === in_array( $field_meta->get_return_format(), array( 'images', 'images-any' ), true ) ) {
			return $this->file_field;
		}

		return true === $field_meta->is_multiple() ?
			$this->gallery_field :
			$this->image_field;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$this->get_field_instance( $markup_field_data->get_field_meta() )
			->print_markup( $field_id, $markup_field_data );
	}

	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		if ( true === $variable_field_data->get_field_meta()->is_multiple() ) {
			if ( true === is_array( $variable_field_data->get_value() ) ) {
				$variable_field_data->set_value(
					array_map(
						function ( $value ) {
							if ( false === is_array( $value ) ) {
								return 0;
							}

							$id = $this->get_int_arg( 'ID', $value );

							// in Pod Blocks 'id' is in lower case.
							return 0 !== $id ?
								$id :
								$this->get_int_arg( 'id', $value );
						},
						$variable_field_data->get_value()
					)
				);
			} else {
				$variable_field_data->set_value( array() );
			}
		} elseif ( true === is_array( $variable_field_data->get_value() ) ) {
			$id = $this->get_int_arg( 'ID', $variable_field_data->get_value() );
			// in Pod Blocks 'id' is in lower case.
			$variable_field_data->set_value(
				0 !== $id ?
					$id :
					$this->get_int_arg( 'id', $variable_field_data->get_value() )
			);
		} else {
			$variable_field_data->set_value( 0 );
		}

		return $this->get_field_instance( $variable_field_data->get_field_meta() )->get_template_variables( $variable_field_data );
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
