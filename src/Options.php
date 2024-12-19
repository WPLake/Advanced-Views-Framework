<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

defined( 'ABSPATH' ) || exit;

class Options {
	const PREFIX                                = Views_Cpt::NAME . '_';
	const OPTION_SETTINGS                       = self::PREFIX . 'settings';
	const TRANSIENT_DEACTIVATED_OTHER_INSTANCES = self::PREFIX . 'deactivated_other_instances';
	const TRANSIENT_LICENSE_EXPIRATION_DISMISS  = self::PREFIX . 'license_expiration_dismiss';

	/**
	 * @return mixed
	 */
	public function get_option( string $name ) {
		return get_option( $name, '' );
	}

	/**
	 * @return mixed
	 */
	public function get_transient( string $name ) {
		return get_transient( $name );
	}

	/**
	 * Autoload = true, to avoid real requests to the DB, as settings are common for all
	 *
	 * @param mixed $value
	 */
	public function update_option( string $name, $value, bool $is_autoload = true ): void {
		update_option( $name, $value, $is_autoload );
	}

	/**
	 * @param mixed $value
	 */
	public function set_transient( string $name, $value, int $expiration_in_seconds ): void {
		set_transient( $name, $value, $expiration_in_seconds );
	}

	public function delete_option( string $name ): void {
		delete_option( $name );
	}

	public function delete_transient( string $name ): void {
		delete_transient( $name );
	}
}
