<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

defined( 'ABSPATH' ) || exit;

trait Safe_Array_Arguments {
	/**
	 * @param array<int|string,mixed> $data
	 *
	 * @return array<int|string,mixed>
	 */
	protected function get_array_arg( string $arg_name, array $data ): array {
		return true === key_exists( $arg_name, $data ) &&
				true === is_array( $data[ $arg_name ] ) ?
			$data[ $arg_name ] :
			array();
	}

	/**
	 * @param array<int|string,mixed> $data
	 */
	protected function get_int_arg( string $arg_name, array $data ): int {
		return true === key_exists( $arg_name, $data ) &&
				true === is_numeric( $data[ $arg_name ] ) ?
			(int) $data[ $arg_name ] :
			0;
	}

	/**
	 * @param array<int|string,mixed> $data
	 */
	protected function get_bool_arg( string $arg_name, array $data ): bool {
		return true === key_exists( $arg_name, $data ) &&
				// [1] and '1' are allowed values for [true] if we talk about boolean,
				// e.g. ACF uses [1] it for the 'multiple' attribute of the select field.
				true === in_array( $data[ $arg_name ], array( true, 1, '1' ), true );
	}

	/**
	 * @param array<int|string,mixed> $data
	 */
	protected function get_int_arg_if_present( string $arg_name, array $data ): ?int {
		return true === key_exists( $arg_name, $data ) &&
				true === is_numeric( $data[ $arg_name ] ) ?
			(int) $data[ $arg_name ] :
			null;
	}

	/**
	 * @param array<int|string,mixed> $data
	 */
	protected function get_string_arg( string $arg_name, array $data ): string {
		return true === key_exists( $arg_name, $data ) &&
				( true === is_string( $data[ $arg_name ] ) || true === is_numeric( $data[ $arg_name ] ) ) ?
			(string) $data[ $arg_name ] :
			'';
	}

	/**
	 * @param array<int|string,mixed> $data
	 */
	protected function get_string_arg_if_present( string $arg_name, array $data ): ?string {
		return true === key_exists( $arg_name, $data ) &&
				( true === is_string( $data[ $arg_name ] ) || true === is_numeric( $data[ $arg_name ] ) ) ?
			(string) $data[ $arg_name ] :
			null;
	}

	/**
	 * @param array<int|string,mixed> $data
	 *
	 * @return array<int|string,mixed>
	 */
	protected function get_array_arg_if_present( string $arg_name, array $data ): ?array {
		return true === key_exists( $arg_name, $data ) &&
				true === is_array( $data[ $arg_name ] ) ?
			$data[ $arg_name ] :
			null;
	}

	/**
	 * @param array<int|string,mixed> $full_array
	 *
	 * @return array<int|string,mixed>
	 */
	protected function apply_array_pagination( array $full_array, int $per_page, int $page_number ): array {
		$count_of_items = count( $full_array );

		$from_index = ( $page_number - 1 ) * $per_page;

		if ( $from_index >= $count_of_items ) {
			return array();
		}

		$length   = $per_page;
		$to_index = $from_index + $length;

		$to_index = $to_index > $count_of_items ?
			$count_of_items :
			$to_index;

		return array_slice(
			$full_array,
			$from_index,
			$to_index - $from_index
		);
	}
}
