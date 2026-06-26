<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Loaders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layout_Git_Box;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layout_Git_Tabs;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layout_Interactive_Fields;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layout_Meta_Boxes;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layout_Save_Actions;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layouts_Cpt;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Table\Layouts_Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Table\Layouts_Cpt_Table;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Table\Layouts_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Cpt\Shortcode\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Cpt\Shortcode\Shortcode_Block;
use Org\Wplake\Advanced_Views\Plugin\Module_Loader;

abstract class Layouts_Loader_Base extends Module_Loader {
	public Cpt_Gutenberg_Editor_Settings $cpt_gutenberg_editor_settings;
	public Layout_Meta_Boxes $cpt_meta_boxes;
	public Layouts_Cpt $cpt;
	public Layouts_Cpt_Table $cpt_table;
	public Layout_Git_Tabs $git_tabs;
	public Layout_Git_Box $git_box;
	public Fs_Only_Tab $fs_only_tab;
	public Layouts_Bulk_Validation_Tab $bulk_validation_tab;
	public Layouts_Pre_Built_Tab $pre_built_tab;


	public Cpt_Assets_Reducer $cpt_assets_reducer;

	public Layout_Shortcode $shortcode;
	public Shortcode_Block $shortcode_block;
	public Layout_Save_Actions $save_actions;
	public Layout_Factory $factory;
	public Layout_Interactive_Fields $interactive_fields;

	public function load(): void {
		$this->add_hookable(
			array(
				$this->cpt_meta_boxes,
				$this->cpt,
				$this->cpt_table,
				$this->fs_only_tab,
				$this->bulk_validation_tab,
				$this->pre_built_tab,
				$this->cpt_gutenberg_editor_settings,
				$this->cpt_assets_reducer,
				$this->save_actions,
				$this->shortcode,
				$this->shortcode_block,
				$this->git_box,
				$this->git_tabs,
				$this->interactive_fields,
			)
		);

		$this->load_hookable();
	}
}
