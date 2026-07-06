<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System_Loader;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;

final class Migration_3_3_0 extends Version_Migration_Base {
	private Layout_Settings_Storage $layouts_settings_storage;
	private Selection_Settings_Storage $post_selections_settings_storage;

	public function __construct(
		Logger $logger,
		Layout_Settings_Storage $layouts_settings_storage,
		Selection_Settings_Storage $post_selections_settings_storage
	) {
		parent::__construct( $logger );

		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->logger                           = $logger;
	}

	public function introduced_version(): string {
		return '3.3.0';
	}

	public function migrate_previous_version(): void {
		File_System_Loader::instance()
			->add_loaded_callback( fn() => $this->move_all_is_without_web_component_to_select() );
	}

	public function migrate_previous_cpt_settings( Cpt_Settings $cpt_settings ): void {
		$this->move_is_without_web_component_to_select( $cpt_settings );
	}

	protected function move_all_is_without_web_component_to_select(): void {
		$unique_ids = array();

		foreach ( $this->layouts_settings_storage->get_all() as $view_data ) {
			$this->move_is_without_web_component_to_select( $view_data, true );

			$this->layouts_settings_storage->save( $view_data );

			$unique_ids[] = $view_data->unique_id;
		}

		foreach ( $this->post_selections_settings_storage->get_all() as $card_data ) {
			$this->move_is_without_web_component_to_select( $card_data, true );

			$this->post_selections_settings_storage->save( $card_data );

			$unique_ids[] = $card_data->unique_id;
		}

		$this->logger->info(
			'upgrade : moved is_without_web_component_setting to select',
			array(
				'unique_ids' => $unique_ids,
			)
		);
	}

	protected function move_is_without_web_component_to_select( Cpt_Settings $cpt_settings, bool $is_batch = false ): void {
		$cpt_settings->web_component = $cpt_settings->is_without_web_component ?
			Cpt_Settings::WEB_COMPONENT_NONE :
			Cpt_Settings::WEB_COMPONENT_CLASSIC;
		// set to the default, so it isn't saved to json anymore.
		$cpt_settings->is_without_web_component = false;

		if ( false === $is_batch ) {
			$this->logger->info(
				'upgrade : moved is_without_web_component_setting to select',
				array(
					'unique_id' => $cpt_settings->unique_id,
				)
			);
		}
	}
}
