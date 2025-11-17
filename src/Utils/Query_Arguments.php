<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Utils;

defined( 'ABSPATH' ) || exit;

final class Query_Arguments {
	const SOURCE_GET    = 'get';
	const SOURCE_POST   = 'post';
	const SOURCE_SERVER = 'server';

	/**
	 * @param mixed $value
	 */
	private static function sanitize_to_string( $value ): string {
		if ( true === is_numeric( $value ) ) {
			$value = (string) $value;
		}

		if ( false === is_string( $value ) ) {
			return '';
		}

		$value = wp_unslash( $value );
		$value = sanitize_text_field( $value );

		return trim( $value );
	}

	/**
	 * @return array<int|string,mixed>
	 */
	private static function get_from_source( string $source ): array {
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
			default:
				return array();
		}
	}

	public static function get_string_for_non_action( string $arg_name, string $from = 'get' ): string {
		$source = self::get_from_source( $from );

		if ( false === key_exists( $arg_name, $source ) ) {
			return '';
		}

		return self::sanitize_to_string( $source[ $arg_name ] );
	}

	public static function get_int_for_non_action(
		string $arg_name,
		string $from = 'get'
	): int {
		$value = self::get_string_for_non_action( $arg_name, $from );

		return '' !== $value &&
				true === is_numeric( $value ) ?
			(int) $value :
			0;
	}

	public static function get_string_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = 'get'
	): string {
		$source = self::get_from_source( $from );

		if ( false === key_exists( $arg_name, $source ) ) {
			return '';
		}

		if ( false === check_admin_referer( $nonce_action_name ) ) {
			return '';
		}

		return self::get_string_for_non_action( $arg_name, $from );
	}

	/**
	 * @return array<int,string>
	 */
	public static function get_string_array_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = 'get'
	): array {
		$source = self::get_from_source( $from );

		if ( false === key_exists( $arg_name, $source ) ) {
			return array();
		}

		if ( false === check_admin_referer( $nonce_action_name ) ) {
			return array();
		}

		$raw_value = $source[ $arg_name ];

		if ( false === is_array( $raw_value ) ) {
			return array();
		}

		$raw_array = wp_unslash( $raw_value );

		$sanitized_array = array();

		foreach ( $raw_array as $raw_item ) {
			$item = self::sanitize_to_string( $raw_item );

			if ( '' === $item ) {
				continue;
			}

			$sanitized_array[] = $item;
		}

		return $sanitized_array;
	}

	public static function get_int_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = 'get'
	): int {
		$value = self::get_string_for_admin_action( $arg_name, $nonce_action_name, $from );

		return '' !== $value &&
				true === is_numeric( $value ) ?
			(int) $value :
			0;
	}
}
