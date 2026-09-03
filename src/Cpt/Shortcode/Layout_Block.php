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

		$html = $this->layout_shortcode->render_shortcode( $attrs );

		return $this->add_style_tag( $html, $attrs['id'] );
	}

	/**
	 * Every render carries its own scoped 'data-avf-id' style tag, instead of relying solely on
	 * Front_Assets' page-level 'wp_head'/'wp_footer' printing - a dynamic 'render_callback' can't reach
	 * that when rendered through the block editor's ServerSideRender REST call, which is a request of its
	 * own with no such page to print into. layout-block.ts (editor-only) then moves this tag into <head>,
	 * replacing any existing tag with the same id, so repeated/updated uses of the same Layout in the
	 * editor don't keep accumulating duplicate CSS.
	 */
	protected function add_style_tag( string $html, string $short_unique_id ): string {
		$unique_id = Layout_Settings::UNIQUE_ID_PREFIX . $short_unique_id;

		if ( ! key_exists( $unique_id, $this->layouts_settings_storage->get_unique_id_with_name_items_list() ) ) {
			return $html;
		}

		$layout_settings = $this->layouts_settings_storage->get( $unique_id );

		// internal (e.g. shadow DOM) CSS is scoped to its own markup and inlined there instead.
		if ( $layout_settings->is_css_internal() ) {
			return $html;
		}

		$css = $this->front_assets->minify_code(
			$layout_settings->get_css_code( Cpt_Settings::CODE_MODE_DISPLAY ),
			Front_Assets::MINIFY_TYPE_CSS
		);

		return sprintf( '<style data-avf-id="%s">%s</style>', esc_attr( $unique_id ), $css ) . $html;
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
}
