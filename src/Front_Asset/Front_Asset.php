<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

defined( 'ABSPATH' ) || exit;

abstract class Front_Asset implements Front_Asset_Interface {
	const NAME = '';

	/**
	 * @var array<string, bool>
	 */
	private array $js_handles;
	/**
	 * @var array<string, bool>
	 */
	private array $css_handles;
	private Plugin $plugin;
	private File_System $file_system;
	private string $auto_discover_name;
	private bool $is_with_web_component;

	public function __construct( Plugin $plugin, File_System $file_system ) {
		$this->plugin                = $plugin;
		$this->file_system           = $file_system;
		$this->js_handles            = array();
		$this->css_handles           = array();
		$this->auto_discover_name    = '';
		$this->is_with_web_component = false;
	}

	protected function print_code_piece( string $name, string $piece_safe ): void {
		echo "\n\n";
		printf( '/* %s : %s (auto-discover-begin) */', esc_html( $this->auto_discover_name ), esc_html( $name ) );
		echo "\n\n";
		// @phpcs:ignore WordPress.Security.EscapeOutput
		echo $piece_safe . "\n\n";
		printf( '/* %s : %s (auto-discover-end) */', esc_html( $this->auto_discover_name ), esc_html( $name ) );
		echo "\n\n";
	}

	protected function get_asset_url( string $file ): string {
		return $this->plugin->get_assets_url( 'front/' . $file );
	}

	protected function get_asset_path( string $file ): string {
		return $this->plugin->get_assets_path( 'front/' . $file );
	}

	protected function print_js_code_piece(
		string $name,
		string $piece_safe,
		string $field_selector,
		bool $is_multiple
	): void {
		ob_start();

		if ( $is_multiple ) {
			printf( "this.querySelectorAll('%s').forEach(item => {\n", esc_html( $field_selector ) );
		} else {
			printf( "var %s = this.querySelector('%s');\n", esc_html( $name ), esc_html( $field_selector ) );
			printf( "if (%s) {\n", esc_html( $name ) );
		}

		// @phpcs:ignore WordPress.Security.EscapeOutput
		echo $piece_safe;
		echo $is_multiple ?
			"\n});" :
			"\n}";

		$js_code_safe = (string) ob_get_clean();

		$this->print_code_piece( $name, $js_code_safe );
	}

	protected function get_wp_handle( string $handle ): string {
		return Views_Cpt::NAME . '_' . $handle;
	}

	protected function get_plugin(): Plugin {
		return $this->plugin;
	}

	protected function is_with_web_component(): bool {
		return $this->is_with_web_component;
	}

	/**
	 * @param array<string, bool> $js_handles
	 */
	protected function set_js_handles( array $js_handles ): void {
		$this->js_handles = $js_handles;
	}

	protected function enable_js_handle( string $js_handle ): void {
		$this->js_handles[ $js_handle ] = true;
	}

	protected function enable_css_handle( string $css_handle ): void {
		$this->css_handles[ $css_handle ] = true;
	}

	protected function is_enabled_js_handle( string $js_handle ): bool {
		return true === key_exists( $js_handle, $this->js_handles ) &&
				true === $this->js_handles[ $js_handle ];
	}

	protected function set_is_with_web_component( bool $is_with_web_component ): void {
		$this->is_with_web_component = $is_with_web_component;
	}

	/**
	 * @param array<string, bool> $css_handles
	 */
	protected function set_css_handles( array $css_handles ): void {
		$this->css_handles = $css_handles;
	}

	protected function set_auto_discover_name( string $auto_discover_name ): void {
		$this->auto_discover_name = $auto_discover_name;
	}

	public function enqueue_active(): string {
		foreach ( $this->js_handles as $js_handle => $is_active ) {
			if ( false === $is_active ) {
				continue;
			}

			wp_enqueue_script(
				$this->get_wp_handle( $js_handle ),
				$this->get_asset_url( 'js/' . $js_handle . '.min.js' ),
				array(),
				$this->plugin->get_version(),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		$css = '';

		$wp_filesystem = $this->file_system->get_wp_filesystem();

		foreach ( $this->css_handles as $css_handle => $is_active ) {
			if ( false === $is_active ) {
				continue;
			}

			$path_to_file = $this->get_asset_path( 'css/' . $css_handle . '.min.css' );

			$css .= (string) $wp_filesystem->get_contents( $path_to_file );
		}

		return $css;
	}

	public function get_auto_discover_name(): string {
		return $this->auto_discover_name;
	}

	public function get_name(): string {
		return static::NAME;
	}
}
