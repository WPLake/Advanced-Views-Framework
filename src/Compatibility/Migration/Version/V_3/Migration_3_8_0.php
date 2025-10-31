<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;

final class Migration_3_8_0 extends Version_Migration {
	private Layouts_Settings_Storage $layouts_settings_storage;

	public function __construct( Layouts_Settings_Storage $layouts_settings_storage ) {
		$this->layouts_settings_storage = $layouts_settings_storage;
	}

	public function introduced_version(): string {
		return '3.8.0';
	}

	public function migrate_previous_version(): void {
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
}
