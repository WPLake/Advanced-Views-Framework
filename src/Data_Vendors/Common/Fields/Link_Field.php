<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Link_Field extends Markup_Field {
	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		echo '<a';
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'target', $field_id, 'target' );
		printf(
			' class="%s"',
			esc_html(
				$this->get_field_class(
					'link',
					$markup_field_data
				)
			)
		);
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'href', $field_id, 'value' );
		echo '>';

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		$markup_field_data->get_template_generator()->print_filled_array_item( $field_id, 'linkLabel', 'title' );

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</a>';
	}

	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value'     => '',
			'target'    => '_self',
			'title'     => '',
			'linkLabel' => $variable_field_data->get_field_data()->get_link_label_translation(),
		);

		$value = is_array( $variable_field_data->get_value() ) ?
			$variable_field_data->get_value() :
			array();

		if ( array() === $value ) {
			return $args;
		}

		$is_target_set = isset( $value['target'] ) && $value['target'];

		$args['value']  = (string) ( $value['url'] ?? '' );
		$args['title']  = (string) ( $value['title'] ?? '' );
		$args['target'] = $variable_field_data->get_field_data()->is_link_target_blank || $is_target_set ?
			'_blank' :
			'_self';

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value'     => 'https://wordpress.org/',
			'target'    => '_self',
			'title'     => 'wordpress.org',
			'linkLabel' => $variable_field_data->get_field_data()->get_link_label_translation(),
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
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Data::FIELD_LINK_LABEL,
				Field_Data::FIELD_IS_LINK_TARGET_BLANK,
			)
		);
	}
}
