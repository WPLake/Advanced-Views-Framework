<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Loaders\Lite;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System;
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
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Post_Selection_Fs_Fields;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Query;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Selection_Markup;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Query\Builders\Selection_Query_Builder;
use Org\Wplake\Advanced_Views\Cpt\Shortcode\Post_Selection_Shortcode;
use Org\Wplake\Advanced_Views\Plugin\Loaders\Post_Selections_Loader_Base;

final class Lite_Post_Selections_Loader extends Post_Selections_Loader_Base {
	public function __construct( Lite_Plugin_Loader $base ) {
		parent::__construct();

		$query_builder         = new Selection_Query_Builder( $base->data_vendors );
		$post_query            = new Post_Query( $query_builder, $base->logger );
		$post_selection_markup = new Post_Selection_Markup(
			$base->front_assets,
			$base->engines_storage,
			$base->layout_cpt
		);
		$this->factory         = new Post_Selection_Factory(
			$base->front_assets,
			$post_query,
			$post_selection_markup,
			$base->engines_storage,
			$base->post_selections_settings_storage
		);
		$this->meta_boxes      = new Selection_Meta_Boxes(
			$base->html,
			$base->plugin,
			$base->post_selections_settings_storage,
			$base->layouts_settings_storage,
			$base->post_selection_cpt,
			$base->layout_cpt
		);
		$this->save_actions    = new Selection_Save_Actions(
			$base->logger,
			$base->post_selections_settings_storage,
			$base->plugin,
			$base->post_selection_settings,
			$base->front_assets,
			$post_selection_markup,
			$query_builder,
			$this->factory,
			$base->post_selection_cpt,
			$base->engines_storage
		);

		$this->cpt                 = new Post_Selections_Cpt(
			$base->post_selection_cpt,
			$base->post_selections_settings_storage
		);
		$this->cpt_table           = new Post_Selections_Table(
			$base->post_selections_settings_storage,
			$base->post_selection_cpt,
			$base->html,
			$this->meta_boxes,
			$base->layout_cpt
		);
		$this->fs_only_tab         = new Fs_Only_Tab(
			$this->cpt_table,
			$base->post_selections_settings_storage
		);
		$this->bulk_validation_tab = new Post_Selections_Bulk_Validation_Tab(
			$this->cpt_table,
			$base->post_selections_settings_storage,
			$this->fs_only_tab,
			$this->factory
		);

		$file_system                      = new File_System(
			$base->logger,
			$base->post_selection_cpt->folder_name(),
			$base->plugin->get_plugin_path( 'pre_built' )
		);
		$db_management                    = new Db_Management(
			$base->logger,
			$file_system,
			$base->post_selection_cpt,
			true
		);
		$post_selections_settings_storage = new Selection_Settings_Storage(
			$base->logger,
			$file_system,
			new Post_Selection_Fs_Fields( $base->engines_storage ),
			$db_management,
			$base->post_selection_settings
		);
		$this->pre_built_tab              = new Post_Selections_Pre_Built_Tab(
			$this->cpt_table,
			$base->post_selections_settings_storage,
			$post_selections_settings_storage,
			$base->data_vendors,
			$base->version_migrator,
			$base->logger,
			$base->layouts_loader->pre_built_tab
		);

		$this->git_tabs = new Selection_Git_Tabs(
			$this->cpt_table,
			$base->settings,
			$base->git_lab_api,
			$base->group_creator->create( Post_Selection_Settings::class ),
			$base->post_selections_settings_storage,
			$base->version_migrator,
			$base->layouts_loader->git_tabs,
			$base->data_vendors,
			$base->logger
		);
		$this->git_box  = new Selection_Git_Box(
			$base->post_selection_cpt->cpt_name(),
			$base->settings,
			$base->post_selections_settings_storage,
			$base->git_lab_api,
			$base->layouts_settings_storage,
			$base->layouts_loader->git_box,
			$base->plugin
		);

		$this->cpt_assets_reducer            = new Cpt_Assets_Reducer(
			$base->settings,
			$base->plugin,
			$base->post_selection_cpt->cpt_name()
		);
		$this->cpt_gutenberg_editor_settings = new Cpt_Gutenberg_Editor_Settings(
			$base->post_selection_cpt->cpt_name()
		);

		$this->layout_integration = new Selection_Layout_Integration(
			$base->post_selections_settings_storage,
			$base->layouts_settings_storage,
			$this->save_actions,
			$base->settings
		);
		$this->shortcode          = new Post_Selection_Shortcode(
			$base->post_selection_cpt,
			$base->settings,
			$base->post_selections_settings_storage,
			$base->front_assets,
			$base->live_reloader_component,
			$this->factory
		);

		$this->interactive_fields = new Selection_Interactive_Fields(
			$base->post_selection_cpt,
			$base->html,
			$base->plugin,
			$base->post_selections_settings_storage,
			$post_selection_markup,
			$this->factory,
			$base->engines_storage,
			$base->data_vendors,
			$base->settings,
			$this->meta_boxes,
			$base->layouts_settings_storage,
		);
	}
}
