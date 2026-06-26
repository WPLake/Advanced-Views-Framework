<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Loaders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Post_Selections_Cpt;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Selection_Git_Box;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Selection_Git_Tabs;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Selection_Interactive_Fields;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Selection_Layout_Integration;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Selection_Meta_Boxes;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Selection_Save_Actions;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Table\Post_Selections_Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Table\Post_Selections_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Table\Post_Selections_Table;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Cpt\Shortcode\Post_Selection_Shortcode;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Labels\Cpt_Labels_Base;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt_Base;
use Org\Wplake\Advanced_Views\Plugin\Module_Loader;

abstract class Post_Selections_Loader_Base extends Module_Loader {
	public Cpt_Assets_Reducer $cpt_assets_reducer;
	public Cpt_Gutenberg_Editor_Settings $cpt_gutenberg_editor_settings;
	public Post_Selections_Table $cpt_table;
	public Post_Selections_Cpt $cpt;
	public Fs_Only_Tab $fs_only_tab;
	public Selection_Meta_Boxes $meta_boxes;
	public Post_Selections_Bulk_Validation_Tab $bulk_validation_tab;
	public Post_Selections_Pre_Built_Tab $pre_built_tab;
	public Selection_Layout_Integration $layout_integration;
	public Post_Selection_Shortcode $shortcode;
	public Selection_Save_Actions $save_actions;
	public Selection_Git_Tabs $git_tabs;
	public Selection_Git_Box $git_box;
	public Post_Selection_Factory $factory;
	public Selection_Interactive_Fields $interactive_fields;

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
				$this->cpt,
				$this->cpt_table,
				$this->fs_only_tab,
				$this->bulk_validation_tab,
				$this->pre_built_tab,
				$this->cpt_assets_reducer,
				$this->cpt_gutenberg_editor_settings,
				$this->meta_boxes,
				$this->save_actions,
				$this->layout_integration,
				$this->shortcode,
				$this->git_tabs,
				$this->git_box,
				$this->interactive_fields,
			)
		);

		$this->load_hookable();
	}
}
