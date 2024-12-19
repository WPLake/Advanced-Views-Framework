<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Acf\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\List_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Page_Link_Field extends List_Field {
	const LOOP_ITEM_NAME = 'link_item';

	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	/**
	 * @return array{url: string, title: string}
	 */
	protected function get_post_info( string $id_or_url ): array {
		$post_info = array(
			'url'   => '',
			'title' => '',
		);

		if ( is_numeric( $id_or_url ) ) {
			$post = get_post( (int) $id_or_url );
		} else {
			$post_slug = str_replace( get_site_url(), '', $id_or_url );
			$post_slug = trim( $post_slug, '/' );
			$post      = get_page_by_path(
				$post_slug,
				OBJECT,
				array(
					'post',
					'page',
				)
			);
		}

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

	/**
	 * @return array<string, string>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		$value = is_string( $variable_field_data->get_value() ) ||
				is_numeric( $variable_field_data->get_value() ) ?
			(string) $variable_field_data->get_value() :
			'';
		$variable_field_data->set_value( $this->get_post_info( $value ) );

		return $this->link_field->get_template_variables( $variable_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array {
		return $this->link_field->get_validation_template_variables( $variable_field_data );
	}

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
		$markup_data->set_is_with_field_wrapper(
			$markup_data->get_field_meta()->is_multiple() ||
			$markup_data->is_with_field_wrapper()
		);

		$this->link_field->print_markup( $item_id, $markup_data );
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Data::FIELD_LINK_LABEL,
				Field_Data::FIELD_IS_LINK_TARGET_BLANK,
			)
		);
	}
}
