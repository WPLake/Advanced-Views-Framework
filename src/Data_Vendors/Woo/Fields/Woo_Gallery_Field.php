<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Gallery_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Woo_Gallery_Field extends Gallery_Field {
	use Custom_Field;

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => array(),
		);

		$product = $this->get_product( $variable_field_data->get_value() );

		if ( null === $product ) {
			return $args;
		}

		$image_ids = $product->get_gallery_image_ids();

		foreach ( $image_ids as $image_id ) {
			$variable_field_data->set_value( $image_id );

			$args['value'][] = $this->image_field->get_template_variables( $variable_field_data );
		}

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => array(),
		);

		$value   = array();
		$value[] = $this->image_field->get_validation_template_variables( $variable_field_data );

		return array_merge(
			$args,
			array(
				'value' => $value,
			)
		);
	}
}
