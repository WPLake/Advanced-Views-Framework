<?php
/**
 * Plugin Name: Advanced Views Lite
 * Plugin URI: https://advanced-views.com/
 * Description: Display content with full control over selection and layout. Lightweight and compatible with any theme or page builder.
 * Version: 3.9.0
 * Author: WPLake
 * Author URI: https://advanced-views.com/
 * Text Domain: acf-views
 * Domain Path: /lang
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
