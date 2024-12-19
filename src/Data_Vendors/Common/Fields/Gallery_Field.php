<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Front_Asset\Acf_Views_Masonry_Front_Asset;
use Org\Wplake\Advanced_Views\Front_Asset\Light_Gallery_Front_Asset;
use Org\Wplake\Advanced_Views\Front_Asset\Macy_Front_Asset;
use Org\Wplake\Advanced_Views\Front_Asset\Splide_Front_Asset;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Gallery_Field extends Markup_Field {
	protected Image_Field $image_field;

	public function __construct( Image_Field $image_field ) {
		$this->image_field = $image_field;
	}

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
		$markup_data->set_is_with_field_wrapper( true );

		$this->image_field->print_markup( $item_id, $markup_data );
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		echo "\r\n";
		$markup_field_data->print_tabs();

		$markup_field_data->get_template_generator()->print_for_of_array_item( $field_id, 'value', 'image_item' );

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		$this->print_item( $field_id, 'image_item', $markup_field_data );

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		$markup_field_data->get_template_generator()->print_end_for();

		echo "\r\n";
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => array(),
		);

		$value = is_array( $variable_field_data->get_value() ) ?
			$variable_field_data->get_value() :
			array();

		if ( array() === $value ) {
			return $args;
		}

		foreach ( $value as $image ) {
			$variable_field_data->set_value( $image );

			$args['value'][] = $this->image_field->get_template_variables( $variable_field_data );
		}

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => array(),
		);

		$value   = array();
		$value[] = $this->image_field->get_validation_template_variables( $variable_field_data );

		return array_merge(
			$args,
			array(
				'value' => $value,
			)
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		$conditional_fields = $this->image_field->get_conditional_fields( $field_meta );

		// repeatable fields aren't supported (they've markup like a repeater field).
		if ( null === $field_meta->get_self_repeatable_meta() ) {
			$conditional_fields[] = Field_Data::FIELD_GALLERY_TYPE;
			$conditional_fields[] = Field_Data::FIELD_SLIDER_TYPE;
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}

	public function get_front_assets( Field_Data $field_data ): array {
		$front_assets = $this->image_field->get_front_assets( $field_data );

		switch ( $field_data->gallery_type ) {
			case 'masonry':
				$front_assets[] = Acf_Views_Masonry_Front_Asset::NAME;
				break;
			case 'lightgallery_v2':
				$front_assets[] = Light_Gallery_Front_Asset::NAME;
				break;
			case 'macy_v2':
				$front_assets[] = Macy_Front_Asset::NAME;
				break;
		}

		if ( 'splide_v4' === $field_data->slider_type ) {
			$front_assets[] = Splide_Front_Asset::NAME;
		}

		return array_merge( parent::get_front_assets( $field_data ), $front_assets );
	}
}
