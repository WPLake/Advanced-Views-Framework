<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Loaders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Labels\Cpt_Labels_Base;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt_Base;
use Org\Wplake\Advanced_Views\Plugin\Module_Loader;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Selection_Git_Box;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Selection_Git_Tabs;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Selection_Layout_Integration;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Selection_Meta_Boxes;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Selection_Save_Actions;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table\Post_Selections_Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table\Post_Selections_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table\Post_Selections_Table;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Shortcode\Post_Selection_Shortcode;

abstract class Post_Selections_Loader_Base extends Module_Loader {
	// fixme rename me to shorter
	public Cpt_Assets_Reducer $post_selections_cpt_assets_reducer;
	public Cpt_Gutenberg_Editor_Settings $post_selection_cpt_gutenberg_editor_settings;
	public Post_Selections_Table $post_selections_cpt_table;
	public Post_Selections_Cpt $post_selections_cpt;
	public Fs_Only_Tab $post_selections_fs_only_tab;
	public Selection_Meta_Boxes $post_selections_cpt_meta_boxes;
	public Post_Selections_Bulk_Validation_Tab $post_selections_bulk_validation_tab;
	public Post_Selections_Pre_Built_Tab $post_selections_pre_built_tab;
	public Selection_Layout_Integration $post_selections_view_integration;
	public Post_Selection_Shortcode $post_selection_shortcode;
	public Selection_Save_Actions $post_selections_cpt_save_actions;
	public Selection_Git_Tabs $post_selection_git_tabs;
	public Selection_Git_Box $post_selection_git_meta_box;
	public Post_Selection_Factory $post_selection_factory;

	public static function make_post_selection_cpt(): Public_Cpt {
		$public_cpt_base = new Public_Cpt_Base();

		$public_cpt_base->cpt_name    = Hard_Post_Selection_Cpt::cpt_name();
		$public_cpt_base->slug_prefix = 'card_';
		$public_cpt_base->folder_name = 'post-selections';

		$public_cpt_base->shortcode        = 'avf-post-selection';
		$public_cpt_base->shortcodes       = array( $public_cpt_base->shortcode, 'avf_card', 'acf_cards' );
		$public_cpt_base->rest_route_names = array( 'post-selection', 'card' );

		$public_cpt_base->labels = new class() extends Cpt_Labels_Base {
			public function singular_name(): string {
				return esc_html__( 'Post Selection', 'acf-views' );
			}

			public function plural_name(): string {
				return esc_html__( 'Post Selections', 'acf-views' );
			}
		};

		return $public_cpt_base;
	}

	public function load(): void {
		$this->add_hookable(
			array(
				$this->post_selections_cpt,
				$this->post_selections_cpt_table,
				$this->post_selections_fs_only_tab,
				$this->post_selections_bulk_validation_tab,
				$this->post_selections_pre_built_tab,
				$this->post_selections_cpt_assets_reducer,
				$this->post_selection_cpt_gutenberg_editor_settings,
				$this->post_selections_cpt_meta_boxes,
				$this->post_selections_cpt_save_actions,
				$this->post_selections_view_integration,
				$this->post_selection_shortcode,
				$this->post_selection_git_tabs,
				$this->post_selection_git_meta_box,
			)
		);

		$this->load_hookable();
	}
}
