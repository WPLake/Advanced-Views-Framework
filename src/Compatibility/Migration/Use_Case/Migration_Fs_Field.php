<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration_Base;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Plugin_Cpt\Plugin_Cpt;

final class Migration_Fs_Field extends Migration_Base {
	private File_System $file_system;
	private string $from_name;
	private string $to_name;

	public function __construct( File_System $file_system, string $from_name, string $to_name ) {
		$this->file_system = $file_system;
		$this->from_name   = $from_name;
		$this->to_name     = $to_name;
	}

	public function migrate(): void {
		self::add_action(
			'after_setup_theme',
			function () {
				if ( $this->file_system->is_active() ) {
					$base_folder = $this->file_system->get_base_folder();

					$this->rename_file_recursively( $base_folder );
				}
			},
			// After File_System->set_hooks().
			11
		);
	}

	protected function rename_file_recursively( string $folder ): void {
		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		$file_items = $wp_filesystem->dirlist( $folder );

		if ( is_array( $file_items ) ) {
			foreach ( $file_items as $file_item ) {
				$item_name = $file_item['name'];
				$item_path = sprintf( '%s/%s', $folder, $item_name );

				if ( 'd' === $file_item['type'] ) {
					self::rename_file_recursively( $item_path );
				} elseif ( $item_name === $this->from_name ) {
					$to_path = sprintf( '%s/%s', $folder, $this->to_name );

					$wp_filesystem->move( $item_path, $to_path );
				}
			}
		}
	}
}
