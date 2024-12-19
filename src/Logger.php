<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

class Logger implements Hooks_Interface {
	const MAX_MESSAGES = 500;

	private string $log_file;
	private string $error_file;
	private Settings $settings;
	private ?WP_Filesystem_Base $wp_filesystem;

	public function __construct( string $folder, Settings $settings ) {
		$this->log_file      = $folder . '/log.txt';
		$this->error_file    = $folder . '/error_log.txt';
		$this->settings      = $settings;
		$this->wp_filesystem = null;
	}

	protected function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem ) {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';

			WP_Filesystem();

			$this->wp_filesystem = $wp_filesystem;
		}

		return $this->wp_filesystem;
	}

	// separate method for tests.
	protected function put_contents( string $file, string $content ): void {
		$this->get_wp_filesystem()->put_contents(
			$file,
			$content,
		);
	}

	protected function get_contents( string $file ): string {
		$wp_filesystem = $this->get_wp_filesystem();

		if ( false === $wp_filesystem->is_file( $file ) ) {
			return '';
		}

		return (string) $wp_filesystem->get_contents( $file );
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	protected function get_back_trace( int $limit, int $splice_length ): array {
		// add splice length to limit.
		$limit += $splice_length;

		$back_trace_lines = array();
		// @phpcs:ignore WordPress.PHP.DevelopmentFunctions
		$back_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $limit );

		// reduce last not informed call's : delete end (self) functions.

		if ( 0 !== $splice_length &&
			count( $back_trace ) > $splice_length ) {
			array_splice( $back_trace, 0, $splice_length );
		}

		foreach ( $back_trace as $debug_info ) {
			$back_trace_line = array();

			if ( true === key_exists( 'class', $debug_info ) ) {
				$class = explode( '\\', $debug_info['class'] );
				$class = $class[ count( $class ) - 1 ];

				$back_trace_line[] = $class;
			}

			if ( key_exists( 'type', $debug_info ) ) {
				$back_trace_line[] = $debug_info['type'];
			}

			if ( '' !== $debug_info['function'] ) {
				$back_trace_line[] = $debug_info['function'];
			}

			if ( array() === $back_trace_line ) {
				if ( key_exists( 'file', $debug_info ) ) {
					$back_trace_line[] = $debug_info['file'];
				}

				if ( key_exists( 'line', $debug_info ) ) {
					$back_trace_line[] = (string) $debug_info['line'];
				}
			}

			$back_trace_lines[] = $back_trace_line;
		}

		return $back_trace_lines;
	}

	/**
	 * @param array<int|string,mixed> $debug_args
	 */
	protected function log( string $level, string $message, array $debug_args ): void {
		if ( 'debug' === $level &&
			( false === $this->settings->is_dev_mode() ||
				false === in_array( 'administrator', wp_get_current_user()->roles, true ) ) ) {
			return;
		}

		$message_parts = array();
		$messages      = array();

		// 1. read current log and reset if it's too big.

		$current_log = $this->get_contents( $this->log_file );

		if ( '' !== $current_log ) {
			$messages = explode( "\n\n", $current_log );

			if ( count( $messages ) >= static::MAX_MESSAGES ) {
				// remove messages over the limit (with one more space for the current message).
				$messages = array_slice( $messages, 0, static::MAX_MESSAGES - 1 );
			}
		}

		// 2. get back trace info (without this class's calls)

		$back_trace_limit = true === defined( 'ACF_VIEWS_LOGGER_BACK_TRACE_LIMIT' ) &&
							true === is_numeric( constant( 'ACF_VIEWS_LOGGER_BACK_TRACE_LIMIT' ) ) ?
			(int) constant( 'ACF_VIEWS_LOGGER_BACK_TRACE_LIMIT' ) :
			4;
		$back_trace_info  = $this->get_back_trace( $back_trace_limit, 3 );

		// 3. make first log line (LEVEL : Message [Class_Name->method])

		// if detected : class name OR filename.
		$from_source = '';

		if ( count( $back_trace_info ) > 0 ) {
			$from_source = implode( '', $back_trace_info[0] );
			// remove first item from $back_trace_info, so it's not added to the back trace.
			array_shift( $back_trace_info );
		}

		$message_parts[] = strtoupper( $level ) . ' : ' . $message . ' (' . $from_source . ')';

		// 4. make second log line (time + Back Trace)

		$back_trace_line = array();
		$count_of_lines  = count( $back_trace_info );

		// convert back_trace_info sub-arrays to strings.
		for ( $i = 0; $i < $count_of_lines; $i++ ) {
			$back_trace_line[ $i ] = implode( '', $back_trace_info[ $i ] );
		}

		// @phpcs:ignore WordPress.DateTime.RestrictedFunctions
		$message_parts[] = date( 'Y-m-d H:i:s' ) . ' (' . implode( ' ; ', $back_trace_line ) . ')';

		// 5. make third log line (optional, args)

		if ( array() !== $debug_args ) {
			// @phpcs:ignore WordPress.PHP.DevelopmentFunctions
			$debug_args_string = print_r( $debug_args, true );
			$message_parts[]   = rtrim( $debug_args_string, "\n" );
		}

		// 6. write to the log file

		// latest first: so add to the beginning.
		array_unshift( $messages, implode( "\n", $message_parts ) );

		$this->put_contents( $this->log_file, implode( "\n\n", $messages ) );
	}

	protected function error_level_to_string( int $level ): string {
		$levels = array(
			E_ERROR             => 'E_ERROR',
			E_WARNING           => 'E_WARNING',
			E_PARSE             => 'E_PARSE',
			E_NOTICE            => 'E_NOTICE',
			E_CORE_ERROR        => 'E_CORE_ERROR',
			E_CORE_WARNING      => 'E_CORE_WARNING',
			E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
			E_USER_ERROR        => 'E_USER_ERROR',
			E_USER_WARNING      => 'E_USER_WARNING',
			E_USER_NOTICE       => 'E_USER_NOTICE',
			E_STRICT            => 'E_STRICT',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED        => 'E_DEPRECATED',
			E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
		);

		if ( true === isset( $levels[ $level ] ) ) {
			return $levels[ $level ];
		} else {
			return 'E_UNKNOWN';
		}
	}

	/**
	 * @param array<int|string,mixed> $debug_args
	 */
	public function debug( string $message, array $debug_args = array() ): void {
		$this->log( 'debug', $message, $debug_args );
	}

	/**
	 * @param array<int|string,mixed> $debug_args
	 */
	public function warning( string $message, array $debug_args = array() ): void {
		$this->log( 'warning', $message, $debug_args );
	}

	/**
	 * @param array<int|string,mixed> $debug_args
	 */
	public function info( string $message, array $debug_args = array() ): void {
		$this->log( 'info', $message, $debug_args );
	}

	public function get_logs(): string {
		return $this->get_contents( $this->log_file );
	}

	public function get_error_logs(): string {
		return $this->get_contents( $this->error_file );
	}

	public function clear_error_logs(): void {
		$wp_filesystem = $this->get_wp_filesystem();

		if ( false === $wp_filesystem->is_file( $this->error_file ) ) {
			return;
		}

		$wp_filesystem->delete( $this->error_file );
	}

	// must return 'false' to continue with the default error handler.
	public function maybe_log_php_error(
		int $error_level,
		string $error_message,
		string $error_file,
		int $error_line
	): bool {
		// without /wp-content/plugins/ to work with the custom 'WP_PLUGIN_DIR' constant value.
		$is_acf_views_lite = false !== strpos( $error_file, '/acf-views/' );
		$is_acf_views_pro  = false !== strpos( $error_file, '/acf-views-pro/' );

		if ( false === $is_acf_views_lite &&
			false === $is_acf_views_pro ) {
			return false;
		}

		$plugin_folder = true === $is_acf_views_lite ?
			'/acf-views/' :
			'/acf-views-pro/';
		// remove everything before the plugin folder to avoid potential disclosure during logs sharing.
		$error_file_relative = substr( $error_file, (int) strpos( $error_file, $plugin_folder ) );

		$current_log = $this->get_contents( $this->error_file );
		$messages    = array();

		if ( '' !== $current_log ) {
			$messages = explode( "\n\n", $current_log );

			// remove messages over the limit (with one more space for the current message).
			if ( count( $messages ) >= static::MAX_MESSAGES ) {
				$messages = array_slice( $messages, 0, static::MAX_MESSAGES - 1 );
			}
		}

		$message = sprintf(
			"%s : %s\n%s (%s:%d)",
			$this->error_level_to_string( $error_level ),
			$error_message,
			// @phpcs:ignore WordPress.DateTime.RestrictedFunctions
			date( 'Y-m-d H:i:s' ),
			$error_file_relative,
			$error_line
		);

		array_unshift( $messages, $message );

		$this->put_contents( $this->error_file, implode( "\n\n", $messages ) );

		return false;
	}

	public function maybe_log_fatal_php_error(): void {
		$last_error_info = error_get_last();

		if ( null === $last_error_info ) {
			return;
		}

		$fatal_php_errors = array(
			E_ERROR,
			E_PARSE,
			E_CORE_ERROR,
			E_CORE_WARNING,
			E_COMPILE_ERROR,
			E_COMPILE_WARNING,
		);

		$error_level = $last_error_info['type'];

		// only fatal errors not handled by the 'set_error_handler'.
		if ( false === in_array( $error_level, $fatal_php_errors, true ) ) {
			return;
		}

		$this->maybe_log_php_error(
			$error_level,
			$last_error_info['message'],
			$last_error_info['file'],
			$last_error_info['line']
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		// @phpcs:ignore WordPress.PHP.DevelopmentFunctions
		set_error_handler( array( $this, 'maybe_log_php_error' ) );

		// separately, as set_error_handler doesn't handle critical errors.
		register_shutdown_function( array( $this, 'maybe_log_fatal_php_error' ) );
	}
}
