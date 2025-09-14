<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\bool;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

final class Avf_User {
	public static function can_manage(): bool {
		$manage_capability = self::get_manage_capability();
		$is_eligible_user  = current_user_can( $manage_capability );

		return bool(
			apply_filters( 'acf_views/user_can_manage', $is_eligible_user )
		);
	}

	public static function get_manage_capability(): string {
		/**
		 * Since Views and Cards templates support Blade with the ability to execute arbitrary PHP code,
		 * we limit access to all management features to users with the 'manage_options' capability.
		 *
		 * About the capability: https://wordpress.org/documentation/article/roles-and-capabilities/#manage_options.
		 * Managing roles and capabilities in WP: https://developer.wordpress.org/plugins/users/roles-and-capabilities/.
		 */
		return string(
			apply_filters( 'acf_views/manage_capability', 'manage_options' )
		);
	}
}
