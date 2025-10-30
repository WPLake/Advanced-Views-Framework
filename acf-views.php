<?php
/**
 * Plugin Name: Advanced Views Lite
 * Plugin URI: https://wplake.org/advanced-views-lite/
 * Description: Effortlessly display WordPress posts, custom fields, and WooCommerce data.
 * Version: 3.7.21
 * Author: WPLake
 * Author URI: https://wplake.org/advanced-views-lite/
 * Text Domain: acf-views
 * Domain Path: /src/lang
 */

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Acf\Acf_Dependency;
use Org\Wplake\Advanced_Views\Acf\Acf_Internal_Features;
use Org\Wplake\Advanced_Views\Assets\Admin_Assets;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Bridge\Advanced_Views;
use Org\Wplake\Advanced_Views\Compatibility\Migration\V_3\Migration_3_3_0;
use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Org\Wplake\Advanced_Views\Features\Post_Selections_Feature;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Migrator;
use Org\Wplake\Advanced_Views\Compatibility\Migration\V_1\{Migration_1_6_0, Migration_1_7_0};
use Org\Wplake\Advanced_Views\Compatibility\Migration\V_2\{Migration_2_1_0,
	Migration_2_2_2,
	Migration_2_2_3,
	Migration_2_0_0,
	Migration_2_2_0,
	Migration_2_3_0,
	Migration_2_4_0,
	Migration_2_4_2,
	Migration_2_4_5};
use Org\Wplake\Advanced_Views\Compatibility\Migration\V_3\Migration_3_0_0;
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
	Field_Settings,
	Git_Repository,
	Item_Settings,
	Repeater_Field_Settings,
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
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Loader as GroupsLoader;
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

defined( 'ABSPATH' ) || exit;

$acf_views = new class() {
	private Html $html;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Template_Engines $template_engines;
	private Plugin $plugin;
	private Item_Settings $item_settings;
	private Options $options;
	private Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;
	private Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions;
	private Layout_Factory $layout_factory;
	private Post_Selection_Factory $post_selection_factory;
	private Layout_Settings $layout_settings;
	private Post_Selection_Settings $post_selection_settings;
	private Creator $group_creator;
	private Settings $settings;
	private Front_Assets $front_assets;
	private Data_Vendors $data_vendors;
	private Layout_Shortcode $layout_shortcode;
	private Post_Selection_Shortcode $post_selection_shortcode;
	private Migrator $migrator;
	private Automatic_Reports $automatic_reports;
	private Logger $logger;
	private Layouts_Pre_Built_Tab $layouts_pre_built_tab;
	private Live_Reloader_Component $live_reloader_component;

	private function load_translations( Current_Screen $current_screen ): void {
		// on the whole admin area, as menu items need translations.
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action(
			'init',
			function (): void {
				load_plugin_textdomain( 'acf-views', false, dirname( plugin_basename( __FILE__ ) ) . '/src/lang' );
			},
			// make sure it's before acf_groups.
			8
		);
	}

	private function acf_groups( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_ajax() &&
			false === $current_screen->is_admin_cpt_related( Layouts_Feature::cpt_name() ) &&
			false === $current_screen->is_admin_cpt_related( Post_Selections_Feature::cpt_name() ) ) {
			return;
		}

		add_action(
			'acf/init',
			function (): void {
				$loader = new GroupsLoader();
				$loader->signUpGroups(
					'Org\Wplake\Advanced_Views\Groups',
					__DIR__ . '/src/Groups'
				);
			},
			// make sure it's after translations.
			9
		);
	}

	private function primary( Current_Screen $current_screen ): void {
		$this->options  = new Options();
		$this->settings = new Settings( $this->options );

		$uploads_folder = wp_upload_dir()['basedir'] . '/acf-views';
		$this->logger   = new Logger( $uploads_folder, $this->settings );

		$this->group_creator           = new Creator();
		$this->layout_settings         = $this->group_creator->create( Layout_Settings::class );
		$this->post_selection_settings = $this->group_creator->create( Post_Selection_Settings::class );

		$this->html = new Html();

		$cards_file_system                      = new File_System( $this->logger, Post_Selections_Feature::folder_name() );
		$this->post_selections_settings_storage = new Post_Selections_Settings_Storage(
			$this->logger,
			$cards_file_system,
			new Post_Selection_Fs_Fields(),
			new Db_Management( $this->logger, $cards_file_system, Post_Selections_Feature::cpt_name(), Post_Selections_Feature::slug_prefix() ),
			$this->post_selection_settings
		);

		$views_file_system              = new File_System( $this->logger, Layouts_Feature::folder_name() );
		$this->layouts_settings_storage = new Layouts_Settings_Storage(
			$this->logger,
			$views_file_system,
			new Fs_Fields(),
			new Db_Management( $this->logger, $views_file_system, Layouts_Feature::cpt_name(), Layouts_Feature::slug_prefix() ),
			$this->layout_settings
		);

		$this->plugin           = new Plugin( __FILE__, $this->options, $this->settings );
		$this->template_engines = new Template_Engines( $uploads_folder, $this->logger, $this->plugin, $this->settings );
		$this->item_settings    = $this->group_creator->create( Item_Settings::class );

		$this->data_vendors            = new Data_Vendors( $this->logger );
		$this->live_reloader_component = new Live_Reloader_Component( $this->plugin, $this->settings );
		$this->front_assets            = new Front_Assets(
			$this->plugin,
			$this->data_vendors,
			$views_file_system,
			$this->live_reloader_component
		);
		$this->migrator                = new Migrator(
			$this->plugin,
			$this->settings,
			$this->logger
		);

		// it's a hack, but there is no other way to pass data (constructor is always called automatically).
		Field_Settings::set_data_vendors( $this->data_vendors );

		$this->logger->set_hooks( $current_screen );
		$this->plugin->set_hooks( $current_screen );
		$this->template_engines->set_hooks( $current_screen );
		$this->front_assets->set_hooks( $current_screen );
		$this->data_vendors->set_hooks( $current_screen );
		$cards_file_system->set_hooks( $current_screen );
		$views_file_system->set_hooks( $current_screen );
		$this->live_reloader_component->set_hooks( $current_screen );
		$this->migrator->set_hooks( $current_screen );
	}

	private function views( Current_Screen $current_screen ): void {
		$field_markup                   = new Field_Markup( $this->data_vendors, $this->front_assets, $this->template_engines );
		$layout_markup                  = new Layout_Markup( $field_markup, $this->data_vendors, $this->template_engines );
		$this->layout_factory           = new Layout_Factory(
			$this->front_assets,
			$this->layouts_settings_storage,
			$layout_markup,
			$this->template_engines,
			$field_markup,
			$this->data_vendors
		);
		$layouts_cpt_meta_boxes         = new Layouts_Cpt_Meta_Boxes(
			$this->html,
			$this->plugin,
			$this->layouts_settings_storage,
			$this->data_vendors
		);
		$this->layouts_cpt_save_actions = new Layouts_Cpt_Save_Actions(
			$this->logger,
			$this->layouts_settings_storage,
			$this->plugin,
			$this->layout_settings,
			$this->front_assets,
			$layout_markup,
			$layouts_cpt_meta_boxes,
			$this->html,
			$this->layout_factory
		);

		$layouts_cpt                 = new Layouts_Cpt( new Layouts_Feature(), $this->layouts_settings_storage );
		$layouts_cpt_table           = new Layouts_Cpt_Table(
			$this->layouts_settings_storage,
			Layouts_Feature::cpt_name(),
			$this->html,
			$layouts_cpt_meta_boxes
		);
		$fs_only_tab                 = new Fs_Only_Tab( $layouts_cpt_table, $this->layouts_settings_storage );
		$layouts_bulk_validation_tab = new Layouts_Bulk_Validation_Tab(
			$layouts_cpt_table,
			$this->layouts_settings_storage,
			$fs_only_tab,
			$this->layout_factory
		);

		$file_system                 = new File_System(
			$this->logger,
			'views',
			__DIR__ . '/src/pre_built'
		);
		$db_management               = new Db_Management(
			$this->logger,
			$file_system,
			Layouts_Feature::cpt_name(),
			'view_',
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
			$layouts_cpt_table,
			$this->layouts_settings_storage,
			$layouts_settings_storage,
			$this->data_vendors,
			$this->migrator,
			$this->logger
		);

		$cpt_assets_reducer            = new Cpt_Assets_Reducer( $this->settings, Layouts_Feature::cpt_name() );
		$cpt_gutenberg_editor_settings = new Cpt_Gutenberg_Editor_Settings( Layouts_Feature::cpt_name() );
		$shortcode_block               = new Shortcode_Block( Layouts_Feature::shortcodes() );

		$this->layout_shortcode = new Layout_Shortcode(
			new Layouts_Feature(),
			$this->settings,
			$this->layouts_settings_storage,
			$this->front_assets,
			$this->live_reloader_component,
			$this->layout_factory,
			$shortcode_block
		);

		$layouts_cpt_meta_boxes->set_hooks( $current_screen );
		$layouts_cpt->set_hooks( $current_screen );
		$layouts_cpt_table->set_hooks( $current_screen );
		$fs_only_tab->set_hooks( $current_screen );
		$layouts_bulk_validation_tab->set_hooks( $current_screen );
		$this->layouts_pre_built_tab->set_hooks( $current_screen );
		$cpt_gutenberg_editor_settings->set_hooks( $current_screen );
		$cpt_assets_reducer->set_hooks( $current_screen );
		$this->layouts_cpt_save_actions->set_hooks( $current_screen );
		$this->layout_shortcode->set_hooks( $current_screen );
		$shortcode_block->set_hooks( $current_screen );
	}

	private function cards( Current_Screen $current_screen ): void {
		$query_builder                          = new Query_Builder( $this->data_vendors, $this->logger );
		$post_selection_markup                  = new Post_Selection_Markup( $this->front_assets, $this->template_engines );
		$this->post_selection_factory           = new Post_Selection_Factory(
			$this->front_assets,
			$query_builder,
			$post_selection_markup,
			$this->template_engines,
			$this->post_selections_settings_storage
		);
		$post_selections_cpt_meta_boxes         = new Post_Selections_Cpt_Meta_Boxes(
			$this->html,
			$this->plugin,
			$this->post_selections_settings_storage,
			$this->layouts_settings_storage
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
			$post_selections_cpt_meta_boxes,
			$this->post_selection_factory
		);

		$post_selections_cpt                 = new Post_Selections_Cpt(
			new Post_Selections_Feature(),
			$this->post_selections_settings_storage
		);
		$post_selections_cpt_table           = new Post_Selections_Cpt_Table(
			$this->post_selections_settings_storage,
			Post_Selections_Feature::cpt_name(),
			$this->html,
			$post_selections_cpt_meta_boxes
		);
		$fs_only_tab                         = new Fs_Only_Tab( $post_selections_cpt_table, $this->post_selections_settings_storage );
		$post_selections_bulk_validation_tab = new Post_Selections_Bulk_Validation_Tab(
			$post_selections_cpt_table,
			$this->post_selections_settings_storage,
			$fs_only_tab,
			$this->post_selection_factory
		);

		$file_system                      = new File_System(
			$this->logger,
			'cards',
			__DIR__ . '/src/pre_built'
		);
		$db_management                    = new Db_Management(
			$this->logger,
			$file_system,
			Post_Selections_Feature::cpt_name(),
			'card_',
			true
		);
		$post_selections_settings_storage = new Post_Selections_Settings_Storage(
			$this->logger,
			$file_system,
			new Post_Selection_Fs_Fields(),
			$db_management,
			$this->post_selection_settings
		);
		$post_selections_pre_built_tab    = new Post_Selections_Pre_Built_Tab(
			$post_selections_cpt_table,
			$this->post_selections_settings_storage,
			$post_selections_settings_storage,
			$this->data_vendors,
			$this->migrator,
			$this->logger,
			$this->layouts_pre_built_tab
		);

		$cpt_assets_reducer            = new Cpt_Assets_Reducer( $this->settings, Post_Selections_Feature::cpt_name() );
		$cpt_gutenberg_editor_settings = new Cpt_Gutenberg_Editor_Settings( Post_Selections_Feature::cpt_name() );

		$post_selections_view_integration = new Post_Selections_View_Integration(
			$this->post_selections_settings_storage,
			$this->layouts_settings_storage,
			$this->post_selections_cpt_save_actions,
			$this->settings
		);
		$this->post_selection_shortcode   = new Post_Selection_Shortcode(
			new Post_Selections_Feature(),
			$this->settings,
			$this->post_selections_settings_storage,
			$this->front_assets,
			$this->live_reloader_component,
			$this->post_selection_factory
		);

		$post_selections_cpt->set_hooks( $current_screen );
		$post_selections_cpt_table->set_hooks( $current_screen );
		$fs_only_tab->set_hooks( $current_screen );
		$post_selections_bulk_validation_tab->set_hooks( $current_screen );
		$post_selections_pre_built_tab->set_hooks( $current_screen );
		$cpt_assets_reducer->set_hooks( $current_screen );
		$cpt_gutenberg_editor_settings->set_hooks( $current_screen );
		$post_selections_cpt_meta_boxes->set_hooks( $current_screen );
		$this->post_selections_cpt_save_actions->set_hooks( $current_screen );
		$post_selections_view_integration->set_hooks( $current_screen );
		$this->post_selection_shortcode->set_hooks( $current_screen );
	}

	private function integration( Current_Screen $current_screen ): void {
		$acf_dependency = new Acf_Dependency( $this->plugin );

		$layout_settings_integration         = new Layout_Settings_Integration(
			Layouts_Feature::cpt_name(),
			$this->data_vendors
		);
		$field_settings_integration          = new Field_Settings_Integration(
			Layouts_Feature::cpt_name(),
			$this->data_vendors
		);
		$post_selection_settings_integration = new Post_Selection_Settings_Integration(
			Post_Selections_Feature::cpt_name(),
			$this->data_vendors
		);
		$item_settings_integration           = new Item_Settings_Integration( Layouts_Feature::cpt_name(), $this->data_vendors );
		// metaField is a part of the Meta Filter, so we use 'cardsCpt' here.
		$meta_field_settings_integration = new Meta_Field_Settings_Integration( Post_Selections_Feature::cpt_name(), $this->data_vendors );
		$views_mount_point_integration   = new Mount_Point_Settings_Integration( Layouts_Feature::cpt_name() );
		$cards_mount_point_integration   = new Mount_Point_Settings_Integration( Post_Selections_Feature::cpt_name() );
		$tax_field_settings_integration  = new Tax_Field_Settings_Integration( Post_Selections_Feature::cpt_name(), $this->data_vendors );
		$tools_settings_integration      = new Tools_Settings_Integration(
			$this->layouts_settings_storage,
			$this->post_selections_settings_storage
		);
		$custom_acf_field_types          = new Custom_Acf_Field_Types( $this->layouts_settings_storage );

		$acf_dependency->set_hooks( $current_screen );

		$layout_settings_integration->set_hooks( $current_screen );
		$field_settings_integration->set_hooks( $current_screen );
		$post_selection_settings_integration->set_hooks( $current_screen );
		$item_settings_integration->set_hooks( $current_screen );
		$meta_field_settings_integration->set_hooks( $current_screen );
		$views_mount_point_integration->set_hooks( $current_screen );
		$cards_mount_point_integration->set_hooks( $current_screen );
		$tax_field_settings_integration->set_hooks( $current_screen );
		$tools_settings_integration->set_hooks( $current_screen );
		$custom_acf_field_types->set_hooks( $current_screen );

		// only now, when views() are called.
		$this->data_vendors->make_integration_instances(
			$current_screen,
			$this->item_settings,
			$this->layouts_settings_storage,
			$this->layouts_cpt_save_actions,
			$this->layout_factory,
			$this->group_creator->create( Repeater_Field_Settings::class ),
			$this->layout_shortcode,
			$this->settings
		);
	}

	private function others( Current_Screen $current_screen ): void {
		$demo_import = new Demo_Import(
			$this->post_selections_cpt_save_actions,
			$this->layouts_cpt_save_actions,
			$this->post_selections_settings_storage,
			$this->layouts_settings_storage,
			$this->settings,
			$this->item_settings
		);

		$dashboard             = new Dashboard( $this->plugin, $this->html, $demo_import );
		$acf_internal_features = new Acf_Internal_Features( $this->plugin );

		$tools_settings     = new Tools_Settings( $this->group_creator );
		$debug_dump_creator = new Debug_Dump_Creator(
			$tools_settings,
			$this->logger,
			$this->layouts_settings_storage,
			$this->post_selections_settings_storage
		);
		$tools              = new Tools(
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
		$settings_page           = new Settings_Page(
			$this->logger,
			new Plugin_Settings( $this->group_creator ),
			$this->settings,
			$this->layouts_settings_storage,
			$this->post_selections_settings_storage,
			$this->group_creator->create( Git_Repository::class ),
			$this->automatic_reports
		);

		$admin_assets = new Admin_Assets(
			$this->plugin,
			$this->post_selections_settings_storage,
			$this->layouts_settings_storage,
			$this->layout_factory,
			$this->post_selection_factory,
			$this->data_vendors
		);

		$live_reloader = new Live_Reloader(
			$this->layouts_settings_storage,
			$this->post_selections_settings_storage,
			$this->layout_shortcode,
			$this->post_selection_shortcode
		);

		$admin_bar = new Admin_Bar(
			$this->layout_shortcode,
			$this->post_selection_shortcode,
			$this->live_reloader_component,
			$this->settings
		);

		$dashboard->set_hooks( $current_screen );
		$demo_import->set_hooks( $current_screen );
		$acf_internal_features->set_hooks( $current_screen );
		// only after late dependencies were set.

		$this->automatic_reports->set_hooks( $current_screen );
		$tools->set_hooks( $current_screen );
		$admin_assets->set_hooks( $current_screen );
		$settings_page->set_hooks( $current_screen );
		$live_reloader->set_hooks( $current_screen );
		$admin_bar->set_hooks( $current_screen );
	}

	private function bridge(): void {
		Advanced_Views::$layout_renderer         = $this->layout_shortcode;
		Advanced_Views::$post_selection_renderer = $this->post_selection_shortcode;
	}

	private function migrations(): void {
		$this->migrator->set_migrations(
			array(
				// v1.
				new Migration_1_6_0(),
				new Migration_1_7_0( $this->layouts_settings_storage, $this->layouts_cpt_save_actions ),
				// v2.
				new Migration_2_0_0( $this->layouts_cpt_save_actions, $this->post_selections_cpt_save_actions ),
				new Migration_2_1_0( $this->layouts_cpt_save_actions, $this->layouts_settings_storage ),
				new Migration_2_2_0( $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_2_2_2( $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_2_2_3( $this->layouts_cpt_save_actions, $this->post_selections_cpt_save_actions ),
				new Migration_2_3_0( $this->template_engines ),
				new Migration_2_4_0( $this->layouts_cpt_save_actions, $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_2_4_2( $this->layouts_settings_storage ),
				new Migration_2_4_5( $this->layouts_settings_storage ),
				// v3.
				new Migration_3_0_0( $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_3_3_0( $this->layouts_settings_storage, $this->post_selections_settings_storage, $this->logger, $this->plugin ),
			)
		);
	}

	public function activation(): void {
		$this->template_engines->create_templates_dir();
		$this->automatic_reports->plugin_activated();
	}

	public function deactivation(): void {
		$this->automatic_reports->plugin_deactivated();
		$this->template_engines->remove_templates_dir();

		// do not check for a security token, as the deactivation plugin link contains it,
		// and WP already has checked it.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_delete_data = true === key_exists( 'advanced-views-delete-data', $_GET ) &&
		                  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							'yes' === $_GET['advanced-views-delete-data'];

		if ( true === $is_delete_data ) {
			$this->layouts_settings_storage->delete_all_items();
			$this->post_selections_settings_storage->delete_all_items();

			if ( true === $this->layouts_settings_storage->get_file_system()->is_active() ) {
				$this->layouts_settings_storage->get_file_system()
										->get_wp_filesystem()
										->rmdir(
											$this->layouts_settings_storage->get_file_system()->get_base_folder(),
											true
										);
			}

			$this->settings->delete_data();
		}
	}

	public function load(): void {
		$current_screen = new Current_Screen();

		$this->load_translations( $current_screen );
		$this->acf_groups( $current_screen );
		$this->primary( $current_screen );
		$this->views( $current_screen );
		$this->cards( $current_screen );
		$this->integration( $current_screen );
		$this->others( $current_screen );
		$this->bridge();
		$this->migration();
	}

	public function init(): void {
		// skip initialization if PRO already active.
		if ( class_exists( Plugin::class ) ) {
			return;
		}

		$start_timestamp = microtime( true );

		require_once __DIR__ . '/prefixed_vendors/vendor/scoper-autoload.php';

		// @phpstan-ignore-next-line
		if ( version_compare( PHP_VERSION, '8.2.0', '>=' ) ) {
			require_once __DIR__ . '/prefixed_vendors_php8/vendor/scoper-autoload.php';
		}

		require_once __DIR__ . '/src/Compatibility/Back_Compatibility/back_compatibility.php';

		$this->load();

		register_activation_hook(
			__FILE__,
			array( $this, 'activation' )
		);

		register_deactivation_hook(
			__FILE__,
			array( $this, 'deactivation' )
		);

		Profiler::plugin_loaded( $start_timestamp );
	}
};

$acf_views->init();
