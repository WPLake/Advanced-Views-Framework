<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template_Engines;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

class Template_Engines extends Action implements Hooks_Interface {
	const TWIG  = 'twig';
	const BLADE = 'blade';

	private string $uploads_folder;
	/**
	 * @var array<string, Template_Engine_Interface|null>
	 */
	private array $template_engines;
	private ?WP_Filesystem_Base $wp_filesystem;
	private Plugin $plugin;
	private Settings $settings;
	/**
	 * @var array<string, Template_Generator>
	 */
	private array $template_generators;

	public function __construct( string $uploads_folder, Logger $logger, Plugin $plugin, Settings $settings ) {
		parent::__construct( $logger );

		$this->uploads_folder      = $uploads_folder;
		$this->plugin              = $plugin;
		$this->settings            = $settings;
		$this->template_engines    = array();
		$this->wp_filesystem       = null;
		$this->template_generators = array();
	}

	protected function is_templates_dir_writable(): bool {
		$templates_dir = $this->uploads_folder;
		$wp_filesystem = $this->get_wp_filesystem();

		if ( false === $wp_filesystem->is_dir( $templates_dir ) ) {
			return false;
		}

		$test_file = $templates_dir . '/test.txt';

		// the best way to check is to make test write
		// (check of permissions or 'is_writable' is not enough, as it can be set to 777, but the folder can be owned by another user).

		$is_written = false !== $wp_filesystem->put_contents( $test_file, 'test' );

		if ( false === $is_written ) {
			return false;
		}

		$content = $wp_filesystem->get_contents( $test_file );

		$is_writable = 'test' === $content;

		$is_removed = $wp_filesystem->delete( $test_file );

		return true === $is_writable &&
				true === $is_removed;
	}

	protected function get_uploads_folder(): string {
		return $this->uploads_folder;
	}

	protected function get_settings(): Settings {
		return $this->settings;
	}

	protected function make_template_engine( string $name ): ?Template_Engine_Interface {
		$instance = null;

		switch ( $name ) {
			case self::TWIG:
				$instance = new Twig(
					$this->uploads_folder,
					$this->get_logger(),
					$this->settings,
					$this->get_wp_filesystem()
				);
				break;
			case self::BLADE:
				$instance = new Blade(
					$this->uploads_folder,
					$this->get_logger(),
					$this->settings,
					$this->get_wp_filesystem()
				);

				$instance = false === $instance->is_available() ?
					null :
					$instance;

				break;
		}

		return $instance;
	}

	// public for tests only.
	public function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem ) {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';

			WP_Filesystem();

			$this->wp_filesystem = $wp_filesystem;
		}

		return $this->wp_filesystem;
	}

	public function show_templates_dir_is_not_writable_warning(): void {
		$screen = get_current_screen();

		// show only on the list pages of Views & Cards.
		if ( null === $screen ||
			! in_array( $screen->post_type, array( Views_Cpt::NAME, Cards_Cpt::NAME ), true ) ||
			'edit' !== $screen->base ) {
			return;
		}

		if ( true === $this->is_templates_dir_writable() ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html( __( 'The templates directory is not writable.', 'acf-views' ) );
		echo ' (path = ' . esc_html( $this->uploads_folder ) . ')<br>';
		echo esc_html( __( 'Most likely, the WordPress uploads directory is not writable.', 'acf-views' ) ) . '<br>';
		echo esc_html(
			__(
				'Check and fix file permissions, then deactivate and activate back the Advanced Views plugin. If the issue persists, contact support.',
				'acf-views'
			)
		);
		echo '</p></div>';
	}

	public function create_templates_dir(): void {
		$templates_dir = $this->uploads_folder;

		$wp_filesystem = $this->get_wp_filesystem();

		// skip if already exists.
		if ( true === $wp_filesystem->is_dir( $templates_dir ) ) {
			return;
		}

		$is_created_dir = $wp_filesystem->mkdir( $templates_dir, 0755 );

		if ( false === $is_created_dir ) {
			$this->get_logger()->warning(
				"can't create the templates directory",
				array(
					'path' => $templates_dir,
				)
			);

			return;
		}

		$wp_filesystem->put_contents(
			$templates_dir . '/readme.txt',
			'This directory is used by the Advanced Views plugin to store logs and temporarily store Twig/Blade templates during execution.'
		);
		$wp_filesystem->put_contents( $templates_dir . '/index.php', '<?php // Silence is golden.' );
		$wp_filesystem->put_contents( $templates_dir . '/.htaccess', "Order Deny,Allow\nDeny from all\n" );
		// some may store the uploads in GIT, so add .gitignore as this folder is for temporary files and installation-related.
		$wp_filesystem->put_contents( $templates_dir . '/.gitignore', '*' );
	}

	public function remove_templates_dir(): void {
		// do not remove if switching versions.
		// Because activation hooks won't be called, so dir will be missing.
		if ( true === $this->plugin->is_switching_versions() ) {
			return;
		}

		$wp_filesystem = $this->get_wp_filesystem();

		$templates_dir = $this->uploads_folder;

		if ( false === $wp_filesystem->is_dir( $templates_dir ) ) {
			return;
		}

		// remove the dir.
		$wp_filesystem->rmdir( $templates_dir, true );
	}

	public function get_template_engine( string $name ): ?Template_Engine_Interface {
		if ( false === key_exists( $name, $this->template_engines ) ) {
			$this->template_engines[ $name ] = $this->make_template_engine( $name );
		}

		return $this->template_engines[ $name ];
	}

	public function get_template_generator( string $template_engine ): Template_Generator {
		if ( false === key_exists( $template_engine, $this->template_generators ) ) {
			$this->template_generators[ $template_engine ] = new Template_Generator( $template_engine );
		}

		return $this->template_generators[ $template_engine ];
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'show_templates_dir_is_not_writable_warning' ) );
	}
}
