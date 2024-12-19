<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class User_Field extends List_Field {
	const LOOP_ITEM_NAME = 'user_item';

	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	/**
	 * @return array{url: string, title: string}
	 */
	protected function get_user_info( int $id ): array {
		$user_info = array(
			'url'   => '',
			'title' => '',
		);

		$user = get_user_by( 'ID', $id );

		if ( false === $user ) {
			return $user_info;
		}

		return array(
			'url'   => get_author_posts_url( $user->ID ),
			'title' => $user->display_name,
		);
	}

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
		$this->link_field->print_markup( $item_id, $markup_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		$id = is_numeric( $variable_field_data->get_value() ) ?
			(int) $variable_field_data->get_value() :
			0;

		$variable_field_data->set_value( $this->get_user_info( $id ) );

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
