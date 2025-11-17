<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration_Base;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Utils\WP_Filesystem_Factory;

final class Migration_Fs_Field extends Migration_Base {
	private File_System $file_system;
	private string $from_name;
	private string $to_name;

	public function __construct( Logger $logger, File_System $file_system, string $from_name, string $to_name ) {
		parent::__construct( $logger );

		$this->file_system = $file_system;
		$this->from_name   = $from_name;
		$this->to_name     = $to_name;
	}

	public function migrate(): void {
		$this->file_system->add_on_loaded_callback( array( $this, 'rename_field_files' ) );
	}

	public function rename_field_files(): void {
		if ( $this->file_system->is_active() ) {
			$base_folder = $this->file_system->get_base_folder();

			$renamed_items = $this->rename_file_recursively( $base_folder );

			$this->logger->info(
				'Renamed files',
				$this->get_log_args(
					array(
						'renamed_items' => $renamed_items,
					)
				)
			);
		} else {
			$this->logger->info(
				'File system is not active, skipping file renaming',
				$this->get_log_args()
			);
		}
	}

	/**
	 * @return string[] renamed items
	 */
	protected function rename_file_recursively( string $folder ): array {
		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		$file_items = $wp_filesystem->dirlist( $folder );

		$renamed_items = array();

		if ( is_array( $file_items ) ) {
			foreach ( $file_items as $file_item ) {
				$item_name = $file_item['name'];
				$item_path = sprintf( '%s/%s', $folder, $item_name );

				if ( 'd' === $file_item['type'] ) {
					$renamed_items = array_merge( $renamed_items, self::rename_file_recursively( $item_path ) );
				} elseif ( $item_name === $this->from_name ) {
					$to_path = sprintf( '%s/%s', $folder, $this->to_name );

					$wp_filesystem->move( $item_path, $to_path );

					$renamed_items[] = $item_name;
				}
			}
		}

		return $renamed_items;
	}

	/**
	 * @param array<string,mixed> $details
	 *
	 * @return array<string,mixed>
	 */
	protected function get_log_args( array $details = array() ): array {
		return array_merge(
			array(
				'from_name' => $this->from_name,
				'to_name'   => $this->to_name,
			),
			$details
		);
	}
}
