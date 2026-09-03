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
	const NAME       = 'acf-views/post-selection';
	const REST_ROUTE = 'post-selection-block/selections';

	private Selection_Settings_Storage $post_selections_settings_storage;
	private Post_Selection_Shortcode $post_selection_shortcode;
	private Front_Assets $front_assets;
	private Plugin $plugin;
	private Public_Cpt $post_selection_cpt;

	public function __construct(
		Selection_Settings_Storage $post_selections_settings_storage,
		Post_Selection_Shortcode $post_selection_shortcode,
		Front_Assets $front_assets,
		Plugin $plugin,
		Public_Cpt $post_selection_cpt
	) {
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->post_selection_shortcode         = $post_selection_shortcode;
		$this->front_assets                     = $front_assets;
		$this->plugin                           = $plugin;
		$this->post_selection_cpt               = $post_selection_cpt;
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
					Cpt_Gutenberg_Block::get_items_list_rest_args( $this->post_selections_settings_storage ),
				)
			);
		}
	}

	public function register_block(): void {
		register_block_type(
			self::NAME,
			array(
				'title'           => __( 'Advanced Views Post Selection', 'acf-views' ),
				'description'     => __( 'Displays an Advanced Views Post Selection.', 'acf-views' ),
				'category'        => Cpt_Gutenberg_Block::CATEGORY,
				'icon'            => 'grid-view',
				'supports'        => array(
					'customClassName' => false,
				),
				'attributes'      => array_merge(
					array(
						'selectionId' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
					Cpt_Gutenberg_Block::get_common_block_attributes()
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
			'id'               => string( $attributes, 'selectionId' ),
			'class'            => string( $attributes, 'class' ),
			'user-with-roles'  => string( $attributes, 'userWithRoles' ),
			'custom-arguments' => string( $attributes, 'customArguments' ),
		);

		$html = $this->post_selection_shortcode->render_shortcode( $attrs );

		$unique_id    = $this->post_selections_settings_storage->get_unique_id_from_shortcode_id(
			string( $attrs, 'id' ),
			$this->post_selection_cpt->cpt_name()
		);
		$cpt_settings = $this->post_selections_settings_storage->get( $unique_id );

		return Cpt_Gutenberg_Block::get_style_tag( $cpt_settings, $this->front_assets ) . $html;
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
				'items'        => Cpt_Gutenberg_Block::get_items_list( $this->post_selections_settings_storage ),
				'newItemUrl'   => admin_url( sprintf( 'post-new.php?post_type=%s', $this->post_selection_cpt->cpt_name() ) ),
				'itemsRestUrl' => sprintf( '/%s/%s', Cpt_Gutenberg_Block::REST_NAMESPACE, self::REST_ROUTE ),
				'canManage'    => Avf_User::can_manage(),
			)
		);
	}
}
