<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Utils;

defined( 'ABSPATH' ) || exit;

final class Query_Arguments {
	const SOURCE_GET     = 'get';
	const SOURCE_POST    = 'post';
	const SOURCE_REQUEST = 'get_or_post';
	const SOURCE_SERVER  = 'server';

	/**
	 * @param string|string[] $key
	 */
	public static function get_string_for_non_action( $key, string $from = self::SOURCE_GET ): string {
		$source = self::get_input_source( $from );

		$argument_names = is_array( $key ) ?
			$key :
			array( $key );

		foreach ( $argument_names as $argument_name ) {
			if ( key_exists( $argument_name, $source ) ) {
				return self::sanitize_as_string( $source[ $argument_name ] );
			}
		}

		return '';
	}

	public static function get_int_for_non_action(
		string $arg_name,
		string $from = self::SOURCE_GET
	): int {
		$value = self::get_string_for_non_action( $arg_name, $from );

		return strlen( $value ) > 0 &&
				is_numeric( $value ) ?
			(int) $value :
			0;
	}

	public static function get_string_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = self::SOURCE_GET
	): string {
		$source = self::get_input_source( $from );

		if ( key_exists( $arg_name, $source ) &&
			self::is_valid_nonce( $nonce_action_name ) ) {
			return self::get_string_for_non_action( $arg_name, $from );
		}

		return '';
	}

	/**
	 * @return array<int,string>
	 */
	public static function get_string_array_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = self::SOURCE_GET
	): array {
		$source = self::get_input_source( $from );

		if ( key_exists( $arg_name, $source ) &&
			self::is_valid_nonce( $nonce_action_name ) ) {
			$raw_value = $source[ $arg_name ];

			if ( is_array( $raw_value ) ) {
				return self::sanitize_array_as_strings( $raw_value );
			}
		}

		return array();
	}

	public static function get_int_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = self::SOURCE_GET
	): int {
		$value = self::get_string_for_admin_action( $arg_name, $nonce_action_name, $from );

		return strlen( $value ) > 0 &&
				is_numeric( $value ) ?
			(int) $value :
			0;
	}

	/**
	 * @param mixed $value
	 */
	protected static function sanitize_as_string( $value ): string {
		if ( is_numeric( $value ) ) {
			$value = (string) $value;
		}

		if ( is_string( $value ) ) {
			$value = wp_unslash( $value );
			$value = sanitize_text_field( $value );

			return trim( $value );
		}

		return '';
	}

	/**
	 * @param mixed[] $input
	 *
	 * @return string[]
	 */
	protected static function sanitize_array_as_strings( array $input ): array {
		$raw_array = wp_unslash( $input );

		$sanitized_array = array();

		foreach ( $raw_array as $raw_item ) {
			$item = self::sanitize_as_string( $raw_item );

			if ( strlen( $item ) > 0 ) {
				$sanitized_array[] = $item;
			}
		}

		return $sanitized_array;
	}

	/**
	 * @return array<int|string,mixed>
	 */
	protected static function get_input_source( string $source ): array {
		switch ( $source ) {
			case self::SOURCE_GET:
				// phpcs:ignore WordPress.Security.NonceVerification
				return $_GET;
			case self::SOURCE_POST:
				// phpcs:ignore WordPress.Security.NonceVerification
				return $_POST;
			case self::SOURCE_SERVER:
				// phpcs:ignore WordPress.Security.NonceVerification
				return $_SERVER;
			case self::SOURCE_REQUEST:
				/**
				 * Manually merge instead of $_REQUEST usage, as it may include $_COOKIES,
				 * which we don't need.
				 *
				 * POST takes priority over GET.
				 */
				// phpcs:ignore WordPress.Security.NonceVerification
				return array_merge( $_GET, $_POST );
			default:
				return array();
		}
	}

	protected static function is_valid_nonce( string $action_name ): bool {
		$referer_status = check_admin_referer( $action_name );

		return is_int( $referer_status );
	}
}
