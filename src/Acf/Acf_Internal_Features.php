<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

class Acf_Internal_Features implements Hooks_Interface {
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

		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'acf/include_field_types', array( $this, 'include_field_types' ), 5 );
		add_action( 'acf/input/admin_enqueue_scripts', array( $this, 'input_admin_enqueue_scripts' ) );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ||
			( false === $current_screen->is_admin_cpt_related( Views_Cpt::NAME ) &&
				false === $current_screen->is_admin_cpt_related( Cards_Cpt::NAME ) &&
				false === $current_screen->is_ajax() ) ) {
			return;
		}

		// only since 'plugins_loaded' we can judge if ACF is loaded or not
		// 20, to be after AcfDependency->maybeIncludeAcfPlugin().
		add_action( 'plugins_loaded', array( $this, 'maybe_include_features' ), 20 );
	}
}
