<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts\Integrations;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Cpt_Gutenberg_Block;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Layout_Gutenberg_Block extends Hookable implements Hooks_Interface {
	const NAME       = 'acf-views/layout';
	const REST_ROUTE = 'layout-block/layouts';

	private Layout_Settings_Storage $layouts_settings_storage;
	private Layout_Shortcode $layout_shortcode;
	private Front_Assets $front_assets;
	private Plugin $plugin;
	private Public_Cpt $layout_cpt;

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
		self::add_action( 'init', array( $this, 'register_block' ) );

		if ( $route_detector->is_admin_route() ) {
			self::add_filter( 'block_categories_all', array( Cpt_Gutenberg_Block::class, 'add_block_category' ) );
			self::add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
			self::add_action(
				'rest_api_init',
				fn() => register_rest_route(
					Cpt_Gutenberg_Block::REST_NAMESPACE,
					self::REST_ROUTE,
					Cpt_Gutenberg_Block::get_items_list_rest_args( $this->layouts_settings_storage ),
				)
			);
		}
	}

	public function register_block(): void {
		register_block_type(
			self::NAME,
			array(
				'title'           => __( 'Advanced Views Layout', 'acf-views' ),
				'description'     => __( 'Displays an Advanced Views Layout.', 'acf-views' ),
				'category'        => Cpt_Gutenberg_Block::CATEGORY,
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

		$unique_id    = $this->layouts_settings_storage->get_unique_id_from_shortcode_id(
			string( $attrs, 'id' ),
			$this->layout_cpt->cpt_name()
		);
		$cpt_settings = $this->layouts_settings_storage->get( $unique_id );

		return Cpt_Gutenberg_Block::get_style_tag( $cpt_settings, $this->front_assets ) . $html;
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
				'itemsRestUrl' => sprintf( '/%s/%s', Cpt_Gutenberg_Block::REST_NAMESPACE, self::REST_ROUTE ),
				'canManage'    => Avf_User::can_manage(),
			)
		);
	}
}
