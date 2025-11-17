<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

class Acf_Dependency extends Hookable implements Hooks_Interface {
	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function maybe_include_acf_plugin(): void {
		if ( true === $this->plugin->is_acf_plugin_available() ) {
			return;
		}

		// Hide ACF admin menu (as we loaded ACF only for our plugin).
		self::add_filter( 'acf/settings/show_admin', '__return_false' );

		require_once __DIR__ . '/../../vendor/advanced-custom-fields/acf.php';

		// used in the AcfDataVendor to skip loading if it's inner ACF.
		define( 'ACF_VIEWS_INNER_ACF', true );
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( false === $route_detector->is_admin_route() ||
			( false === $route_detector->is_cpt_admin_route( Hard_Layout_Cpt::cpt_name() ) &&
				false === $route_detector->is_cpt_admin_route( Hard_Post_Selection_Cpt::cpt_name() ) &&
				! wp_doing_ajax() ) ) {
			return;
		}

		self::add_action(
			'plugins_loaded',
			array( $this, 'maybe_include_acf_plugin' ),
			// -2, so it's before Acf_Internal_Features
			Data_Vendors::PLUGINS_LOADED_HOOK_PRIORITY - 2
		);
	}
}
