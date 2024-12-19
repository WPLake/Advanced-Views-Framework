<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors;

use DateTime;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Acf\Acf_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Related_Groups_Import_Result;
use Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Meta_Box_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Pods\Pods_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Woo_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Wp_Data_Vendor;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Field_Meta;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;

defined( 'ABSPATH' ) || exit;

class Data_Vendors extends Action implements Hooks_Interface {
	use Safe_Array_Arguments;

	/**
	 * @var array<string, Data_Vendor_Interface> name => instance
	 */
	private array $data_vendors;
	/**
	 * @var array<string,array<string,Field_Meta_Interface>>
	 */
	private array $field_meta_cache;

	public function __construct( Logger $logger ) {
		parent::__construct( $logger );

		$this->data_vendors     = array();
		$this->field_meta_cache = array();
	}

	/**
	 * @return Data_Vendor_Interface[]
	 */
	protected function get_vendors(): array {
		return array(
			new Wp_Data_Vendor( $this->get_logger() ),
			new Woo_Data_Vendor( $this->get_logger() ),
			new Acf_Data_Vendor( $this->get_logger() ),
			new Meta_Box_Data_Vendor( $this->get_logger() ),
			new Pods_Data_Vendor( $this->get_logger() ),
		);
	}

	/**
	 * @return  array<string, Data_Vendor_Interface> name => instance
	 */
	public function get_data_vendors(): array {
		return $this->data_vendors;
	}

	protected function load_integration_instance(
		Current_Screen $current_screen,
		Data_Vendor_Integration_Interface $integration_instance,
		Views_Data_Storage $views_data_storage
	): void {
		// functions below only for the admin part.
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		$integration_instance->add_tab_to_meta_group();
		$integration_instance->add_column_to_list_table();
		$integration_instance->validate_related_views_on_group_change();
		$integration_instance->maybe_create_view_for_group();
	}

	/**
	 * @return array<string, string>
	 */
	public function get_group_choices( bool $is_only_meta_vendors = false ): array {
		$choices = array(
			'' => __( 'Select', 'acf-views' ),
		);

		foreach ( $this->data_vendors as $data_vendor ) {
			if ( $is_only_meta_vendors &&
				! $data_vendor->is_meta_vendor() ) {
				continue;
			}

			$choices = array_merge( $choices, $data_vendor->get_group_choices() );
		}

		return $choices;
	}

	/**
	 * @return array<string|int, string|Field_Meta_Interface>
	 */
	public function get_field_choices(
		bool $is_only_meta_vendors = false,
		bool $is_only_types_with_sub_fields = false,
		bool $is_field_name_as_label = false
	): array {
		$choices = false === $is_only_types_with_sub_fields ?
			array(
				'' => __( 'Select', 'acf-views' ),
			) :
			array();

		foreach ( $this->data_vendors as $data_vendor ) {
			if ( $is_only_meta_vendors &&
				false === $data_vendor->is_meta_vendor() ) {
				continue;
			}

			$only_field_types = true === $is_only_types_with_sub_fields ?
				$data_vendor->get_field_types_with_sub_fields() :
				array();

			// skip if types with subFields were requested, but vendor doesn't have such types.
			if ( $is_only_types_with_sub_fields &&
				array() === $only_field_types ) {
				continue;
			}

			$choices = array_merge(
				$choices,
				$data_vendor->get_field_choices( $only_field_types, false, $is_field_name_as_label )
			);
		}

		return $choices;
	}

	/**
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_sub_field_choices( bool $is_only_meta_vendors = false, bool $is_field_name_as_label = false ): array {
		$choices = array(
			'' => __( 'Select', 'acf-views' ),
		);

		foreach ( $this->data_vendors as $data_vendor ) {
			if ( $is_only_meta_vendors &&
				! $data_vendor->is_meta_vendor() ) {
				continue;
			}

			$choices = array_merge( $choices, $data_vendor->get_sub_field_choices( false, $is_field_name_as_label ) );
		}

		return $choices;
	}

	/**
	 * @return array<string, array<int,string|int>>
	 */
	public function get_field_key_conditional_rules( bool $is_sub_fields = false ): array {
		$field_key_conditions = array();

		foreach ( $this->data_vendors as $data_vendor ) {
			$vendor_field_key_conditional_rules = $data_vendor->get_field_key_conditional_rules( $is_sub_fields );

			foreach ( $vendor_field_key_conditional_rules as $vendor_field => $vendor_field_conditions ) {
				$field_key_conditions[ $vendor_field ] = $field_key_conditions[ $vendor_field ] ?? array();
				$field_key_conditions[ $vendor_field ] = array_merge(
					$field_key_conditions[ $vendor_field ],
					$vendor_field_conditions
				);
			}
		}

		return $field_key_conditions;
	}

	public function get_markup_field_instance(
		string $vendor_name,
		string $field_type
	): ?Markup_Field_Interface {
		if ( ! key_exists( $vendor_name, $this->data_vendors ) ) {
			return null;
		}

		return $this->data_vendors[ $vendor_name ]->get_markup_field_instance( $field_type );
	}

	public function is_empty_value_supported_in_markup( string $vendor_name, string $field_type ): bool {
		if ( ! key_exists( $vendor_name, $this->data_vendors ) ) {
			return false;
		}

		return $this->data_vendors[ $vendor_name ]->is_empty_value_supported_in_markup( $field_type );
	}

	/**
	 * @param string $vendor_name
	 *
	 * @return string[]
	 */
	public function get_supported_field_types( string $vendor_name ): array {
		if ( false === key_exists( $vendor_name, $this->data_vendors ) ) {
			return array();
		}

		return $this->data_vendors[ $vendor_name ]->get_supported_field_types();
	}

	public function get_field_meta( string $vendor_name, string $field_id ): Field_Meta_Interface {
		$vendor = $this->data_vendors[ $vendor_name ] ?? null;

		$this->field_meta_cache[ $vendor_name ] = $this->field_meta_cache[ $vendor_name ] ?? array();

		if ( false === key_exists( $field_id, $this->field_meta_cache[ $vendor_name ] ) ) {
			$field_meta = new Field_Meta( $vendor_name, $field_id );

			if ( null !== $vendor ) {
				$vendor->fill_field_meta( $field_meta );
			}

			// it's okay if vendor isn't loaded (e.g. it's ACF field and ACF plugin is not present on the site)
			// so it'll have fieldMeta with isFieldExists() false.
			$this->field_meta_cache[ $vendor_name ][ $field_id ] = $field_meta;
		}

		return $this->field_meta_cache[ $vendor_name ][ $field_id ];
	}

	/**
	 * @param array<string|int,mixed> $local_data
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
		$vendor_name = $field_meta->get_vendor_name();

		if ( false === key_exists( $vendor_name, $this->data_vendors ) ) {
			return null;
		}

		return $this->data_vendors[ $vendor_name ]->get_field_value(
			$field_data,
			$field_meta,
			$source,
			$item_data,
			$is_formatted,
			$local_data
		);
	}

	// use $isForceLoading for tests only.
	public function load_available_vendors( bool $is_force_loading = false ): void {
		foreach ( $this->get_vendors() as $vendor ) {
			if ( ! $vendor->is_available() &&
				! $is_force_loading ) {
				continue;
			}

			$this->data_vendors[ $vendor->get_name() ] = $vendor;
		}
	}

	/**
	 * @return string[]
	 */
	public function get_field_front_assets( string $vendor_name, Field_Data $field_data ): array {
		if ( ! key_exists( $vendor_name, $this->data_vendors ) ) {
			return array();
		}

		$field_front_assets = $this->data_vendors[ $vendor_name ]->get_field_front_assets( $field_data );

		// avoid duplicates (can be in case of the inheritance chain).
		return array_unique( $field_front_assets );
	}

	/**
	 * @return array{0:Field_Data[],1:Field_Data[]}
	 */
	public function get_fields_by_front_asset( string $asset_name, View_Data $view_data ): array {
		$fields = array(
			array(),
			array(),
		);

		foreach ( $view_data->items as $item ) {
			foreach ( $item->repeater_fields as $repeater_field ) {
				$vendor_name = $repeater_field->get_vendor_name();

				if ( ! in_array( $asset_name, $this->get_field_front_assets( $vendor_name, $repeater_field ), true ) ) {
					continue;
				}

				$fields[1][] = $repeater_field;
			}

			$vendor_name = $item->field->get_vendor_name();

			if ( ! in_array( $asset_name, $this->get_field_front_assets( $vendor_name, $item->field ), true ) ) {
				continue;
			}

			$fields[0][] = $item->field;
		}

		return $fields;
	}

	/**
	 * @return string[]
	 */
	public function get_all_conditional_fields(): array {
		return array(
			Field_Data::FIELD_LINK_LABEL,
			Field_Data::FIELD_IS_LINK_TARGET_BLANK,
			Field_Data::FIELD_ACF_VIEW_ID,
			Field_Data::FIELD_SLIDER_TYPE,
			Field_Data::FIELD_MAP_MARKER_ICON,
			Field_Data::FIELD_MAP_MARKER_ICON_TITLE,
			Field_Data::FIELD_MAP_ADDRESS_FORMAT,
			Field_Data::FIELD_IS_MAP_WITH_ADDRESS,
			Field_Data::FIELD_IS_MAP_WITHOUT_GOOGLE_MAP,
			Field_Data::FIELD_IMAGE_SIZE,
			Field_Data::FIELD_LIGHTBOX_TYPE,
			Field_Data::FIELD_GALLERY_WITH_LIGHT_BOX,
			Field_Data::FIELD_GALLERY_TYPE,
			Field_Data::FIELD_OPTIONS_DELIMITER,
		);
	}

	public function is_field_type_with_sub_fields( string $vendor, string $field_type ): bool {
		if ( false === key_exists( $vendor, $this->data_vendors ) ) {
			return false;
		}

		return true === in_array(
			$field_type,
			$this->data_vendors[ $vendor ]->get_field_types_with_sub_fields(),
			true
		);
	}

	public function convert_date_to_string_for_db_comparison(
		string $vendor,
		DateTime $date_time,
		Field_Meta_Interface $field_meta_interface
	): string {
		if ( false === key_exists( $vendor, $this->data_vendors ) ) {
			return '';
		}

		return $this->data_vendors[ $vendor ]->convert_date_to_string_for_db_comparison(
			$date_time,
			$field_meta_interface
		);
	}

	public function make_integration_instances(
		Current_Screen $current_screen,
		Item_Data $item_data,
		Views_Data_Storage $views_data_storage,
		Views_Cpt_Save_Actions $views_cpt_save_actions,
		View_Factory $view_factory,
		Repeater_Field_Data $repeater_field_data,
		View_Shortcode $view_shortcode,
		Settings $settings
	): void {
		// 1. must on or later 'plugins_load', when meta plugins are loaded
		// 2. must be on or later 'after_setup_theme', when FS only Views and Cards are available
		add_action(
			'after_setup_theme',
			function () use (
				$current_screen,
				$item_data,
				$views_data_storage,
				$views_cpt_save_actions,
				$view_factory,
				$repeater_field_data,
				$view_shortcode,
				$settings
			) {
				foreach ( $this->data_vendors as $vendor ) {
					$integration_instance = $vendor->make_integration_instance(
						$item_data,
						$views_data_storage,
						$this,
						$views_cpt_save_actions,
						$view_factory,
						$repeater_field_data,
						$view_shortcode,
						$settings
					);

					// integration instance is optional (e.g. Woo and WP don't have).
					if ( null === $integration_instance ) {
						continue;
					}

					$this->load_integration_instance( $current_screen, $integration_instance, $views_data_storage );
				}
			}
		);
	}

	/**
	 * @return null|array{title:string,url:string}
	 */
	public function get_group_link_by_group_id( string $group_id, string $vendor_name = '' ): ?array {
		if ( '' === $vendor_name ) {
			$vendor_name                    = Field_Data::get_vendor_name_by_key( $group_id . '|fake-field-id' );
			$group_id_without_vendor_prefix = explode( ':', $group_id )[1] ?? $group_id;
		} else {
			$group_id_without_vendor_prefix = $group_id;
		}

		if ( false === key_exists( $vendor_name, $this->data_vendors ) ) {
			return null;
		}

		return $this->data_vendors[ $vendor_name ]->get_group_link_by_group_id( $group_id_without_vendor_prefix );
	}

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime {
		$vendor_name = $field_meta->get_vendor_name();

		if ( false === key_exists( $vendor_name, $this->data_vendors ) ) {
			return null;
		}

		return $this->data_vendors[ $vendor_name ]->convert_string_to_date_time( $field_meta, $value );
	}

	/**
	 * @param array<string, string> $import_files name => content
	 */
	public function import_related_group_files( array $import_files ): Related_Groups_Import_Result {
		$import_result = new Related_Groups_Import_Result();

		foreach ( $import_files as $file_name => $file_content ) {
			if ( false === strpos( $file_name, '.json' ) ) {
				continue;
			}

			$file_vendor = str_replace( '.json', '', $file_name );

			if ( false === key_exists( $file_vendor, $this->get_data_vendors() ) ) {
				continue;
			}

			$import_data = json_decode( $file_content, true );

			if ( false === is_array( $import_data ) ) {
				continue;
			}

			$vendor = $this->get_data_vendors()[ $file_vendor ];

			/**
			 * @var array<string,mixed> $meta_data
			 */
			$meta_data = $this->get_array_arg( 'meta', $import_data );
			// compatibility with the old export format, which didn't have meta at all.
			$groups_data = true === key_exists( 'groups', $import_data ) ?
				$this->get_array_arg( 'groups', $import_data ) :
				$import_data;

			foreach ( $groups_data as $group_data ) {
				$imported_group_id = $vendor->import_group( $group_data, $meta_data );

				if ( null === $imported_group_id ) {
					continue;
				}

				$import_result->add_group( $vendor->get_name(), $imported_group_id );
			}
		}

		return $import_result;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		// before other hooks inside this plugin that listen to plugins_loaded.
		add_action( 'plugins_loaded', array( $this, 'load_available_vendors' ), 5 );
	}
}
