<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Integrations;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Cpt_Gutenberg_Block;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Selection_Gutenberg_Block extends Hookable implements Hooks_Interface {
	const NAME       = 'advanced-views/post-selection';
	const REST_ROUTE = 'post-selection-block/selections';

	private Selection_Settings_Storage $selections_settings_storage;
	private Post_Selection_Shortcode $selection_shortcode;
	private Front_Assets $front_assets;
	private Plugin $plugin;
	private Public_Cpt $selection_cpt;
	private Route_Detector $route_detector;

	public function __construct(
		Selection_Settings_Storage $post_selections_settings_storage,
		Post_Selection_Shortcode $post_selection_shortcode,
		Front_Assets $front_assets,
		Plugin $plugin,
		Public_Cpt $post_selection_cpt
	) {
		$this->selections_settings_storage = $post_selections_settings_storage;
		$this->selection_shortcode         = $post_selection_shortcode;
		$this->front_assets                = $front_assets;
		$this->plugin                      = $plugin;
		$this->selection_cpt               = $post_selection_cpt;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		$this->route_detector = $route_detector;

		self::add_action( 'init', array( $this, 'register_block' ) );

		if ( $route_detector->is_admin_route() ) {
			self::add_filter( 'block_categories_all', array( Cpt_Gutenberg_Block::class, 'add_block_category' ) );
			self::add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
			self::add_action(
				'rest_api_init',
				fn() => register_rest_route(
					Cpt_Gutenberg_Block::REST_NAMESPACE,
					self::REST_ROUTE,
					Cpt_Gutenberg_Block::get_items_list_rest_args( $this->selections_settings_storage ),
				)
			);
		}
	}

	public function register_block(): void {
		register_block_type(
			self::NAME,
			array(
				'title'           => __( 'Post Selection: Advanced Views', 'acf-views' ),
				'description'     => __( 'Displays a Post Selection from Advanced Views.', 'acf-views' ),
				'category'        => Cpt_Gutenberg_Block::CATEGORY,
				'icon'            => 'layout',
				'supports'        => array(
					'customClassName' => false,
					'customCSS'       => false,
				),
				'attributes'      => array_merge(
					array(
						'selectionId' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
					Cpt_Gutenberg_Block::get_attribute_declarations()
				),
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $attributes
	 */
	public function render_block( array $attributes ): string {
		$attrs = self::parse_attributes( $attributes );

		$unique_id          = string( $attrs, 'id' );
		$selection_settings = $this->selections_settings_storage->get( $unique_id );

		if ( $selection_settings->isLoaded() ) {
			$html = $this->selection_shortcode->render_shortcode( $attrs );

			return $this->route_detector->is_admin_route() ?
				Cpt_Gutenberg_Block::render_preview( $selection_settings, $this->front_assets, $html ) :
				$html;
		}

		return $this->route_detector->is_admin_route() ?
			Cpt_Gutenberg_Block::get_empty_preview_placeholder( $this->selection_cpt->labels()->singular_name() ) :
			'';
	}

	public function enqueue_editor_assets(): void {
		wp_enqueue_script(
			self::NAME,
			$this->plugin->get_assets_url( 'admin/js/blocks/selection-block.min.js' ),
			Cpt_Gutenberg_Block::get_block_js_dependencies(),
			$this->plugin->get_version(),
			true
		);

		wp_localize_script(
			self::NAME,
			'avfSelectionBlock',
			array(
				'blockName'    => self::NAME,
				'items'        => Cpt_Gutenberg_Block::get_items_list( $this->selections_settings_storage ),
				'newItemUrl'   => admin_url( sprintf( 'post-new.php?post_type=%s', $this->selection_cpt->cpt_name() ) ),
				'itemsRestUrl' => sprintf( '/%s/%s', Cpt_Gutenberg_Block::REST_NAMESPACE, self::REST_ROUTE ),
				'canManage'    => Avf_User::can_manage(),
				'itemLabel'    => $this->selection_cpt->labels()->singular_name(),
			)
		);
	}

	/**
	 * @param array<string,mixed> $attributes
	 *
	 * @return array<string,string>
	 */
	protected static function parse_attributes( array $attributes ): array {
		$attrs = array_merge(
			array( 'id' => string( $attributes, 'selectionId' ) ),
			Cpt_Gutenberg_Block::parse_attributes( $attributes )
		);

		return array_filter(
			$attrs,
			fn( string $value ): bool => strlen( $value ) > 0
		);
	}
}
