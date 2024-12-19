<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Woo;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Gallery_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Height_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Length_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Price_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Regular_Price_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Sale_Price_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Sku_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Stock_Status_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Weight_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Width_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Field_Meta;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;

defined( 'ABSPATH' ) || exit;

class Woo_Data_Vendor extends Data_Vendor {
	// for back compatibility only.
	const NAME = 'woo';

	// for back compatibility only.
	protected function is_without_name_in_keys(): bool {
		return true;
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_fields_with_labels( bool $is_field_name_as_label = false ): array {
		return array(
			Woo_Fields::FIELD_GALLERY       => false === $is_field_name_as_label ?
				__( 'Gallery', 'acf-views' ) : 'gallery',
			Woo_Fields::FIELD_PRICE         => false === $is_field_name_as_label ?
				__( 'Price', 'acf-views' ) : 'price',
			Woo_Fields::FIELD_REGULAR_PRICE => false === $is_field_name_as_label ?
				__( 'Regular price', 'acf-views' ) : 'regular_price',
			Woo_Fields::FIELD_SALE_PRICE    => false === $is_field_name_as_label ?
				__( 'Sale price', 'acf-views' ) : 'sale_price',
			Woo_Fields::FIELD_SKU           => false === $is_field_name_as_label ?
				__( 'SKU', 'acf-views' ) : 'sku',
			Woo_Fields::FIELD_STOCK_STATUS  => false === $is_field_name_as_label ?
				__( 'Stock status', 'acf-views' ) : 'stock_status',
			Woo_Fields::FIELD_WEIGHT        => false === $is_field_name_as_label ?
				__( 'Weight', 'acf-views' ) : 'weight',
			Woo_Fields::FIELD_LENGTH        => false === $is_field_name_as_label ?
				__( 'Length', 'acf-views' ) : 'length',
			Woo_Fields::FIELD_WIDTH         => false === $is_field_name_as_label ?
				__( 'Width', 'acf-views' ) : 'width',
			Woo_Fields::FIELD_HEIGHT        => false === $is_field_name_as_label ?
				__( 'Height', 'acf-views' ) : 'height',
		);
	}

	/**
	 * @return array<string,Markup_Field_Interface>
	 */
	protected function get_field_types(): array {
		return array(
			Woo_Fields::FIELD_GALLERY       => new Woo_Gallery_Field( new Image_Field() ),
			Woo_Fields::FIELD_PRICE         => new Woo_Price_Field(),
			Woo_Fields::FIELD_REGULAR_PRICE => new Woo_Regular_Price_Field(),
			Woo_Fields::FIELD_SALE_PRICE    => new Woo_Sale_Price_Field(),
			Woo_Fields::FIELD_SKU           => new Woo_Sku_Field(),
			Woo_Fields::FIELD_STOCK_STATUS  => new Woo_Stock_Status_Field(),
			Woo_Fields::FIELD_WEIGHT        => new Woo_Weight_Field(),
			Woo_Fields::FIELD_LENGTH        => new Woo_Length_Field(),
			Woo_Fields::FIELD_WIDTH         => new Woo_Width_Field(),
			Woo_Fields::FIELD_HEIGHT        => new Woo_Height_Field(),
		);
	}

	protected function get_real_field_name( string $field_id ): string {
		switch ( $field_id ) {
			case Woo_Fields::FIELD_GALLERY:
				return '_product_image_gallery';
			case Woo_Fields::FIELD_PRICE:
				return '_price';
			case Woo_Fields::FIELD_REGULAR_PRICE:
				return '_regular_price';
			case Woo_Fields::FIELD_SALE_PRICE:
				return '_sale_price';
			case Woo_Fields::FIELD_SKU:
				return '_sku';
			case Woo_Fields::FIELD_STOCK_STATUS:
				return '_stock_status';
			case Woo_Fields::FIELD_WEIGHT:
				return '_weight';
			case Woo_Fields::FIELD_LENGTH:
				return '_length';
			case Woo_Fields::FIELD_WIDTH:
				return '_width';
			case Woo_Fields::FIELD_HEIGHT:
				return '_height';
			default:
				return '';
		}
	}

	public function get_name(): string {
		return static::NAME;
	}

	public function is_meta_vendor(): bool {
		return true;
	}

	public function is_available(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function make_integration_instance(
		Item_Data $item_data,
		Views_Data_Storage $views_data_storage,
		Data_Vendors $data_vendors,
		Views_Cpt_Save_Actions $views_cpt_save_actions,
		View_Factory $view_factory,
		Repeater_Field_Data $repeater_field_data,
		View_Shortcode $view_shortcode,
		Settings $settings
	): ?Data_Vendor_Integration_Interface {
		return null;
	}

	/**
	 * @return array<string, string>
	 */
	public function get_group_choices(): array {
		$groups = array(
			Woo_Fields::GROUP_NAME => __( 'Product (WooCommerce)', 'acf-views' ),
		);

		$group_choices = array();

		foreach ( $groups as $group_id => $group_name ) {
			$group_choices[ $this->get_group_key( $group_id ) ] = $group_name;
		}

		return $group_choices;
	}

	/**
	 * @param string[] $include_only_types
	 *
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_field_choices(
		array $include_only_types = array(),
		bool $is_meta_format = false,
		bool $is_field_name_as_label = false
	): array {
		$field_choices = array();

		foreach ( $this->get_fields_with_labels( $is_field_name_as_label ) as $field_id => $field_name ) {
			if ( ( array() !== $include_only_types && ! in_array( $field_id, $include_only_types, true ) ) ) {
				continue;
			}

			$field_key = $this->get_field_key( Woo_Fields::GROUP_NAME, $field_id );

			if ( $is_meta_format ) {
				$value = new Field_Meta( $this->get_name(), $field_id );
				$this->fill_field_meta( $value );
			} else {
				$value = $field_name;
			}

			$field_choices[ $field_key ] = $value;
		}

		return $field_choices;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public function fill_field_meta( Field_Meta_Interface $field_meta, array $data = array() ): void {
		if ( ! in_array( $field_meta->get_field_id(), $this->get_supported_field_types(), true ) ) {
			return;
		}

		$field_meta->set_type( $field_meta->get_field_id() );
		// it's necessary to have real meta names for WP_Query.
		$field_meta->set_name( $this->get_real_field_name( $field_meta->get_field_id() ) );
		$field_meta->set_is_field_exist( true );

		switch ( $field_meta->get_field_id() ) {
			case Woo_Fields::FIELD_GALLERY:
				$field_meta->set_is_multiple( true );
				break;
		}
	}

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
	) {
		if ( $source->is_options() ) {
			return null;
		}

		return $source->get_id();
	}

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime {
		return null;
	}

	public function convert_date_to_string_for_db_comparison(
		DateTime $date_time,
		Field_Meta_Interface $field_meta
	): string {
		return '';
	}

	/**
	 * @return null|array{title:string,url:string}
	 */
	public function get_group_link_by_group_id( string $group_id ): ?array {
		return null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_group_export_data( string $group_id ): ?array {
		// the feature is not supported.
		return null;
	}

	/**
	 * @param array<int|string, mixed> $group_data
	 * @param array<string, mixed> $meta_data
	 */
	public function import_group( array $group_data, array $meta_data ): ?string {
		// the feature is not supported.
		return null;
	}
}
