<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views;

defined( 'ABSPATH' ) || exit;

class Field_Meta implements Field_Meta_Interface {
	private string $vendor_name;
	private string $field_id;
	private string $name;
	private string $type;
	private string $return_format;
	/**
	 * @var array<string, string>
	 */
	private array $choices;
	private bool $is_field_exist;
	private string $display_format;
	private bool $is_multiple;
	private bool $is_repeater;
	private bool $is_group;
	private ?Field_Meta_Interface $self_repeatable_meta;
	/**
	 * @var string|string[]
	 */
	private $default_value;
	/**
	 * @var array<string, mixed>
	 */
	private array $custom_args;
	private int $zoom;
	private string $center_lat;
	private string $center_lng;
	private bool $is_ui_only;

	public function __construct( string $vendor_name, string $field_id ) {
		$this->vendor_name          = $vendor_name;
		$this->field_id             = $field_id;
		$this->name                 = '';
		$this->type                 = '';
		$this->return_format        = '';
		$this->choices              = array();
		$this->is_field_exist       = false;
		$this->display_format       = '';
		$this->is_multiple          = false;
		$this->is_repeater          = false;
		$this->is_group             = false;
		$this->self_repeatable_meta = null;
		$this->default_value        = '';
		$this->custom_args          = array();
		$this->zoom                 = 0;
		$this->center_lat           = '';
		$this->center_lng           = '';
		$this->is_ui_only           = false;
	}

	public function is_field_exist(): bool {
		return $this->is_field_exist;
	}

	public function get_field_id(): string {
		return $this->field_id;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_type(): string {
		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function get_custom_arg( string $arg_name ) {
		return $this->custom_args[ $arg_name ] ?? null;
	}

	/**
	 * @param mixed $arg_value
	 */
	public function set_custom_arg( string $arg_name, $arg_value ): void {
		$this->custom_args[ $arg_name ] = $arg_value;
	}

	public function unset_custom_arg( string $arg_name ): void {
		if ( ! key_exists( $arg_name, $this->custom_args ) ) {
			return;
		}

		unset( $this->custom_args[ $arg_name ] );
	}

	public function get_return_format(): string {
		return $this->return_format;
	}

	public function get_display_format(): string {
		return $this->display_format;
	}

	/**
	 * @return array<string,string>
	 */
	public function get_choices(): array {
		return $this->choices;
	}

	public function is_multiple(): bool {
		return $this->is_multiple;
	}

	public function is_repeater(): bool {
		return $this->is_repeater;
	}

	public function is_group(): bool {
		return $this->is_group;
	}

	public function get_self_repeatable_meta(): ?Field_Meta_Interface {
		return $this->self_repeatable_meta;
	}

	/**
	 * @return string|string[]
	 */
	public function get_default_value() {
		return $this->default_value;
	}

	public function get_zoom(): int {
		return $this->zoom;
	}

	public function get_center_lat(): string {
		return $this->center_lat;
	}

	public function get_center_lng(): string {
		return $this->center_lng;
	}

	public function get_vendor_name(): string {
		return $this->vendor_name;
	}

	public function is_ui_only(): bool {
		return $this->is_ui_only;
	}

	// setters.

	public function set_is_field_exist( bool $is_field_exist ): void {
		$this->is_field_exist = $is_field_exist;
	}

	public function set_name( string $name ): void {
		$this->name = $name;
	}

	public function set_type( string $type ): void {
		$this->type = $type;
	}

	public function set_return_format( string $return_format ): void {
		$this->return_format = $return_format;
	}

	public function set_display_format( string $display_format ): void {
		$this->display_format = $display_format;
	}

	/**
	 * @param array<string|int,mixed> $choices
	 */
	public function set_choices( array $choices ): void {
		foreach ( $choices as $key => $value ) {
			if ( false === is_string( $value ) &&
				false === is_numeric( $value ) ) {
				continue;
			}

			$this->choices[ (string) $key ] = (string) $value;
		}
	}

	public function set_is_multiple( bool $is_multiple ): void {
		$this->is_multiple = $is_multiple;
	}

	public function set_is_repeater( bool $is_repeater ): void {
		$this->is_repeater = $is_repeater;
	}

	public function set_is_group( bool $is_group ): void {
		$this->is_group = $is_group;
	}

	public function set_self_repeatable_meta( ?Field_Meta_Interface $self_repeatable_meta ): void {
		$this->self_repeatable_meta = $self_repeatable_meta;
	}

	/**
	 * @param string|string[] $default_value
	 */
	public function set_default_value( $default_value ): void {
		$this->default_value = $default_value;
	}

	public function set_zoom( int $zoom ): void {
		$this->zoom = $zoom;
	}

	public function set_center_lat( string $center_lat ): void {
		$this->center_lat = $center_lat;
	}

	public function set_center_lng( string $center_lng ): void {
		$this->center_lng = $center_lng;
	}

	public function set_is_ui_only( bool $is_ui_only ): void {
		$this->is_ui_only = $is_ui_only;
	}
}
