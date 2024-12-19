<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class File_Field extends List_Field {
	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
		$this->link_field->print_markup( $item_id, $markup_data );
	}

	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		$post_id = true === is_numeric( $variable_field_data->get_value() ) ?
			(int) $variable_field_data->get_value() :
			0;

		$variable_field_data->set_value(
			0 !== $post_id ?
				array(
					'url'   => (string) wp_get_attachment_url( $post_id ),
					'title' => get_post( $post_id )->post_title ?? '',
				) :
				array()
		);

		return $this->link_field->get_template_variables( $variable_field_data );
	}

	protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array {
		return $this->link_field->get_validation_template_variables( $variable_field_data );
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		$conditional_fields = array(
			Field_Data::FIELD_LINK_LABEL,
			Field_Data::FIELD_IS_LINK_TARGET_BLANK,
			Field_Data::FIELD_ACF_VIEW_ID,
		);

		if ( true === $field_meta->is_multiple() ) {
			$conditional_fields[] = Field_Data::FIELD_SLIDER_TYPE;
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}
}
