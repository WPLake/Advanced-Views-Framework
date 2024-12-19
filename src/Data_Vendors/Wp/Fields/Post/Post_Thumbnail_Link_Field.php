<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Post_Thumbnail_Link_Field extends Markup_Field {
	use Custom_Field;

	protected Image_Field $image_field;

	public function __construct( Image_Field $image_field ) {
		$this->image_field = $image_field;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		echo '<a';
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'target', $field_id, 'target' );
		printf(
			' class="%s"',
			esc_html(
				$this->get_field_class( 'link', $markup_field_data )
			)
		);
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'href', $field_id, 'href' );
		echo '>';

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		$markup_field_data->set_is_with_field_wrapper( true );
		$this->image_field->print_markup( $field_id, $markup_field_data );

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</a>';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'target' => $variable_field_data->get_field_data()->is_link_target_blank ?
				'_blank' :
				'_self',
			'href'   => '',
		);

		$post = $this->get_post( $variable_field_data->get_value() );

		if ( null === $post ) {
			$variable_field_data->set_value( 0 );

			return array_merge(
				$args,
				$this->image_field->get_template_variables( $variable_field_data )
			);
		}

		// @phpstan-ignore-next-line
		$args['href'] = (string) get_the_permalink( $post );
		$image_id     = (int) get_post_thumbnail_id( $post );

		$variable_field_data->set_value( $image_id );

		return array_merge(
			$args,
			$this->image_field->get_template_variables( $variable_field_data )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'target' => $variable_field_data->get_field_data()->is_link_target_blank ?
				'_blank' :
				'_self',
			'href'   => '',
		);

		$link_args = $this->image_field->get_validation_template_variables( $variable_field_data );

		return array_merge( $args, $link_args );
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return $view_data->is_with_unnecessary_wrappers;
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Data::FIELD_IMAGE_SIZE,
				Field_Data::FIELD_IS_LINK_TARGET_BLANK,
			)
		);
	}
}
