<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

abstract class Hookable {
	/**
	 * @var array<string,array{time_sec:float,calls:int}>
	 */
	private static array $classes_total_usage = array();
	/**
	 * @var array<string,array<string,array{time_sec:float,calls:int}>>
	 */
	private static array $class_hooks_usage = array();
	/**
	 * @var array{time_sec:float,calls:int}
	 */
	private static array $total_usage        = array(
		'calls'    => 0,
		'time_sec' => 0,
	);
	private static ?bool $is_profiler_active = null;

	public static function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		// @phpstan-ignore-next-line
		add_action(
			$hook_name,
			function ( ...$args ) use ( $hook_name, $callback ) {
				return self::execute_callback( $hook_name, $callback, $args );
			},
			$priority,
			$accepted_args
		);
	}

	public static function add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		add_filter(
			$hook_name,
			function ( ...$args ) use ( $hook_name, $callback ) {
				return self::execute_callback( $hook_name, $callback, $args );
			},
			$priority,
			$accepted_args
		);
	}

	/**
	 * @return array<string,array{time_sec:float,calls:int, hooks:string[]}>
	 */
	public static function get_classes_total_usage(): array {
		$classes_total_usage = array();

		foreach ( self::$classes_total_usage as $class => $class_total_usage ) {
			$hooks = self::$class_hooks_usage[ $class ] ?? array();

			$classes_total_usage[ $class ] = array_merge(
				$class_total_usage,
				array(
					'hooks' => array_keys( $hooks ),
				)
			);
		}

		uasort(
			$classes_total_usage,
			function ( array $first, array $second ) {
				return $second['time_sec'] <=> $first['time_sec'];
			}
		);

		return $classes_total_usage;
	}

	/**
	 * @return array<string,array<string,array{time_sec:float,calls:int}>>
	 */
	public static function get_class_hooks_usage(): array {
		return self::$class_hooks_usage;
	}

	/**
	 * @return array{time_sec:float,calls:int}
	 */
	public static function get_total_usage(): array {
		return self::$total_usage;
	}

	public static function is_profiler_active(): bool {
		if ( null === self::$is_profiler_active ) {
			// @phpcs:ignore
			self::$is_profiler_active = defined( 'AVF_PROFILER' ) && isset( $_GET['_avf_profiler'] );
		}

		return self::$is_profiler_active;
	}

	/**
	 * @param array<string|int,mixed> $args
	 *
	 * @return mixed
	 */
	private static function execute_callback( string $hook_name, callable $callback, array $args ) {

		if ( self::is_profiler_active() ) {
			$start_at = microtime( true );

			$result = call_user_func_array( $callback, $args );

			$execution_time_sec = microtime( true ) - $start_at;

			self::track_call( $hook_name, $execution_time_sec );

			return $result;
		}

		return call_user_func_array( $callback, $args );
	}

	private static function track_call( string $hook_name, float $execution_time_sec ): void {
		$class = str_replace( 'Org\Wplake\Advanced_Views\\', '', static::class );

		self::$classes_total_usage[ $class ]             = self::$classes_total_usage[ $class ] ?? array(
			'time_sec' => 0,
			'calls'    => 0,
			'hooks'    => array(),
		);
		self::$class_hooks_usage[ $class ]               = self::$class_hooks_usage[ $class ] ?? array();
		self::$class_hooks_usage[ $class ][ $hook_name ] = self::$class_hooks_usage[ $class ][ $hook_name ] ?? array(
			'time_sec' => 0,
			'calls'    => 0,
		);

		self::$total_usage['calls']    = self::$total_usage['calls'] + 1;
		self::$total_usage['time_sec'] = self::$total_usage['time_sec'] + $execution_time_sec;

		self::$classes_total_usage[ $class ]['calls']    = self::$classes_total_usage[ $class ]['calls'] + 1;
		self::$classes_total_usage[ $class ]['time_sec'] = self::$classes_total_usage[ $class ]['time_sec'] + $execution_time_sec;

		self::$class_hooks_usage[ $class ][ $hook_name ]['calls']    = self::$class_hooks_usage[ $class ][ $hook_name ]['calls'] + 1;
		self::$class_hooks_usage[ $class ][ $hook_name ]['time_sec'] = self::$class_hooks_usage[ $class ][ $hook_name ]['time_sec'] + $execution_time_sec;
	}
}
