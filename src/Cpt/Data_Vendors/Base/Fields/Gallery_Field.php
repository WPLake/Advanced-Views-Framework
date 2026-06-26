<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields;

use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\View_Assets\Light_Gallery_Asset;
use Org\Wplake\Advanced_Views\Cpt\View_Assets\Macy_Asset;
use Org\Wplake\Advanced_Views\Cpt\View_Assets\Masonry_Asset;
use Org\Wplake\Advanced_Views\Cpt\View_Assets\Splide_Asset;

defined( 'ABSPATH' ) || exit;

class Gallery_Field extends Markup_Field {
	protected Image_Field $image_field;

	public function __construct( Image_Field $image_field ) {
		$this->image_field = $image_field;
	}

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_field_data ): void {
		$markup_field_data->set_is_with_field_wrapper( true );

		$this->image_field->print_markup( $item_id, $markup_field_data );
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory = $markup_field_data->get_token_factory();

		$source_var = $token_factory->variable( $field_id )
									->add_item_path( 'value' );
		$item_var   = $token_factory->variable( 'image_item' );
		$loop_body  = $token_factory->html(
			function () use ( $markup_field_data, $field_id, $item_var, $token_factory ) {
				$token_factory->format()
					->new_line();
				$markup_field_data->increment_and_print_tabs();

				$this->print_item( $field_id, $item_var->get_name(), $markup_field_data );

				$token_factory->format()
						->new_line();
				$markup_field_data->decrement_and_print_tabs();
			}
		);

		$loop = $token_factory->loop()
						->set_source_variable( $source_var )
						->set_item_variable( $item_var )
						->set_body( $loop_body );

		$token_factory->format()
						->new_line();
		$markup_field_data->print_tabs();

		$loop->print();

		$token_factory->format()
						->new_line();
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
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
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
			$conditional_fields[] = Field_Settings::FIELD_GALLERY_TYPE;
			$conditional_fields[] = Field_Settings::FIELD_SLIDER_TYPE;
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}

	public function get_front_assets( Field_Settings $field_settings ): array {
		$front_assets = $this->image_field->get_front_assets( $field_settings );

		switch ( $field_settings->gallery_type ) {
			case 'masonry':
				$front_assets[] = Masonry_Asset::NAME;
				break;
			case 'lightgallery_v2':
				$front_assets[] = Light_Gallery_Asset::NAME;
				break;
			case 'macy_v2':
				$front_assets[] = Macy_Asset::NAME;
				break;
		}

		if ( 'splide_v4' === $field_settings->slider_type ) {
			$front_assets[] = Splide_Asset::NAME;
		}

		return array_merge( parent::get_front_assets( $field_settings ), $front_assets );
	}
}
