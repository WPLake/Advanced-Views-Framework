<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views;

defined( 'ABSPATH' ) || exit;

interface Field_Meta_Interface {
	// getters.
	public function is_field_exist(): bool;

	public function get_field_id(): string;

	public function get_name(): string;

	public function get_type(): string;

	public function get_return_format(): string;

	public function get_display_format(): string;

	/**
	 * @return array<string, string>
	 */
	public function get_choices(): array;

	public function is_multiple(): bool;

	public function is_repeater(): bool;

	public function is_group(): bool;

	public function get_self_repeatable_meta(): ?Field_Meta_Interface;

	/**
	 * @return string|string[]
	 */
	public function get_default_value();

	public function get_zoom(): int;

	public function get_center_lat(): string;

	public function get_center_lng(): string;

	public function get_vendor_name(): string;

	public function is_ui_only(): bool;

	/**
	 * @return mixed
	 */
	public function get_custom_arg( string $arg_name );

	// setters.

	public function set_is_field_exist( bool $is_field_exist ): void;

	public function set_name( string $name ): void;

	public function set_type( string $type ): void;

	public function set_return_format( string $return_format ): void;

	public function set_display_format( string $display_format ): void;

	/**
	 * @param array<string|int,mixed> $choices
	 */
	public function set_choices( array $choices ): void;

	public function set_is_multiple( bool $is_multiple ): void;

	public function set_is_repeater( bool $is_repeater ): void;

	public function set_is_group( bool $is_group ): void;

	public function set_self_repeatable_meta( ?Field_Meta_Interface $self_repeatable_meta ): void;

	/**
	 * @param string|string[] $default_value
	 */
	public function set_default_value( $default_value ): void;

	public function set_zoom( int $zoom ): void;

	public function set_center_lat( string $center_lat ): void;

	public function set_center_lng( string $center_lng ): void;

	/**
	 * @param mixed $arg_value
	 */
	public function set_custom_arg( string $arg_name, $arg_value ): void;

	public function unset_custom_arg( string $arg_name ): void;

	public function set_is_ui_only( bool $is_ui_only ): void;
}
