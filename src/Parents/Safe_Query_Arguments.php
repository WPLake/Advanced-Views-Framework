<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

defined( 'ABSPATH' ) || exit;

trait Safe_Query_Arguments {
	/**
	 * @param mixed $value
	 */
	private function sanitize_to_string( $value ): string {
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
	private function get_from_source( string $from ): array {
		switch ( $from ) {
			case 'get':
				// phpcs:ignore WordPress.Security.NonceVerification
				return $_GET;
			case 'post':
				// phpcs:ignore WordPress.Security.NonceVerification
				return $_POST;
			case 'server':
				// phpcs:ignore WordPress.Security.NonceVerification
				return $_SERVER;
			default:
				return array();
		}
	}

	protected function get_query_string_arg_for_non_action( string $arg_name, string $from = 'get' ): string {
		$source = $this->get_from_source( $from );

		if ( false === key_exists( $arg_name, $source ) ) {
			return '';
		}

		return $this->sanitize_to_string( $source[ $arg_name ] );
	}

	protected function get_query_int_arg_for_non_action(
		string $arg_name,
		string $from = 'get'
	): int {
		$value = $this->get_query_string_arg_for_non_action( $arg_name, $from );

		return '' !== $value &&
				true === is_numeric( $value ) ?
			(int) $value :
			0;
	}

	protected function get_query_string_arg_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = 'get'
	): string {
		$source = $this->get_from_source( $from );

		if ( false === key_exists( $arg_name, $source ) ) {
			return '';
		}

		if ( false === check_admin_referer( $nonce_action_name ) ) {
			return '';
		}

		return $this->get_query_string_arg_for_non_action( $arg_name, $from );
	}

	/**
	 * @return array<int,string>
	 */
	protected function get_query_string_array_arg_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = 'get'
	): array {
		$source = $this->get_from_source( $from );

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
			$item = $this->sanitize_to_string( $raw_item );

			if ( '' === $item ) {
				continue;
			}

			$sanitized_array[] = $item;
		}

		return $sanitized_array;
	}

	protected function get_query_int_arg_for_admin_action(
		string $arg_name,
		string $nonce_action_name,
		string $from = 'get'
	): int {
		$value = $this->get_query_string_arg_for_admin_action( $arg_name, $nonce_action_name, $from );

		return '' !== $value &&
				true === is_numeric( $value ) ?
			(int) $value :
			0;
	}
}
