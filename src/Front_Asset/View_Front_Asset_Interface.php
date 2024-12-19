<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;

defined( 'ABSPATH' ) || exit;

interface View_Front_Asset_Interface extends Front_Asset_Interface {
	public function get_row_wrapper_class( string $row_type ): string;

	public function get_row_wrapper_tag( Field_Data $field_data, string $row_type ): string;

	public function get_field_wrapper_tag( Field_Data $field_data, string $row_type ): string;

	/**
	 * @return array<string,string>
	 */
	public function get_field_wrapper_attrs( Field_Data $field_data, string $field_id ): array;

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_field_outers(
		View_Data $view_data,
		Field_Data $field_data,
		string $field_id,
		string $row_type
	): array;

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_item_outers(
		View_Data $view_data,
		Field_Data $field_data,
		string $field_id,
		string $item_id
	): array;

	/**
	 * @return array<string,array{field_id:string,item_key:string,}>
	 */
	public function get_inner_variable_attributes( Field_Data $field_data, string $field_id ): array;

	public function is_label_out_of_row(): bool;
}
