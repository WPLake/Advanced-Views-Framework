<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Taxonomy_Field extends List_Field {
	const LOOP_ITEM_NAME = 'term_item';

	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	/**
	 * @return array{url: string, title: string}
	 */
	protected function get_term_info( int $id ): array {
		$post_info = array(
			'url'   => '',
			'title' => '',
		);

		$term = get_term( $id );

		if ( null === $term ||
			is_wp_error( $term ) ) {
			return $post_info;
		}

		$term_link = get_term_link( $term );

		return array(
			'url'   => is_string( $term_link ) ? $term_link : '',
			// decode to avoid double encoding in Twig.
			'title' => html_entity_decode( $term->name, ENT_QUOTES ),
		);
	}

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
		$markup_data->set_is_with_field_wrapper(
			$markup_data->get_field_meta()->is_multiple() ||
			$markup_data->is_with_field_wrapper()
		);

		$this->link_field->print_markup( $item_id, $markup_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		$value = is_numeric( $variable_field_data->get_value() ) ?
			(int) $variable_field_data->get_value() :
			0;

		$variable_field_data->set_value( $this->get_term_info( $value ) );

		return $this->link_field->get_template_variables( $variable_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
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

		if ( $field_meta->is_multiple() ) {
			$conditional_fields[] = Field_Data::FIELD_SLIDER_TYPE;
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}
}
