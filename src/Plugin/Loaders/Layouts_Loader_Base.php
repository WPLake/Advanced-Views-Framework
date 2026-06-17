<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Loaders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Git_Box;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Git_Tabs;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Meta_Boxes;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Cpt_Table;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Plugin\Module_Loader;
use Org\Wplake\Advanced_Views\Shortcode\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode_Block;

abstract class Layouts_Loader_Base extends Module_Loader {
	public Cpt_Gutenberg_Editor_Settings $layout_cpt_gutenberg_editor_settings;
	public Layout_Meta_Boxes $layouts_cpt_meta_boxes;
	public Layouts_Cpt $layouts_cpt;
	public Layouts_Cpt_Table $layouts_cpt_table;
	public Layout_Git_Tabs $layouts_git_cpt_table_tabs;
	public Layout_Git_Box $layouts_git_meta_box;
	public Fs_Only_Tab $layouts_fs_only_tab;
	public Layouts_Bulk_Validation_Tab $layouts_bulk_validation_tab;
	public Layouts_Pre_Built_Tab $layouts_pre_built_tab;


	public Cpt_Assets_Reducer $layouts_cpt_assets_reducer;

	public Layout_Shortcode $layout_shortcode;
	public Shortcode_Block $layouts_shortcode_block;
	public Layout_Save_Actions $layouts_cpt_save_actions;
	public Layout_Factory $layout_factory;

	public function load(): void {
		$this->add_hookable(
			array(
				$this->layouts_cpt_meta_boxes,
				$this->layouts_cpt,
				$this->layouts_cpt_table,
				$this->layouts_fs_only_tab,
				$this->layouts_bulk_validation_tab,
				$this->layouts_pre_built_tab,
				$this->layout_cpt_gutenberg_editor_settings,
				$this->layouts_cpt_assets_reducer,
				$this->layouts_cpt_save_actions,
				$this->layout_shortcode,
				$this->layouts_shortcode_block,
				$this->layouts_git_meta_box,
				$this->layouts_git_cpt_table_tabs,
			)
		);

		$this->load_hookable();
	}
}
