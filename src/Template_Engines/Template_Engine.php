<?php
declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template_Engines;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Settings;
use WP_Filesystem_Base;

abstract class Template_Engine extends Action implements Template_Engine_Interface {
	private string $templates_folder;
	private Settings $settings;
	private WP_Filesystem_Base $wp_filesystem;

	public function __construct( string $templates_folder, Logger $logger, Settings $settings, WP_Filesystem_Base $wp_filesystem ) {
		parent::__construct( $logger );

		$this->settings         = $settings;
		$this->templates_folder = $templates_folder;
		$this->wp_filesystem    = $wp_filesystem;
	}

	/**
	 * @param array<string,mixed> $args
	 * @throws Exception
	 */
	abstract protected function render( string $template_name, array $args ): string;

	abstract protected function get_extension(): string;

	abstract protected function get_cache_file( string $unique_id ): string;

	abstract public function is_available(): bool;

	protected function get_templates_folder(): string {
		return $this->templates_folder;
	}

	protected function print_error_message( string $unique_view_id, string $error_message ): void {
		printf(
			'<p style="color:red;" class="acf-views__error">Advanced Views (%s) template: <span class="acf-views__error-message">%s</span></p>',
			esc_html( $unique_view_id ),
			esc_html( $error_message )
		);
	}

	/**
	 * @param array<string,mixed> $args
	 */
	public function print( string $unique_id, string $template, array $args, bool $is_validation = false ): void {
		if ( false === $this->wp_filesystem->is_dir( $this->templates_folder ) ) {
			$this->get_logger()->warning(
				"can't render the twig template as the templates folder is not writable",
				array(
					'unique_id' => $unique_id,
				)
			);

			$this->print_error_message( $unique_id, 'Templates folder is not writable' );

			return;
		}

		// emulate the template file for every View.
		// as Twig generates a PHP class for every template file
		// so if you use the same, it'll have HTML of the very first View.

		$template_name = sprintf( '%s.%s', $unique_id, $this->get_extension() );
		$template_file = $this->templates_folder . '/' . $template_name;
		$wp_filesystem = $this->wp_filesystem;

		$is_written = false !== $wp_filesystem->put_contents( $template_file, $template );

		// check 'is_file' too, as it seems on some servers 'put_contents' returns true, but the dir/file is missing.
		if ( false === $is_written ||
			false === $wp_filesystem->is_file( $template_file ) ) {
			$this->get_logger()->warning(
				"can't write the template file",
				array(
					'unique_id' => $unique_id,
				)
			);

			$this->print_error_message( $unique_id, "Can't write template file" );

			return;
		}

		try {
			$html = $this->render( $unique_id, $args );

			if ( false !== strpos( $html, 'data-wp-interactive' ) &&
				true === function_exists( 'wp_interactivity_process_directives' ) ) {
				$html = wp_interactivity_process_directives( $html );
			}

			// @phpcs:ignore
			echo $html;
		} catch ( Exception $e ) {
			$is_admin_user = in_array( 'administrator', wp_get_current_user()->roles, true );
			$is_debug_mode = $is_admin_user && $this->settings->is_dev_mode();

			$error_message = $e->getMessage();

			// the right line number is available only for unminified template (for validation during saving).
			if ( true === $is_validation ) {
				$error_message .= ' Line ' . $e->getLine();
			} else {
				// only real render error should be logged
				// (we don't need to log the validation attempts).
				$this->get_logger()->warning(
					"can't render the template, as it contains an error",
					array(
						'unique_id' => $unique_id,
						'error'     => $error_message,
					)
				);
			}

			$this->print_error_message( $unique_id, $error_message );

			// do not include in case of the validation, it doesn't have sense + breaks the error grep regex.
			if ( $is_debug_mode &&
				false === $is_validation ) {
				// @phpcs:ignore WordPress.PHP.DevelopmentFunctions
				echo '<pre>' . esc_html( print_r( $args, true ) ) . '</pre>';
			}
		}

		$wp_filesystem->delete( $template_file );

		$cache_file = $this->get_cache_file( $unique_id );

		// e.g. Blade doesn't allow to disable caching, so we must clean up manually.
		if ( '' !== $cache_file ) {
			$wp_filesystem->delete( $cache_file );
		}
	}
}
