<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Base;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Plugin;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\bool;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Avf_User {
	public static function can_manage(): bool {
		$manage_capability = self::get_manage_capability();
		$is_eligible_user  = current_user_can( $manage_capability );

		$can_manage = Plugin::apply_filters(
			array(
				'advanced_views/user_can_manage',
				'acf_views/user_can_manage',
			),
			$is_eligible_user
		);

		return bool( $can_manage );
	}

	public static function can_see_errors(): bool {
		$is_user_logged_in = is_user_logged_in();

		$can_see_errors = Plugin::apply_filters(
			array(
				'advanced_views/user_can_see_errors',
				'acf_views/user_can_see_errors',
			),
			$is_user_logged_in
		);

		return bool( $can_see_errors );
	}

	public static function get_manage_capability(): string {
		$manage_capability = Plugin::apply_filters(
			array(
				'advanced_views/manage_capability',
				'acf_views/manage_capability',
			),
			'manage_options'
		);

		/**
		 * Since Layout and Post Selection templates support Blade with the ability to execute arbitrary PHP code,
		 * we limit access to all management features to users with the 'manage_options' capability.
		 *
		 * About the capability: https://wordpress.org/documentation/article/roles-and-capabilities/#manage_options.
		 * Managing roles and capabilities in WP: https://developer.wordpress.org/plugins/users/roles-and-capabilities/.
		 */
		return string( $manage_capability );
	}
}
