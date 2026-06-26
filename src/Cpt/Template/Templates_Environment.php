<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Base\Action;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use Org\Wplake\Advanced_Views\Plugin\Utils\WP_Filesystem_Factory;
use WP_Filesystem_Base;

class Templates_Environment extends Action implements Hooks_Interface {
	private string $uploads_folder;

	private ?WP_Filesystem_Base $wp_filesystem_base;
	private Plugin $plugin;

	public function __construct( string $uploads_folder, Logger $logger, Plugin $plugin ) {
		parent::__construct( $logger );

		$this->uploads_folder     = $uploads_folder;
		$this->plugin             = $plugin;
		$this->wp_filesystem_base = null;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( ! $route_detector->is_admin_route() ) {
			return;
		}

		self::add_action( 'admin_notices', array( $this, 'show_templates_dir_is_not_writable_warning' ) );
	}

	// public for tests only.
	public function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem_base ) {
			$this->wp_filesystem_base = WP_Filesystem_Factory::get_wp_filesystem();
		}

		return $this->wp_filesystem_base;
	}

	public function show_templates_dir_is_not_writable_warning(): void {
		$screen = get_current_screen();

		// show only on the list pages of Views & Cards.
		if ( null === $screen ||
			! in_array( $screen->post_type, array( Hard_Layout_Cpt::cpt_name(), Hard_Post_Selection_Cpt::cpt_name() ), true ) ||
			'edit' !== $screen->base ) {
			return;
		}

		if ( $this->is_templates_dir_writable() ) {
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
		if ( $wp_filesystem->is_dir( $templates_dir ) ) {
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
		if ( $this->plugin->is_switching_versions() ) {
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

		return $is_writable &&
				$is_removed;
	}
}
