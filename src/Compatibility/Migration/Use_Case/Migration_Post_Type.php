<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Base\Migration_Base;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Utils\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;

final class Migration_Post_Type extends Migration_Base {
	private Plugin_Cpt $from_cpt;
	private Plugin_Cpt $to_cpt;
	private Cpt_Settings_Storage $cpt_settings_storage;

	public function __construct(
		Logger $logger,
		Cpt_Settings_Storage $cpt_settings_storage,
		Plugin_Cpt $from_cpt,
		Plugin_Cpt $to_cpt
	) {
		parent::__construct( $logger );

		$this->from_cpt             = $from_cpt;
		$this->to_cpt               = $to_cpt;
		$this->cpt_settings_storage = $cpt_settings_storage;
	}

	public function migrate(): void {
		$this->replace_type_in_posts_table();

		$this->cpt_settings_storage->add_on_loaded_callback( array( $this, 'replace_type_in_file_system' ) );
	}

	public function replace_type_in_file_system(): void {
		$file_system = $this->cpt_settings_storage->get_file_system();

		if ( $file_system->is_active() ) {
			$base_folder = $file_system->get_base_folder();

			$this->rename_cpt_folder( $base_folder );
		} else {
			$this->logger->info(
				'File system is not active, skipping folder replacement',
				$this->get_log_args()
			);
		}
	}

	protected function replace_type_in_posts_table(): void {
		global $wpdb;

		// @phpcs:ignore
		$update_response = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
				$this->to_cpt->cpt_name(),
				$this->from_cpt->cpt_name()
			)
		);

		$updated_rows_count = intval( $update_response );

		$this->logger->info(
			'Replaced post type in posts table.',
			$this->get_log_args( array( 'updated_rows_count' => $updated_rows_count ) )
		);
	}

	protected function rename_cpt_folder( string $base_folder ): void {
		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		$from_path = sprintf( '%s/%s', $base_folder, $this->from_cpt->folder_name() );
		$to_path   = sprintf( '%s/%s', $base_folder, $this->to_cpt->folder_name() );

		if ( $wp_filesystem->exists( $from_path ) ) {
			$wp_filesystem->move( $from_path, $to_path );

			$this->logger->info(
				'Renamed folder in file system.',
				$this->get_log_args(
					array(
						'from_path' => $from_path,
						'to_path'   => $to_path,
					)
				)
			);
		} else {
			$this->logger->info(
				'Skipping folder renaming: folder does not exist.',
				$this->get_log_args(
					array(
						'from_path' => $from_path,
					)
				)
			);
		}
	}

	/**
	 * @param array<string,mixed> $details
	 *
	 * @return array<string,mixed>
	 */
	protected function get_log_args( array $details = array() ): array {
		return array_merge(
			array(
				'from_cpt_name' => $this->from_cpt->cpt_name(),
				'to_cpt_name'   => $this->to_cpt->cpt_name(),
			),
			$details
		);
	}
}
