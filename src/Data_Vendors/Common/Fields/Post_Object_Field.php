<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

defined( 'ABSPATH' ) || exit;

class Post_Object_Field extends List_Field {
	const LOOP_ITEM_NAME = 'post_item';

	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	/**
	 * @return array{url: string, title: string}
	 */
	protected function get_post_info( int $id ): array {
		$post_info = array(
			'url'   => '',
			'title' => '',
		);

		$post = get_post( $id );

		if ( null === $post ) {
			return $post_info;
		}

		$title = get_the_title( $post );

		return array(
			'url'   => (string) get_permalink( $post->ID ),
			// avoid double encoding in Twig.
			'title' => html_entity_decode( $title, ENT_QUOTES ),
		);
	}

	protected function print_internal_item_layout( string $item_id, Markup_Field_Data $markup_field_data ): void {
		$markup_field_data->set_is_with_field_wrapper(
			$markup_field_data->get_field_meta()->is_multiple() ||
			$markup_field_data->is_with_field_wrapper()
		);

		$this->link_field->print_markup( $item_id, $markup_field_data );
	}

	protected function print_external_item_layout( string $field_id, string $item_id, Markup_Field_Data $markup_field_data ): void {
		$object_id_source = $markup_field_data->get_field_meta()->is_multiple() ?
			'post_item' :
			$field_id;

		printf(
			'[%s view-id="{{ %s.view_id }}" object-id="{{ %s.value }}"]',
			esc_html( Hard_Layout_Cpt::cpt_name() ),
			esc_html( $field_id ),
			esc_html( $object_id_source )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array_merge(
			parent::get_template_variables( $variable_field_data ),
			array(
				'view_id' => $variable_field_data->get_field_data()->get_short_unique_acf_view_id(),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array_merge(
			parent::get_template_variables( $variable_field_data ),
			array(
				'view_id' => $variable_field_data->get_field_data()->get_short_unique_acf_view_id(),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value' => $variable_field_data->get_value(),
			);
		}

		$value = int( $variable_field_data->get_value() );

		$link_args = $this->get_post_info( $value );

		$variable_field_data->set_value( $link_args );

		return $this->link_field->get_template_variables( $variable_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array {
		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value' => '',
			);

		}

		return $this->link_field->get_validation_template_variables( $variable_field_data );
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		$conditional_fields = array(
			Field_Settings::FIELD_LINK_LABEL,
			Field_Settings::FIELD_IS_LINK_TARGET_BLANK,
			Field_Settings::FIELD_ACF_VIEW_ID,
		);

		if ( $field_meta->is_multiple() ) {
			$conditional_fields[] = Field_Settings::FIELD_SLIDER_TYPE;
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
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
