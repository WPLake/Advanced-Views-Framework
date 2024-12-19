<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Acf;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Acf\Fields\Color_Picker_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Acf\Fields\Icon_Picker_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Acf\Fields\Page_Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Date_Picker_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\File_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Gallery_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Html_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Map_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Plain_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Post_Object_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Pro_Stub_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Select_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Tab_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Taxonomy_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\True_False_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Url_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\User_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data;
use Org\Wplake\Advanced_Views\Parents\Group;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Field_Meta;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;

defined( 'ABSPATH' ) || exit;

class Acf_Data_Vendor extends Data_Vendor {
	// for back compatibility only.
	const NAME = 'acf';

	// for back compatibility only.
	protected function is_without_name_in_keys(): bool {
		return true;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_groups(): array {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return array();
		}

		$acf_groups = acf_get_field_groups();

		// Important! To avoid recursion, otherwise within 'getChoices()' will be available the same group as the current
		// and this class will call 'acf_get_fields()' that will call 'getChoices()'.
		return array_filter(
			$acf_groups,
			function ( $acf_group ) {
				$is_private = $this->get_bool_arg( 'private', $acf_group );
				$is_own     = 0 === strpos( $this->get_string_arg( 'key', $acf_group ), Group::GROUP_NAME_PREFIX );
				// don't check for 'local' at all, as 'local' not presented only when json is disabled.
				// in other cases contains 'php' or 'json'.

				return ( ! $is_private &&
						! $is_own );
			}
		);
	}

	/**
	 * @param array<string|int,mixed> $fields
	 *
	 * @return array<string|int,mixed>
	 */
	protected function paste_clone_fields( array $fields ): array {
		// stub for the Pro version (for clone fields).
		return $fields;
	}

	/**
	 * @param array<string|int,Field_Meta_Interface|string> $field_choices
	 * @param array<string|int,mixed> $fields
	 * @param string[] $supported_field_types
	 * @param string[] $include_only_types
	 * @param string[] $pro_stub_field_types
	 *
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	protected function get_field_choices_recursively(
		array $field_choices,
		array $fields,
		array $supported_field_types,
		array $include_only_types,
		array $pro_stub_field_types,
		string $group_id,
		bool $is_meta_format,
		string $parent_choice_value = '',
		bool $is_field_name_as_label = false
	): array {
		foreach ( $fields as $group_field ) {
			if ( false === is_array( $group_field ) ) {
				continue;
			}

			$type = $this->get_string_arg( 'type', $group_field );

			if ( false === in_array( $type, $supported_field_types, true ) ||
				( array() !== $include_only_types && false === in_array( $type, $include_only_types, true ) ) ) {
				continue;
			}

			$field_id  = $this->get_string_arg( 'key', $group_field );
			$field_key = '' === $parent_choice_value ?
				$this->get_field_key( $group_id, $field_id ) :
				Field_Data::create_field_key( $parent_choice_value, $field_id );

			if ( false === $is_meta_format ) {
				if ( false === $is_field_name_as_label ) {
					$value = $group_field['label'] . ' (' . $type . ')';

					if ( true === in_array( $type, $pro_stub_field_types, true ) ) {
						$value .= ' ' . $this->get_pro_only_label();
					}
				} else {
					$value = $this->get_string_arg( 'name', $group_field );
				}
			} else {
				$value = new Field_Meta( $this->get_name(), $field_id );
				$this->fill_field_meta( $value, $group_field );
			}

			$field_choices[ $field_key ] = $value;

			$sub_fields = $this->get_array_arg( 'sub_fields', $group_field );
			$sub_fields = $this->paste_clone_fields( $sub_fields );

			$layouts = $this->get_array_arg( 'layouts', $group_field );
			// by default, layouts don't have any type.
			foreach ( $layouts as &$layout ) {
				if ( false === is_array( $layout ) ) {
					continue;
				}

				$layout['type'] = '_flexible_content_layout';
			}

			$sub_fields = array_merge( $sub_fields, $layouts );

			if ( array() !== $sub_fields ) {
				$field_choices = $this->get_field_choices_recursively(
					$field_choices,
					$sub_fields,
					$supported_field_types,
					$include_only_types,
					$pro_stub_field_types,
					$group_id,
					$is_meta_format,
					$field_key,
					$is_field_name_as_label
				);
			}
		}

		return $field_choices;
	}

	public function get_name(): string {
		return static::NAME;
	}

	public function is_meta_vendor(): bool {
		return true;
	}

	public function is_available(): bool {
		// do not mark available if it's inner ACF.
		return function_exists( 'get_field' ) &&
				! defined( 'ACF_VIEWS_INNER_ACF' );
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
		return new Acf_Integration(
			$item_data,
			$views_data_storage,
			$data_vendors,
			$views_cpt_save_actions,
			$view_factory,
			$this,
			$view_shortcode,
			$settings
		);
	}

	/**
	 * @return array<string,Markup_Field_Interface>
	 */
	public function get_field_types(): array {
		$basic = array(
			'text'     => new Plain_Field(),
			// we don't need ACF to wrap every line of the value with <p> tags.
			'textarea' => new Html_Field( false, true ),
			'number'   => new Plain_Field(),
			'range'    => new Plain_Field(),
			'email'    => new Plain_Field(),
			'url'      => new Url_Field(),
			'password' => new Plain_Field(),
		);

		$content = array(
			'image'   => new Image_Field(),
			'file'    => new File_Field( new Link_Field() ),
			'wysiwyg' => new Html_Field(),
			'oembed'  => new Html_Field(),
			'gallery' => new Gallery_Field( new Image_Field() ),
		);

		$choice = array(
			'select'       => new Select_Field(),
			'checkbox'     => new Select_Field(),
			'radio'        => new Select_Field(),
			'button_group' => new Select_Field(),
			'true_false'   => new True_False_Field(),
		);

		$relational = array(
			'link'         => new Link_Field(),
			'post_object'  => new Post_Object_Field( new Link_Field() ),
			'page_link'    => new Page_Link_Field( new Link_Field() ),
			'relationship' => new Post_Object_Field( new Link_Field() ),
			'taxonomy'     => new Taxonomy_Field( new Link_Field() ),
			'user'         => new User_Field( new Link_Field() ),
		);

		$advanced = array(
			'google_map'       => new Map_Field(),
			// https://wordpress.org/plugins/acf-google-map-field-multiple-markers/.
			'google_map_multi' => new Map_Field(),
			// https://wordpress.org/plugins/acf-openstreetmap-field/.
			'open_street_map'  => new Map_Field(),
			'date_picker'      => new Date_Picker_Field(),
			'date_time_picker' => new Date_Picker_Field(),
			'time_picker'      => new Date_Picker_Field(),
			'color_picker'     => new Color_Picker_Field(),
			'icon_picker'      => new Icon_Picker_Field( new Image_Field() ),
		);

		$layout = array(
			'group'            => new Pro_Stub_Field(),
			'repeater'         => new Pro_Stub_Field(),
			'flexible_content' => new Pro_Stub_Field(),
			'tab'              => new Tab_Field(),
		);

		return array_merge( $basic, $content, $choice, $relational, $advanced, $layout );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_group_choices(): array {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return array();
		}

		$acf_source_label = __( '(ACF)', 'acf-views' );

		$group_choices = array();

		foreach ( $this->get_groups() as $acf_group ) {
			$key = $this->get_string_arg( 'key', $acf_group );

			$group_key                   = $this->get_group_key( $key );
			$group_choices[ $group_key ] = $acf_group['title'] . ' ' . $acf_source_label;
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
		if ( ! function_exists( 'acf_get_fields' ) ) {
			return array();
		}

		$field_choices = array();

		$supported_field_types = $this->get_supported_field_types();
		$pro_stub_field_types  = $this->get_pro_stub_field_types();

		foreach ( $this->get_groups() as $group ) {
			$fields = acf_get_fields( $group );
			$fields = $this->paste_clone_fields( $fields );

			$group_id = $this->get_string_arg( 'key', $group );

			$field_choices = $this->get_field_choices_recursively(
				$field_choices,
				$fields,
				$supported_field_types,
				$include_only_types,
				$pro_stub_field_types,
				$group_id,
				$is_meta_format,
				'',
				$is_field_name_as_label
			);
		}

		return $field_choices;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public function fill_field_meta( Field_Meta_Interface $field_meta, array $data = array() ): void {
		if ( false === function_exists( 'get_field_object' ) ) {
			return;
		}

		if ( array() === $data ) {
			$data = get_field_object( $field_meta->get_field_id() );
			$data = is_array( $data ) ?
				$data :
				array();
		}

		$field_meta->set_name( $this->get_string_arg( 'name', $data ) );
		$field_meta->set_type( $this->get_string_arg( 'type', $data ) );
		$field_meta->set_return_format( $this->get_string_arg( 'return_format', $data ) );
		$field_meta->set_choices( $this->get_array_arg( 'choices', $data ) );
		$field_meta->set_display_format( $this->get_string_arg( 'display_format', $data ) );
		$field_meta->set_is_multiple( $this->get_bool_arg( 'multiple', $data ) );
		$field_meta->set_zoom( $this->get_int_arg( 'zoom', $data ) );
		$field_meta->set_center_lat( $this->get_string_arg( 'center_lat', $data ) );
		$field_meta->set_center_lng( $this->get_string_arg( 'center_lng', $data ) );

		if ( true === key_exists( 'default_value', $data ) ) {
			if ( true === is_array( $data['default_value'] ) ) {
				$field_meta->set_default_value( $data['default_value'] );
			} else {
				$field_meta->set_default_value( $this->get_string_arg( 'default_value', $data ) );
			}
		}

		$field_meta->set_is_field_exist( '' !== $field_meta->get_type() );

		switch ( $field_meta->get_type() ) {
			case 'checkbox':
			case 'relationship':
			case 'google_map_multi':
			case 'open_street_map':
			case 'gallery':
				$field_meta->set_is_multiple( true );
				break;
			case 'taxonomy':
				$appearance = $this->get_string_arg( 'field_type', $data );
				$field_meta->set_is_multiple( in_array( $appearance, array( 'checkbox', 'multi_select' ), true ) );
				break;
			case 'repeater':
			case '_flexible_content_layout':
				$field_meta->set_is_repeater( true );
				break;
			case 'group':
				$field_meta->set_is_group( true );
				break;
			case 'tab':
				$field_meta->set_is_ui_only( true );
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
		if ( ! function_exists( 'get_field' ) ) {
			return null;
		}

		$field_id = $field_meta->get_field_id();

		if ( null === $local_data ) {
			if ( ! $is_formatted ) {
				$value = ! $source->is_block() ?
					get_field( $field_id, $source->get_id(), false ) :
					get_field( $field_id, false, false );
			} else {
				$value = false === $source->is_block() ?
					get_field( $field_id, $source->get_id() ) :
					get_field( $field_id );
			}
		} else {
			$value = $local_data[ $field_id ] ?? null;

			if ( true === $is_formatted &&
				true === function_exists( 'acf_get_field' ) ) {
				/**
				 * There is 'acf_format_value()' function, but we can't use it
				 * because it uses field value cache, and cache key is equal to post_id:field_id
				 * which is wrong in this case, as we've the inner view, with a piece of field (e.g. repeater row), not the whole field value
				 * so 'acf/format_value' filter is the only proper way to format the value in our case.
				 */
				$value = apply_filters(
					'acf/format_value',
					$value,
					$source->get_id(),
					acf_get_field( $field_id ),
					false
				);
			}
		}

		return $value;
	}

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime {
		$date_time = false;

		switch ( $field_meta->get_type() ) {
			case 'date_picker':
				$date_time = DateTime::createFromFormat( 'Ymd', $value );
				break;
			case 'date_time_picker':
				$date_time = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
				break;
			case 'time_picker':
				$date_time = DateTime::createFromFormat( 'H:i:s', $value );
				break;
		}

		return false !== $date_time ?
			$date_time :
			null;
	}

	public function convert_date_to_string_for_db_comparison(
		DateTime $date_time,
		Field_Meta_Interface $field_meta
	): string {
		switch ( $field_meta->get_type() ) {
			case 'date_picker':
				return $date_time->format( 'Ymd' );
			case 'time_picker':
				return $date_time->format( 'H:i:s' );
			case 'date_time_picker':
				return $date_time->format( 'Y-m-d H:i:s' );
		}

		return '';
	}

	/**
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_sub_field_choices( bool $is_meta_format = false, bool $is_field_name_as_label = false ): array {
		if ( false === function_exists( 'acf_get_fields' ) ) {
			return array();
		}

		$sub_field_choices           = array();
		$supported_field_types       = $this->get_supported_field_types();
		$field_types_with_sub_fields = array( 'repeater', 'group', 'flexible_content', 'clone' );

		foreach ( $this->get_groups() as $group ) {
			$fields = acf_get_fields( $group );

			$group_key = $this->get_string_arg( 'key', $group );

			foreach ( $fields as $group_field ) {
				$field_type = $this->get_string_arg( 'type', $group_field );

				// ignore 'clone' if it isn't supported (Lite version).
				if ( false === in_array( $field_type, $supported_field_types, true ) ) {
					continue;
				}

				$sub_fields = $this->get_array_arg( 'sub_fields', $group_field );
				$sub_fields = $this->paste_clone_fields( $sub_fields );

				// by default, layouts don't have any type.
				$layouts = $this->get_array_arg( 'layouts', $group_field );
				foreach ( $layouts as &$layout ) {
					if ( false === is_array( $layout ) ) {
						continue;
					}
					$layout['type'] = '_flexible_content_layout';
				}

				$sub_fields = array_merge( $sub_fields, $layouts );

				if ( false === in_array( $field_type, $field_types_with_sub_fields, true ) ||
					array() === $sub_fields ) {
					continue;
				}

				$field_key = $this->get_field_key( $group_key, $this->get_string_arg( 'key', $group_field ) );

				$sub_field_choices = $this->get_field_choices_recursively(
					$sub_field_choices,
					$sub_fields,
					$supported_field_types,
					array(),
					array(),
					$group_key,
					$is_meta_format,
					$field_key,
					$is_field_name_as_label
				);
			}
		}

		return $sub_field_choices;
	}

	/**
	 * @return null|array{title:string,url:string}
	 */
	public function get_group_link_by_group_id( string $group_id ): ?array {
		$post = get_page_by_path( $group_id, OBJECT, 'acf-field-group' );

		if ( null === $post ) {
			return null;
		}

		return array(
			'title' => $post->post_title,
			'url'   => (string) get_edit_post_link( $post ),
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_group_export_data( string $group_id ): ?array {
		if ( false === function_exists( 'acf_get_field_group' ) ||
			false === function_exists( 'acf_get_fields' ) ||
			false === function_exists( 'acf_prepare_internal_post_type_for_export' ) ) {
			return null;
		}

		$group_info = acf_get_field_group( $group_id );

		if ( false === is_array( $group_info ) ) {
			$this->get_logger()->warning(
				'failed to get ACF field group info',
				array(
					'group_id' => $group_id,
				)
			);

			return null;
		}

		// fields are missing (empty) in the 'acf_get_field_group' call.
		$fields_info = acf_get_fields( $group_info );

		if ( false === is_array( $fields_info ) ) {
			$this->get_logger()->warning(
				'failed to get ACF fields info',
				array(
					'group_id' => $group_id,
				)
			);

			return null;
		}

		$group_info['fields'] = $fields_info;

		$group_info = acf_prepare_internal_post_type_for_export( $group_info );

		if ( false === is_array( $group_info ) ) {
			$this->get_logger()->warning(
				'failed to prepare ACF field group for export',
				array(
					'group_id' => $group_id,
				)
			);

			return null;
		}

		return $group_info;
	}

	/**
	 * @param array<int|string, mixed> $group_data
	 * @param array<string, mixed> $meta_data
	 */
	public function import_group( array $group_data, array $meta_data ): ?string {
		if ( false === function_exists( 'acf_get_field_group' ) ||
			false === function_exists( 'acf_import_field_group' ) ) {
			return null;
		}

		$group_key           = $this->get_string_arg( 'key', $group_data );
		$existing_group_info = acf_get_field_group( $group_key );

		if ( true === is_array( $existing_group_info ) ) {
			$group_id         = $this->get_int_arg( 'ID', $existing_group_info );
			$group_data['ID'] = $group_id;

			$this->get_logger()->debug(
				'ACF group to import already exists: overriding instead of creation',
				array(
					'key' => $group_key,
					'ID'  => $group_id,
				)
			);
		}

		$group_data = acf_import_field_group( $group_data );

		if ( false === is_array( $group_data ) ) {
			$this->get_logger()->warning(
				'failed to import ACF field group',
				array(
					'group_data' => $group_data,
				)
			);

			return null;
		}

		return $this->get_string_arg_if_present( 'key', $group_data );
	}
}
