<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;

final class Migration_2_4_5 extends Version_Migration_Base {
	private Layouts_Settings_Storage $layouts_settings_storage;

	public function __construct( Layouts_Settings_Storage $layouts_settings_storage ) {
		$this->layouts_settings_storage = $layouts_settings_storage;
	}

	public function introduced_version(): string {
		return '2.4.5';
	}

	public function migrate_previous_version(): void {
		self::add_action(
			'acf/init',
			function (): void {
				$this->enable_name_back_compatibility_checkbox_for_views_with_gutenberg();
			}
		);
	}

	protected function enable_name_back_compatibility_checkbox_for_views_with_gutenberg(): void {
		$views = $this->layouts_settings_storage->get_db_management()->get_all_posts();

		foreach ( $views as $view ) {
			$view_data = $this->layouts_settings_storage->get( $view->post_name );

			if ( ! $view_data->is_has_gutenberg_block ) {
				continue;
			}

			$view_data->is_gutenberg_block_with_digital_id = true;

			$this->layouts_settings_storage->save( $view_data );
		}
	}
}
