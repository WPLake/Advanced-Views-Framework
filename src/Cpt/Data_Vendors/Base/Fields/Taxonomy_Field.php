<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\List_Field;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use WP_Term;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

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

	protected function print_internal_item_layout( string $item_id, Markup_Field_Data $markup_field_data ): void {
		$markup_field_data->set_is_with_field_wrapper(
			$markup_field_data->get_field_meta()->is_multiple() ||
			$markup_field_data->is_with_field_wrapper()
		);

		$this->link_field->print_markup( $item_id, $markup_field_data );
	}

	protected function print_external_item_layout( string $field_id, string $item_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory    = $markup_field_data->get_token_factory();
		$object_id_source = $markup_field_data->get_field_meta()->is_multiple() ?
			'term_item' :
			$field_id;

		$id_var      = $token_factory->variable( $field_id )
										->add_item_path( 'layout_id' );
		$term_id_var = $token_factory->variable( $object_id_source )
										->add_item_path( 'value' );

		printf( '[%s', esc_html( Hard_Layout_Cpt::cpt_name() ) );

		$token_factory->format()
			->attributes(
				array(
					'id'        => $id_var,
					'object-id' => 'term',
					'term-id'   => $term_id_var,
				)
			);

		echo ']';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$layout_id = $variable_field_data->get_field_data()->get_short_unique_acf_view_id();

		return array_merge(
			parent::get_template_variables( $variable_field_data ),
			array(
				'view_id'   => $layout_id,
				'layout_id' => $layout_id,
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$layout_id = $variable_field_data->get_field_data()->get_short_unique_acf_view_id();

		return array_merge(
			parent::get_validation_template_variables( $variable_field_data ),
			array(
				'view_id'   => $layout_id,
				'layout_id' => $layout_id,
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		$term_id = $variable_field_data->get_value();
		$wp_term = is_numeric( $term_id ) ?
			get_term( (int) $term_id ) :
			null;

		$parent_id = $wp_term instanceof WP_Term ?
			$wp_term->parent :
			0;

		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value'     => $term_id,
				'parent_id' => $parent_id,
			);
		}

		$value = int( $variable_field_data->get_value() );

		$variable_field_data->set_value( $this->get_term_info( $value ) );

		return array_merge(
			$this->link_field->get_template_variables( $variable_field_data ),
			array(
				'parent_id' => $parent_id,
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array {
		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value'     => 0,
				'parent_id' => 0,
			);
		}

		return array_merge(
			$this->link_field->get_validation_template_variables( $variable_field_data ),
			array(
				'parent_id' => 0,

			)
		);
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
