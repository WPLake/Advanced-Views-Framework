<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;

final class Migration_Post_Type extends Migration {

	public function migrate(): void {
		// TODO: Implement migrate() method.
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
