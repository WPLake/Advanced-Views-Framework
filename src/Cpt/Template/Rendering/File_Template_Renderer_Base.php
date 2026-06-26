<?php
declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Rendering;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use WP_Filesystem_Base;

abstract class File_Template_Renderer_Base extends Template_Renderer_Base {
	private string $templates_folder;
	private WP_Filesystem_Base $wp_filesystem_base;

	public function __construct( string $templates_folder, Logger $logger, Settings_Storage $settings, WP_Filesystem_Base $wp_filesystem_base ) {
		parent::__construct( $logger, $settings );

		$this->templates_folder   = $templates_folder;
		$this->wp_filesystem_base = $wp_filesystem_base;
	}

	/**
	 * @param array<string,mixed> $args
	 * @throws Exception
	 */
	abstract protected function render( string $template_name, array $args ): string;

	abstract protected function get_extension(): string;

	abstract protected function get_cache_file( string $unique_id ): string;

	abstract public function is_available(): bool;



	/**
	 * @param array<string,mixed> $args
	 */
	public function print( string $unique_id, string $template, array $args, bool $is_validation = false ): void {
		if ( ! $this->wp_filesystem_base->is_dir( $this->templates_folder ) ) {
			$this->get_logger()->warning(
				"can't render the twig template as the templates folder is not writable",
				array(
					'unique_id' => $unique_id,
				)
			);

			self::print_error_message( $unique_id, 'Templates folder is not writable' );

			return;
		}

		// emulate the template file for every View.
		// as Twig generates a PHP class for every template file
		// so if you use the same, it'll have HTML of the very first View.

		$template_name = sprintf( '%s.%s', $unique_id, $this->get_extension() );
		$template_file = $this->templates_folder . '/' . $template_name;
		$wp_filesystem = $this->wp_filesystem_base;

		$is_written = $wp_filesystem->put_contents( $template_file, $template );

		// check 'is_file' too, as it seems on some servers 'put_contents' returns true, but the dir/file is missing.
		if ( ! $is_written ||
			! $wp_filesystem->is_file( $template_file ) ) {
			$this->get_logger()->warning(
				"can't write the template file",
				array(
					'unique_id' => $unique_id,
				)
			);

			self::print_error_message( $unique_id, "Can't write template file" );

			return;
		}

		try {
			$html = $this->render( $unique_id, $args );

			if ( false !== strpos( $html, 'data-wp-interactive' ) &&
				function_exists( 'wp_interactivity_process_directives' ) ) {
				$html = wp_interactivity_process_directives( $html );
			}

			// @phpcs:ignore
			echo $html;
		} catch ( Exception $e ) {
			$this->handle_error( $e, $template, $args, $unique_id, $is_validation );
		}

		$wp_filesystem->delete( $template_file );

		$cache_file = $this->get_cache_file( $unique_id );

		// e.g. Blade doesn't allow to disable caching, so we must clean up manually.
		if ( strlen( $cache_file ) > 0 ) {
			$wp_filesystem->delete( $cache_file );
		}
	}

	protected function get_templates_folder(): string {
		return $this->templates_folder;
	}
}
