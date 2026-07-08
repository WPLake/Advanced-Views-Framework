<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Loaders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Acf_Dependency;
use Org\Wplake\Advanced_Views\Acf\Acf_Internal_Features;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Custom_Acf_Field_Types;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Item_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Layout_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Meta_Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Mount_Point_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Post_Selection_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Tax_Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Group_Integrations\Tools_Settings_Integration;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Repeater_Field_Settings;
use Org\Wplake\Advanced_Views\Assets\Admin_Assets;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
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
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System_Loader;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Mount_Points;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Cpt\Template\Templates_Environment;
use Org\Wplake\Advanced_Views\Dashboard\Admin_Bar;
use Org\Wplake\Advanced_Views\Dashboard\Admin_Pages;
use Org\Wplake\Advanced_Views\Dashboard\Live_Reloader\Live_Reloader;
use Org\Wplake\Advanced_Views\Dashboard\Live_Reloader\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Dashboard\Tools\Demo_Importer;
use Org\Wplake\Advanced_Views\Dashboard\Tools_Page;
use Org\Wplake\Advanced_Views\Plugin\Automated_Reports\State_Report;
use Org\Wplake\Advanced_Views\Plugin\Automated_Reports\Usage_Report;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Labels\Cpt_Labels_Base;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt_Base;
use Org\Wplake\Advanced_Views\Plugin\Module_Loader;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Plugin_Environment;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Page;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Profiler;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Creator;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Loader;

abstract class Plugin_Loader_Base extends Module_Loader {
	public Plugin $plugin;
	public Plugin_Environment $plugin_environment;
	public Version_Migrator $version_migrator;
	public Logger $logger;
	public Layout_Settings_Storage $layouts_settings_storage;

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
	public Settings_Storage $settings;
	public Creator $group_creator;
	public Admin_Pages $dashboard;
	public Demo_Importer $demo_import;
	public Acf_Internal_Features $acf_internal_features;
	public Usage_Report $usage_report;
	public State_Report $state_report;
	public Tools_Page $tools;
	public Admin_Assets $admin_assets;
	public Settings_Page $settings_page;
	public Live_Reloader $live_reloader;
	public Admin_Bar $admin_bar;
	public Upgrade_Notice $upgrade_notice;
	public Mount_Points $mount_points;
	public Git_Lab_Api $git_lab_api;

	public Engines_Storage $engines_storage;
	public Selection_Settings_Storage $post_selections_settings_storage;
	public Post_Selections_Loader_Base $selections_loader;
	public Layouts_Loader_Base $layouts_loader;

	/**
	 * @var Plugin_Cpt[]
	 */
	protected array $plugin_cpts = array();
	/**
	 * @var array<string, string> domain => relative_path
	 */
	protected array $lang_relative_paths = array();

	public function __construct() {
		parent::__construct();

		$this->lang_relative_paths['acf-views'] = 'lang';
	}

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

	protected function translations( Route_Detector $route_detector ): void {
		// on the whole admin area, as menu items need translations.
		if ( ! $route_detector->is_admin_route() ) {
			return;
		}

		add_action(
			'after_setup_theme',
			function (): void {
				foreach ( $this->lang_relative_paths as $domain => $relative_path ) {
					$path = $this->plugin->get_relative_plugins_path( $relative_path );

					load_plugin_textdomain(
						$domain,
						false,
						$path
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
				File_System_Loader::instance(),
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
					'Org\Wplake\Advanced_Views\Acf\Groups',
					$this->plugin->get_plugin_path( 'src/Acf/Groups' )
				);
			},
			// make sure it's after translations.
			9
		);
	}

	protected function layouts(): void {
		$this->layouts_loader->load();
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
			$this->layouts_loader->save_actions,
			$this->layouts_loader->factory,
			$this->group_creator->create( Repeater_Field_Settings::class ),
			$this->layouts_loader->shortcode,
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
				$this->usage_report,
				$this->state_report,
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
		Advanced_Views::$layout_renderer         = $this->layouts_loader->shortcode;
		Advanced_Views::$post_selection_renderer = $this->selections_loader->shortcode;
	}

	protected function version_migrations(): void {
		$this->version_migrator->add_version_migrations(
			array(
				// v1.
				new Migration_1_6_0( $this->logger ),
				new Migration_1_7_0( $this->logger, $this->layouts_settings_storage, $this->layouts_loader->save_actions ),
				// v2.
				new Migration_2_0_0(
					$this->logger,
					$this->layouts_loader->save_actions,
					$this->selections_loader->save_actions
				),
				new Migration_2_1_0(
					$this->logger,
					$this->layouts_loader->save_actions,
					$this->layouts_settings_storage
				),
				new Migration_2_2_0(
					$this->logger,
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage
				),
				new Migration_2_2_2(
					$this->logger,
					$this->layouts_settings_storage,
					$this->post_selections_settings_storage
				),
				new Migration_2_2_3(
					$this->logger,
					$this->layouts_loader->save_actions,
					$this->selections_loader->save_actions
				),
				new Migration_2_3_0( $this->logger, $this->templates_environment ),
				new Migration_2_4_0(
					$this->logger,
					$this->layouts_loader->save_actions,
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

		$this->add_hookable( array( $this->plugin_environment ) );
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
