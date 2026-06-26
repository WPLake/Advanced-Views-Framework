<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;


defined( 'ABSPATH' ) || exit;

class Post_Thumbnail_Link_Field extends Markup_Field {
	use Custom_Field;

	protected Image_Field $image_field;

	public function __construct( Image_Field $image_field ) {
		$this->image_field = $image_field;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory = $markup_field_data->get_token_factory();

		$target_var = $token_factory->variable( $field_id )
										->add_item_path( 'target' );
		$href_var   = $token_factory->variable( $field_id )
										->add_item_path( 'href' );

		echo '<a';

		$token_factory->format()
						->attribute( 'target', $target_var );

		printf(
			' class="%s"',
			esc_html(
				$this->get_field_class( 'link', $markup_field_data )
			)
		);

		$token_factory->format()
						->attribute( 'href', $href_var );

		echo '>';

		$token_factory->format()
						->new_line();

		$markup_field_data->increment_and_print_tabs();

		$markup_field_data->set_is_with_field_wrapper( true );
		$this->image_field->print_markup( $field_id, $markup_field_data );

		$token_factory->format()
						->new_line();

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
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return $layout_settings->is_with_unnecessary_wrappers;
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Settings::FIELD_IMAGE_SIZE,
				Field_Settings::FIELD_IS_LINK_TARGET_BLANK,
			)
		);
	}
}
