<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration_Base;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Plugin_Cpt\Plugin_Cpt;

final class Migration_Post_Type extends Migration_Base {
	private File_System $file_system;
	private Plugin_Cpt $from_cpt;
	private Plugin_Cpt $to_cpt;

	public function __construct( File_System $file_system, Plugin_Cpt $from_cpt, Plugin_Cpt $to_cpt ) {
		$this->file_system = $file_system;
		$this->from_cpt    = $from_cpt;
		$this->to_cpt      = $to_cpt;
	}

	public function migrate(): void {
		$this->replace_posts_type();

		$this->replace_slug_prefix( $this->to_cpt->cpt_name() );

		self::add_action(
			'after_setup_theme',
			function () {
				if ( $this->file_system->is_active() ) {
					$base_folder = $this->file_system->get_base_folder();

					$this->rename_folder( $base_folder );
				}
			},
			// After File_System->set_hooks().
			11
		);
	}

	protected function replace_slug_prefix( string $cpt_name ): void {
		global $wpdb;

		// @phpcs:ignore
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_name = REPLACE(post_name, %s, %s) WHERE post_type = %s",
				$this->from_cpt->slug_prefix(),
				$this->to_cpt->slug_prefix(),
				$cpt_name
			)
		);
	}

	protected function replace_posts_type(): void {
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

	protected function rename_folder( string $base_folder ): void {
		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		$from_path = sprintf( '%s/%s', $base_folder, $this->from_cpt->folder_name() );
		$to_path   = sprintf( '%s/%s', $base_folder, $this->to_cpt->folder_name() );

		if ( $wp_filesystem->exists( $from_path ) ) {
			$wp_filesystem->move( $from_path, $to_path );
		}
	}
}
