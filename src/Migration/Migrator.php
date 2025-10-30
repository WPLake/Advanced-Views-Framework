<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Migration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;

class Migrator extends Hookable implements Hooks_Interface {
	private Plugin $plugin;
	private Settings $settings;
	private Logger $logger;
	/**
	 * @var Migration[]
	 */
	private array $migrations;

	public function __construct( Plugin $plugin, Settings $settings, Logger $logger ) {
		$this->plugin   = $plugin;
		$this->settings = $settings;
		$this->logger   = $logger;

		$this->migrations = array();
	}

	protected static function is_version_lower( string $version, string $target_version ): bool {
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
	 * @param Migration[] $migrations
	 */
	public function setup_migrations( array $migrations ): void {
		$this->migrations = $migrations;
	}

	public function run_migrations( string $previous_version ): void {
		$current_screen = new Current_Screen();

		foreach ( $this->migrations as $migration ) {
			if ( self::is_version_lower( $previous_version, $migration->introduced_at_version() ) ) {
				$migration->migrate();

				$migration->set_hooks( $current_screen );
			}
		}
	}

	public function perform_upgrade(): void {
		// all versions since 1.6.0 has a version.
		$previous_version = $this->settings->get_version();

		// skip the very first run, no data is available, nothing to fix.
		if ( strlen( $previous_version ) > 0 ) {
			// clear error logs for the previous version, as they are not relevant anymore.
			$this->logger->clear_error_logs();

			$this->run_migrations( $previous_version );

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
}
