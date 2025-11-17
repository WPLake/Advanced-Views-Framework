<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration;
use Org\Wplake\Advanced_Views\Options;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Utils\Query_Arguments;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Upgrade_Notice extends Hookable implements Hooks_Interface {
	private Plugin $plugin;
	private string $dismiss_key;
	private string $dismiss_nonce_action;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;

		$this->dismiss_key          = sprintf( '_%s-upgrade-notice-dismiss', $this->plugin->get_short_slug() );
		$this->dismiss_nonce_action = 'avf-upgrade-notice';
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( $current_screen->is_admin() ) {
			$upgrade_notice = $this->get_upgrade_notice();

			if ( strlen( $upgrade_notice ) > 0 ) {
				self::add_action(
					'admin_notices',
					function () {
						if ( ! $this->hide_notice() ) {
							$this->print_notice();
						}
					}
				);
			}
		}
	}

	/**
	 * @param Version_Migration[] $version_migrations
	 */
	public function setup_upgrade_notice( array $version_migrations ): void {
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

	public function print_notice(): void {
		$upgrade_notice = $this->get_upgrade_notice();

		echo '<div class="notice notice-info">';
		echo '<p>';

		echo esc_html__( 'Advanced Views plugin has been successfully upgraded!', 'acf-views' );

		echo '<br>';

		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo str_replace( "\n", '<br>', esc_html( $upgrade_notice ) );

		if ( Avf_User::can_manage() ) {
			$dismiss_url = add_query_arg(
				array(
					$this->dismiss_key => 1,
					'_wpnonce'         => wp_create_nonce( $this->dismiss_nonce_action ),
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

	protected function hide_notice(): bool {
		$dismiss_value = Query_Arguments::get_string_for_admin_action(
			$this->dismiss_key,
			$this->dismiss_nonce_action
		);

		if ( strlen( $dismiss_value ) > 0 &&
			Avf_User::can_manage() ) {
			Options::delete_transient( Options::TRANSIENT_UPGRADE_NOTICE );

			return true;
		}

		return false;
	}

	protected function get_upgrade_notice(): string {
		$upgrade_transient = Options::get_transient( Options::TRANSIENT_UPGRADE_NOTICE );

		return string( $upgrade_transient );
	}
}
