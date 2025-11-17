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

class Acf_Internal_Features extends Hookable implements Hooks_Interface {
	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function include_field_types(): void {
		$internal_features_path = __DIR__ . '/../../vendor/acf-internal-features';

		include_once $internal_features_path . '/inc/class-acf-field-clone.php';
		include_once $internal_features_path . '/inc/class-acf-repeater-table.php';
		include_once $internal_features_path . '/inc/class-acf-field-repeater.php';
		include_once $internal_features_path . '/inc/options-page.php';
		include_once $internal_features_path . '/inc/admin-options-page.php';
		include_once $internal_features_path . '/inc/class-acf-location-options-page.php';
	}

	public function register_assets(): void {
		// register scripts.
		wp_register_script(
			'acf-pro-input',
			$this->plugin->get_acf_internal_assets_url( 'acf-pro-input.min.js' ),
			array( 'acf-input' ),
			$this->plugin->get_version(),
			array(
				'in_footer' => false,
			)
		);

		// register styles.
		wp_register_style(
			'acf-pro-input',
			$this->plugin->get_acf_internal_assets_url( 'acf-pro-input.min.css' ),
			array( 'acf-input' ),
			$this->plugin->get_version()
		);
	}

	public function input_admin_enqueue_scripts(): void {
		wp_enqueue_script( 'acf-pro-input' );
		wp_enqueue_style( 'acf-pro-input' );
	}

	public function maybe_include_features(): void {
		// skip if 'ACF Pro' is available.

		if ( true === $this->plugin->is_acf_plugin_available( true ) ) {
			return;
		}

		self::add_action( 'init', array( $this, 'register_assets' ) );
		self::add_action( 'acf/include_field_types', array( $this, 'include_field_types' ), 5 );
		self::add_action( 'acf/input/admin_enqueue_scripts', array( $this, 'input_admin_enqueue_scripts' ) );
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( false === $route_detector->is_admin_route() ||
			( false === $route_detector->is_cpt_admin_route( Hard_Layout_Cpt::cpt_name() ) &&
				false === $route_detector->is_cpt_admin_route( Hard_Post_Selection_Cpt::cpt_name() ) &&
				! wp_doing_ajax() ) ) {
			return;
		}

		// only since 'plugins_loaded' we can judge if ACF is loaded or not
		// '-1' so it's after AcfDependency->maybeIncludeAcfPlugin().
		self::add_action(
			'plugins_loaded',
			array( $this, 'maybe_include_features' ),
			Data_Vendors::PLUGINS_LOADED_HOOK_PRIORITY - 1
		);
	}
}
