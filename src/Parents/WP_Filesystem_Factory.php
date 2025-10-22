<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

defined( 'ABSPATH' ) || exit;

use WP_Filesystem_Base;

abstract class WP_Filesystem_Factory {
	public static function get_wp_filesystem(): WP_Filesystem_Base {
		global $wp_filesystem;

		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		return $wp_filesystem;
	}
}
