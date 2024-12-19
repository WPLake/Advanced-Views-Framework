<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;

defined( 'ABSPATH' ) || exit;

interface Data_Vendor_Interface {
	public function get_name(): string;

	public function is_meta_vendor(): bool;

	public function is_available(): bool;

	public function make_integration_instance(
		Item_Data $item_data,
		Views_Data_Storage $views_data_storage,
		Data_Vendors $data_vendors,
		Views_Cpt_Save_Actions $views_cpt_save_actions,
		View_Factory $view_factory,
		Repeater_Field_Data $repeater_field_data,
		View_Shortcode $view_shortcode,
		Settings $settings
	): ?Data_Vendor_Integration_Interface;

	public function get_group_key( string $group_id ): string;

	/**
	 * @return array<string, string>
	 */
	public function get_group_choices(): array;

	/**
	 * @param string[] $include_only_types
	 *
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_field_choices(
		array $include_only_types = array(),
		bool $is_meta_format = false,
		bool $is_field_name_as_label = false
	): array;

	/**
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_sub_field_choices( bool $is_meta_format = false, bool $is_field_name_as_label = false ): array;

	/**
	 * @return array<string, array<int,string|int>>
	 */
	public function get_field_key_conditional_rules( bool $is_sub_fields = false ): array;

	/**
	 * @return string[]
	 */
	public function get_supported_field_types(): array;

	public function get_markup_field_instance( string $field_type ): ?Markup_Field_Interface;

	public function is_empty_value_supported_in_markup( string $field_type ): bool;

	/**
	 * @param array<string,mixed> $data
	 */
	public function fill_field_meta( Field_Meta_Interface $field_meta, array $data = array() ): void;

	/**
	 * @param array<string|int,mixed>|null $local_data
	 *
	 * @return mixed
	 */
	public function get_field_value(
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		Source $source,
		?Item_Data $item_data = null,
		bool $is_formatted = false,
		?array $local_data = null
	);

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime;

	public function convert_date_to_string_for_db_comparison(
		DateTime $date_time,
		Field_Meta_Interface $field_meta
	): string;

	/**
	 * @return string[]
	 */
	public function get_field_front_assets( Field_Data $field_data ): array;

	/**
	 * @return string[]
	 */
	public function get_field_types_with_sub_fields(): array;

	/**
	 * @return null|array{title:string,url:string}
	 */
	public function get_group_link_by_group_id( string $group_id ): ?array;

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_group_export_data( string $group_id ): ?array;

	/**
	 * @param array<string, mixed> $groups_data
	 *
	 * @return array<string, mixed>
	 */
	public function get_export_meta_data( array $groups_data ): array;

	/**
	 * @param array<int|string, mixed> $group_data
	 * @param array<string, mixed> $meta_data
	 */
	public function import_group( array $group_data, array $meta_data ): ?string;
}
