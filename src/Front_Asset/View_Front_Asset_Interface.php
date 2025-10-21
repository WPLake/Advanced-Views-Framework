<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;

defined( 'ABSPATH' ) || exit;

interface View_Front_Asset_Interface extends Front_Asset_Interface {
	public function get_row_wrapper_class( string $row_type ): string;

	public function get_row_wrapper_tag( Field_Settings $field_settings, string $row_type ): string;

	public function get_field_wrapper_tag( Field_Settings $field_settings, string $row_type ): string;

	/**
	 * @return array<string,string>
	 */
	public function get_field_wrapper_attrs( Field_Settings $field_settings, string $field_id ): array;

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_field_outers(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		string $field_id,
		string $row_type
	): array;

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_item_outers(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		string $field_id,
		string $item_id
	): array;

	/**
	 * @return array<string,array{field_id:string,item_key:string,}>
	 */
	public function get_inner_variable_attributes( Field_Settings $field_settings, string $field_id ): array;

	public function is_label_out_of_row(): bool;
}
