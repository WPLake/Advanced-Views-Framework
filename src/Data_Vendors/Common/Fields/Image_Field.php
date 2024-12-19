<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Front_Asset\Acf_Views_Lightbox_Front_Asset;
use Org\Wplake\Advanced_Views\Front_Asset\Light_Gallery_Front_Asset;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Image_Field extends Markup_Field {
	protected function print_inner_attributes( string $field_id, Markup_Field_Data $markup_data ): void {
		$inner_attributes = array();

		foreach ( $markup_data->get_field_assets() as $field_asset ) {
			$inner_attributes = array_merge(
				$inner_attributes,
				$field_asset->get_inner_variable_attributes( $markup_data->get_field_data(), $field_id )
			);
		}

		foreach ( $inner_attributes as $name => $variable_info ) {
			$markup_data->get_template_generator()->print_array_item_attribute(
				$name,
				$variable_info['field_id'],
				$variable_info['item_key']
			);
		}
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$attributes_map = array(
			'src'      => 'value',
			'width'    => 'width',
			'height'   => 'height',
			'alt'      => 'alt',
			'decoding' => 'decoding',
			'loading'  => 'loading',
			'srcset'   => 'srcset',
			'sizes'    => 'sizes',
		);

		printf(
			'<img class="%s"',
			esc_html(
				$this->get_field_class(
					'image',
					$markup_field_data
				)
			),
		);

		foreach ( $attributes_map as $attribute_name => $item_key ) {
			$markup_field_data->get_template_generator()->print_array_item_attribute( $attribute_name, $field_id, $item_key );
		}

		$this->print_inner_attributes( $field_id, $markup_field_data );

		echo '>';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'width'       => 0,
			'height'      => 0,
			'value'       => '',
			'alt'         => '',
			'caption'     => '',
			'description' => '',
			'srcset'      => '',
			'sizes'       => '',
			'decoding'    => 'async',
			'loading'     => 'lazy',
			'full_size'   => '',
			'svg_content' => '',
			// for cases when image is used inside a gallery,
			// it allows to get image meta fields (using another View shortcode).
			'id'          => 0,
		);

		$image_size = '' !== $variable_field_data->get_field_data()->image_size ?
			$variable_field_data->get_field_data()->image_size :
			'full';

		$value = is_numeric( $variable_field_data->get_value() ) ?
			(int) $variable_field_data->get_value() :
			0;

		if ( 0 === $value ) {
			return $args;
		}

		$image_data = wp_get_attachment_image_src( $value, $image_size );
		$image_data = false === $image_data ?
			array() :
			$image_data;

		$image_src = $image_data[0] ?? '';
		$width     = $image_data[1] ?? 0;
		$height    = $image_data[2] ?? 0;

		if ( '' === $image_src ) {
			return $args;
		}

		$alt = get_post_meta( $value, '_wp_attachment_image_alt', true );

		$args['id']          = $value;
		$args['width']       = $width;
		$args['height']      = $height;
		$args['value']       = $image_src;
		$args['alt']         = is_string( $alt ) || is_numeric( $alt ) ?
			$alt :
			'';
		$args['full_size']   = (string) wp_get_attachment_image_url( $value, 'full' );
		$args['caption']     = get_post_field( 'post_excerpt', $value );
		$args['description'] = get_post_field( 'post_content', $value );

		if ( false !== strpos( $image_src, '.svg' ) ) {
			$attached_file       = (string) get_attached_file( $value );
			$args['svg_content'] = '' !== $attached_file ?
				// @phpcs:ignore
				(string) file_get_contents( $attached_file ) :
				'';
		}

		$image_meta = wp_get_attachment_metadata( $value );

		if ( false === is_array( $image_meta ) ) {
			return $args;
		}

		$sizes_array = array( absint( $width ), absint( $height ) );
		$src_et      = (string) wp_calculate_image_srcset( $sizes_array, $image_src, $image_meta, $value );
		$sizes       = (string) wp_calculate_image_sizes( $sizes_array, $image_src, $image_meta, $value );

		if ( '' === $src_et ||
			'' === $sizes ) {
			return $args;
		}

		$args['srcset'] = $src_et;
		$args['sizes']  = $sizes;

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'width'       => 0,
			'height'      => 0,
			'value'       => '',
			'alt'         => '',
			'caption'     => '',
			'description' => '',
			'srcset'      => '',
			'sizes'       => '',
			'decoding'    => 'async',
			'loading'     => 'lazy',
			'full_size'   => '',
			'id'          => 0,
			'svg_content' => '',
		);

		return array_merge(
			$args,
			array(
				'width'       => 1,
				'height'      => 1,
				'value'       => 'https://wordpress.org/',
				'alt'         => 'wordpress.org',
				'caption'     => 'wp.org screenshot',
				'description' => 'wp.org screenshot',
				'srcset'      => 'https://wordpress.org/ 1w',
				'sizes'       => '(max-width: 1px) 1vw',
				'full_size'   => 'https://wordpress.org/',
				'svg_content' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"></svg>',
			)
		);
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
		$conditional_fields = array( Field_Data::FIELD_IMAGE_SIZE );

		// repeatable fields aren't supported (they've markup like a repeater field).
		if ( null === $field_meta->get_self_repeatable_meta() ) {
			$conditional_fields = array_merge(
				$conditional_fields,
				array(
					Field_Data::FIELD_LIGHTBOX_TYPE,
					Field_Data::FIELD_GALLERY_WITH_LIGHT_BOX,
				)
			);
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}

	public function get_front_assets( Field_Data $field_data ): array {
		$front_assets = array();

		switch ( $field_data->lightbox_type ) {
			case 'simple':
				$front_assets[] = Acf_Views_Lightbox_Front_Asset::NAME;
				break;
			case 'lightgallery_v2':
				$front_assets[] = Light_Gallery_Front_Asset::NAME;
				break;
		}

		return array_merge( parent::get_front_assets( $field_data ), $front_assets );
	}
}
