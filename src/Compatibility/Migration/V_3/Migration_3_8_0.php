<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;

final class Migration_3_8_0 extends Migration {
	private Layouts_Settings_Storage $layouts_settings_storage;

	public function __construct( Layouts_Settings_Storage $layouts_settings_storage ) {
		$this->layouts_settings_storage = $layouts_settings_storage;
	}

	public function introduced_version(): string {
		return '3.8.0';
	}

	public function migrate(): void {
		$this->replace_post_slug_prefix( 'acf_views', 'view_', 'layout-' );
		$this->replace_post_slug_prefix( 'acf_cards', 'card_', 'post-selection-' );

		$this->replace_post_type_name( 'acf_views', 'layout' );
		$this->replace_post_type_name( 'acf_cards', 'post-selection' );

		self::add_action(
			'after_setup_theme',
			function () {
				$file_system = $this->layouts_settings_storage->get_file_system();
				if ( $file_system->is_active() ) {
					$base_folder = $file_system->get_base_folder();

					$this->rename_fs_item( $base_folder, 'views', 'layouts' );
					$this->rename_fs_item( $base_folder, 'cards', 'post-selections' );

					$this->rename_file_recursively( $base_folder, 'view.php', 'controller.php' );
					$this->rename_file_recursively( $base_folder, 'card.php', 'controller.php' );
				}
			},
			// After File_System->set_hooks().
			11
		);
	}

	protected function replace_post_slug_prefix( string $post_type, string $old_prefix, string $new_prefix ): void {
		global $wpdb;

		// @phpcs:ignore
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_name = REPLACE(post_name, %s, %s) WHERE post_type = %s",
				$old_prefix,
				$new_prefix,
				$post_type,
			)
		);
	}

	protected function replace_post_type_name( string $old_name, string $new_name ): void {
		global $wpdb;

		// @phpcs:ignore
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
				$old_name,
				$new_name,
			)
		);
	}

	protected function rename_fs_item( string $base_folder, string $old_name, string $new_name ): void {
		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		$old_path = sprintf( '%s/%s', $base_folder, $old_name );
		$new_path = sprintf( '%s/%s', $base_folder, $new_name );

		if ( $wp_filesystem->exists( $old_path ) ) {
			$wp_filesystem->move( $old_path, $new_path );
		}
	}

	protected function rename_file_recursively( string $base_folder, string $old_name, string $new_name ): void {
		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		$files = $wp_filesystem->dirlist( $base_folder );

		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$file_name = $file['name'];
				$file_path = sprintf( '%s/%s', $base_folder, $file_name );

				if ( 'd' === $file['type'] ) {
					$this->rename_file_recursively( $file_path, $old_name, $new_name );
				} elseif ( $file_name === $old_name ) {
					$this->rename_fs_item( $base_folder, $old_name, $new_name );
				}
			}
		}
	}
}
