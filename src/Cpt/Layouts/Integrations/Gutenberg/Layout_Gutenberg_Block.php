<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts\Integrations\Gutenberg;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Cpt_Gutenberg_Block;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Integrations\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Layout_Gutenberg_Block extends Hookable implements Hooks_Interface {
	// prefixed by the plugin name for wp.org Plugin directory discover.
	const NAME       = Plugin::PRODUCT_SLUG . '/layout';
	const REST_ROUTE = 'layout-block/layouts';

	private Layout_Settings_Storage $layouts_settings_storage;
	private Layout_Shortcode $layout_shortcode;
	private Front_Assets $front_assets;
	private Plugin $plugin;
	private Public_Cpt $layout_cpt;
	private Route_Detector $route_detector;

	public function __construct(
		Layout_Settings_Storage $layouts_settings_storage,
		Layout_Shortcode $layout_shortcode,
		Front_Assets $front_assets,
		Plugin $plugin,
		Public_Cpt $layout_cpt
	) {
		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layout_shortcode         = $layout_shortcode;
		$this->front_assets             = $front_assets;
		$this->plugin                   = $plugin;
		$this->layout_cpt               = $layout_cpt;
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
					Plugin::REST_NAMESPACE,
					self::REST_ROUTE,
					Cpt_Gutenberg_Block::get_items_list_rest_args( $this->layouts_settings_storage ),
				)
			);
		}
	}

	public function register_block(): void {
		register_block_type(
			__DIR__ . '/block.json',
			array(
				'category'        => Cpt_Gutenberg_Block::CATEGORY,
				'supports'        => Cpt_Gutenberg_Block::get_supports(),
				'attributes'      => array_merge(
					self::get_attribute_declarations(),
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

		$unique_id       = string( $attrs, 'id' );
		$layout_settings = $this->layouts_settings_storage->get( $unique_id );

		if ( $layout_settings->isLoaded() ) {
			$html = $this->layout_shortcode->render_shortcode( $attrs );

			return $this->route_detector->is_admin_route() ?
				Cpt_Gutenberg_Block::render_preview( $layout_settings, $this->front_assets, $html ) :
				$html;
		}

		return $this->route_detector->is_admin_route() ?
			Cpt_Gutenberg_Block::get_empty_preview_placeholder( $this->layout_cpt->labels()->singular_name() ) :
			'';
	}

	public function enqueue_editor_assets(): void {
		wp_enqueue_script(
			self::NAME,
			$this->plugin->get_assets_url( 'admin/js/blocks/layout-block.min.js' ),
			Cpt_Gutenberg_Block::get_block_js_dependencies(),
			$this->plugin->get_version(),
			true
		);

		wp_localize_script(
			self::NAME,
			'avfLayoutBlock',
			array(
				'blockName'    => self::NAME,
				'items'        => Cpt_Gutenberg_Block::get_items_list( $this->layouts_settings_storage ),
				'newItemUrl'   => admin_url( sprintf( 'post-new.php?post_type=%s', $this->layout_cpt->cpt_name() ) ),
				'itemsRestUrl' => sprintf( '/%s/%s', Plugin::REST_NAMESPACE, self::REST_ROUTE ),
				'canManage'    => Avf_User::can_manage(),
				'itemLabel'    => $this->layout_cpt->labels()->singular_name(),
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
			array(
				'id'        => string( $attributes, 'layoutId' ),
				'object-id' => string( $attributes, 'objectId' ),
			),
			self::parse_lookup_attributes( $attributes ),
			Cpt_Gutenberg_Block::parse_attributes( $attributes ),
		);

		return array_filter(
			$attrs,
			fn( string $value ): bool => strlen( $value ) > 0
		);
	}

	/**
	 * @param array<string,mixed> $attributes
	 *
	 * @return array<string,string>
	 */
	protected static function parse_lookup_attributes( array $attributes ): array {
		return array(
			'post-slug'  => string( $attributes, 'postSlug' ),
			'user-id'    => string( $attributes, 'userId' ),
			'term-id'    => string( $attributes, 'termId' ),
			'menu-slug'  => string( $attributes, 'menuSlug' ),
			'comment-id' => string( $attributes, 'commentId' ),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected static function get_attribute_declarations(): array {
		return array(
			'layoutId'     => array(
				'type'    => 'string',
				'default' => '',
			),
			'objectId'     => array(
				'type'    => 'string',
				'default' => '',
			),
			'objectSource' => array(
				'type'    => 'string',
				'default' => 'post',
			),
			'postLookup'   => array(
				'type'    => 'string',
				'default' => 'current',
			),
			'postId'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'userId'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'termId'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'menuSlug'     => array(
				'type'    => 'string',
				'default' => '',
			),
			'postSlug'     => array(
				'type'    => 'string',
				'default' => '',
			),
			'commentId'    => array(
				'type'    => 'string',
				'default' => '',
			),
		);
	}
}
