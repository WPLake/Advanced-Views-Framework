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
					'customCSS'       => false,
				),
				'attributes'      => array_merge(
					array(
						'layoutId'     => array(
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
			'id'                 => string( $attributes, 'layoutId' ),
			// fixme it's a mess. Should pass the real final value from the front already (id/post/options/user/term/comment/menu)
			'object-id'          => self::resolve_object_id_attribute( $attributes ),
			'class'              => string( $attributes, 'class' ),
			'user-with-roles'    => string( $attributes, 'userWithRoles' ),
			'user-without-roles' => string( $attributes, 'userWithoutRoles' ),
			'custom-arguments'   => string( $attributes, 'customArguments' ),
		);

		// fixme use array filter & merge instead.
		foreach ( self::get_object_source_lookup_attributes( $attributes ) as $key => $value ) {
			if ( '' !== $value ) {
				$attrs[ $key ] = $value;
			}
		}

		$html = $this->layout_shortcode->render_shortcode( $attrs );

		$unique_id    = $this->layouts_settings_storage->get_unique_id_from_shortcode_id(
			string( $attrs, 'id' ),
			$this->layout_cpt->cpt_name()
		);
		$cpt_settings = $this->layouts_settings_storage->get( $unique_id );

		return Cpt_Gutenberg_Block::get_style_tag( $cpt_settings, $this->front_assets ) . $html;
	}

	/**
	 * Turns the Object Source control's selection (see layoutObjectSource.ts) back into the raw 'object-id'
	 * shortcode value Layout_Shortcode::get_data_post_id() expects.
	 *
	 * @param array<string,mixed> $attributes
	 */
	private static function resolve_object_id_attribute( array $attributes ): string {
		$object_source = string( $attributes, 'objectSource' );

		if ( 'post' === $object_source ) {
			switch ( string( $attributes, 'postLookup' ) ) {
				case 'slug':
					return 'post';
				case 'id':
					return string( $attributes, 'postId' );
				default:
					// the "Current Post" lookup - an empty 'object-id' falls back to the current object.
					return '';
			}
		}

		// 'menu' isn't its own 'object-id' value - it's the 'term' case, resolved via the 'menu-slug' lookup below.
		return 'menu' === $object_source ?
			'term' :
			$object_source; // 'options' | 'user' | 'term' | 'comment'
	}

	/**
	 * The lookup values the Object Source control's subfields collect (Post Slug, User/Term/Comment ID, Menu Slug),
	 * keyed by their shortcode attribute name.
	 *
	 * @param array<string,mixed> $attributes
	 *
	 * @return array<string,string>
	 */
	private static function get_object_source_lookup_attributes( array $attributes ): array {
		return array(
			'post-slug'  => string( $attributes, 'postSlug' ),
			'user-id'    => string( $attributes, 'userId' ),
			'term-id'    => string( $attributes, 'termId' ),
			'menu-slug'  => string( $attributes, 'menuSlug' ),
			'comment-id' => string( $attributes, 'commentId' ),
		);
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
