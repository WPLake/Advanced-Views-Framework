<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

defined( 'ABSPATH' ) || exit;

class Acf_Dependency implements Hooks_Interface {
	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function maybe_include_acf_plugin(): void {
		if ( true === $this->plugin->is_acf_plugin_available() ) {
			return;
		}

		// Hide ACF admin menu (as we loaded ACF only for our plugin).
		add_filter( 'acf/settings/show_admin', '__return_false' );

		require_once __DIR__ . '/../../vendor/advanced-custom-fields/acf.php';

		// used in the AcfDataVendor to skip loading if it's inner ACF.
		define( 'ACF_VIEWS_INNER_ACF', true );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ||
			( false === $current_screen->is_admin_cpt_related( Views_Cpt::NAME ) &&
				false === $current_screen->is_admin_cpt_related( Cards_Cpt::NAME ) &&
				false === $current_screen->is_ajax() ) ) {
			return;
		}

		add_action( 'plugins_loaded', array( $this, 'maybe_include_acf_plugin' ) );
	}
}
