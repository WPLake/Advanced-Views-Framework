<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration_Base;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;

final class Migration_Post_Type extends Migration_Base {
	private Plugin_Cpt $from_cpt;
	private Plugin_Cpt $to_cpt;
	private Cpt_Settings_Storage $cpt_settings_storage;

	public function __construct(
		Cpt_Settings_Storage $cpt_settings_storage,
		Plugin_Cpt $from_cpt,
		Plugin_Cpt $to_cpt
	) {
		$this->from_cpt             = $from_cpt;
		$this->to_cpt               = $to_cpt;
		$this->cpt_settings_storage = $cpt_settings_storage;
	}

	public function migrate(): void {
		$this->replace_type_in_posts_table();

		$this->cpt_settings_storage->add_on_loaded_callback(
			function (): void {
				$file_system = $this->cpt_settings_storage->get_file_system();

				if ( $file_system->is_active() ) {
					$base_folder = $file_system->get_base_folder();

					$this->rename_cpt_folder( $base_folder );
				}
			}
		);
	}

	protected function replace_type_in_posts_table(): void {
		global $wpdb;

		// @phpcs:ignore
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
				$this->to_cpt->cpt_name(),
				$this->from_cpt->cpt_name()
			)
		);
	}

	protected function rename_cpt_folder( string $base_folder ): void {
		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		$from_path = sprintf( '%s/%s', $base_folder, $this->from_cpt->folder_name() );
		$to_path   = sprintf( '%s/%s', $base_folder, $this->to_cpt->folder_name() );

		if ( $wp_filesystem->exists( $from_path ) ) {
			$wp_filesystem->move( $from_path, $to_path );
		}
	}
}
