<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;

final class Migration_3_3_0 extends Version_Migration_Base {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Logger $logger;
	private Plugin $plugin;

	public function __construct(
		Layouts_Settings_Storage $layouts_settings_storage,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Logger $logger,
		Plugin $plugin
	) {
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->logger                           = $logger;
		$this->plugin                           = $plugin;
	}

	public function introduced_version(): string {
		return '3.3.0';
	}

	public function migrate_previous_version(): void {
		// NOTE: when you add new upgrade, you should use 'after_setup_theme' hook if the acf plugin isn't available
		// (as ACF isn't a direct dependency now).
		// You shouldn't use 'after_setup_theme' if acf is available, as this hooks is fired before 'acf/init', so it'll
		// miss upgrades for the previous versions.

		$action = true === $this->plugin->is_acf_plugin_available() &&
					false === defined( 'ACF_VIEWS_INNER_ACF' ) ?
			'acf/init' :
			'after_setup_theme';

		self::add_action(
			$action,
			function (): void {
					$this->move_all_is_without_web_component_to_select();
			}
		);
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
		$cpt_settings->web_component = true === $cpt_settings->is_without_web_component ?
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
