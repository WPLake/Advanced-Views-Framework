<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Integrations;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;

/**
 * Stateless logic shared by every Gutenberg block backed by a Cpt_Settings_Storage (Layout, Post Selection...):
 * the block category filter, the scoped 'data-avf-id' style tag, and the "list items" lookup used both for the
 * editor's SelectControl and its "Refresh" REST route. A concrete block (e.g. Layout_Gutenberg_Block,
 * Selection_Gutenberg_Block) still owns its own hook registration, block metadata, and REST/enqueue wiring
 * directly - only the actual computation/declarations that are identical across CPTs live here, not the WP-API
 * glue around them.
 */
final class Cpt_Gutenberg_Block {
	const CATEGORY       = 'advanced-views';
	const REST_NAMESPACE = 'advanced_views/v1';

	private function __construct() {
	}

	/**
	 * @param mixed[] $categories
	 *
	 * @return mixed[]
	 */
	public static function add_block_category( array $categories ): array {
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
	 * The attributes every Cpt_Gutenberg_Block-backed block supports, regardless of which CPT it targets.
	 * A concrete block merges its own item-id attribute (and any CPT-specific ones, e.g. Layout's 'objectId')
	 * on top.
	 *
	 * @return array<string,array{type:string,default:string}>
	 */
	public static function get_common_block_attributes(): array {
		return array(
			'class'            => array(
				'type'    => 'string',
				'default' => '',
			),
			'userWithRoles'    => array(
				'type'    => 'string',
				'default' => '',
			),
			'userWithoutRoles' => array(
				'type'    => 'string',
				'default' => '',
			),
			'customArguments'  => array(
				'type'    => 'string',
				'default' => '',
			),
		);
	}

	/**
	 * @return string[]
	 */
	public static function get_block_js_dependencies(): array {
		return array(
			'wp-blocks',
			'wp-element',
			'wp-block-editor',
			'wp-components',
			'wp-server-side-render',
			'wp-i18n',
			'wp-api-fetch',
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
	public static function get_style_tag(
		Cpt_Settings $cpt_settings,
		Front_Assets $front_assets
	): string {
		// isLoaded() is false for a missing/blank id (e.g. nothing selected in the block yet).
		if ( $cpt_settings->isLoaded() &&
			// internal (e.g. shadow DOM) CSS is scoped to its own markup and inlined there instead.
			! $cpt_settings->is_css_internal() ) {
			$css = $front_assets->minify_code(
				$cpt_settings->get_css_code( Cpt_Settings::CODE_MODE_DISPLAY ),
				Front_Assets::MINIFY_TYPE_CSS
			);

			return sprintf(
				'<style data-avf-id="%s">%s</style>',
				esc_attr( $cpt_settings->get_unique_id() ),
				$css
			);
		}

		return '';
	}

	/**
	 * Keyed by the item's full unique id - not the short id used in legacy hand-typed shortcodes - so
	 * consumers (the block editor's SelectControl, the "Refresh" REST route) never need to add/strip a
	 * CPT-specific prefix themselves.
	 *
	 * @return array<string,array{title:string,editUrl:string}>
	 */
	public static function get_items_list( Cpt_Settings_Storage $settings_storage ): array {
		$list = array();

		foreach ( $settings_storage->get_unique_id_with_name_items_list() as $unique_id => $title ) {
			$list[ $unique_id ] = array(
				'title'   => $title,
				'editUrl' => $settings_storage->get( $unique_id )->get_edit_post_link( 'raw' ),
			);
		}

		return $list;
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_items_list_rest_args( Cpt_Settings_Storage $settings_storage ): array {
		return array(
			'methods'             => 'GET',
			'permission_callback' => fn(): bool => Avf_User::can_manage(),
			/**
			 * @return array<string,array{title:string,editUrl:string}>
			 */
			'callback'            => fn(): array => self::get_items_list( $settings_storage ),
		);
	}
}
