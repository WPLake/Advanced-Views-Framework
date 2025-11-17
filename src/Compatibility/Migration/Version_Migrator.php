<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Base\Migration;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Upgrade_Notice;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration;
use Org\Wplake\Advanced_Views\Utils\Cache_Flusher;
use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

final class Version_Migrator extends Hookable implements Hooks_Interface {
	private Plugin $plugin;
	private Settings $settings;
	/**
	 * @var array<string,Version_Migration> version => migrationInstance
	 */
	private array $version_migrations;
	private Logger $logger;
	private Upgrade_Notice $upgrade_notice;
	private Cache_Flusher $cache_flusher;

	public function __construct(
		Plugin $plugin,
		Settings $settings,
		Logger $logger,
		Upgrade_Notice $upgrade_notice,
		Cache_Flusher $cache_flusher
	) {
		$this->plugin         = $plugin;
		$this->settings       = $settings;
		$this->logger         = $logger;
		$this->upgrade_notice = $upgrade_notice;
		$this->cache_flusher  = $cache_flusher;

		$this->version_migrations = array();
	}

	public static function is_version_lower( string $version, string $target_version ): bool {
		if ( ! self::is_valid_version( $version ) ||
			! self::is_valid_version( $target_version ) ) {
			return false;
		}

		$current_version = explode( '.', $version );
		$target_version  = explode( '.', $target_version );

		// check for IDE.
		if ( 3 !== count( $current_version ) ||
			3 !== count( $target_version ) ) {
			return false;
		}

		// convert to int.

		foreach ( $current_version as &$part ) {
			$part = int( $part );
		}
		foreach ( $target_version as &$part ) {
			$part = int( $part );
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

	public static function is_valid_version( string $version ): bool {
		return 1 === preg_match( '/^\d+\.\d+\.\d+$/', $version );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		// don't use 'upgrader_process_complete' hook, as user can update the plugin manually by FTP.
		$db_version   = $this->settings->get_version();
		$code_version = $this->plugin->get_version();

		/**
		 * Run upgrade if the DB version is set, and different from the code version.
		 * (it's unset until the plugin activation hook called, which happens later than wp_loaded)
		 */
		if ( '' === $db_version ||
			$db_version === $code_version ) {
			return;
		}

		// only at this hook can be sure that other plugin's functions are available.
		self::add_action(
			'plugins_loaded',
			array(
				$this,
				'upgrade_from_previous_version',
			),
			// with the priority higher than in the Data_Vendors.
			Data_Vendors::PLUGINS_LOADED_HOOK_PRIORITY + 1
		);

		// late "wp_loaded" hook ensures all migration hooks are called.
		self::add_action(
			'wp_loaded',
			array( $this, 'complete_version_upgrade' )
		);
	}

	/**
	 * @param Version_Migration[] $version_migrations
	 */
	public function add_version_migrations( array $version_migrations ): void {

		foreach ( $version_migrations as $version_migration ) {
			$version = $version_migration->introduced_version();

			$this->version_migrations[ $version ] = $version_migration;
		}
	}

	public function migrate_cpt_settings( string $previous_version, Cpt_Settings $cpt_settings ): void {
		$version_migrations = $this->get_version_migrations( $previous_version );

		foreach ( $version_migrations as $version_migration ) {
			$version_migration->migrate_cpt_settings( $cpt_settings );
		}
	}

	public function upgrade_from_previous_version(): void {
		// previous error logs are not relevant anymore.
		$this->logger->clear_error_logs();

		// all versions since 1.6.0 have a DB version record.
		$previous_version = $this->settings->get_version();

		$this->upgrade_from_version( $previous_version );
	}

	public function complete_version_upgrade(): void {
		$this->cache_flusher->flush_caches();

		$this->update_db_plugin_version();

		$this->logger->info( 'Version upgrade completed' );
	}

	public function upgrade_from_version( string $from_version ): void {
		$version_migrations = $this->get_version_migrations( $from_version );

		$version_migration_names = array_map(
			fn( Version_Migration $version_migration )=> $this->get_migration_name( $version_migration ),
			$version_migrations
		);

		$this->logger->info(
			'Performing version upgrade',
			array(
				'previous_version'   => $from_version,
				'current_version'    => $this->plugin->get_version(),
				'version_migrations' => $version_migration_names,
			)
		);

		foreach ( $version_migrations as $version_migration ) {
			$this->logger->info(
				'Running version migration case',
				array(
					'migration' => $this->get_migration_name( $version_migration ),
				)
			);

			$version_migration->migrate();
		}

		$this->upgrade_notice->setup_upgrade_notice( $version_migrations );
	}

	protected function update_db_plugin_version(): void {
		$this->settings->set_version( $this->plugin->get_version() );
		$this->settings->save();
	}

	/**
	 * @return Version_Migration[]
	 */
	protected function get_version_migrations( string $from_version ): array {
		$target_migrations = array_filter(
			$this->version_migrations,
			fn( Version_Migration $version_migration ) =>
			self::is_version_lower( $from_version, $version_migration->introduced_version() )
		);

		// ASC sort.
		usort(
			$target_migrations,
			fn( Version_Migration $first, Version_Migration $second ) =>
				$first->get_order() <=> $second->get_order()
		);

		return $target_migrations;
	}

	protected function get_migration_name( Migration $migration ): string {
		$full_class_name  = get_class( $migration );
		$class_name_parts = explode( '\\', $full_class_name );

		return end( $class_name_parts );
	}
}
