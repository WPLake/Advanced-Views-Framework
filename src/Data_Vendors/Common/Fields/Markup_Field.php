<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;

defined( 'ABSPATH' ) || exit;

abstract class Markup_Field implements Markup_Field_Interface {
	use Safe_Array_Arguments;

	protected function print_item_markup( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
	}

	protected function print_item( string $field_id, string $item_id, Markup_Field_Data $markup_data ): void {
		$item_outers = $markup_data->get_item_outers( $field_id, $item_id );

		$markup_data->print_opening_item_outers( $item_outers );
		$this->print_item_markup( $field_id, $item_id, $markup_data );
		$markup_data->print_closing_item_outers( $item_outers );
	}

	protected function get_field_class( string $suffix, Markup_Field_Data $markup_data ): string {
		if ( Cpt_Data::CLASS_GENERATION_NONE === $markup_data->get_view_data()->classes_generation ) {
			return '';
		}

		$classes      = array();
		$is_first_tag = ! $markup_data->is_with_row_wrapper() &&
						! $markup_data->is_with_field_wrapper();

		if ( $is_first_tag ) {
			$classes[] = $markup_data->get_view_data()->get_bem_name() . '__' . $markup_data->get_field_data()->id;

			if ( ! $markup_data->get_view_data()->is_with_common_classes ) {
				return implode( ' ', $classes );
			}
		}

		$classes[] = $this->get_item_class( $suffix, $markup_data->get_view_data(), $markup_data->get_field_data() );

		if ( ! $markup_data->is_with_field_wrapper() &&
			$markup_data->get_view_data()->is_with_common_classes ) {
			$classes[] = $markup_data->get_view_data()->get_bem_name() . '__field';
		}

		return implode( ' ', $classes );
	}

	// method is kept for backward compatibility, use the View->getItemClass() instead.
	protected function get_item_class( string $suffix, View_Data $view_data, Field_Data $field ): string {
		return $view_data->get_item_class( $suffix, $field );
	}

	public function is_empty_value_supported_in_markup(): bool {
		return false;
	}

	public function get_custom_field_wrapper_tag(): string {
		return '';
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array();
	}

	public function is_sub_fields_supported(): bool {
		return false;
	}

	public function get_front_assets( Field_Data $field_data ): array {
		return array();
	}
}
