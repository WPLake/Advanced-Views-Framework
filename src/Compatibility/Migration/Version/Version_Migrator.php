<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Options;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

class Version_Migrator extends Hookable implements Hooks_Interface {
	private Plugin $plugin;
	private Settings $settings;
	/**
	 * @var array<string,Version_Migration> version => migrationInstance
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

	protected static function get_upgrade_notice(): string {
		$upgrade_transient = Options::get_transient( Options::TRANSIENT_UPGRADE_NOTICE );

		return string( $upgrade_transient );
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

		if ( $current_screen->is_admin() ) {
			$upgrade_notice = self::get_upgrade_notice();

			if ( strlen( $upgrade_notice ) > 0 ) {
				self::add_action( 'admin_notices', array( $this, 'print_upgrade_notice' ) );
			}
		}
	}

	public function print_upgrade_notice(): void {
		$dismiss_key          = sprintf( '_%s-upgrade-notice-dismiss', $this->plugin->get_short_slug() );
		$dismiss_nonce_action = 'avf-upgrade-notice';

		$dismiss_input = Query_Arguments::get_string_for_admin_action( $dismiss_key, $dismiss_nonce_action );

		if ( strlen( $dismiss_input ) > 0 &&
			Avf_User::can_manage() ) {
			Options::delete_transient( Options::TRANSIENT_UPGRADE_NOTICE );

			return;
		}

		$upgrade_notice = self::get_upgrade_notice();

		echo '<div class="notice notice-info">';
		echo '<p>';

		echo esc_html__( 'Advanced Views plugin has been successfully upgraded!', 'acf-views' );

		echo '<br>';

		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo str_replace( "\n", '<br>', esc_html( $upgrade_notice ) );

		if ( Avf_User::can_manage() ) {
			$dismiss_url = add_query_arg(
				array(
					$dismiss_key => 1,
					'_wpnonce'   => wp_create_nonce( $dismiss_nonce_action ),
				),
				Plugin::get_current_admin_url()
			);

			printf(
				'<a style="float:right;" href="%s">%s</a>',
				esc_url( $dismiss_url ),
				esc_html( __( 'Thanks, hide', 'acf-views' ) )
			);
		}
		echo '</p>';
		echo '</div>';
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

	/**
	 * @param Migration[] $migrations
	 */
	public function add_migrations( array $migrations ): void {
		$this->migrations = array_merge( $this->migrations, $migrations );
	}

	public function migrate( string $previous_version ): void {
		foreach ( $this->migrations as $migration ) {
			$migration->migrate();
		}

		$version_migrations = $this->get_version_migrations( $previous_version );

		foreach ( $version_migrations as $version_migration ) {
			$version_migration->migrate();
		}

		$this->set_upgrade_notice_text( $version_migrations );
	}

	public function migrate_cpt_settings( string $previous_version, Cpt_Settings $cpt_settings ): void {
		foreach ( $this->migrations as $migration ) {
			$migration->migrate_cpt_settings( $cpt_settings );
		}

		$version_migrations = $this->get_version_migrations( $previous_version );

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

	/**
	 * @return Version_Migration[]
	 */
	protected function get_version_migrations( string $previous_version ): array {
		$target_migrations = array_filter(
			$this->version_migrations,
			fn( Version_Migration $version_migration ) =>
			self::is_version_lower( $previous_version, $version_migration->introduced_version() )
		);

		// ASC sort.
		usort(
			$target_migrations,
			fn( Version_Migration $first, Version_Migration $second ) =>
				$first->get_order() <=> $second->get_order()
		);

		return $target_migrations;
	}

	/**
	 * @param Version_Migration[] $version_migrations
	 */
	protected function set_upgrade_notice_text( array $version_migrations ): void {
		Plugin::on_translations_ready(
			function () use ( $version_migrations ) {
				$upgrade_notices = array();

				foreach ( $version_migrations as $version_migration ) {
					$version_upgrade_notice = $version_migration->get_upgrade_notice_text();

					if ( is_string( $version_upgrade_notice ) ) {
						$upgrade_notices[] = sprintf( '%s (v. %s)', $version_upgrade_notice, $version_migration->introduced_version() );
					}
				}

				if ( count( $upgrade_notices ) > 0 ) {
					$upgrade_notice = implode( "\n", $upgrade_notices );

					Options::set_transient( Options::TRANSIENT_UPGRADE_NOTICE, $upgrade_notice, WEEK_IN_SECONDS );
				}
			}
		);
	}
}
