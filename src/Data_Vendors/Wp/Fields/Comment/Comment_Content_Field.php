<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Comment_Content_Field extends Markup_Field {
	use Custom_Field;

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$markup_field_data->get_template_generator()->print_array_item( $field_id, 'value', true );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => $variable_field_data->get_field_data()->default_value,
		);

		$comment = $this->get_comment( $variable_field_data->get_value() );

		if ( null === $comment ) {
			return $args;
		}

		$content = $comment->comment_content;

		// 1. avoid double encoding
		// 2. do not use get_comment_text() because it adds 'In reply to' text
		$args['value'] = '' !== $content ?
			html_entity_decode( $content, ENT_QUOTES ) :
			$args['value'];

		// replace new lines with <br>.
		$args['value'] = str_replace( "\n", '<br>', $args['value'] );

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value' => 'content',
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}
}
