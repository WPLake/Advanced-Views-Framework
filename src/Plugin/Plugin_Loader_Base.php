<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Acf_Dependency;
use Org\Wplake\Advanced_Views\Acf\Acf_Internal_Features;
use Org\Wplake\Advanced_Views\Assets\Admin_Assets;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Automatic_Reports;
use Org\Wplake\Advanced_Views\Bridge\Advanced_Views;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case\Migration_Error_Logs;
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
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migrator;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Dashboard\Admin_Bar;
use Org\Wplake\Advanced_Views\Dashboard\Dashboard;
use Org\Wplake\Advanced_Views\Dashboard\Live_Reloader;
use Org\Wplake\Advanced_Views\Dashboard\Settings_Page;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
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
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Cpt_Table;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Assets_Reducer;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Gutenberg_Editor_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Labels\Cpt_Labels;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Labels\Cpt_Labels_Base;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt_Base;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_View_Integration;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table\Post_Selections_Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table\Post_Selections_Cpt_Table;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table\Post_Selections_Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Profiler;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Shortcode\Layout_Shortcode;
use Org\Wplake\Advanced_Views\Shortcode\Post_Selection_Shortcode;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode_Block;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Tools\Demo_Import;
use Org\Wplake\Advanced_Views\Tools\Tools;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Creator;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Loader;

abstract class Plugin_Loader_Base {
	protected Plugin $plugin;
	protected Plugin_Environment $plugin_environment;
	protected Version_Migrator $version_migrator;
	protected Logger $logger;
	protected Layouts_Settings_Storage $layouts_settings_storage;
	protected Post_Selections_Settings_Storage $post_selections_settings_storage;
	protected Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;
	protected Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions;
	protected Template_Engines $template_engines;
	protected Public_Cpt $layout_cpt;
	protected Public_Cpt $post_selection_cpt;
	protected Data_Vendors $data_vendors;
	protected Front_Assets $front_assets;
	protected Live_Reloader_Component $live_reloader_component;
	/**
	 * @var File_System[]
	 */
	protected array $file_systems = array();
	protected Layouts_Cpt_Meta_Boxes $layouts_cpt_meta_boxes;
	protected Layouts_Cpt $layouts_cpt;
	protected Layouts_Cpt_Table $layouts_cpt_table;
	protected Fs_Only_Tab $layouts_fs_only_tab;
	protected Layouts_Bulk_Validation_Tab $layouts_bulk_validation_tab;
	protected Layouts_Pre_Built_Tab $layouts_pre_built_tab;
	protected Cpt_Gutenberg_Editor_Settings $layout_cpt_gutenberg_editor_settings;
	protected Cpt_Gutenberg_Editor_Settings $post_selection_cpt_gutenberg_editor_settings;
	protected Cpt_Assets_Reducer $layouts_cpt_assets_reducer;
	protected Cpt_Assets_Reducer $post_selections_cpt_assets_reducer;
	protected Layout_Shortcode $layout_shortcode;
	protected Shortcode_Block $layouts_shortcode_block;
	protected Post_Selections_Cpt_Table $post_selections_cpt_table;
	protected Post_Selections_Cpt $post_selections_cpt;
	protected Fs_Only_Tab $post_selections_fs_only_tab;
	protected Post_Selections_Cpt_Meta_Boxes $post_selections_cpt_meta_boxes;
	protected Post_Selections_Bulk_Validation_Tab $post_selections_bulk_validation_tab;
	protected Post_Selections_Pre_Built_Tab $post_selections_pre_built_tab;
	protected Post_Selections_View_Integration $post_selections_view_integration;
	protected Post_Selection_Shortcode $post_selection_shortcode;

	protected Acf_Dependency $acf_dependency;
	protected Layout_Settings_Integration $layout_settings_integration;
	protected Field_Settings_Integration $field_settings_integration;
	protected Post_Selection_Settings_Integration $post_selection_settings_integration;
	protected Item_Settings_Integration $item_settings_integration;
	protected Meta_Field_Settings_Integration $meta_field_settings_integration;
	protected Mount_Point_Settings_Integration $layout_mount_point_integration;
	protected Mount_Point_Settings_Integration $post_selection_mount_point_integration;
	protected Tax_Field_Settings_Integration $tax_field_settings_integration;
	protected Tools_Settings_Integration $tools_settings_integration;
	protected Custom_Acf_Field_Types $custom_acf_field_types;
	protected Item_Settings $item_settings;
	protected Layout_Factory $layout_factory;
	protected Settings $settings;
	protected Creator $group_creator;
	protected Dashboard $dashboard;
	protected Demo_Import $demo_import;
	protected Acf_Internal_Features $acf_internal_features;
	protected Automatic_Reports $automatic_reports;
	protected Tools $tools;
	protected Admin_Assets $admin_assets;
	protected Settings_Page $settings_page;
	protected Live_Reloader $live_reloader;
	protected Admin_Bar $admin_bar;
	/**
	 * @var Hooks_Interface[]
	 */
	protected array $hookable = array();
	/**
	 * @var Plugin_Cpt[]
	 */
	protected array $plugin_cpts = array();

	public function load_plugin(): void {
		$start_timestamp = microtime( true );

		$current_screen = new Current_Screen();

		$this->load_modules( $current_screen );

		foreach ( $this->hookable as $hookable ) {
			$hookable->set_hooks( $current_screen );
		}

		Profiler::plugin_loaded( $start_timestamp );
	}

	protected function load_modules( Current_Screen $current_screen ): void {
		$this->translations( $current_screen );
		$this->primary();
		$this->acf_groups( $current_screen );
		$this->layouts();
		$this->post_selections();
		$this->integration( $current_screen );
		$this->others();
		$this->bridge();
		$this->version_migrations();
		$this->environment();
	}

	/**
	 * @param string[] $paths
	 */
	protected function translations( Current_Screen $current_screen, array $paths = array() ): void {
		// on the whole admin area, as menu items need translations.
		if ( false === $current_screen->is_admin() ) {
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
				$this->template_engines,
				$this->front_assets,
				$this->data_vendors,
				$this->live_reloader_component,
				$this->version_migrator,
			)
		);

		$this->add_hookable( $this->file_systems );
	}

	protected function acf_groups( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_ajax() &&
			false === $current_screen->is_admin_cpt_related( $this->layout_cpt->cpt_name() ) &&
			false === $current_screen->is_admin_cpt_related( $this->post_selection_cpt->cpt_name() ) ) {
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
			)
		);
	}

	protected function post_selections(): void {
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
			)
		);
	}

	protected function integration( Current_Screen $current_screen ): void {
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
			$current_screen,
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
			)
		);
	}

	protected function bridge(): void {
		Advanced_Views::$layout_renderer         = $this->layout_shortcode;
		Advanced_Views::$post_selection_renderer = $this->post_selection_shortcode;
	}

	protected function version_migrations(): void {
		$this->version_migrator->add_migrations(
			array(
				new Migration_Error_Logs( $this->logger ),
			)
		);

		$this->version_migrator->add_version_migrations(
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
				new Migration_2_4_0(
					$this->layouts_cpt_save_actions,
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage
				),
				new Migration_2_4_2( $this->layouts_settings_storage ),
				new Migration_2_4_5( $this->layouts_settings_storage ),
				// v3.
				new Migration_3_0_0( $this->layouts_settings_storage, $this->post_selections_settings_storage ),
				new Migration_3_3_0(
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage,
					$this->logger
				),
				new Migration_3_8_0(
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage,
					$this->layout_cpt,
					$this->post_selection_cpt
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
	 * @param Hooks_Interface[] $hookable
	 */
	protected function add_hookable( array $hookable ): void {
		$this->hookable = array_merge( $this->hookable, $hookable );
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

	protected static function make_post_selection_cpt(): Public_Cpt {
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

	protected static function uploads_folder(): string {
		return wp_upload_dir()['basedir'] . '/acf-views';
	}
}
