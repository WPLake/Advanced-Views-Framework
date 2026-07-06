<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

class Acf_Dependency extends Hookable implements Hooks_Interface {
	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function maybe_include_acf_plugin(): void {
		if ( $this->plugin->is_acf_plugin_available() ) {
			return;
		}

		$acf_file       = $this->plugin->get_standalone_vendor_dir( 'advanced-custom-fields/acf.php' );
		$acf_plugin_url = $this->plugin->get_standalone_vendor_url( 'advanced-custom-fields/' );

		// Hide ACF admin menu (as we loaded ACF only for our plugin).
		self::add_filter( 'acf/settings/show_admin', '__return_false' );
		// ensure right url, otherwise internal ACF asset paths are incorrect.
		self::add_filter( 'acf/settings/url', fn() => $acf_plugin_url );

		require_once $acf_file;

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
