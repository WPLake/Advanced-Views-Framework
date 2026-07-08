<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System_Loader;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Table\Post_Selections_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Cpt\Template\Templates_Environment;
use Org\Wplake\Advanced_Views\Plugin\Automated_Reports\State_Report;
use Org\Wplake\Advanced_Views\Plugin\Automated_Reports\Usage_Report;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Settings\Options_Storage;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

final class Plugin_Environment implements Hooks_Interface {
	protected const TRUE_TRANSIENT_VALUE = '1';

	private Templates_Environment $template_engines;
	private State_Report $state_report;
	private Usage_Report $usage_report;
	private Settings_Storage $settings;
	private Plugin $plugin;
	private Post_Selections_Pre_Built_Tab $selections_pre_built_tab;
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
		Templates_Environment $template_engines,
		State_Report $state_report,
		Usage_Report $usage_report,
		Settings_Storage $settings,
		Plugin $plugin,
		Post_Selections_Pre_Built_Tab $selections_pre_built_tab,
		array $file_systems,
		array $storages
	) {
		$this->template_engines         = $template_engines;
		$this->state_report             = $state_report;
		$this->usage_report             = $usage_report;
		$this->settings                 = $settings;
		$this->plugin                   = $plugin;
		$this->selections_pre_built_tab = $selections_pre_built_tab;

		$this->file_systems = $file_systems;
		$this->storages     = $storages;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( $route_detector->is_admin_route() &&
			$route_detector->is_complete_cycle_request() ) {
			$this->process_transient_jobs();
		}
	}

	public function prepare_environment(): void {
		$is_initial_setup = $this->set_initial_plugin_version();
		$this->template_engines->create_templates_dir();

		$this->state_report->plugin_activated();

		if ( $is_initial_setup ) {
			// we cannot listen to any hooks right here,
			// so have to setup transients - https://developer.wordpress.org/reference/functions/register_activation_hook/.
			Options_Storage::set_transient(
				Options_Storage::TRANSIENT_FIRST_INSTALLATION,
				self::TRUE_TRANSIENT_VALUE,
				WEEK_IN_SECONDS
			);
		}
	}

	public function clean_environment(): void {
		$this->state_report->plugin_deactivated();
		// un_schedule in any case, as could be scheduled before disabling the automatic reports.
		$this->usage_report->unschedule();

		$this->template_engines->remove_templates_dir();

		// do not check for a security token, as the deactivation plugin link contains it,
		// and WP already has checked it.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_delete_data = key_exists( 'advanced-views-delete-data', $_GET ) &&
		                  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							'yes' === $_GET['advanced-views-delete-data'];

		if ( $is_delete_data ) {
			$this->delete_data();
		}
	}

	protected function process_transient_jobs(): void {
		$transient_jobs = $this->get_transient_jobs();

		foreach ( $transient_jobs as $transient_name => $job ) {
			if ( self::TRUE_TRANSIENT_VALUE === Options_Storage::get_transient( $transient_name ) ) {
				$job();
			}
		}
	}

	/**
	 * @return array<string, callable():void>
	 */
	protected function get_transient_jobs(): array {
		return array(
			Options_Storage::TRANSIENT_FIRST_INSTALLATION => function () {
				File_System_Loader::instance()
									->add_loaded_callback(
										function () {
											$this->prepare_first_installation();

											Options_Storage::delete_transient( Options_Storage::TRANSIENT_FIRST_INSTALLATION );
										}
									);
			},
		);
	}

	protected function prepare_first_installation(): void {
		// item is in the 'pre_built' folder.
		$this->selections_pre_built_tab->import_cpt_data_with_all_related_items( 'card_6a479135a1a81' );
	}

	/**
	 * Sets the plugin version in the database if there is no version there.
	 * Otherwise, we keep the db version as is, for the version migration code.
	 */
	protected function set_initial_plugin_version(): bool {
		$db_plugin_version   = $this->settings->get_version();
		$is_db_version_unset = '' === $db_plugin_version;

		if ( $is_db_version_unset ) {
			$code_plugin_version = $this->plugin->get_version();

			$this->settings->set_version( $code_plugin_version );
			$this->settings->save();

			return true;
		}

		return false;
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

		Options_Storage::delete_all_options();
		Options_Storage::delete_all_transients();
	}
}
