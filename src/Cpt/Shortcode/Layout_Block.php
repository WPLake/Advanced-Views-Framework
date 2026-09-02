<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Shortcode;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Layout_Block extends Hookable implements Hooks_Interface {
	const NAME     = 'acf-views/layout';
	const CATEGORY = 'advanced-views';

	private Layout_Settings_Storage $layouts_settings_storage;
	private Layout_Shortcode $layout_shortcode;
	private Front_Assets $front_assets;
	private Plugin $plugin;

	public function __construct(
		Layout_Settings_Storage $layouts_settings_storage,
		Layout_Shortcode $layout_shortcode,
		Front_Assets $front_assets,
		Plugin $plugin
	) {
		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layout_shortcode         = $layout_shortcode;
		$this->front_assets             = $front_assets;
		$this->plugin                   = $plugin;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		self::add_action( 'init', array( $this, 'register_block' ) );

		if ( $route_detector->is_admin_route() ) {
			self::add_filter( 'block_categories_all', array( $this, 'add_block_category' ) );
			self::add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
			// unlike 'enqueue_block_editor_assets', this one is also mirrored by Gutenberg into the
			// editor canvas iframe, which is where the block's own rendered markup actually lives.
			self::add_action( 'enqueue_block_assets', array( $this, 'enqueue_content_styles' ) );
		}
	}

	/**
	 * @param mixed[] $categories
	 *
	 * @return mixed[]
	 */
	public function add_block_category( array $categories ): array {
		return array_merge(
			array(
				array(
					'slug'  => self::CATEGORY,
					'title' => __( 'Advanced Views', 'acf-views' ),
				),
			),
			$categories
		);
	}

	public function register_block(): void {
		register_block_type(
			self::NAME,
			array(
				'title'           => __( 'Advanced Views Layout', 'acf-views' ),
				'description'     => __( 'Displays an Advanced Views Layout.', 'acf-views' ),
				'category'        => self::CATEGORY,
				'icon'            => 'layout',
				'uses_context'    => array( 'postId' ),
				'supports'        => array(
					'customClassName' => false,
				),
				'attributes'      => array(
					'layoutId'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'objectId'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'class'           => array(
						'type'    => 'string',
						'default' => '',
					),
					'userWithRoles'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'customArguments' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $attributes
	 */
	public function render_block( array $attributes ): string {
		$attrs = array(
			'id'               => string( $attributes, 'layoutId' ),
			'object-id'        => string( $attributes, 'objectId' ),
			'class'            => string( $attributes, 'class' ),
			'user-with-roles'  => string( $attributes, 'userWithRoles' ),
			'custom-arguments' => string( $attributes, 'customArguments' ),
		);

		return $this->layout_shortcode->render_shortcode( $attrs );
	}

	public function enqueue_editor_assets(): void {
		wp_enqueue_script(
			self::NAME,
			$this->plugin->get_assets_url( 'admin/js/blocks/layout-block.min.js' ),
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
				'wp-i18n',
			),
			$this->plugin->get_version(),
			true
		);

		wp_localize_script(
			self::NAME,
			'avfLayoutBlock',
			array(
				'blockName' => self::NAME,
				'layouts'   => $this->get_layouts_list(),
			)
		);
	}

	/**
	 * Layouts render via a dynamic 'render_callback', so the editor never gets their CSS through the usual
	 * Front_Assets 'wp_head'/'wp_footer' printing (that only runs on a real front-end page load). Enqueue it
	 * once here instead of inlining it per block instance - otherwise every repeated use of the same Layout
	 * (e.g. inside a Query Loop) would duplicate it. This must run on 'enqueue_block_assets', not
	 * 'enqueue_block_editor_assets': only the former is mirrored by Gutenberg into the editor canvas iframe,
	 * which is where the block's own rendered markup actually lives.
	 *
	 * Every Layout's CSS is loaded unconditionally here, not just ones already used on the current page:
	 * this hook only fires once, on the initial editor page load, so it can't react to a Layout picked
	 * afterwards from the block's own inserter - the same reason WordPress core loads all registered
	 * blocks' styles up front in the editor and only restricts to what's actually used on the front end
	 * (see 'wp_should_load_separate_core_block_assets()', which is admin-exempt for exactly this reason).
	 */
	public function enqueue_content_styles(): void {
		wp_register_style( self::NAME, false, array(), $this->plugin->get_version() );
		wp_enqueue_style( self::NAME );
		wp_add_inline_style( self::NAME, $this->get_layouts_css() );
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_layouts_list(): array {
		$list = array();

		foreach ( $this->layouts_settings_storage->get_unique_id_with_name_items_list() as $unique_id => $title ) {
			$short_id          = substr( $unique_id, strlen( Layout_Settings::UNIQUE_ID_PREFIX ) );
			$list[ $short_id ] = $title;
		}

		return $list;
	}

	protected function get_layouts_css(): string {
		$css = '';

		foreach ( array_keys( $this->layouts_settings_storage->get_unique_id_with_name_items_list() ) as $unique_id ) {
			$layout_settings = $this->layouts_settings_storage->get( $unique_id );

			// internal (e.g. shadow DOM) CSS is scoped to its own markup and inlined there instead.
			if ( $layout_settings->is_css_internal() ) {
				continue;
			}

			$css .= $this->front_assets->minify_code(
				$layout_settings->get_css_code( Cpt_Settings::CODE_MODE_DISPLAY ),
				Front_Assets::MINIFY_TYPE_CSS
			);
		}

		return $css;
	}
}
