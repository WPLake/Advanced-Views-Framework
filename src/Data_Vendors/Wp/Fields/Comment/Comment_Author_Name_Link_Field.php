<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Comment_Author_Name_Link_Field extends Markup_Field {
	use Custom_Field;

	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$this->link_field->print_markup( $field_id, $markup_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$comment = $this->get_comment( $variable_field_data->get_value() );

		if ( null === $comment ) {
			$variable_field_data->set_value( array() );

			return $this->link_field->get_template_variables( $variable_field_data );
		}

		$author_name = get_comment_author( $comment );
		$author_url  = get_comment_author_url( $comment );

		$filed_args = array(
			'url'   => $author_url,
			// avoid double escaping in Twig.
			'title' => html_entity_decode( $author_name, ENT_QUOTES ),
		);

		$variable_field_data->set_value( $filed_args );

		return $this->link_field->get_template_variables( $variable_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return $this->link_field->get_validation_template_variables( $variable_field_data );
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return $view_data->is_with_unnecessary_wrappers ||
				$this->link_field->is_with_field_wrapper( $view_data, $field, $field_meta );
	}
}
