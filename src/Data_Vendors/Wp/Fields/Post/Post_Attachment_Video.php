<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Post_Attachment_Video extends Markup_Field {
	use Custom_Field;

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$template_generator = $markup_field_data->get_template_generator();

		printf(
			'<video class="%s" controls>',
			esc_html(
				$this->get_field_class(
					'element',
					$markup_field_data
				)
			),
		);
		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();
		echo '<source';
		$template_generator->print_array_item_attribute( 'src', $field_id, 'value' );
		$template_generator->print_array_item_attribute( 'type', $field_id, 'mime_type' );
		echo '>';
		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();
		echo '</video>';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$post = $this->get_post( $variable_field_data->get_value() );

		if ( null === $post ||
			'attachment' !== $post->post_type ||
			0 !== strpos( $post->post_mime_type, 'video/' ) ) {
			return array(
				'value'     => '',
				'mime_type' => '',
			);
		}

		$attachment_url = (string) wp_get_attachment_url( $post->ID );

		return array(
			'value'     => $attachment_url,
			'mime_type' => $post->post_mime_type,
		);
	}

	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value'     => 'https://site.com/video.mp4',
			'mime_type' => 'video/mp4',
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return false;
	}
}
