<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor\Cpt_Elementor_Bridge;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor\Cpt_Elementor_Widget;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Integrations\Post_Selection_Shortcode;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

final class Selection_Elementor_Integration extends Hookable implements Hooks_Interface {
	private Selection_Settings_Storage $selections_settings_storage;
	private Post_Selection_Shortcode $selection_shortcode;
	private Front_Assets $front_assets;
	private Public_Cpt $selection_cpt;

	public function __construct(
		Selection_Settings_Storage $selections_settings_storage,
		Post_Selection_Shortcode $selection_shortcode,
		Front_Assets $front_assets,
		Public_Cpt $selection_cpt
	) {
		$this->selections_settings_storage = $selections_settings_storage;
		$this->selection_shortcode         = $selection_shortcode;
		$this->front_assets                = $front_assets;
		$this->selection_cpt               = $selection_cpt;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
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
			$this->selections_settings_storage,
			$this->selection_shortcode,
			$this->front_assets,
			$this->selection_cpt
		);

		Selection_Elementor_Widget::set_dependencies( $bridge );

		$widgets_manager->register( new Selection_Elementor_Widget() );
	}
}
