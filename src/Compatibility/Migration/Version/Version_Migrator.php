<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;

class Version_Migrator extends Hookable implements Hooks_Interface {
	private Plugin $plugin;
	private Settings $settings;
	/**
	 * @var Version_Migration[]
	 */
	private array $version_migrations;
	/**
	 * @var Migration[]
	 */
	private array $migrations;

	public function __construct( Plugin $plugin, Settings $settings ) {
		$this->plugin   = $plugin;
		$this->settings = $settings;

		$this->version_migrations = array();
		$this->migrations         = array();
	}

	public static function is_version_lower( string $version, string $target_version ): bool {
		// empty means the very first run, no data is available, nothing to fix.
		if ( '' === $version ) {
			return false;
		}

		$current_version = explode( '.', $version );
		$target_version  = explode( '.', $target_version );

		// versions are broken.
		if ( 3 !== count( $current_version ) ||
			3 !== count( $target_version ) ) {
			return false;
		}

		// convert to int.

		foreach ( $current_version as &$part ) {
			$part = (int) $part;
		}
		foreach ( $target_version as &$part ) {
			$part = (int) $part;
		}

		// compare.

		// major.
		if ( $current_version[0] > $target_version[0] ) {
			return false;
		} elseif ( $current_version[0] < $target_version[0] ) {
			return true;
		}

		// minor.
		if ( $current_version[1] > $target_version[1] ) {
			return false;
		} elseif ( $current_version[1] < $target_version[1] ) {
			return true;
		}

		// patch.
		if ( $current_version[2] >= $target_version[2] ) {
			return false;
		}

		return true;
	}

	/**
	 * @param Version_Migration[] $version_migrations
	 */
	public function set_version_migrations( array $version_migrations ): void {
		$this->version_migrations = $version_migrations;
	}

	/**
	 * @param Migration[] $migrations
	 */
	public function set_migrations( array $migrations ): void {
		$this->migrations = $migrations;
	}

	public function migrate( string $previous_version ): void {
		foreach ( $this->migrations as $migration ) {
			$migration->migrate();
		}

		$version_migrations = $this->get_target_version_migrations( $previous_version );

		foreach ( $version_migrations as $version_migration ) {
			$version_migration->migrate_previous_version();
		}
	}

	public function migrate_cpt_settings( string $previous_version, Cpt_Settings $cpt_settings ): void {
		foreach ( $this->migrations as $migration ) {
			$migration->migrate_cpt_settings( $cpt_settings );
		}

		$version_migrations = $this->get_target_version_migrations( $previous_version );

		foreach ( $version_migrations as $version_migration ) {
			$version_migration->migrate_cpt_settings( $cpt_settings );
		}
	}

	public function perform_upgrade(): void {
		// all versions since 1.6.0 has a version.
		$previous_version = $this->settings->get_version();

		// skip the very first run, no data is available, nothing to fix.
		if ( strlen( $previous_version ) > 0 ) {
			$this->migrate( $previous_version );
		}

		$this->settings->set_version( $this->plugin->get_version() );
		$this->settings->save();
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		// don't use 'upgrader_process_complete' hook, as user can update the plugin manually by FTP.
		$db_version   = $this->settings->get_version();
		$code_version = $this->plugin->get_version();

		// run upgrade if version in the DB is different from the code version.
		if ( $db_version !== $code_version ) {
			// 1. only at this hook can be sure that other plugin's functions are available.
			// 2. with the priority higher than in the Data_Vendors
			self::add_action(
				'plugins_loaded',
				array(
					$this,
					'perform_upgrade',
				),
				Data_Vendors::PLUGINS_LOADED_HOOK_PRIORITY + 1
			);
		}
	}

	/**
	 * @return Migration[]
	 */
	protected function get_target_version_migrations( string $previous_version ): array {
		return array_filter(
			$this->version_migrations,
			fn( Version_Migration $version_migration ) =>
			self::is_version_lower( $previous_version, $version_migration->introduced_version() )
		);
	}
}
