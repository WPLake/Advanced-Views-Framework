<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Pods;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Date_Picker_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\File_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Gallery_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Html_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Plain_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Post_Object_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Select_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Taxonomy_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\True_False_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Url_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\User_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Pods\Fields\Pods_Pick_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Pods\Fields\Pods_Upload_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Field_Meta;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use Pods_Migrate_Packages;

defined( 'ABSPATH' ) || exit;

class Pods_Data_Vendor extends Data_Vendor {
	const NAME = 'pods';

	/**
	 * @var array<string|int,mixed>|null
	 */
	private ?array $pick_related_objects;

	public function __construct( Logger $logger ) {
		parent::__construct( $logger );

		$this->pick_related_objects = null;
	}

	/**
	 * @return array<string,array<int,object>>
	 */
	protected function get_groups(): array {
		if ( false === function_exists( 'pods_api' ) ) {
			return array();
		}

		$pods_api = pods_api();

		if ( false === is_object( $pods_api ) ||
			false === method_exists( $pods_api, 'load_pods' ) ) {
			return array();
		}

		$pods = $pods_api->load_pods();

		$groups = array();

		foreach ( $pods as $type => $pod ) {
			if ( false === is_object( $pod ) ||
				false === method_exists( $pod, 'get_groups' ) ) {
				continue;
			}

			$type            = (string) $type;
			$pod_groups      = $pod->get_groups();
			$groups[ $type ] = array();

			foreach ( $pod_groups as $pod_group ) {
				$groups[ $type ][] = $pod_group;
			}
		}

		return $groups;
	}

	protected function get_php_date_format( string $js_date_format ): string {
		$date_formats = array(
			'mdy'       => 'm/d/Y',
			'mdy_dash'  => 'm-d-Y',
			'mdy_dot'   => 'm.d.Y',
			'dmy'       => 'd/m/Y',
			'dmy_dash'  => 'd-m-Y',
			'dmy_dot'   => 'd.m.Y',
			'ymd_slash' => 'Y/m/d',
			'ymd_dash'  => 'Y-m-d',
			'ymd_dot'   => 'Y.m.d',
			'dMy'       => 'd/M/Y',
			'dMy_dash'  => 'd-M-Y',
			'fjy'       => 'F j, Y',
			'fjsy'      => 'F jS, Y',
			'y'         => 'Y',
			'c'         => 'c',
		);

		return true === key_exists( $js_date_format, $date_formats ) ?
			$date_formats[ $js_date_format ] :
			'';
	}

	protected function get_php_time_format( string $js_time_format ): string {
		$time_formats = array(
			'h_mm_A'     => 'g:i A',
			'h_mm_ss_A'  => 'g:i:s A',
			'hh_mm_A'    => 'h:i A',
			'hh_mm_ss_A' => 'h:i:s A',
			'h_mma'      => 'g:ia',
			'hh_mma'     => 'h:ia',
			'h_mm'       => 'g:i',
			'h_mm_ss'    => 'g:i:s',
			'hh_mm'      => 'h:i',
			'hh_mm_ss'   => 'h:i:s',
		);

		return true === key_exists( $js_time_format, $time_formats ) ?
			$time_formats[ $js_time_format ] :
			'';
	}

	/**
	 * @param array<string,mixed> $data
	 */
	protected function get_display_format( array $data ): string {
		$field_type = $this->get_string_arg( 'type', $data );

		switch ( $field_type ) {
			case 'datetime':
				$date_time_type = $this->get_string_arg( 'datetime_type', $data );

				if ( 'wp' === $date_time_type ) {
					return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				}

				$display_format = $this->get_string_arg( 'datetime_format', $data );
				$display_format = '' !== $display_format ?
					$this->get_php_date_format( $display_format ) :
					'';

				$date_time_type = $this->get_string_arg( 'datetime_time_type', $data );

				$display_time_format = '24' === $date_time_type ?
					$this->get_string_arg( 'datetime_time_format_24', $data ) :
					$this->get_string_arg( 'datetime_time_format', $data );
				$display_time_format = '' !== $display_time_format ?
					$this->get_php_time_format( $display_time_format ) :
					'';

				$display_format .= '' !== $display_time_format ?
					' ' . $display_time_format :
					'';

				return $display_format;
			case 'date':
				$date_type = $this->get_string_arg( 'date_type', $data );

				if ( 'wp' === $date_type ) {
					$format = get_option( 'date_format' );

					return true === is_string( $format ) ?
						$format :
						'';
				}

				$display_format = $this->get_string_arg( 'date_format', $data );

				return $this->get_php_date_format( $display_format );
			case 'time':
				$time_type = $this->get_string_arg( 'time_type', $data );

				if ( 'wp' === $time_type ) {
					$format = get_option( 'time_format' );

					return true === is_string( $format ) ?
						$format :
						'';
				}

				$display_time_format = '24' === $time_type ?
					$this->get_string_arg( 'time_format_24', $data ) :
					$this->get_string_arg( 'time_format', $data );

				return $this->get_php_time_format( $display_time_format );
			default:
				return '';
		}
	}

	/**
	 * @return array<string|int,mixed>
	 */
	protected function get_pick_related_objects(): array {
		// cache.
		if ( null !== $this->pick_related_objects ) {
			return $this->pick_related_objects;
		}

		$pods_pick = 'PodsField_Pick';

		if ( false === class_exists( $pods_pick ) ||
			false === property_exists( $pods_pick, 'related_objects' ) ) {
			$this->pick_related_objects = array();

			return $this->pick_related_objects;
		}

		$pods_pick_instance = new $pods_pick();

		if ( false === is_object( $pods_pick_instance ) ||
			false === method_exists( $pods_pick_instance, 'setup_related_objects' ) ) {
			$this->pick_related_objects = array();

			return $this->pick_related_objects;
		}

		call_user_func( array( $pods_pick_instance, 'setup_related_objects' ) );

		$related_objects = $pods_pick::$related_objects;

		$this->pick_related_objects = true === is_array( $related_objects ) ?
			$related_objects :
			array();

		return $this->pick_related_objects;
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_dynamic_choices( string $pick_object ): array {
		$pick_related_objects = $this->get_pick_related_objects();

		$related_object = true === key_exists( $pick_object, $pick_related_objects ) &&
							true === is_array( $pick_related_objects[ $pick_object ] ) ?
			$pick_related_objects[ $pick_object ] :
			array();

		$options = true === key_exists( 'data_callback', $related_object ) &&
					true === is_callable( $related_object['data_callback'] ) ?
			call_user_func( $related_object['data_callback'] ) :
			array();

		return true === is_array( $options ) ?
			$options :
			array();
	}

	/**
	 * @param array<string,mixed> $data
	 *
	 * @return array<string,string>
	 */
	protected function get_choices( array $data ): array {
		$pick_object = $this->get_string_arg( 'pick_object', $data );

		if ( 'custom-simple' !== $pick_object ) {
			// skip non-select fields (in Fields/Pods_Pick_Field.php they don't employ the Select field).
			if ( true === in_array( $pick_object, array( 'user', 'post_type', 'taxonomy', 'media' ), true ) ) {
				return array();
			}

			return $this->get_dynamic_choices( $pick_object );
		}

		$pick_choices = $this->get_string_arg( 'pick_custom', $data );

		if ( '' === $pick_choices ) {
			return array();
		}

		$choices      = array();
		$pick_choices = explode( "\n", $pick_choices );

		foreach ( $pick_choices as $pick_choice ) {
			$pick_choice = explode( '|', $pick_choice );
			$choice_key  = trim( $pick_choice[0] );
			// optional, can be just one value.
			$choice_value = key_exists( 1, $pick_choice ) ?
				trim( $pick_choice[1] ) :
				$choice_key;

			$choices[ $choice_key ] = $choice_value;
		}

		return $choices;
	}

	/**
	 * @param int|string $source_id
	 * @param array<string|int,mixed>|null $local_data
	 *
	 * @return mixed
	 */
	protected function get_pods_value(
		string $field_name,
		string $field_source_type,
		$source_id,
		bool $is_formatted,
		?array $local_data = null
	) {
		if ( false === function_exists( 'pods' ) ) {
			return null;
		}

		if ( true === is_string( $source_id ) ) {
			if ( false !== strpos( $source_id, 'user_' ) ) {
				$source_id = str_replace( 'user_', '', $source_id );
			} elseif ( false !== strpos( $source_id, 'term_' ) ) {
				$source_id = str_replace( 'term_', '', $source_id );
			} elseif ( false !== strpos( $source_id, 'comment_' ) ) {
				$source_id = str_replace( 'comment_', '', $source_id );
			}
		}

		// it also works with user/term/comment, as pod is attached to specific type (post/user/term).
		$pod = pods( $field_source_type, $source_id );

		if ( false === is_object( $pod ) ||
			false === method_exists( $pod, 'is_valid' ) ||
			false === method_exists( $pod, 'field' ) ||
			false === $pod->is_valid() ) {
			return null;
		}

		return $pod->field( $field_name, null, $is_formatted );
	}

	protected function get_pod_object_by_group_id( string $group_id ): ?object {
		if ( false === function_exists( 'pods_api' ) ) {
			return null;
		}

		$pods_api = pods_api();

		if ( false === is_object( $pods_api ) ||
			false === method_exists( $pods_api, 'load_pod' ) ) {
			return null;
		}

		$pod_id = explode( '.', $group_id )[0];

		$pod = $pods_api->load_pod(
			array(
				'name' => $pod_id,
			)
		);

		return true === is_object( $pod ) ?
			$pod :
			null;
	}

	protected function get_group_object_by_group_id( string $group_id ): ?object {
		if ( false === function_exists( 'pods_api' ) ) {
			return null;
		}

		$pods_api = pods_api();

		if ( false === is_object( $pods_api ) ||
			false === method_exists( $pods_api, 'load_group' ) ) {
			return null;
		}

		$pod_id         = explode( '.', $group_id )[0];
		$group_short_id = explode( '.', $group_id )[1] ?? '';

		$group = $pods_api->load_group(
			array(
				'pod'  => $pod_id,
				'name' => $group_short_id,
			)
		);

		return true === is_object( $group ) ?
			$group :
			null;
	}

	public function get_name(): string {
		return static::NAME;
	}

	public function is_meta_vendor(): bool {
		return true;
	}

	public function is_available(): bool {
		return function_exists( 'pods' );
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
		return new Pods_Integration(
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

	// groupId without the type doesn't allow to identify the group itself.
	public function get_pods_group_id( string $pod_name, string $group_id ): string {
		return $pod_name . '.' . $group_id;
	}

	// fieldId without the type doesn't allow to identify the field itself.
	public function get_pods_field_id( string $type, string $field_id ): string {
		return $type . '.' . $field_id;
	}

	public function get_field_types(): array {
		$text = array(
			'text'     => new Plain_Field(),
			'website'  => new Url_Field(),
			'phone'    => new Plain_Field(),
			'email'    => new Plain_Field(),
			'password' => new Plain_Field(),
		);

		$paragraph = array(
			'paragraph' => new Html_Field( true, true ),
			'wysiwyg'   => new Html_Field( true, true ),
			'code'      => new Html_Field(),
		);

		$date = array(
			'datetime' => new Date_Picker_Field(),
			'date'     => new Date_Picker_Field(),
			'time'     => new Date_Picker_Field(),
		);

		$number = array(
			'number'   => new Plain_Field(),
			'currency' => new Html_Field(),
		);

		$relationship = array(
			'file'   => new Pods_Upload_Field(
				new Image_Field(),
				new File_Field( new Link_Field() ),
				new Gallery_Field( new Image_Field() )
			),
			'oembed' => new Html_Field(),
			'pick'   => new Pods_Pick_Field(
				new Select_Field(),
				new User_Field( new Link_Field() ),
				new Post_Object_Field( new Link_Field() ),
				new Taxonomy_Field( new Link_Field() ),
				new File_Field( new Link_Field() )
			),
		);

		$other = array(
			'boolean' => new True_False_Field(),
			'color'   => new Plain_Field(),
		);

		return array_merge( $text, $paragraph, $date, $number, $relationship, $other );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_group_choices(): array {
		$source_label  = __( '(Pods)', 'acf-views' );
		$group_choices = array();

		foreach ( $this->get_groups() as $type => $groups ) {
			foreach ( $groups as $group ) {
				if ( false === method_exists( $group, 'get_args' ) ) {
					continue;
				}

				$group_info = $group->get_args();
				$group_info = is_array( $group_info ) ?
					$group_info :
					array();

				$group_id    = $this->get_string_arg( 'name', $group_info );
				$group_title = $this->get_string_arg( 'label', $group_info );

				$group_key                   = $this->get_group_key( $this->get_pods_group_id( $type, $group_id ) );
				$group_choices[ $group_key ] = sprintf( '[%s] %s %s', $type, $group_title, $source_label );
			}
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

		$repeatable_label     = __( 'repeatable', 'acf-views' );
		$pro_stub_field_types = $this->get_pro_stub_field_types();

		$is_repeatable_not_supported = false === key_exists( 'group', $this->get_registered_field_types() );

		foreach ( $this->get_groups() as $type => $groups ) {
			foreach ( $groups as $group ) {
				if ( false === method_exists( $group, 'get_args' ) ||
					false === method_exists( $group, 'get_fields' ) ) {
					continue;
				}

				$group_info = $group->get_args();
				$group_info = is_array( $group_info ) ?
					$group_info :
					array();

				$group_id = $this->get_string_arg( 'name', $group_info );
				$group_id = $this->get_pods_group_id( $type, $group_id );

				foreach ( $group->get_fields() as $field ) {
					if ( false === is_object( $field ) ||
						false === method_exists( $field, 'get_args' ) ) {
						continue;
					}

					$field_info = $field->get_args();
					$field_info = is_array( $field_info ) ?
						$field_info :
						array();
					$field_type = $this->get_string_arg( 'type', $field_info );

					if ( false === in_array( $field_type, $this->get_supported_field_types(), true ) ||
						( array() !== $include_only_types && false === in_array(
							$field_type,
							$include_only_types,
							true
						) ) ) {
						continue;
					}

					$field_id = $this->get_string_arg( 'name', $field_info );
					$field_id = $this->get_pods_field_id( $type, $field_id );

					$field_key = $this->get_field_key( $group_id, $field_id );

					if ( true === $is_meta_format ) {
						$value = new Field_Meta( $this->get_name(), $field_id );
						$this->fill_field_meta( $value, $field_info );
					} elseif ( false === $is_field_name_as_label ) {
						$is_repeatable    = $this->get_bool_arg( 'repeatable', $field_info );
						$repeatable_field = $is_repeatable ?
							$repeatable_label . ' ' :
							'';
						$label            = $this->get_string_arg( 'label', $field_info );
						$value            = sprintf( '%s (%s%s)', $label, $repeatable_field, $field_type );

						if ( true === in_array( $field_type, $pro_stub_field_types, true ) ||
							( true === $is_repeatable && true === $is_repeatable_not_supported ) ) {
							$value .= ' ' . $this->get_pro_only_label();
						}
					} else {
						$value = $this->get_string_arg( 'name', $field_info );
					}

					$field_choices[ $field_key ] = $value;
				}
			}
		}

		return $field_choices;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public function fill_field_meta( Field_Meta_Interface $field_meta, array $data = array() ): void {
		if ( array() === $data ) {
			$field_id          = $field_meta->get_field_id();
			$field_source_type = explode( '.', $field_id )[0];
			$field_name        = explode( '.', $field_id )[1] ?? '';

			foreach ( $this->get_groups() as $type => $groups ) {
				if ( $field_source_type !== $type ) {
					continue;
				}

				foreach ( $groups as $group ) {
					if ( false === method_exists( $group, 'get_fields' ) ) {
						continue;
					}

					foreach ( $group->get_fields() as $field ) {
						if ( false === is_object( $field ) ||
							false === method_exists( $field, 'get_args' ) ) {
							continue;
						}
						$field_info = $field->get_args();
						$field_info = is_array( $field_info ) ?
							$field_info :
							array();
						$field_id   = $this->get_string_arg( 'name', $field_info );

						if ( $field_id === $field_name ) {
							$data = $field_info;
							break;
						}
					}
				}
			}
		}

		$field_type = $this->get_string_arg( 'type', $data );

		if ( array() === $data ||
			! in_array( $field_type, $this->get_supported_field_types(), true ) ) {
			return;
		}

		$field_meta->set_name( $this->get_string_arg( 'name', $data ) );
		$field_meta->set_type( $field_type );
		$field_meta->set_display_format( $this->get_display_format( $data ) );

		// for file and pick fields.
		$return_format = $this->get_string_arg( 'file_type', $data );
		$return_format = '' === $return_format ?
			$this->get_string_arg( 'pick_object', $data ) :
			$return_format;
		$field_meta->set_return_format( $return_format );

		// for file and pick fields.
		$format_type = $this->get_string_arg( 'file_format_type', $data );
		$format_type = '' === $format_type ?
			$this->get_string_arg( 'pick_format_type', $data ) :
			$format_type;
		$field_meta->set_is_multiple( 'multi' === $format_type );

		$field_meta->set_choices( $this->get_choices( $data ) );

		$field_meta->set_is_field_exist( true );

		$is_repeatable = ( $this->get_bool_arg( 'repeatable', $data ) );

		if ( true === $is_repeatable ) {
			$field_meta->set_self_repeatable_meta( clone $field_meta );
			$field_meta->set_type( 'group' );
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
		if ( false === function_exists( 'pods' ) ) {
			return null;
		}

		$field_id          = $field_meta->get_field_id();
		$field_source_type = explode( '.', $field_id )[0];
		$field_name        = explode( '.', $field_id )[1] ?? '';
		$source_id         = $source->get_id();

		$value = $this->get_pods_value( $field_name, $field_source_type, $source_id, $is_formatted, $local_data );

		switch ( $field_meta->get_type() ) {
			case 'oembed':
				$value = true === is_string( $value ) &&
						'' !== $value ?
					wp_oembed_get( $value ) :
					'';
				$value = true === is_string( $value ) ?
					$value :
					'';
				break;
		}

		return $value;
	}

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime {
		$date_time = false;

		switch ( $field_meta->get_type() ) {
			case 'datetime':
				$date_time = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
				break;
			case 'date':
				$date_time = DateTime::createFromFormat( 'Y-m-d', $value );
				break;
			case 'time':
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
			case 'datetime':
				return $date_time->format( 'Y-m-d H:i:s' );
			case 'date':
				return $date_time->format( 'Y-m-d' );
			case 'time':
				return $date_time->format( 'H:i:s' );
			default:
				return '';
		}
	}

	/**
	 * @return null|array{title:string,url:string}
	 */
	public function get_group_link_by_group_id( string $group_id ): ?array {
		$pod   = $this->get_pod_object_by_group_id( $group_id );
		$group = $this->get_group_object_by_group_id( $group_id );

		if ( false === is_object( $pod ) ||
			false === is_object( $group ) ||
			false === method_exists( $pod, 'get_args' ) ||
			false === method_exists( $group, 'get_args' ) ) {
			return null;
		}

		$pod_info   = $pod->get_args();
		$pod_info   = true === is_array( $pod_info ) ?
			$pod_info :
			array();
		$group_info = $group->get_args();
		$group_info = true === is_array( $group_info ) ?
			$group_info :
			array();

		$pod_id      = $this->get_int_arg( 'id', $pod_info );
		$group_label = $this->get_string_arg( 'label', $group_info );

		return array(
			'title' => $group_label,
			'url'   => admin_url( 'admin.php?page=pods&action=edit&id=' . $pod_id ),
		);
	}

	/**
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_sub_field_choices( bool $is_meta_format = false, bool $is_field_name_as_label = false ): array {
		return array();
	}

	/**
	 * Exports of the whole pod
	 * (as the group is dependent part, and won't be exported if the pod is missing)
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_group_export_data( string $group_id ): ?array {
		$pod = $this->get_pod_object_by_group_id( $group_id );

		if ( null === $pod ||
			false === is_callable( array( $pod, 'get_id' ) ) ||
			false === defined( 'PODS_DIR' ) ||
			false === class_exists( 'Pods_Migrate_Packages' ) ||
			false === is_callable( array( 'Pods_Migrate_Packages', 'export' ) ) ) {
			return null;
		}

		include_once PODS_DIR . 'components/Migrate-Packages/Migrate-Packages.php';

		$export_data = Pods_Migrate_Packages::export(
			array(
				'pods' => array( $pod->get_id() ),
			)
		);

		if ( false === is_string( $export_data ) ) {
			$this->get_logger()->warning( 'pods export failed', array( 'group_id' => $group_id ) );

			return null;
		}

		$export_data = json_decode( $export_data, true );

		if ( false === is_array( $export_data ) ) {
			$this->get_logger()->warning( 'pods export data is not an array', array( 'group_id' => $group_id ) );

			return null;
		}

		$pods_export_data = $this->get_array_arg_if_present( 'pods', $export_data );

		if ( null === $pods_export_data ) {
			$this->get_logger()->warning( 'pods missing in the export data', array( 'group_id' => $group_id ) );

			return null;
		}

		return count( $pods_export_data ) > 0 &&
				true === is_array( $pods_export_data[0] ) ?
			$pods_export_data[0] :
			null;
	}

	/**
	 * @param array<string, mixed> $groups_data
	 *
	 * @return array<string, mixed>
	 */
	public function get_export_meta_data( array $groups_data ): array {
		$pods_version = defined( 'PODS_VERSION' ) ?
			constant( 'PODS_VERSION' ) :
			'';

		return array(
			'version' => $pods_version,
			'build'   => time(),
		);
	}

	/**
	 * @param array<int|string, mixed> $group_data
	 * @param array<string, mixed> $meta_data
	 */
	public function import_group( array $group_data, array $meta_data ): ?string {
		if ( false === class_exists( 'Pods_Migrate_Packages' ) ||
			false === defined( 'PODS_DIR' ) ||
			false === is_callable( array( 'Pods_Migrate_Packages', 'import_pod' ) ) ) {
			return null;
		}

		include_once PODS_DIR . 'components/Migrate-Packages/Migrate-Packages.php';

		// for compatibility with our old export format, which didn't have meta.
		if ( array() === $meta_data ) {
			$pods_version = defined( 'PODS_VERSION' ) ?
				constant( 'PODS_VERSION' ) :
				'';

			$meta_data = array(
				'version' => $pods_version,
				'build'   => time(),
			);
		}

		$imported_pod = Pods_Migrate_Packages::import(
			array(
				'@meta' => $meta_data,
				'pods'  => array(
					$group_data,
				),
			)
		);

		if ( false === is_array( $imported_pod ) ) {
			$this->get_logger()->warning(
				'Pods import failed',
				array(
					'group_data' => $group_data,
				)
			);

			return null;
		}

		return $this->get_string_arg_if_present( 'name', $group_data );
	}
}
