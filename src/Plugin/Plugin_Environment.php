<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Automatic_Reports;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;

final class Plugin_Environment {
	private Template_Engines $template_engines;
	private Automatic_Reports $automatic_reports;
	private Settings $settings;
	private Plugin $plugin;
	/**
	 * @var File_System[]
	 */
	private array $file_systems;
	/**
	 * @var Cpt_Settings_Storage[]
	 */
	private array $storages;

	/**
	 * @param File_System[] $file_systems
	 * @param Cpt_Settings_Storage[] $storages
	 */
	public function __construct(
		Template_Engines $template_engines,
		Automatic_Reports $automatic_reports,
		Settings $settings,
		Plugin $plugin,
		array $file_systems,
		array $storages
	) {
		$this->template_engines  = $template_engines;
		$this->automatic_reports = $automatic_reports;
		$this->settings          = $settings;
		$this->plugin            = $plugin;

		$this->file_systems = $file_systems;
		$this->storages     = $storages;
	}

	public function prepare_environment(): void {
		$this->set_initial_plugin_version();
		$this->template_engines->create_templates_dir();
		$this->automatic_reports->plugin_activated();
	}

	public function clean_environment(): void {
		$this->automatic_reports->plugin_deactivated();
		$this->template_engines->remove_templates_dir();

		// do not check for a security token, as the deactivation plugin link contains it,
		// and WP already has checked it.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_delete_data = true === key_exists( 'advanced-views-delete-data', $_GET ) &&
		                  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							'yes' === $_GET['advanced-views-delete-data'];

		if ( true === $is_delete_data ) {
			$this->delete_data();
		}
	}

	/**
	 * Sets the plugin version in the database if there is no version there.
	 * Otherwise, we keep the db version as is, for the version migration code.
	 */
	protected function set_initial_plugin_version(): void {
		$db_plugin_version   = $this->settings->get_version();
		$is_db_version_unset = '' === $db_plugin_version;

		if ( $is_db_version_unset ) {
			$code_plugin_version = $this->plugin->get_version();

			$this->settings->set_version( $code_plugin_version );
			$this->settings->save();
		}
	}

	protected function delete_data(): void {
		foreach ( $this->storages as $storage ) {
			$storage->delete_all_items();
		}

		foreach ( $this->file_systems as $file_system ) {
			if ( $file_system->is_active() ) {
				$base_folder = $file_system->get_base_folder();

				$file_system->get_wp_filesystem()
							->rmdir( $base_folder, true );
			}
		}

		$this->settings->delete_data();
	}
}
