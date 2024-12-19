<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Date_Picker_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Html_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Map_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Plain_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Post_Object_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Pro_Stub_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Select_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\True_False_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Url_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\User_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields\Mb_File_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields\Mb_Gallery_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields\Mb_Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Fields\Mb_Taxonomy_Field;
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
use RWMB_Field;

defined( 'ABSPATH' ) || exit;

class Meta_Box_Data_Vendor extends Data_Vendor {
	const NAME = 'meta-box';

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_groups(): array {
		if ( ! function_exists( 'rwmb_get_registry' ) ) {
			return array();
		}

		$meta_box_registry = rwmb_get_registry( 'meta_box' );
		$groups_info       = $meta_box_registry->all();

		$core_group_ids = array(
			'relationship-id',
			'settings-page',
			'field-group-id',
			'mbb-documentation',
			'mbup-email-confirmation',
			'mbv-template-editor',
			'mbv-settings',
			'mbv-shortcode',
		);

		$groups = array();

		foreach ( $groups_info as $group_id => $group_info ) {
			// only added by users.
			if ( in_array( $group_id, $core_group_ids, true ) ) {
				continue;
			}

			$groups[] = $group_info->meta_box ?? array();
		}

		return $groups;
	}

	/**
	 * @param int|string|false $source_id
	 *
	 * @return array<string,string>
	 */
	protected function get_field_request_args(
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		Source $source,
		&$source_id
	): array {
		$args = array();

		if ( true === is_string( $source_id ) &&
			false === is_numeric( $source_id ) &&
			false === $source->is_block() ) {
			if ( 0 === strpos( $source_id, 'term_' ) ) {
				$source_id           = str_replace( 'term_', '', $source_id );
				$args['object_type'] = 'term';
			} elseif ( 0 === strpos( $source_id, 'user_' ) ) {
				$source_id           = str_replace( 'user_', '', $source_id );
				$args['object_type'] = 'user';
			} elseif ( 0 === strpos( $source_id, 'comment_' ) ) {
				$source_id           = str_replace( 'comment_', '', $source_id );
				$args['object_type'] = 'comment';
			} else {
				// option pages.
				$args['object_type'] = 'setting';
			}
		}

		return $args;
	}

	/**
	 * @return string[]
	 */
	protected function get_export_meta_keys(): array {
		return array( 'settings', 'fields', 'data', 'meta_box' );
	}

	/**
	 * @param array<string,Field_Meta_Interface|string> $field_choices
	 * @param array<string|int,mixed> $fields
	 * @param string[] $supported_field_types
	 * @param string[] $include_only_types
	 * @param string[] $pro_stub_field_types
	 *
	 * @return array<string, Field_Meta_Interface|string>
	 */
	protected function get_field_choices_recursively(
		array $field_choices,
		array $fields,
		array $supported_field_types,
		array $include_only_types,
		array $pro_stub_field_types,
		string $group_id,
		bool $is_meta_format,
		string $repeatable_label,
		bool $is_repeatable_not_supported,
		string $parent_choice_value = '',
		bool $is_field_name_as_label = false
	): array {
		foreach ( $fields as $field ) {
			if ( false === is_array( $field ) ) {
				continue;
			}

			$field_type = $this->get_string_arg( 'type', $field );

			if ( ! in_array( $field_type, $supported_field_types, true ) ||
				( array() !== $include_only_types && ! in_array( $field_type, $include_only_types, true ) ) ) {
				continue;
			}

			$field_id = $this->get_string_arg( 'id', $field );

			$field_key = '' === $parent_choice_value ?
				$this->get_field_key( $group_id, $field_id ) :
				Field_Data::create_field_key( $parent_choice_value, $field_id );

			if ( true === $is_meta_format ) {
				$value = new Field_Meta( $this->get_name(), $field_id );
				$this->fill_field_meta( $value, $field );
			} elseif ( false === $is_field_name_as_label ) {
				$is_repeatable    = $this->get_bool_arg( 'clone', $field );
				$repeatable_field = $is_repeatable ?
					$repeatable_label . ' ' :
					'';
				// hidden type doesn't have label.
				$name  = 'hidden' !== $field_type ?
					$field['name'] :
					__( 'Hidden', 'acf-views' );
				$value = sprintf( '%s (%s%s)', $name, $repeatable_field, $field_type );

				if ( in_array( $field_type, $pro_stub_field_types, true ) ||
					( $is_repeatable && $is_repeatable_not_supported ) ) {
					$value .= ' ' . $this->get_pro_only_label();
				}
			} else {
				$value = $this->get_string_arg( 'id', $field );
			}

			$field_choices[ $field_key ] = $value;

			$sub_fields = $this->get_array_arg( 'fields', $field );

			if ( array() !== $sub_fields ) {
				$field_choices = $this->get_field_choices_recursively(
					$field_choices,
					$sub_fields,
					$supported_field_types,
					$include_only_types,
					$pro_stub_field_types,
					$group_id,
					$is_meta_format,
					$repeatable_label,
					$is_repeatable_not_supported,
					$field_key,
					$is_field_name_as_label
				);
			}
		}

		return $field_choices;
	}

	/**
	 * @param array<string|int,mixed> $fields
	 *
	 * @return array<string|int,mixed>
	 */
	protected function get_field_recursively( string $target_field_id, array $fields ): array {
		foreach ( $fields as $field ) {
			if ( false === is_array( $field ) ) {
				continue;
			}

			$field_id = $this->get_string_arg( 'id', $field );

			if ( $field_id === $target_field_id ) {
				return $field;
			}

			$sub_fields = $this->get_array_arg( 'fields', $field );

			if ( array() !== $sub_fields ) {
				$sub_field_info = $this->get_field_recursively( $target_field_id, $sub_fields );

				if ( array() !== $sub_field_info ) {
					return $sub_field_info;
				}
			}
		}

		return array();
	}

	/**
	 * There is 'rwmb_get_field_settings' function, but it's a context specific function
	 * (required postId of the post, where current field is attached...).
	 *
	 * @return array<string|int,mixed>
	 */
	protected function get_field_info( string $target_field_id ): array {
		foreach ( $this->get_groups() as $group_info ) {
			$fields = $this->get_array_arg( 'fields', $group_info );

			$data = $this->get_field_recursively( $target_field_id, $fields );

			if ( array() !== $data ) {
				return $data;
			}
		}

		return array();
	}

	/**
	 * @param mixed $raw_value
	 *
	 * @return mixed
	 */
	protected function format_group_item_value( $raw_value, Field_Data $field_data, Field_Meta_Interface $field_meta, Source $source ) {
		/**
		 * Hand fix for maps.
		 * https://docs.metabox.io/fields/osm/#outputting-a-map-in-a-group
		 * https://support.metabox.io/topic/bug-clonable-map-field-not-displaying-data/
		 */
		if ( true === in_array( $field_meta->get_type(), array( 'osm', 'map' ), true ) ) {
			$render_map_callback = 'osm' === $field_meta->get_type() ?
				array( 'RWMB_OSM_Field', 'render_map' ) :
				array( 'RWMB_Map_Field', 'render_map' );

			$field_request_args = $this->get_field_request_args( $field_data, $field_meta, $source, $source_id );

			return true === is_callable( $render_map_callback ) ?
				call_user_func( $render_map_callback, $raw_value, $field_request_args ) :
				$raw_value;
		}

		return $raw_value;
	}

	/**
	 * @param mixed $raw_value
	 *
	 * @return array<int|string,mixed>
	 */
	protected function format_group_value( $raw_value, Field_Data $field_data, Field_Meta_Interface $field_meta, Item_Data $item_data, Source $source ): array {
		$raw_value = true === is_array( $raw_value ) ?
			$raw_value :
			array();

		$formatted_value = array();

		// repeater.
		if ( true === $field_meta->is_repeater() ) {
			foreach ( $raw_value as $raw_row ) {
				$formatted_row = array();

				foreach ( $item_data->repeater_fields as $repeater_field ) {
					$repeater_field_id   = $repeater_field->get_field_meta()->get_field_id();
					$repeater_field_name = $repeater_field->get_field_meta()->get_name();

					$field_value                           = $raw_row[ $repeater_field_id ] ?? '';
					$formatted_row[ $repeater_field_name ] = $this->format_group_item_value( $field_value, $repeater_field, $repeater_field->get_field_meta(), $source );
				}

				$formatted_value[] = $formatted_row;
			}

			return $formatted_value;
		}

		$self_repeatable = $field_meta->get_self_repeatable_meta();

		// self-repeatable.
		if ( null !== $self_repeatable ) {
			foreach ( $raw_value as $item ) {
				$formatted_value[] = $this->format_group_item_value( $item, $field_data, $self_repeatable, $source );
			}

			return $formatted_value;
		}

		// group.
		foreach ( $item_data->repeater_fields as $repeater_field ) {
			$repeater_field_id   = $repeater_field->get_field_meta()->get_field_id();
			$repeater_field_name = $repeater_field->get_field_meta()->get_name();

			$field_value                             = $raw_value[ $repeater_field_id ] ?? '';
			$formatted_value[ $repeater_field_name ] = $this->format_group_item_value( $field_value, $repeater_field, $repeater_field->get_field_meta(), $source );
		}

		return $formatted_value;
	}

	public function get_name(): string {
		return static::NAME;
	}

	public function is_meta_vendor(): bool {
		return true;
	}

	public function is_available(): bool {
		return function_exists( 'rwmb_get_value' );
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
		return new Meta_Box_Integration(
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

	public function get_field_types(): array {
		$basic = array(
			'checkbox'      => new True_False_Field(),
			'checkbox_list' => new Select_Field(),
			'radio'         => new Select_Field(),
			'select'        => new Select_Field(),
			'text'          => new Plain_Field(),
			'textarea'      => new Html_Field(),
		);

		$advanced = array(
			'autocomplete'    => new Select_Field(),
			// we need to use 'the_value' function, so HTML field fits here.
			'background'      => new Html_Field(),
			'button_group'    => new Select_Field(),
			'color'           => new Plain_Field(),
			'date'            => new Date_Picker_Field(),
			'datetime'        => new Date_Picker_Field(),
			'map'             => new Map_Field(),
			'hidden'          => new Plain_Field(),
			// FontAwesome or SVG icon.
			'icon'            => new Html_Field(),
			'image_select'    => new Select_Field(),
			'oembed'          => new Html_Field(),
			'osm'             => new Map_Field(),
			'password'        => new Plain_Field(),
			'select_advanced' => new Select_Field(),
			// it's a range number.
			'slider'          => new Plain_Field(),
			'switch'          => new True_False_Field(),
			'time'            => new Plain_Field(),
			'wysiwyg'         => new Html_Field(),
		);

		$html5 = array(
			'email'  => new Plain_Field(),
			'number' => new Plain_Field(),
			'range'  => new Plain_Field(),
			'url'    => new Url_Field(),
		);

		$wordpress = array(
			'post'              => new Post_Object_Field( new Link_Field() ),
			'sidebar'           => new Html_Field(),
			'taxonomy'          => new Mb_Taxonomy_Field( new Link_Field() ),
			'taxonomy_advanced' => new Mb_Taxonomy_Field( new Link_Field() ),
			'user'              => new User_Field( new Link_Field() ),
		);

		$upload = array(
			'file'           => new Mb_File_Field( new Link_Field() ),
			'file_advanced'  => new Mb_File_Field( new Link_Field() ),
			'file_input'     => new Mb_File_Field( new Link_Field() ),
			'file_upload'    => new Mb_File_Field( new Link_Field() ),
			'image'          => new Mb_Gallery_Field( new Mb_Image_Field() ),
			'image_advanced' => new Mb_Gallery_Field( new Mb_Image_Field() ),
			'image_upload'   => new Mb_Gallery_Field( new Mb_Image_Field() ),
			'single_image'   => new Mb_Image_Field(),
			'video'          => new Html_Field(),
		);

		$layout = array(
			'group' => new Pro_Stub_Field(),
		);

		return array_merge( $basic, $advanced, $html5, $wordpress, $upload, $layout );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_group_choices(): array {
		$source_label = __( '(Meta Box)', 'acf-views' );
		$groups       = array();

		foreach ( $this->get_groups() as $group_info ) {
			$group_id = $this->get_string_arg( 'id', $group_info );

			$group_key            = $this->get_group_key( $group_id );
			$groups[ $group_key ] = $this->get_string_arg( 'title', $group_info ) . ' ' . $source_label;
		}

		return $groups;
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

		$repeatable_label     = __( 'repeatable', 'acf-views' );
		$pro_stub_field_types = $this->get_pro_stub_field_types();

		// we can check if repeatable is supported by checking if group field is a ProStubField.
		$group_instance              = $this->get_registered_field_types()['group'] ?? null;
		$is_repeatable_not_supported = $group_instance instanceof Pro_Stub_Field;

		foreach ( $this->get_groups() as $group_info ) {
			$fields   = $this->get_array_arg( 'fields', $group_info );
			$group_id = $this->get_string_arg( 'id', $group_info );

			$field_choices = $this->get_field_choices_recursively(
				$field_choices,
				$fields,
				$this->get_supported_field_types(),
				$include_only_types,
				$pro_stub_field_types,
				$group_id,
				$is_meta_format,
				$repeatable_label,
				$is_repeatable_not_supported,
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
		if ( array() === $data ) {
			$data = $this->get_field_info( $field_meta->get_field_id() );
		}

		$field_type = $this->get_string_arg( 'type', $data );

		if ( array() === $data ||
			! in_array( $field_type, $this->get_supported_field_types(), true ) ) {
			return;
		}

		$field_meta->set_name( $this->get_string_arg( 'name', $data ) );
		$field_meta->set_type( $field_type );
		$field_meta->set_choices( $this->get_array_arg( 'options', $data ) );
		$field_meta->set_is_multiple( $this->get_bool_arg( 'multiple', $data ) );
		$field_meta->set_return_format( $this->get_string_arg( 'save_format', $data ) );
		$field_meta->set_display_format( $this->get_string_arg( 'save_format', $data ) );

		$timestamp = (bool) ( $data['timestamp'] ?? false );

		if ( true === $timestamp ) {
			$field_meta->set_return_format( 'U' );
		}

		if ( key_exists( 'std', $data ) ) {
			if ( is_array( $data['std'] ) ) {
				$field_meta->set_default_value( $data['std'] );
			} else {
				$field_meta->set_default_value( $this->get_string_arg( 'std', $data ) );
			}
		}

		switch ( $field_type ) {
			case 'file':
			case 'file_advanced':
			case 'file_upload':
				$field_meta->set_is_multiple( true );
				break;
			case 'date':
				// set the default value (from MetaBox)
				// if user changes the format using the jsOptions setting, we can't get it, so he must define 'save_format' in that case.
				if ( '' === $field_meta->get_return_format() ) {
					$field_meta->set_return_format( 'Y-m-d' );
					$field_meta->set_display_format( 'Y-m-d' );
				} elseif ( 'U' === $field_meta->get_return_format() ) {
					$field_meta->set_display_format( 'Y-m-d' );
				}
				break;
			case 'datetime':
				// set the default value (from MetaBox)
				// if user changes the format using the jsOptions setting, we can't get it, so he must define 'save_format' in that case.
				if ( '' === $field_meta->get_return_format() ) {
					$field_meta->set_return_format( 'Y-m-d H:i' );
					$field_meta->set_display_format( 'Y-m-d H:i' );
				} elseif ( 'U' === $field_meta->get_return_format() ) {
					$field_meta->set_display_format( 'Y-m-d H:i' );
				}
				break;
		}

		if ( 'group' === $field_type ) {
			$field_meta->set_is_group( true );
		}

		$field_meta->set_is_field_exist( true );

		$is_repeatable = ( $this->get_bool_arg( 'clone', $data ) );
		// some fields don't require the repeater trick, as they use the HTML field with the formatted value.
		// so markup will already include all the content.
		$field_types_without_self_repeatable = array(
			'video',
			'sidebar',
			'background',
			'oembed',
			'wysiwyg',
		);

		if ( true === $is_repeatable &&
			false === in_array( $field_type, $field_types_without_self_repeatable, true ) ) {
			if ( 'group' === $field_type ) {
				$field_meta->set_is_group( false );
				$field_meta->set_is_repeater( true );
			} else {
				$field_meta->set_self_repeatable_meta( clone $field_meta );
				$field_meta->set_type( 'group' );
			}
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
		$field_id = $field_meta->get_field_id();

		$source_id       = $source->get_id();
		$args            = $this->get_field_request_args( $field_data, $field_meta, $source, $source_id );
		$self_repeatable = $field_meta->get_self_repeatable_meta();

		if ( null !== $local_data ) {
			$value = $local_data[ $field_id ] ?? null;

			if ( true === $is_formatted &&
				true === function_exists( 'rwmb_get_field_settings' ) &&
				true === class_exists( 'RWMB_Field' ) &&
				true === is_callable( array( 'RWMB_Field', 'call' ) ) ) {
				$field = $this->get_field_info( $field_id );

				$action = null !== $self_repeatable ?
					'format_clone_value' :
					'format_single_value';

				/**
				 * @var mixed $value
				 */
				$value = RWMB_Field::call( $action, $field, $value, $args, $source_id );
			}

			return $value;
		}

		if ( false === $is_formatted ) {
			if ( false === $source->is_block() ) {
				if ( null === $self_repeatable ||
					false === in_array( $self_repeatable->get_type(), array( 'osm', 'map' ), true ) ) {
					$value = false !== function_exists( 'rwmb_get_value' ) ?
						rwmb_get_value( $field_id, $args, $source_id ) :
						null;
				} else {
					// self repeatable maps can't be read via rwmb_get_value - https://support.metabox.io/topic/bug-clonable-map-field-not-displaying-data/.
					$value = get_post_meta( $source_id, $field_id, true );
				}
			} else {
				$value = false !== function_exists( 'mb_get_block_field' ) ?
					mb_get_block_field( $field_id, $args ) :
					null;
			}

			return $value;
		}

		if ( false === $source->is_block() ) {
			if ( 'group' !== $field_meta->get_type() ) {
				$value = false !== function_exists( 'rwmb_the_value' ) ?
					rwmb_the_value( $field_id, $args, $source_id, false ) :
					null;
			} else {
				/**
				 * Rwmb_the_value() doesn't fit for groups, repeaters and self-repeatable.
				 * Instead of looping through each field and formatting it, it returns HTML for the whole array as single string.
				 * Note: get_post_meta() works with clonable map, while rwmb_get_value not - https://support.metabox.io/topic/bug-clonable-map-field-not-displaying-data/
				 */
				$value = get_post_meta( $source_id, $field_id, true );

				// It's impossible to format the group value without the item data.
				$value = null !== $item_data ?
					$this->format_group_value( $value, $field_data, $field_meta, $item_data, $source ) :
					$value;
			}
			return $value;
		}

		$value = false !== function_exists( 'mb_the_block_field' ) ?
			mb_the_block_field( $field_id, $args, false ) :
			null;

		return $value;
	}

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime {
		$date_time = false;

		switch ( $field_meta->get_type() ) {
			case 'date':
			case 'datetime':
				$date_time = DateTime::createFromFormat( $field_meta->get_return_format(), $value );
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
		return $date_time->format( $field_meta->get_return_format() );
	}

	/**
	 * @return null|array{title:string,url:string}
	 */
	public function get_group_link_by_group_id( string $group_id ): ?array {
		$post = get_page_by_path( $group_id, OBJECT, 'meta-box' );

		if ( null === $post ) {
			return null;
		}

		return array(
			'title' => $post->post_title,
			'url'   => (string) get_edit_post_link( $post->ID ),
		);
	}

	/**
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_sub_field_choices( bool $is_meta_format = false, bool $is_field_name_as_label = false ): array {
		$sub_field_choices = array();

		$repeatable_label = __( 'repeatable', 'acf-views' );

		foreach ( $this->get_groups() as $group_info ) {
			$fields = $this->get_array_arg( 'fields', $group_info );

			$group_id = $this->get_string_arg( 'id', $group_info );

			foreach ( $fields as $field ) {
				if ( false === is_array( $field ) ||
					'group' !== $this->get_string_arg( 'type', $field ) ) {
					continue;
				}

				$sub_fields = $this->get_array_arg( 'fields', $field );

				$field_key = $this->get_field_key( $group_id, $this->get_string_arg( 'id', $field ) );

				$sub_field_choices = $this->get_field_choices_recursively(
					$sub_field_choices,
					$sub_fields,
					$this->get_supported_field_types(),
					array(),
					array(),
					$group_id,
					$is_meta_format,
					$repeatable_label,
					false,
					$field_key,
					$is_field_name_as_label
				);
			}
		}

		return $sub_field_choices;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_group_export_data( string $group_id ): ?array {
		$post = get_page_by_path( $group_id, OBJECT, 'meta-box' );

		if ( null === $post ) {
			$this->get_logger()->warning(
				'failed to get MetaBox group by path',
				array(
					'path' => $group_id,
				)
			);

			return null;
		}

		$post_data = array(
			'post_type'    => $post->post_type,
			'post_name'    => $post->post_name,
			'post_title'   => $post->post_title,
			'post_date'    => $post->post_date,
			'post_status'  => $post->post_status,
			'post_content' => $post->post_content,
		);

		foreach ( $this->get_export_meta_keys() as $meta_key ) {
			$post_data[ $meta_key ] = get_post_meta( $post->ID, $meta_key, true );
		}

		return $post_data;
	}

	/**
	 * @param array<int|string, mixed> $group_data
	 * @param array<string, mixed> $meta_data
	 */
	public function import_group( array $group_data, array $meta_data ): ?string {
		if ( false === function_exists( 'rwmb_get_registry' ) ) {
			return null;
		}

		$registry = rwmb_get_registry( 'meta_box' );

		if ( false === is_object( $registry ) ||
			false === method_exists( $registry, 'make' ) ) {
			return null;
		}

		$post_name = $this->get_string_arg_if_present( 'post_name', $group_data );

		if ( null === $post_name ) {
			$this->get_logger()->warning(
				'failed to get post_name from MetaBox group data',
				array(
					'group_data' => $group_data,
				)
			);

			return null;
		}

		$existing_post = get_page_by_path( $post_name, OBJECT, 'meta-box' );

		// set existing post id, so it'll be updated instead of inserted.
		if ( null !== $existing_post ) {
			$group_data['ID'] = $existing_post->ID;
		}

		// @phpstan-ignore-next-line
		$post_id = wp_insert_post( $group_data, true );

		if ( true === is_wp_error( $post_id ) ) {
			$this->get_logger()->warning(
				'failed to insert MetaBox group',
				array(
					'error_message' => $post_id->get_error_message(),
					'error_code'    => $post_id->get_error_code(),
					'group_data'    => $group_data,
				)
			);

			return null;
		}

		$post = get_post( $post_id );

		if ( null === $post ) {
			$this->get_logger()->warning(
				'failed to get MetaBox group by id',
				array(
					'post_id' => $post_id,
				)
			);

			return null;
		}

		foreach ( $this->get_export_meta_keys() as $meta_key ) {
			if ( false === key_exists( $meta_key, $group_data ) ) {
				continue;
			}

			update_post_meta( $post_id, $meta_key, $group_data[ $meta_key ] );
		}

		// manually signup in MetaBox registry,
		// otherwise won't be available for query within this request.
		$registry->make( $group_data );

		return $post->post_name;
	}
}
