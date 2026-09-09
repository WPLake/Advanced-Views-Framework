<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

use Elementor\Plugin as Elementor_Plugin;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Cpt_Gutenberg_Block;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Shortcode_Renderer;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

/**
 * Bridges Lite's constructor-injected services into an Elementor widget's re-constructed instance (Elementor
 * discards whatever instance was passed to register() and builds a fresh one - with its own no-args constructor -
 * for every actual render), so Layout_Elementor_Widget/Selection_Elementor_Widget only need to hold one static
 * reference instead of each of their CPT's raw dependencies.
 */
final class Cpt_Elementor_Bridge {
	private Cpt_Settings_Storage $settings_storage;
	private Shortcode_Renderer $shortcode;
	private Front_Assets $front_assets;
	private Public_Cpt $cpt;

	public function __construct(
		Cpt_Settings_Storage $settings_storage,
		Shortcode_Renderer $shortcode,
		Front_Assets $front_assets,
		Public_Cpt $cpt
	) {
		$this->settings_storage = $settings_storage;
		$this->shortcode        = $shortcode;
		$this->front_assets     = $front_assets;
		$this->cpt              = $cpt;
	}

	/**
	 * @return array<string,array{title:string,editUrl:string}>
	 */
	public function get_items_list(): array {
		return Cpt_Gutenberg_Block::get_items_list( $this->settings_storage );
	}

	/**
	 * @param array<string,string> $attrs
	 */
	public function render( array $attrs ): string {
		$unique_id    = string( $attrs, 'id' );
		$cpt_settings = $this->settings_storage->get( $unique_id );

		if ( $cpt_settings->isLoaded() ) {
			$html = $this->shortcode->render_shortcode( $attrs );

			return self::is_editor_preview() ?
				Cpt_Gutenberg_Block::render_preview( $cpt_settings, $this->front_assets, $html ) :
				$html;
		}

		return self::is_editor_preview() ?
			Cpt_Gutenberg_Block::get_empty_preview_placeholder( $this->cpt->labels()->singular_name() ) :
			'';
	}

	/**
	 * Elementor's main canvas preview loads the real front-end URL through normal template routing (caught by
	 * is_preview_mode()'s 'elementor-preview' query arg), while its per-widget AJAX partial re-render runs through
	 * admin-ajax.php with edit mode explicitly turned on for the duration of the render (caught by is_edit_mode()) -
	 * neither is covered by Route_Detector::is_admin_route().
	 */
	protected static function is_editor_preview(): bool {
		$elementor = Elementor_Plugin::$instance;

		return $elementor->editor->is_edit_mode() ||
				$elementor->preview->is_preview_mode();
	}
}
