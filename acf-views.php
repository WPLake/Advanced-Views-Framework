<?php
/**
 * Plugin Name: Advanced Views Lite
 * Plugin URI: https://wplake.org/advanced-views-lite/
 * Description: Effortlessly display WordPress posts, custom fields, and WooCommerce data.
 * Version: 3.7.22
 * Author: WPLake
 * Author URI: https://wplake.org/advanced-views-lite/
 * Text Domain: acf-views
 * Domain Path: /src/lang
 */

namespace Org\Wplake\Advanced_Views;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Acf_Dependency;
use Org\Wplake\Advanced_Views\Acf\Acf_Internal_Features;
use Org\Wplake\Advanced_Views\Assets\Admin_Assets;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migrator;
use Org\Wplake\Advanced_Views\Plugin\Plugin_Environment;
use Org\Wplake\Advanced_Views\Plugin\Plugin_Loader_Base;
use Org\Wplake\Advanced_Views\Post_Selections\{Post_Selection_Factory,
	Post_Selection_Markup,
	Cpt\Post_Selections_Cpt,
	Cpt\Post_Selections_Cpt_Meta_Boxes,
	Cpt\Post_Selections_Cpt_Save_Actions,
	Cpt\Post_Selections_View_Integration,
	Cpt\Table\Post_Selections_Bulk_Validation_Tab,
	Cpt\Table\Post_Selections_Cpt_Table,
	Cpt\Table\Post_Selections_Pre_Built_Tab,
	Data_Storage\Post_Selection_Fs_Fields,
	Data_Storage\Post_Selections_Settings_Storage,
	Query_Builder};
use Org\Wplake\Advanced_Views\Dashboard\Admin_Bar;
use Org\Wplake\Advanced_Views\Dashboard\Dashboard;
use Org\Wplake\Advanced_Views\Tools\Debug_Dump_Creator;
use Org\Wplake\Advanced_Views\Tools\Demo_Import;
use Org\Wplake\Advanced_Views\Dashboard\Live_Reloader;
use Org\Wplake\Advanced_Views\Dashboard\Settings_Page;
use Org\Wplake\Advanced_Views\Tools\Tools;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups_Integration\{
	Post_Selection_Settings_Integration,
	Custom_Acf_Field_Types,
	Field_Settings_Integration,
	Item_Settings_Integration,
	Meta_Field_Settings_Integration,
	Mount_Point_Settings_Integration,
	Tax_Field_Settings_Integration,
	Tools_Settings_Integration,
	Layout_Settings_Integration,
};
use Org\Wplake\Advanced_Views\Groups\{Post_Selection_Settings,
	Git_Repository,
	Item_Settings,
	Plugin_Settings,
	Tools_Settings,
	Layout_Settings};
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Fs_Fields;
use Org\Wplake\Advanced_Views\Shortcode\Post_Selection_Shortcode;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode_Block;
use Org\Wplake\Advanced_Views\Shortcode\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Creator;
use Org\Wplake\Advanced_Views\Layouts\{Cpt\Table\Layouts_Bulk_Validation_Tab,
	Cpt\Table\Layouts_Cpt_Table,
	Cpt\Table\Layouts_Pre_Built_Tab,
	Cpt\Layouts_Cpt,
	Cpt\Layouts_Cpt_Meta_Boxes,
	Cpt\Layouts_Cpt_Save_Actions,
	Data_Storage\Layouts_Settings_Storage,
	Fields\Field_Markup,
	Layout_Factory,
	Layout_Markup};

( function () {
	// omit loading if the Pro version is already loaded.
	if ( class_exists( Plugin::class ) ) {
		return;
	}

	require_once __DIR__ . '/src/autoloader.php';

	$plugin_loader = new class() extends Plugin_Loader_Base {
		private Html $html;
		private Layout_Settings $layout_settings;
		private Post_Selection_Settings $post_selection_settings;
		private Post_Selection_Factory $post_selection_factory;
		private Options $options;

		protected function primary(): void {
			$this->layout_cpt         = self::make_layout_cpt();
			$this->post_selection_cpt = self::make_post_selection_cpt();

			$this->options  = new Options();
			$this->settings = new Settings( $this->options );

			$uploads_folder = self::uploads_folder();
			$this->logger   = new Logger( $uploads_folder, $this->settings );

			$this->group_creator           = new Creator();
			$this->layout_settings         = $this->group_creator->create( Layout_Settings::class );
			$this->post_selection_settings = $this->group_creator->create( Post_Selection_Settings::class );

			$this->html = new Html();

			$post_selections_file_system            = new File_System(
				$this->logger,
				$this->post_selection_cpt->folder_name()
			);
			$this->post_selections_settings_storage = new Post_Selections_Settings_Storage(
				$this->logger,
				$post_selections_file_system,
				new Post_Selection_Fs_Fields(),
				new Db_Management( $this->logger, $post_selections_file_system, $this->post_selection_cpt ),
				$this->post_selection_settings
			);

			$layouts_file_system            = new File_System( $this->logger, $this->layout_cpt->folder_name() );
			$this->layouts_settings_storage = new Layouts_Settings_Storage(
				$this->logger,
				$layouts_file_system,
				new Fs_Fields(),
				new Db_Management( $this->logger, $layouts_file_system, $this->layout_cpt ),
				$this->layout_settings
			);

			$this->plugin           = new Plugin( __FILE__, $this->options, $this->settings );
			$this->template_engines = new Template_Engines(
				$uploads_folder,
				$this->logger,
				$this->plugin,
				$this->settings
			);
			$this->item_settings    = $this->group_creator->create( Item_Settings::class );

			$this->data_vendors            = new Data_Vendors( $this->logger );
			$this->live_reloader_component = new Live_Reloader_Component( $this->plugin, $this->settings );
			$this->front_assets            = new Front_Assets(
				$this->plugin,
				$this->data_vendors,
				$layouts_file_system,
				$this->live_reloader_component
			);
			$this->version_migrator        = new Version_Migrator(
				$this->plugin,
				$this->settings,
			);

			$this->add_file_systems(
				array(
					$layouts_file_system,
					$post_selections_file_system,
				)
			);

			parent::primary();
		}

		protected function layouts(): void {
			$field_markup  = new Field_Markup( $this->data_vendors, $this->front_assets, $this->template_engines );
			$layout_markup = new Layout_Markup( $field_markup, $this->data_vendors, $this->template_engines );

			$this->layout_factory           = new Layout_Factory(
				$this->front_assets,
				$this->layouts_settings_storage,
				$layout_markup,
				$this->template_engines,
				$field_markup,
				$this->data_vendors
			);
			$this->layouts_cpt_meta_boxes   = new Layouts_Cpt_Meta_Boxes(
				$this->html,
				$this->plugin,
				$this->layouts_settings_storage,
				$this->data_vendors,
				$this->layout_cpt
			);
			$this->layouts_cpt_save_actions = new Layouts_Cpt_Save_Actions(
				$this->logger,
				$this->layouts_settings_storage,
				$this->plugin,
				$this->layout_settings,
				$this->front_assets,
				$layout_markup,
				$this->layouts_cpt_meta_boxes,
				$this->html,
				$this->layout_factory,
				$this->layout_cpt
			);

			$this->layouts_cpt                 = new Layouts_Cpt( $this->layout_cpt, $this->layouts_settings_storage );
			$this->layouts_cpt_table           = new Layouts_Cpt_Table(
				$this->layouts_settings_storage,
				$this->layout_cpt,
				$this->html,
				$this->layouts_cpt_meta_boxes
			);
			$this->layouts_fs_only_tab         = new Fs_Only_Tab( $this->layouts_cpt_table, $this->layouts_settings_storage );
			$this->layouts_bulk_validation_tab = new Layouts_Bulk_Validation_Tab(
				$this->layouts_cpt_table,
				$this->layouts_settings_storage,
				$this->layouts_fs_only_tab,
				$this->layout_factory
			);

			$file_system                 = new File_System(
				$this->logger,
				$this->layout_cpt->folder_name(),
				$this->plugin->get_plugin_path( 'src/pre_built' )
			);
			$db_management               = new Db_Management(
				$this->logger,
				$file_system,
				$this->layout_cpt,
				true
			);
			$layouts_settings_storage    = new Layouts_Settings_Storage(
				$this->logger,
				$file_system,
				new Fs_Fields(),
				$db_management,
				$this->layout_settings
			);
			$this->layouts_pre_built_tab = new Layouts_Pre_Built_Tab(
				$this->layouts_cpt_table,
				$this->layouts_settings_storage,
				$layouts_settings_storage,
				$this->data_vendors,
				$this->version_migrator,
				$this->logger
			);

			$this->layouts_cpt_assets_reducer           = new Cpt_Assets_Reducer( $this->settings, $this->layout_cpt->cpt_name() );
			$this->layout_cpt_gutenberg_editor_settings = new Cpt_Gutenberg_Editor_Settings( $this->layout_cpt->cpt_name() );
			$this->layouts_shortcode_block              = new Shortcode_Block( $this->layout_cpt->shortcodes() );

			$this->layout_shortcode = new Layout_Shortcode(
				$this->layout_cpt,
				$this->settings,
				$this->layouts_settings_storage,
				$this->front_assets,
				$this->live_reloader_component,
				$this->layout_factory,
				$this->layouts_shortcode_block
			);

			parent::layouts();
		}

		protected function post_selections(): void {
			$query_builder                          = new Query_Builder( $this->data_vendors, $this->logger );
			$post_selection_markup                  = new Post_Selection_Markup(
				$this->front_assets,
				$this->template_engines,
				$this->layout_cpt
			);
			$this->post_selection_factory           = new Post_Selection_Factory(
				$this->front_assets,
				$query_builder,
				$post_selection_markup,
				$this->template_engines,
				$this->post_selections_settings_storage
			);
			$this->post_selections_cpt_meta_boxes   = new Post_Selections_Cpt_Meta_Boxes(
				$this->html,
				$this->plugin,
				$this->post_selections_settings_storage,
				$this->layouts_settings_storage,
				$this->post_selection_cpt
			);
			$this->post_selections_cpt_save_actions = new Post_Selections_Cpt_Save_Actions(
				$this->logger,
				$this->post_selections_settings_storage,
				$this->plugin,
				$this->post_selection_settings,
				$this->front_assets,
				$post_selection_markup,
				$query_builder,
				$this->html,
				$this->post_selections_cpt_meta_boxes,
				$this->post_selection_factory,
				$this->post_selection_cpt
			);

			$this->post_selections_cpt                 = new Post_Selections_Cpt(
				$this->post_selection_cpt,
				$this->post_selections_settings_storage
			);
			$this->post_selections_cpt_table           = new Post_Selections_Cpt_Table(
				$this->post_selections_settings_storage,
				$this->post_selection_cpt,
				$this->html,
				$this->post_selections_cpt_meta_boxes
			);
			$this->post_selections_fs_only_tab         = new Fs_Only_Tab(
				$this->post_selections_cpt_table,
				$this->post_selections_settings_storage
			);
			$this->post_selections_bulk_validation_tab = new Post_Selections_Bulk_Validation_Tab(
				$this->post_selections_cpt_table,
				$this->post_selections_settings_storage,
				$this->post_selections_fs_only_tab,
				$this->post_selection_factory
			);

			$file_system                         = new File_System(
				$this->logger,
				$this->post_selection_cpt->folder_name(),
				$this->plugin->get_plugin_path( 'src/pre_built' )
			);
			$db_management                       = new Db_Management(
				$this->logger,
				$file_system,
				$this->post_selection_cpt,
				true
			);
			$post_selections_settings_storage    = new Post_Selections_Settings_Storage(
				$this->logger,
				$file_system,
				new Post_Selection_Fs_Fields(),
				$db_management,
				$this->post_selection_settings
			);
			$this->post_selections_pre_built_tab = new Post_Selections_Pre_Built_Tab(
				$this->post_selections_cpt_table,
				$this->post_selections_settings_storage,
				$post_selections_settings_storage,
				$this->data_vendors,
				$this->version_migrator,
				$this->logger,
				$this->layouts_pre_built_tab
			);

			$this->post_selections_cpt_assets_reducer           = new Cpt_Assets_Reducer(
				$this->settings,
				$this->post_selection_cpt->cpt_name()
			);
			$this->post_selection_cpt_gutenberg_editor_settings = new Cpt_Gutenberg_Editor_Settings(
				$this->post_selection_cpt->cpt_name()
			);

			$this->post_selections_view_integration = new Post_Selections_View_Integration(
				$this->post_selections_settings_storage,
				$this->layouts_settings_storage,
				$this->post_selections_cpt_save_actions,
				$this->settings
			);
			$this->post_selection_shortcode         = new Post_Selection_Shortcode(
				$this->post_selection_cpt,
				$this->settings,
				$this->post_selections_settings_storage,
				$this->front_assets,
				$this->live_reloader_component,
				$this->post_selection_factory
			);

			parent::post_selections();
		}

		protected function integration( Current_Screen $current_screen ): void {
			$this->acf_dependency = new Acf_Dependency( $this->plugin );

			$this->layout_settings_integration         = new Layout_Settings_Integration(
				$this->layout_cpt->cpt_name(),
				$this->data_vendors
			);
			$this->field_settings_integration          = new Field_Settings_Integration(
				$this->layout_cpt->cpt_name(),
				$this->data_vendors
			);
			$this->post_selection_settings_integration = new Post_Selection_Settings_Integration(
				$this->post_selection_cpt->cpt_name(),
				$this->data_vendors
			);
			$this->item_settings_integration           = new Item_Settings_Integration(
				$this->layout_cpt->cpt_name(),
				$this->data_vendors
			);
			// metaField is a part of the Meta Filter, so we use 'cardsCpt' here.
			$this->meta_field_settings_integration        = new Meta_Field_Settings_Integration(
				$this->post_selection_cpt->cpt_name(),
				$this->data_vendors
			);
			$this->layout_mount_point_integration         = new Mount_Point_Settings_Integration(
				$this->layout_cpt->cpt_name()
			);
			$this->post_selection_mount_point_integration = new Mount_Point_Settings_Integration(
				$this->post_selection_cpt->cpt_name()
			);
			$this->tax_field_settings_integration         = new Tax_Field_Settings_Integration(
				$this->post_selection_cpt->cpt_name(),
				$this->data_vendors
			);
			$this->tools_settings_integration             = new Tools_Settings_Integration(
				$this->layouts_settings_storage,
				$this->post_selections_settings_storage
			);
			$this->custom_acf_field_types                 = new Custom_Acf_Field_Types( $this->layouts_settings_storage );

			parent::integration( $current_screen );
		}

		protected function others(): void {
			$this->demo_import = new Demo_Import(
				$this->post_selections_cpt_save_actions,
				$this->layouts_cpt_save_actions,
				$this->post_selections_settings_storage,
				$this->layouts_settings_storage,
				$this->settings,
				$this->item_settings
			);

			$this->dashboard             = new Dashboard( $this->plugin, $this->html, $this->demo_import );
			$this->acf_internal_features = new Acf_Internal_Features( $this->plugin );

			$tools_settings     = new Tools_Settings( $this->group_creator );
			$debug_dump_creator = new Debug_Dump_Creator(
				$tools_settings,
				$this->logger,
				$this->layouts_settings_storage,
				$this->post_selections_settings_storage
			);
			$this->tools        = new Tools(
				$tools_settings,
				$this->post_selections_settings_storage,
				$this->layouts_settings_storage,
				$this->plugin,
				$this->logger,
				$debug_dump_creator
			);

			$this->automatic_reports = new Automatic_Reports(
				$this->logger,
				$this->plugin,
				$this->settings,
				$this->options,
				$this->layouts_settings_storage
			);
			$this->settings_page     = new Settings_Page(
				$this->logger,
				new Plugin_Settings( $this->group_creator ),
				$this->settings,
				$this->layouts_settings_storage,
				$this->post_selections_settings_storage,
				$this->group_creator->create( Git_Repository::class ),
				$this->automatic_reports
			);

			$this->admin_assets = new Admin_Assets(
				$this->plugin,
				$this->post_selections_settings_storage,
				$this->layouts_settings_storage,
				$this->layout_factory,
				$this->post_selection_factory,
				$this->data_vendors
			);

			$this->live_reloader = new Live_Reloader(
				$this->layouts_settings_storage,
				$this->post_selections_settings_storage,
				$this->layout_shortcode,
				$this->post_selection_shortcode
			);

			$this->admin_bar = new Admin_Bar(
				$this->layout_shortcode,
				$this->post_selection_shortcode,
				$this->live_reloader_component,
				$this->settings
			);

			parent::others();
		}

		protected function environment(): void {
			$this->plugin_environment = new Plugin_Environment(
				$this->template_engines,
				$this->automatic_reports,
				$this->settings,
				$this->file_systems,
				array( $this->layouts_settings_storage, $this->post_selections_settings_storage )
			);
		}
	};

	$plugin_loader->load_plugin();
} )();
