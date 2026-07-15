<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Base;

use Org\Wplake\Advanced_Views\Plugin\Utils\Profiler;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\any;

abstract class Hookable {
	public static function add_action(
		string $hook_name,
		callable $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$action_callback = self::get_callback( $hook_name, $callback );

		add_action( $hook_name, $action_callback, $priority, $accepted_args );
	}

	public static function add_manage_action(
		string $hook_name,
		callable $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$verification_callback = function ( ...$args ) use ( $callback ) {
			if ( Avf_User::can_manage() ) {
				call_user_func_array( $callback, $args );
			}
		};

		self::add_action( $hook_name, $verification_callback, $priority, $accepted_args );
	}

	public static function add_filter(
		string $hook_name,
		callable $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$filter_callback = self::get_callback( $hook_name, $callback );

		add_filter( $hook_name, $filter_callback, $priority, $accepted_args );
	}

	public static function add_manage_filter(
		string $hook_name,
		callable $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$verification_callback = function ( ...$args ) use ( $callback ) {
			if ( Avf_User::can_manage() ) {
				return call_user_func_array( $callback, $args );
			}

			return any( $args, 0 );
		};

		self::add_filter( $hook_name, $verification_callback, $priority, $accepted_args );
	}

	public static function add_shortcode( string $tag, callable $callback ): void {
		$shortcode_callback = self::get_callback( $tag, $callback );

		// @phpstan-ignore-next-line
		add_shortcode( $tag, $shortcode_callback );
	}

	private static function get_callback( string $hook_name, callable $callback ): callable {
		$source = str_replace( 'Org\Wplake\Advanced_Views\\', '', static::class );

		return Profiler::get_callback( $source, $hook_name, $callback );
	}
}
