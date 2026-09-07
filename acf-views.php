<?php
/**
 * Plugin Name: Advanced Views Lite
 * Plugin URI: https://advanced-views.com/
 * Description: Display framework with full control over selection and layout. Lightweight and compatible with any theme or page builder.
 * Version: 3.9.6
 * Author: WPLake
 * Author URI: https://advanced-views.com/
 * Text Domain: acf-views
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Org\Wplake\Advanced_Views;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Loaders\Lite\Lite_Plugin_Loader;
use Org\Wplake\Advanced_Views\Plugin\Plugin;

( function (): void {
	// omit loading if the Pro version is already loaded.
	if ( class_exists( Plugin::class ) ) {
		return;
	}

	require_once __DIR__ . '/src/autoloader.php';

	$plugin_loader = new Lite_Plugin_Loader( __FILE__ );
	$plugin_loader->load();
} )();
