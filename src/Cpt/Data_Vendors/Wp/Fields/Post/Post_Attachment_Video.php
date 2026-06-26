<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Wp\Fields\Post;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;

class Post_Attachment_Video extends Markup_Field {
	use Custom_Field;

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory = $markup_field_data->get_token_factory();

		printf(
			'<video class="%s" controls>',
			esc_html(
				$this->get_field_class(
					'element',
					$markup_field_data
				)
			),
		);

		$token_factory->format()
						->new_line();
		$markup_field_data->increment_and_print_tabs();

		$src_var  = $token_factory->variable( $field_id )
									->add_item_path( 'value' );
		$type_var = $token_factory->variable( $field_id )
									->add_item_path( 'mime_type' );

		echo '<source';

		$token_factory->format()
						->attributes(
							array(
								'src'  => $src_var,
								'type' => $type_var,
							)
						);

		echo '>';

		$token_factory->format()
						->new_line();
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
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return false;
	}
}
