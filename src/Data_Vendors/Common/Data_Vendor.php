<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Pro_Stub_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;

defined( 'ABSPATH' ) || exit;

abstract class Data_Vendor extends Action implements Data_Vendor_Interface {
	use Safe_Array_Arguments;

	/**
	 * @var array<string,Markup_Field_Interface>
	 */
	private array $field_types;

	public function __construct( Logger $logger ) {
		parent::__construct( $logger );

		$this->field_types = $this->get_field_types();
	}

	/**
	 * @return array<string,Markup_Field_Interface>
	 */
	abstract protected function get_field_types(): array;

	protected function get_field_key( string $group_id, string $field_id, string $sub_field_id = '' ): string {
		$source = ! $this->is_without_name_in_keys() ?
			$this->get_name() :
			'';

		return Field_Data::create_field_key( $group_id, $field_id, $sub_field_id, $source );
	}

	// for back compatibility only.
	protected function is_without_name_in_keys(): bool {
		return false;
	}

	/**
	 * @return string[]
	 */
	protected function get_pro_stub_field_types(): array {
		$pro_stub_field_types = array();

		foreach ( $this->field_types as $field_type => $instance ) {
			if ( false === ( $instance instanceof Pro_Stub_Field ) ) {
				continue;
			}

			$pro_stub_field_types[] = $field_type;
		}

		return $pro_stub_field_types;
	}

	protected function get_pro_only_label(): string {
		return __( '[PRO only]', 'acf-views' );
	}

	/**
	 * @return  array<string,Markup_Field_Interface>
	 */
	protected function get_registered_field_types(): array {
		return $this->field_types;
	}

	public function get_group_key( string $group_id ): string {
		$source = false === $this->is_without_name_in_keys() ?
			$this->get_name() :
			'';

		return Item_Data::create_group_key( $group_id, $source );
	}

	/**
	 * @return string[]
	 */
	public function get_supported_field_types(): array {
		return array_keys( $this->field_types );
	}

	/**
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_sub_field_choices( bool $is_meta_format = false, bool $is_field_name_as_label = false ): array {
		return array();
	}

	public function get_markup_field_instance( string $field_type ): ?Markup_Field_Interface {
		$instance = $this->field_types[ $field_type ] ?? null;

		return $instance instanceof Markup_Field_Interface ?
			$instance :
			null;
	}

	/**
	 * @return array<string, array<int,string|int>>
	 */
	public function get_field_key_conditional_rules( bool $is_sub_fields = false ): array {
		$field_key_conditional_rules = array();

		$field_choices = false === $is_sub_fields ?
			$this->get_field_choices( array(), true ) :
			$this->get_sub_field_choices( true );

		/**
		 * @var Field_Meta_Interface $field_meta
		 */
		foreach ( $field_choices as $field_key => $field_meta ) {
			if ( false === $field_meta->is_field_exist() ) {
				continue;
			}

			$field_instance = $this->get_markup_field_instance( $field_meta->get_type() );

			if ( null === $field_instance ) {
				continue;
			}

			$conditional_instance_field_keys = $field_instance->get_conditional_fields( $field_meta );

			$self_repeatable_meta = $field_meta->get_self_repeatable_meta();

			// add repeatable setting to the field settings too.
			if ( null !== $self_repeatable_meta ) {
				$repeatable_instance = $this->get_markup_field_instance( $self_repeatable_meta->get_type() );

				if ( null !== $repeatable_instance ) {
					$conditional_instance_field_keys = array_merge(
						$conditional_instance_field_keys,
						$repeatable_instance->get_conditional_fields( $field_meta )
					);
					$conditional_instance_field_keys = array_unique( $conditional_instance_field_keys );
				}
			}

			foreach ( $conditional_instance_field_keys as $conditional_instance_field_key ) {
				$field_key_conditional_rules[ $conditional_instance_field_key ]   = $field_key_conditional_rules[ $conditional_instance_field_key ] ?? array();
				$field_key_conditional_rules[ $conditional_instance_field_key ][] = $field_key;
			}
		}

		return $field_key_conditional_rules;
	}

	public function is_empty_value_supported_in_markup( string $field_type ): bool {
		$field_instance = $this->field_types[ $field_type ] ?? null;

		return null !== $field_instance &&
				$field_instance->is_empty_value_supported_in_markup();
	}

	/**
	 * @return string[]
	 */
	public function get_field_front_assets( Field_Data $field_data ): array {
		$field_type     = $field_data->get_field_meta()->get_type();
		$field_instance = $this->field_types[ $field_type ] ?? null;

		return null !== $field_instance ?
			$field_instance->get_front_assets( $field_data ) :
			array();
	}

	/**
	 * @return string[]
	 */
	public function get_field_types_with_sub_fields(): array {
		$field_types_with_sub_fields = array();

		foreach ( $this->field_types as $field_type => $instance ) {
			if ( false === $instance->is_sub_fields_supported() ) {
				continue;
			}

			$field_types_with_sub_fields[] = $field_type;
		}

		return $field_types_with_sub_fields;
	}

	/**
	 * @param array<string, mixed> $groups_data
	 *
	 * @return array<string, mixed>
	 */
	public function get_export_meta_data( array $groups_data ): array {
		// by default, no special meta is needed (it's vendor specific only, e.g. for Pods).
		return array();
	}
}
