<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Automatic_Reports;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;

final class Plugin_Activator {
	private Template_Engines $template_engines;
	private Automatic_Reports $automatic_reports;
	private Settings $settings;
	private File_System $file_system;
	/**
	 * @var Cpt_Settings_Storage[]
	 */
	private array $storages;

	/**
	 * @param Cpt_Settings_Storage[] $storages
	 */
	public function __construct(
		Template_Engines $template_engines,
		Automatic_Reports $automatic_reports,
		Settings $settings,
		File_System $file_system,
		array $storages
	) {
		$this->template_engines  = $template_engines;
		$this->automatic_reports = $automatic_reports;
		$this->settings          = $settings;
		$this->file_system       = $file_system;
		$this->storages          = $storages;
	}
	public function activate(): void {
		$this->template_engines->create_templates_dir();
		$this->automatic_reports->plugin_activated();
	}

	public function deactivate(): void {
		$this->automatic_reports->plugin_deactivated();
		$this->template_engines->remove_templates_dir();

		// do not check for a security token, as the deactivation plugin link contains it,
		// and WP already has checked it.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_delete_data = true === key_exists( 'advanced-views-delete-data', $_GET ) &&
		                  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							'yes' === $_GET['advanced-views-delete-data'];

		if ( true === $is_delete_data ) {
			foreach ( $this->storages as $storage ) {
				$storage->delete_all_items();
			}

			if ( true === $this->file_system->is_active() ) {
				$this->file_system
												->get_wp_filesystem()
												->rmdir(
													$this->file_system->get_base_folder(),
													true
												);
			}

			$this->settings->delete_data();
		}
	}
}
