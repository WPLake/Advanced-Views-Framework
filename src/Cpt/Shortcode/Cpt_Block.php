<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Shortcode;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

/**
 * Everything a Gutenberg block backed by a Cpt_Settings_Storage (Layout, Post Selection...) needs, regardless
 * of which CPT it targets: the scoped 'data-avf-id' style tag, the admin-only "list items" REST route used by
 * the editor's "Refresh" action, and the editor script/localized-data wiring. Concrete subclasses only add their
 * own block metadata (name/description/icon/attributes) and the actual shortcode-rendering glue - see
 * Layout_Block.
 */
abstract class Cpt_Block extends Hookable implements Hooks_Interface {
	const CATEGORY       = 'advanced-views';
	const REST_NAMESPACE = 'advanced_views/v1';

	protected Cpt_Settings_Storage $settings_storage;
	protected Front_Assets $front_assets;
	protected Plugin $plugin;
	protected Public_Cpt $public_cpt;

	public function __construct(
		Cpt_Settings_Storage $settings_storage,
		Front_Assets $front_assets,
		Plugin $plugin,
		Public_Cpt $public_cpt
	) {
		$this->settings_storage = $settings_storage;
		$this->front_assets     = $front_assets;
		$this->plugin           = $plugin;
		$this->public_cpt       = $public_cpt;
	}

	abstract protected function get_unique_id_prefix(): string;

	abstract protected function get_block_name(): string;

	abstract protected function get_rest_route(): string;

	abstract protected function get_editor_script_path(): string;

	abstract protected function get_js_global_name(): string;

	abstract public function register_block(): void;

	/**
	 * @param array<string,mixed> $attributes
	 */
	abstract public function render_block( array $attributes ): string;

	public function set_hooks( Route_Detector $route_detector ): void {
		self::add_action( 'init', array( $this, 'register_block' ) );

		if ( $route_detector->is_admin_route() ) {
			self::add_filter( 'block_categories_all', array( $this, 'add_block_category' ) );
			self::add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
			self::add_action( 'rest_api_init', array( $this, 'register_items_rest_route' ) );
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

	/**
	 * Every render carries its own scoped 'data-avf-id' style tag, instead of relying solely on
	 * Front_Assets' page-level 'wp_head'/'wp_footer' printing - a dynamic 'render_callback' can't reach
	 * that when rendered through the block editor's ServerSideRender REST call, which is a request of its
	 * own with no such page to print into. cpt-block.ts (editor-only) then moves this tag into <head>,
	 * replacing any existing tag with the same id, so repeated/updated uses of the same item in the
	 * editor don't keep accumulating duplicate CSS.
	 */
	protected function add_style_tag( string $html, string $short_unique_id ): string {
		$unique_id    = $this->get_unique_id_prefix() . $short_unique_id;
		$cpt_settings = $this->settings_storage->get( $unique_id );

		// isLoaded() is false for a missing/blank id (e.g. nothing selected in the block yet).
		if ( ! $cpt_settings->isLoaded() ||
			// internal (e.g. shadow DOM) CSS is scoped to its own markup and inlined there instead.
			$cpt_settings->is_css_internal() ) {
			return $html;
		}

		$css = $this->front_assets->minify_code(
			$cpt_settings->get_css_code( Cpt_Settings::CODE_MODE_DISPLAY ),
			Front_Assets::MINIFY_TYPE_CSS
		);

		return sprintf( '<style data-avf-id="%s">%s</style>', esc_attr( $unique_id ), $css ) . $html;
	}

	public function enqueue_editor_assets(): void {
		$block_name = $this->get_block_name();

		wp_enqueue_script(
			$block_name,
			$this->plugin->get_assets_url( $this->get_editor_script_path() ),
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
				'wp-i18n',
				'wp-api-fetch',
			),
			$this->plugin->get_version(),
			true
		);

		wp_localize_script(
			$block_name,
			$this->get_js_global_name(),
			array(
				'blockName'    => $block_name,
				'items'        => $this->get_items_list(),
				'newItemUrl'   => admin_url( sprintf( 'post-new.php?post_type=%s', $this->public_cpt->cpt_name() ) ),
				'itemsRestUrl' => sprintf( '/%s/%s', self::REST_NAMESPACE, $this->get_rest_route() ),
				'canManage'    => Avf_User::can_manage(),
			)
		);
	}

	public function register_items_rest_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			$this->get_rest_route(),
			array(
				'methods'             => 'GET',
				'permission_callback' => fn(): bool => Avf_User::can_manage(),
				/**
				 * @return array<string,array{title:string,editUrl:string}>
				 */
				'callback'            => fn(): array => $this->get_items_list(),
			)
		);
	}

	/**
	 * @return array<string,array{title:string,editUrl:string}>
	 */
	protected function get_items_list(): array {
		$list   = array();
		$prefix = $this->get_unique_id_prefix();

		foreach ( $this->settings_storage->get_unique_id_with_name_items_list() as $unique_id => $title ) {
			$short_id = substr( $unique_id, strlen( $prefix ) );

			$list[ $short_id ] = array(
				'title'   => $title,
				'editUrl' => $this->settings_storage->get( $unique_id )->get_edit_post_link( 'raw' ),
			);
		}

		return $list;
	}
}
