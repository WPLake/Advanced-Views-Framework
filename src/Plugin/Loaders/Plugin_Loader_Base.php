<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Loaders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Acf_Dependency;
use Org\Wplake\Advanced_Views\Acf\Acf_Internal_Features;
use Org\Wplake\Advanced_Views\Assets\Admin_Assets;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Automated_Reports;
use Org\Wplake\Advanced_Views\Bridge\Advanced_Views;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Upgrade_Notice;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_1\Migration_1_6_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_1\Migration_1_7_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_0_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_1_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_2_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_2_2;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_2_3;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_3_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_4_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_4_2;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2\Migration_2_4_5;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3\Migration_3_0_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3\Migration_3_3_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3\Migration_3_8_0;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3\Migration_3_8_9;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version_Migrator;
use Org\Wplake\Advanced_Views\Dashboard\Admin_Bar;
use Org\Wplake\Advanced_Views\Dashboard\Dashboard;
use Org\Wplake\Advanced_Views\Dashboard\Live_Reloader;
use Org\Wplake\Advanced_Views\Dashboard\Settings_Page;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Settings;
use Org\Wplake\Advanced_Views\Groups_Integration\Custom_Acf_Field_Types;
use Org\Wplake\Advanced_Views\Groups_Integration\Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Item_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Layout_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Meta_Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Mount_Point_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Post_Selection_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Tax_Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Tools_Settings_Integration;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Git_Box;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Git_Tabs;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Meta_Boxes;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Cpt_Table;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Mount_Points;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Labels\Cpt_Labels_Base;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt_Base;
use Org\Wplake\Advanced_Views\Plugin\Plugin_Environment;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Shortcode\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode_Block;
use Org\Wplake\Advanced_Views\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Template\Templates_Environment;
use Org\Wplake\Advanced_Views\Tools\Demo_Import;
use Org\Wplake\Advanced_Views\Tools\Tools;
use Org\Wplake\Advanced_Views\Utils\Profiler;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Creator;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Loader;

abstract class Plugin_Loader_Base extends Plugin\Module_Loader {
	public Plugin $plugin;
	public Plugin_Environment $plugin_environment;
	public Version_Migrator $version_migrator;
	public Logger $logger;
	public Layout_Settings_Storage $layouts_settings_storage;

	public Layout_Save_Actions $layouts_cpt_save_actions;

	public Templates_Environment $templates_environment;
	public Public_Cpt $layout_cpt;
	public Public_Cpt $post_selection_cpt;
	public Data_Vendors $data_vendors;
	public Front_Assets $front_assets;
	public Live_Reloader_Component $live_reloader_component;
	/**
	 * @var File_System[]
	 */
	public array $file_systems = array();
	public Layout_Meta_Boxes $layouts_cpt_meta_boxes;
	public Layouts_Cpt $layouts_cpt;
	public Layouts_Cpt_Table $layouts_cpt_table;
	public Layout_Git_Tabs $layouts_git_cpt_table_tabs;
	public Layout_Git_Box $layouts_git_meta_box;
	public Fs_Only_Tab $layouts_fs_only_tab;
	public Layouts_Bulk_Validation_Tab $layouts_bulk_validation_tab;
	public Layouts_Pre_Built_Tab $layouts_pre_built_tab;
	public Cpt_Gutenberg_Editor_Settings $layout_cpt_gutenberg_editor_settings;

	public Cpt_Assets_Reducer $layouts_cpt_assets_reducer;

	public Layout_Shortcode $layout_shortcode;
	public Shortcode_Block $layouts_shortcode_block;


	public Acf_Dependency $acf_dependency;
	public Layout_Settings_Integration $layout_settings_integration;
	public Field_Settings_Integration $field_settings_integration;
	public Post_Selection_Settings_Integration $post_selection_settings_integration;
	public Item_Settings_Integration $item_settings_integration;
	public Meta_Field_Settings_Integration $meta_field_settings_integration;
	public Mount_Point_Settings_Integration $layout_mount_point_integration;
	public Mount_Point_Settings_Integration $post_selection_mount_point_integration;
	public Tax_Field_Settings_Integration $tax_field_settings_integration;
	public Tools_Settings_Integration $tools_settings_integration;
	public Custom_Acf_Field_Types $custom_acf_field_types;
	public Item_Settings $item_settings;
	public Layout_Factory $layout_factory;
	public Settings $settings;
	public Creator $group_creator;
	public Dashboard $dashboard;
	public Demo_Import $demo_import;
	public Acf_Internal_Features $acf_internal_features;
	public Automated_Reports $automatic_reports;
	public Tools $tools;
	public Admin_Assets $admin_assets;
	public Settings_Page $settings_page;
	public Live_Reloader $live_reloader;
	public Admin_Bar $admin_bar;
	public Upgrade_Notice $upgrade_notice;
	public Mount_Points $mount_points;
	public Git_Lab_Api $git_lab_api;

	public Engines_Storage $engines_storage;
	public Selection_Settings_Storage $post_selections_settings_storage;

	/**
	 * @var Plugin_Cpt[]
	 */
	protected array $plugin_cpts = array();
	protected Post_Selections_Loader_Base $selections_loader;

	public function load(): void {
		$start_timestamp = microtime( true );

		$route_detector = new Route_Detector();

		$this->load_modules( $route_detector );

		$this->load_hookable();

		Profiler::plugin_loaded( $start_timestamp );
	}

	protected function load_modules( Route_Detector $route_detector ): void {
		$this->translations( $route_detector );
		$this->primary();
		$this->acf_groups( $route_detector );
		$this->layouts();
		$this->post_selections();
		$this->integration( $route_detector );
		$this->others();
		$this->bridge();
		$this->version_migrations();
		$this->environment();
	}

	/**
	 * @param string[] $paths
	 */
	protected function translations( Route_Detector $route_detector, array $paths = array() ): void {
		// on the whole admin area, as menu items need translations.
		if ( false === $route_detector->is_admin_route() ) {
			return;
		}

		$paths[] = 'src/lang';

		add_action(
			'init',
			function () use ( $paths ): void {
				foreach ( $paths as  $path ) {
					load_plugin_textdomain(
						'acf-views',
						false,
						$this->plugin->get_plugin_path( $path )
					);
				}
			},
			// make sure it's before acf_groups.
			8
		);
	}

	protected function primary(): void {
		// it's a hack, but there is no other way to pass data (constructor is always called automatically).
		Field_Settings::set_data_vendors( $this->data_vendors );

		$this->add_hookable(
			array(
				$this->logger,
				$this->plugin,
				$this->templates_environment,
				$this->front_assets,
				$this->data_vendors,
				$this->live_reloader_component,
				$this->version_migrator,
				$this->upgrade_notice,
			)
		);

		$this->add_hookable( $this->file_systems );
	}

	protected function acf_groups( Route_Detector $route_detector ): void {
		if ( ! wp_doing_ajax() &&
			false === $route_detector->is_cpt_admin_route( $this->layout_cpt->cpt_name() ) &&
			false === $route_detector->is_cpt_admin_route( $this->post_selection_cpt->cpt_name() ) ) {
			return;
		}

		add_action(
			'acf/init',
			function (): void {
				$loader = new Loader();

				$loader->signUpGroups(
					'Org\Wplake\Advanced_Views\Groups',
					$this->plugin->get_plugin_path( 'src/Groups' )
				);
			},
			// make sure it's after translations.
			9
		);
	}

	protected function layouts(): void {
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
	}

	protected function post_selections(): void {
		$this->selections_loader->load();
	}

	protected function integration( Route_Detector $route_detector ): void {
		$this->add_hookable(
			array(
				$this->acf_dependency,
				$this->layout_settings_integration,
				$this->field_settings_integration,
				$this->post_selection_settings_integration,
				$this->item_settings_integration,
				$this->meta_field_settings_integration,
				$this->layout_mount_point_integration,
				$this->post_selection_mount_point_integration,
				$this->tax_field_settings_integration,
				$this->tools_settings_integration,
				$this->custom_acf_field_types,
			)
		);

		// only now, when layouts() are called.
		$this->data_vendors->make_integration_instances(
			$route_detector,
			$this->item_settings,
			$this->layouts_settings_storage,
			$this->layouts_cpt_save_actions,
			$this->layout_factory,
			$this->group_creator->create( Repeater_Field_Settings::class ),
			$this->layout_shortcode,
			$this->settings,
			$this->layout_cpt,
		);
	}

	protected function others(): void {
		$this->add_hookable(
			array(
				$this->dashboard,
				$this->demo_import,
				$this->acf_internal_features,
				// only after late dependencies were set.
				$this->automatic_reports,
				$this->tools,
				$this->admin_assets,
				$this->settings_page,
				$this->live_reloader,
				$this->admin_bar,
				$this->mount_points,
			)
		);
	}

	protected function bridge(): void {
		Advanced_Views::$layout_renderer         = $this->layout_shortcode;
		Advanced_Views::$post_selection_renderer = $this->selections_loader->post_selection_shortcode;
	}

	protected function version_migrations(): void {
		$this->version_migrator->add_version_migrations(
			array(
				// v1.
				new Migration_1_6_0( $this->logger ),
				new Migration_1_7_0( $this->logger, $this->layouts_settings_storage, $this->layouts_cpt_save_actions ),
				// v2.
				new Migration_2_0_0( $this->logger, $this->layouts_cpt_save_actions, $this->selections_loader->post_selections_cpt_save_actions ),
				new Migration_2_1_0( $this->logger, $this->layouts_cpt_save_actions, $this->layouts_settings_storage ),
				new Migration_2_2_0( $this->logger, $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_2_2_2( $this->logger, $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_2_2_3( $this->logger, $this->layouts_cpt_save_actions, $this->selections_loader->post_selections_cpt_save_actions ),
				new Migration_2_3_0( $this->logger, $this->templates_environment ),
				new Migration_2_4_0(
					$this->logger,
					$this->layouts_cpt_save_actions,
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage
				),
				new Migration_2_4_2( $this->logger, $this->layouts_settings_storage ),
				new Migration_2_4_5( $this->logger, $this->layouts_settings_storage ),
				// v3.
				new Migration_3_0_0( $this->logger, $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_3_3_0(
					$this->logger,
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage,
				),
				new Migration_3_8_0(
					$this->logger,
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage,
					$this->layout_cpt,
					$this->post_selection_cpt
				),
				new Migration_3_8_9(
					$this->logger,
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage,
				),
			)
		);
	}

	protected function environment(): void {
		register_activation_hook(
			$this->plugin->get_slug(),
			array( $this->plugin_environment, 'prepare_environment' )
		);

		register_deactivation_hook(
			$this->plugin->get_slug(),
			array( $this->plugin_environment, 'clean_environment' )
		);
	}

	/**
	 * @param File_System[] $file_systems
	 */
	protected function add_file_systems( array $file_systems ): void {
		$this->file_systems = array_merge( $this->file_systems, $file_systems );
	}

	protected static function make_layout_cpt(): Public_Cpt {
		$public_cpt_base = new Public_Cpt_Base();

		$public_cpt_base->cpt_name = Hard_Layout_Cpt::cpt_name();
		// replacement will require changes in ALL the "layout-pointer" fields values, like Post Selection -> Item layout.
		$public_cpt_base->slug_prefix = 'view_';
		$public_cpt_base->folder_name = 'layouts';

		$public_cpt_base->shortcode        = 'avf-layout';
		$public_cpt_base->shortcodes       = array( $public_cpt_base->shortcode, 'avf_view', 'acf_views' );
		$public_cpt_base->rest_route_names = array( 'layout', 'view' );

		$public_cpt_base->labels = new class() extends Cpt_Labels_Base{
			public function singular_name(): string {
				return esc_html__( 'Layout', 'acf-views' );
			}

			public function plural_name(): string {
				return esc_html__( 'Layouts', 'acf-views' );
			}
		};

		return $public_cpt_base;
	}

	protected static function uploads_folder(): string {
		return wp_upload_dir()['basedir'] . '/acf-views';
	}

	/**
	 * @return array<string, callable():boolean>
	 */
	protected function get_cache_cleaners(): array {
		/**
		 * @var array<string, callable():boolean> $cache_cleaners
		 */
		$cache_cleaners = array(
			// Redis - upgrades may have had direct DB changes.
			'wpdb' => 'wp_cache_flush',
		);

		// Opcache - upgrades may have had FS changes (e.g. theme template updates).
		if ( function_exists( 'opcache_reset' ) ) {
			$cache_cleaners['opcache'] = 'opcache_reset';
		}

		return $cache_cleaners;
	}
}
