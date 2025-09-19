<?php
/**
 * Plugin Name: Advanced Views Lite
 * Plugin URI: https://wplake.org/advanced-views-lite/
 * Description: Effortlessly display WordPress posts, custom fields, and WooCommerce data.
 * Version: 3.7.20
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
use Org\Wplake\Advanced_Views\Cards\{Card_Factory,
	Card_Markup,
	Cpt\Cards_Cpt,
	Cpt\Cards_Cpt_Meta_Boxes,
	Cpt\Cards_Cpt_Save_Actions,
	Cpt\Cards_View_Integration,
	Cpt\Table\Cards_Bulk_Validation_Tab,
	Cpt\Table\Cards_Cpt_Table,
	Cpt\Table\Cards_Pre_Built_Tab,
	Data_Storage\Card_Fs_Fields,
	Data_Storage\Cards_Data_Storage,
	Query_Builder};
use Org\Wplake\Advanced_Views\Dashboard\Admin_Bar;
use Org\Wplake\Advanced_Views\Dashboard\Dashboard;
use Org\Wplake\Advanced_Views\Tools\Debug_Dump_Creator;
use Org\Wplake\Advanced_Views\Tools\Demo_Import;
use Org\Wplake\Advanced_Views\Dashboard\Live_Reloader;
use Org\Wplake\Advanced_Views\Dashboard\Settings_Page;
use Org\Wplake\Advanced_Views\Tools\Tools;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\{Card_Data,
	Field_Data,
	Git_Repository,
	Integration\Card_Data_Integration,
	Integration\Custom_Acf_Field_Types,
	Integration\Field_Data_Integration,
	Integration\Item_Data_Integration,
	Integration\Meta_Field_Data_Integration,
	Integration\Mount_Point_Data_Integration,
	Integration\Tax_Field_Data_Integration,
	Integration\Tools_Data_Integration,
	Integration\View_Data_Integration,
	Item_Data,
	Repeater_Field_Data,
	Settings_Data,
	Tools_Data,
	View_Data};
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Fs_Fields;
use Org\Wplake\Advanced_Views\Shortcode\Card_Shortcode;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode_Block;
use Org\Wplake\Advanced_Views\Shortcode\View_Shortcode;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Creator;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Loader as GroupsLoader;
use Org\Wplake\Advanced_Views\Views\{Cpt\Table\Views_Bulk_Validation_Tab,
	Cpt\Table\Views_Cpt_Table,
	Cpt\Table\Views_Pre_Built_Tab,
	Cpt\Views_Cpt,
	Cpt\Views_Cpt_Meta_Boxes,
	Cpt\Views_Cpt_Save_Actions,
	Data_Storage\Views_Data_Storage,
	Fields\Field_Markup,
	View,
	View_Factory,
	View_Markup};

defined( 'ABSPATH' ) || exit;

$acf_views = new class() {
	private Html $html;
	private Cards_Data_Storage $cards_data_storage;
	private Views_Data_Storage $views_data_storage;
	private Template_Engines $template_engines;
	private Plugin $plugin;
	private Item_Data $item;
	private Options $options;
	private Views_Cpt_Save_Actions $views_cpt_save_actions;
	private Cards_Cpt_Save_Actions $cards_cpt_save_actions;
	private View_Factory $view_factory;
	private Card_Factory $card_factory;
	private View_Data $view_data;
	private Card_Data $card_data;
	private Creator $group_creator;
	private Settings $settings;
	private Front_Assets $front_assets;
	private Data_Vendors $data_vendors;
	private View_Shortcode $view_shortcode;
	private Card_Shortcode $card_shortcode;
	private Upgrades $upgrades;
	private Automatic_Reports $automatic_reports;
	private Logger $logger;
	private Views_Pre_Built_Tab $views_pre_built_tab;
	private Live_Reloader_Component $live_reloader_component;

	private function load_translations( Current_Screen $current_screen ): void {
		// on the whole admin area, as menu items need translations.
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action(
			'init',
			function () {
				load_plugin_textdomain( 'acf-views', false, dirname( plugin_basename( __FILE__ ) ) . '/src/lang' );
			},
			// make sure it's before acf_groups.
			8
		);
	}

	private function acf_groups( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_ajax() &&
			false === $current_screen->is_admin_cpt_related( Views_Cpt::NAME ) &&
			false === $current_screen->is_admin_cpt_related( Cards_Cpt::NAME ) ) {
			return;
		}

		add_action(
			'acf/init',
			function () {
				$acf_groups_loader = new GroupsLoader();
				$acf_groups_loader->signUpGroups(
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

		$this->group_creator = new Creator();
		$this->view_data     = $this->group_creator->create( View_Data::class );
		$this->card_data     = $this->group_creator->create( Card_Data::class );

		$this->html = new Html();

		$cards_file_system        = new File_System( $this->logger, 'cards' );
		$this->cards_data_storage = new Cards_Data_Storage(
			$this->logger,
			$cards_file_system,
			new Card_Fs_Fields(),
			new Db_Management( $this->logger, $cards_file_system, Cards_Cpt::NAME, 'card_' ),
			$this->card_data
		);

		$views_file_system        = new File_System( $this->logger, 'views' );
		$this->views_data_storage = new Views_Data_Storage(
			$this->logger,
			$views_file_system,
			new Fs_Fields(),
			new Db_Management( $this->logger, $views_file_system, Views_Cpt::NAME, 'view_' ),
			$this->view_data
		);

		$this->plugin           = new Plugin( __FILE__, $this->options, $this->settings );
		$this->template_engines = new Template_Engines( $uploads_folder, $this->logger, $this->plugin, $this->settings );
		$this->item             = $this->group_creator->create( Item_Data::class );

		$this->data_vendors            = new Data_Vendors( $this->logger );
		$this->live_reloader_component = new Live_Reloader_Component( $this->plugin, $this->settings );
		$this->front_assets            = new Front_Assets(
			$this->plugin,
			$this->data_vendors,
			$views_file_system,
			$this->live_reloader_component
		);
		$this->upgrades                = new Upgrades(
			$this->logger,
			$this->plugin,
			$this->settings,
			$this->template_engines
		);

		// it's a hack, but there is no other way to pass data (constructor is always called automatically).
		Field_Data::set_data_vendors( $this->data_vendors );

		$this->logger->set_hooks( $current_screen );
		$this->plugin->set_hooks( $current_screen );
		$this->template_engines->set_hooks( $current_screen );
		$this->front_assets->set_hooks( $current_screen );
		$this->data_vendors->set_hooks( $current_screen );
		$cards_file_system->set_hooks( $current_screen );
		$views_file_system->set_hooks( $current_screen );
		$this->live_reloader_component->set_hooks( $current_screen );
	}

	private function views( Current_Screen $current_screen ): void {
		$field_markup                 = new Field_Markup( $this->data_vendors, $this->front_assets, $this->template_engines );
		$view_markup                  = new View_Markup( $field_markup, $this->data_vendors, $this->template_engines );
		$this->view_factory           = new View_Factory(
			$this->front_assets,
			$this->views_data_storage,
			$view_markup,
			$this->template_engines,
			$field_markup,
			$this->data_vendors
		);
		$view_cpt_meta_boxes          = new Views_Cpt_Meta_Boxes(
			$this->html,
			$this->plugin,
			$this->views_data_storage,
			$this->data_vendors
		);
		$this->views_cpt_save_actions = new Views_Cpt_Save_Actions(
			$this->logger,
			$this->views_data_storage,
			$this->plugin,
			$this->view_data,
			$this->front_assets,
			$view_markup,
			$view_cpt_meta_boxes,
			$this->html,
			$this->view_factory
		);

		$views_cpt                           = new Views_Cpt( $this->views_data_storage );
		$views_cpt_table                     = new Views_Cpt_Table(
			$this->views_data_storage,
			Views_Cpt::NAME,
			$this->html,
			$view_cpt_meta_boxes
		);
		$fs_only_cpt_table_tab               = new Fs_Only_Tab( $views_cpt_table, $this->views_data_storage );
		$views_bulk_validation_cpt_table_tab = new Views_Bulk_Validation_Tab(
			$views_cpt_table,
			$this->views_data_storage,
			$fs_only_cpt_table_tab,
			$this->view_factory
		);

		$views_pre_built_file_system   = new File_System(
			$this->logger,
			'views',
			__DIR__ . '/src/pre_built'
		);
		$views_pre_built_db_management = new Db_Management(
			$this->logger,
			$views_pre_built_file_system,
			Views_Cpt::NAME,
			'view_',
			true
		);
		$views_pre_built_data_storage  = new Views_Data_Storage(
			$this->logger,
			$views_pre_built_file_system,
			new Fs_Fields(),
			$views_pre_built_db_management,
			$this->view_data
		);
		$this->views_pre_built_tab     = new Views_Pre_Built_Tab(
			$views_cpt_table,
			$this->views_data_storage,
			$views_pre_built_data_storage,
			$this->data_vendors,
			$this->upgrades,
			$this->logger
		);

		$views_cpt_assets_reducer            = new Cpt_Assets_Reducer( $this->settings, Views_Cpt::NAME );
		$views_cpt_gutenberg_editor_settings = new Cpt_Gutenberg_Editor_Settings( Views_Cpt::NAME );
		$shortcode_block                     = new Shortcode_Block( array( View_Shortcode::NAME, View_Shortcode::OLD_NAME ) );

		$this->view_shortcode = new View_Shortcode(
			$this->settings,
			$this->views_data_storage,
			$this->front_assets,
			$this->live_reloader_component,
			$this->view_factory,
			$shortcode_block
		);

		$view_cpt_meta_boxes->set_hooks( $current_screen );
		$views_cpt->set_hooks( $current_screen );
		$views_cpt_table->set_hooks( $current_screen );
		$fs_only_cpt_table_tab->set_hooks( $current_screen );
		$views_bulk_validation_cpt_table_tab->set_hooks( $current_screen );
		$this->views_pre_built_tab->set_hooks( $current_screen );
		$views_cpt_gutenberg_editor_settings->set_hooks( $current_screen );
		$views_cpt_assets_reducer->set_hooks( $current_screen );
		$this->views_cpt_save_actions->set_hooks( $current_screen );
		$this->view_shortcode->set_hooks( $current_screen );
		$shortcode_block->set_hooks( $current_screen );
	}

	private function cards( Current_Screen $current_screen ): void {
		$query_builder                = new Query_Builder( $this->data_vendors, $this->logger );
		$card_markup                  = new Card_Markup( $this->front_assets, $this->template_engines );
		$this->card_factory           = new Card_Factory(
			$this->front_assets,
			$query_builder,
			$card_markup,
			$this->template_engines,
			$this->cards_data_storage
		);
		$cards_cpt_meta_boxes         = new Cards_Cpt_Meta_Boxes(
			$this->html,
			$this->plugin,
			$this->cards_data_storage,
			$this->views_data_storage
		);
		$this->cards_cpt_save_actions = new Cards_Cpt_Save_Actions(
			$this->logger,
			$this->cards_data_storage,
			$this->plugin,
			$this->card_data,
			$this->front_assets,
			$card_markup,
			$query_builder,
			$this->html,
			$cards_cpt_meta_boxes,
			$this->card_factory
		);

		$cards_cpt                           = new Cards_Cpt(
			$this->cards_data_storage
		);
		$cards_cpt_table                     = new Cards_Cpt_Table(
			$this->cards_data_storage,
			Cards_Cpt::NAME,
			$this->html,
			$cards_cpt_meta_boxes
		);
		$cards_fs_only_cpt_table_tab         = new Fs_Only_Tab( $cards_cpt_table, $this->cards_data_storage );
		$cards_bulk_validation_cpt_table_tab = new Cards_Bulk_Validation_Tab(
			$cards_cpt_table,
			$this->cards_data_storage,
			$cards_fs_only_cpt_table_tab,
			$this->card_factory
		);

		$cards_pre_built_file_system      = new File_System(
			$this->logger,
			'cards',
			__DIR__ . '/src/pre_built'
		);
		$cards_pre_built_db_management    = new Db_Management(
			$this->logger,
			$cards_pre_built_file_system,
			Cards_Cpt::NAME,
			'card_',
			true
		);
		$cards_pre_built_cpt_data_storage = new Cards_Data_Storage(
			$this->logger,
			$cards_pre_built_file_system,
			new Card_Fs_Fields(),
			$cards_pre_built_db_management,
			$this->card_data
		);
		$cards_pre_built_cpt_table_tab    = new Cards_Pre_Built_Tab(
			$cards_cpt_table,
			$this->cards_data_storage,
			$cards_pre_built_cpt_data_storage,
			$this->data_vendors,
			$this->upgrades,
			$this->logger,
			$this->views_pre_built_tab
		);

		$cards_cpt_assets_reducer            = new Cpt_Assets_Reducer( $this->settings, Cards_Cpt::NAME );
		$cards_cpt_gutenberg_editor_settings = new Cpt_Gutenberg_Editor_Settings( Cards_Cpt::NAME );

		$cards_views_integration = new Cards_View_Integration(
			$this->cards_data_storage,
			$this->views_data_storage,
			$this->cards_cpt_save_actions,
			$this->settings
		);
		$this->card_shortcode    = new Card_Shortcode(
			$this->settings,
			$this->cards_data_storage,
			$this->front_assets,
			$this->live_reloader_component,
			$this->card_factory
		);

		$cards_cpt->set_hooks( $current_screen );
		$cards_cpt_table->set_hooks( $current_screen );
		$cards_fs_only_cpt_table_tab->set_hooks( $current_screen );
		$cards_bulk_validation_cpt_table_tab->set_hooks( $current_screen );
		$cards_pre_built_cpt_table_tab->set_hooks( $current_screen );
		$cards_cpt_assets_reducer->set_hooks( $current_screen );
		$cards_cpt_gutenberg_editor_settings->set_hooks( $current_screen );
		$cards_cpt_meta_boxes->set_hooks( $current_screen );
		$this->cards_cpt_save_actions->set_hooks( $current_screen );
		$cards_views_integration->set_hooks( $current_screen );
		$this->card_shortcode->set_hooks( $current_screen );
	}

	private function integration( Current_Screen $current_screen ): void {
		$acf_dependency = new Acf_Dependency( $this->plugin );

		$view_data_integration = new View_Data_Integration(
			Views_Cpt::NAME,
			$this->data_vendors
		);
		$field_integration     = new Field_Data_Integration(
			Views_Cpt::NAME,
			$this->data_vendors
		);
		$card_data_integration = new Card_Data_Integration(
			Cards_Cpt::NAME,
			$this->data_vendors
		);
		$item_data_integration = new Item_Data_Integration( Views_Cpt::NAME, $this->data_vendors );
		// metaField is a part of the Meta Filter, so we use 'cardsCpt' here.
		$meta_field_data_integration   = new Meta_Field_Data_Integration( Cards_Cpt::NAME, $this->data_vendors );
		$views_mount_point_integration = new Mount_Point_Data_Integration( Views_Cpt::NAME );
		$cards_mount_point_integration = new Mount_Point_Data_Integration( Cards_Cpt::NAME );
		$tax_field_data_integration    = new Tax_Field_Data_Integration( Cards_Cpt::NAME, $this->data_vendors );
		$tools_data_integration        = new Tools_Data_Integration(
			$this->views_data_storage,
			$this->cards_data_storage
		);
		$custom_acf_field_types        = new Custom_Acf_Field_Types( $this->views_data_storage );

		$acf_dependency->set_hooks( $current_screen );

		$view_data_integration->set_hooks( $current_screen );
		$field_integration->set_hooks( $current_screen );
		$card_data_integration->set_hooks( $current_screen );
		$item_data_integration->set_hooks( $current_screen );
		$meta_field_data_integration->set_hooks( $current_screen );
		$views_mount_point_integration->set_hooks( $current_screen );
		$cards_mount_point_integration->set_hooks( $current_screen );
		$tax_field_data_integration->set_hooks( $current_screen );
		$tools_data_integration->set_hooks( $current_screen );
		$custom_acf_field_types->set_hooks( $current_screen );

		// only now, when views() are called.
		$this->data_vendors->make_integration_instances(
			$current_screen,
			$this->item,
			$this->views_data_storage,
			$this->views_cpt_save_actions,
			$this->view_factory,
			$this->group_creator->create( Repeater_Field_Data::class ),
			$this->view_shortcode,
			$this->settings
		);
	}

	private function others( Current_Screen $current_screen ): void {
		$demo_import = new Demo_Import(
			$this->cards_cpt_save_actions,
			$this->views_cpt_save_actions,
			$this->cards_data_storage,
			$this->views_data_storage,
			$this->settings,
			$this->item
		);

		$dashboard             = new Dashboard( $this->plugin, $this->html, $demo_import );
		$acf_internal_features = new Acf_Internal_Features( $this->plugin );
		// late dependencies.
		$this->upgrades->set_dependencies(
			$this->views_data_storage,
			$this->cards_data_storage,
			$this->views_cpt_save_actions,
			$this->cards_cpt_save_actions,
		);

		$tools_data         = new Tools_Data( $this->group_creator );
		$debug_dump_creator = new Debug_Dump_Creator(
			$tools_data,
			$this->logger,
			$this->views_data_storage,
			$this->cards_data_storage
		);
		$tools              = new Tools(
			$tools_data,
			$this->cards_data_storage,
			$this->views_data_storage,
			$this->plugin,
			$this->logger,
			$debug_dump_creator
		);

		$this->automatic_reports = new Automatic_Reports(
			$this->logger,
			$this->plugin,
			$this->settings,
			$this->options,
			$this->views_data_storage
		);
		$settings                = new Settings_Page(
			$this->logger,
			new Settings_Data( $this->group_creator ),
			$this->settings,
			$this->views_data_storage,
			$this->cards_data_storage,
			$this->group_creator->create( Git_Repository::class ),
			$this->automatic_reports
		);

		$admin_assets = new Admin_Assets(
			$this->plugin,
			$this->cards_data_storage,
			$this->views_data_storage,
			$this->view_factory,
			$this->card_factory,
			$this->data_vendors
		);

		$live_reloader = new Live_Reloader(
			$this->views_data_storage,
			$this->cards_data_storage,
			$this->view_shortcode,
			$this->card_shortcode
		);

		$admin_bar = new Admin_Bar(
			$this->view_shortcode,
			$this->card_shortcode,
			$this->live_reloader_component,
			$this->settings
		);

		$dashboard->set_hooks( $current_screen );
		$demo_import->set_hooks( $current_screen );
		$acf_internal_features->set_hooks( $current_screen );
		// only after late dependencies were set.
		$this->upgrades->set_hooks( $current_screen );
		$this->automatic_reports->set_hooks( $current_screen );
		$tools->set_hooks( $current_screen );
		$admin_assets->set_hooks( $current_screen );
		$settings->set_hooks( $current_screen );
		$live_reloader->set_hooks( $current_screen );
		$admin_bar->set_hooks( $current_screen );
	}

	private function bridge(): void {
		Advanced_Views::$inner_view_shortcode = $this->view_shortcode;
		Advanced_Views::$inner_card_shortcode = $this->card_shortcode;
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
			$this->views_data_storage->delete_all_items();
			$this->cards_data_storage->delete_all_items();

			if ( true === $this->views_data_storage->get_file_system()->is_active() ) {
				$this->views_data_storage->get_file_system()
										->get_wp_filesystem()
										->rmdir(
											$this->views_data_storage->get_file_system()->get_base_folder(),
											true
										);
			}

			$this->settings->delete_data();
		}
	}

	public function init(): void {
		// skip initialization if PRO already active.
		if ( class_exists( Plugin::class ) ) {
			return;
		}

		require_once __DIR__ . '/prefixed_vendors/vendor/scoper-autoload.php';

		if ( true === version_compare( PHP_VERSION, '8.2.0', '>=' ) ) {
			require_once __DIR__ . '/prefixed_vendors_php8/vendor/scoper-autoload.php';
		}

		require_once __DIR__ . '/src/Back_Compatibility/Back_Compatibility.php';

		$current_screen = new Current_Screen();

		$this->load_translations( $current_screen );
		$this->acf_groups( $current_screen );
		$this->primary( $current_screen );
		$this->views( $current_screen );
		$this->cards( $current_screen );
		$this->integration( $current_screen );
		$this->others( $current_screen );
		$this->bridge();

		register_activation_hook(
			__FILE__,
			array( $this, 'activation' )
		);

		register_deactivation_hook(
			__FILE__,
			array( $this, 'deactivation' )
		);
	}
};

$acf_views->init();
