<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor\Cpt_Elementor_Bridge;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor\Cpt_Elementor_Widget;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Integrations\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

final class Layout_Elementor_Integration extends Hookable implements Hooks_Interface {
	private Layout_Settings_Storage $layouts_settings_storage;
	private Layout_Shortcode $layout_shortcode;
	private Front_Assets $front_assets;
	private Public_Cpt $layout_cpt;

	public function __construct(
		Layout_Settings_Storage $layouts_settings_storage,
		Layout_Shortcode $layout_shortcode,
		Front_Assets $front_assets,
		Public_Cpt $layout_cpt
	) {
		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layout_shortcode         = $layout_shortcode;
		$this->front_assets             = $front_assets;
		$this->layout_cpt               = $layout_cpt;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		/**
		 * fixme
		 * the goal of isElementorAvailable() check will fail at this point, as set_hooks called before plugins_loaded.
		 * should be handled on the level above - Loader:
		 * 1. introduce isElementorAvailable() method into Cpt_Elementor_Widget.
		 * 2. Loader: add load_plugin_extensions method, and bind to 'plugins_loaded' hooks, with return of hookable array.
		 * then create Elementor_Integration instance only if isElementorAvailable(), and return it.
		 */
		if ( did_action( 'elementor/loaded' ) === 0 ) {
			return;
		}

		self::add_action(
			'elementor/elements/categories_registered',
			fn( Elements_Manager $elements_manager ) => Cpt_Elementor_Widget::add_category( $elements_manager )
		);
		self::add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	public function register_widget( Widgets_Manager $widgets_manager ): void {
		$bridge = new Cpt_Elementor_Bridge(
			$this->layouts_settings_storage,
			$this->layout_shortcode,
			$this->front_assets,
			$this->layout_cpt
		);

		Layout_Elementor_Widget::set_dependencies( $bridge );

		$widgets_manager->register( new Layout_Elementor_Widget() );
	}
}
