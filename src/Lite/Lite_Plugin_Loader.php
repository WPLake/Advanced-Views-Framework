<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Lite;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Acf_Dependency;
use Org\Wplake\Advanced_Views\Acf\Acf_Internal_Features;
use Org\Wplake\Advanced_Views\Assets\Admin_Assets;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Upgrade_Notice;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version_Migrator;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Fs_Fields;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Post_Selection_Fs_Fields;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Dashboard\Admin_Bar;
use Org\Wplake\Advanced_Views\Dashboard\Dashboard;
use Org\Wplake\Advanced_Views\Dashboard\Live_Reloader;
use Org\Wplake\Advanced_Views\Dashboard\Settings_Page;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Groups\Git_Repository;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Plugin_Settings;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Tools_Settings;
use Org\Wplake\Advanced_Views\Groups_Integration\Custom_Acf_Field_Types;
use Org\Wplake\Advanced_Views\Groups_Integration\Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Item_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Layout_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Meta_Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Mount_Point_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Post_Selection_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Tax_Field_Settings_Integration;
use Org\Wplake\Advanced_Views\Groups_Integration\Tools_Settings_Integration;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Mount_Points;
use Org\Wplake\Advanced_Views\Plugin\Automated_Reports;
use Org\Wplake\Advanced_Views\Plugin\Loaders\Plugin_Loader_Base;
use Org\Wplake\Advanced_Views\Plugin\Options;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Plugin_Environment;
use Org\Wplake\Advanced_Views\Plugin\Settings;
use Org\Wplake\Advanced_Views\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Template\Templates_Environment;
use Org\Wplake\Advanced_Views\Tools\Debug_Dump_Creator;
use Org\Wplake\Advanced_Views\Tools\Demo_Import;
use Org\Wplake\Advanced_Views\Tools\Tools;
use Org\Wplake\Advanced_Views\Utils\Cache_Flusher;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Creator;

final class Lite_Plugin_Loader extends Plugin_Loader_Base {
	public Html $html;
	public Layout_Settings $layout_settings;
	public Post_Selection_Settings $post_selection_settings;
	public Options $options;
	public Cache_Flusher $cache_flusher;

	public string $plugin_file;

	public function __construct( string $plugin_file ) {
		parent::__construct();

		$this->plugin_file = $plugin_file;
	}

	protected function primary(): void {
		$this->layout_cpt         = self::make_layout_cpt();
		$this->post_selection_cpt = Lite_Post_Selections_Loader::make_post_selection_cpt();

		$this->plugin_cpts = array(
			$this->layout_cpt,
			$this->post_selection_cpt,
		);

		$this->options  = new Options();
		$this->settings = new Settings( $this->options );

		$uploads_folder = self::uploads_folder();
		$this->logger   = new Logger( $uploads_folder, $this->settings );

		$this->group_creator           = new Creator();
		$this->layout_settings         = $this->group_creator->create( Layout_Settings::class );
		$this->post_selection_settings = $this->group_creator->create( Post_Selection_Settings::class );

		$this->html            = new Html();
		$this->engines_storage = new Engines_Storage( $uploads_folder, $this->logger, $this->settings );

		$post_selections_file_system            = new File_System(
			$this->logger,
			$this->post_selection_cpt->folder_name()
		);
		$this->post_selections_settings_storage = new Selection_Settings_Storage(
			$this->logger,
			$post_selections_file_system,
			new Post_Selection_Fs_Fields( $this->engines_storage ),
			new Db_Management( $this->logger, $post_selections_file_system, $this->post_selection_cpt ),
			$this->post_selection_settings
		);

		$layouts_file_system            = new File_System( $this->logger, $this->layout_cpt->folder_name() );
		$this->layouts_settings_storage = new Layout_Settings_Storage(
			$this->logger,
			$layouts_file_system,
			new Layout_Fs_Fields( $this->engines_storage ),
			new Db_Management( $this->logger, $layouts_file_system, $this->layout_cpt ),
			$this->layout_settings
		);

		$this->plugin                = new Plugin( $this->plugin_file, $this->options, $this->settings );
		$this->templates_environment = new Templates_Environment(
			$uploads_folder,
			$this->logger,
			$this->plugin,
		);

		$this->item_settings = $this->group_creator->create( Item_Settings::class );

		$this->data_vendors            = new Data_Vendors( $this->logger );
		$this->live_reloader_component = new Live_Reloader_Component( $this->plugin, $this->settings );
		$this->front_assets            = new Front_Assets(
			$this->plugin,
			$this->data_vendors,
			$layouts_file_system,
			$this->live_reloader_component
		);
		$this->git_lab_api             = new Git_Lab_Api(
			$this->logger,
			$this->options,
			$this->layout_cpt,
			$this->post_selection_cpt
		);
		$this->upgrade_notice          = new Upgrade_Notice( $this->plugin );
		$this->cache_flusher           = new Cache_Flusher( $this->logger, $this->get_cache_cleaners() );
		$this->version_migrator        = new Version_Migrator(
			$this->plugin,
			$this->settings,
			$this->logger,
			$this->upgrade_notice,
			$this->cache_flusher
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
		$this->layouts_loader = new Lite_Layouts_Loader( $this );

		parent::layouts();
	}

	protected function post_selections(): void {
		$this->selections_loader = new Lite_Post_Selections_Loader( $this );

		parent::post_selections();
	}

	protected function integration( Route_Detector $route_detector ): void {
		$this->acf_dependency = new Acf_Dependency( $this->plugin );

		$this->layout_settings_integration         = new Layout_Settings_Integration(
			$this->layout_cpt->cpt_name(),
			$this->data_vendors
		);
		$this->field_settings_integration          = new Field_Settings_Integration(
			$this->data_vendors,
			$this->layout_cpt
		);
		$this->post_selection_settings_integration = new Post_Selection_Settings_Integration(
			$this->post_selection_cpt->cpt_name(),
			$this->data_vendors,
			$this->layout_cpt
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

		parent::integration( $route_detector );
	}

	protected function others(): void {
		$this->demo_import = new Demo_Import(
			$this->selections_loader->save_actions,
			$this->layouts_loader->save_actions,
			$this->post_selections_settings_storage,
			$this->layouts_settings_storage,
			$this->settings,
			$this->item_settings
		);

		$this->dashboard             = new Dashboard(
			$this->plugin,
			$this->html,
			$this->demo_import,
			$this->plugin_cpts
		);
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
			$debug_dump_creator,
			$this->layout_cpt,
			$this->post_selection_cpt,
			$this->settings,
			$this->cache_flusher
		);

		$this->automatic_reports = new Automated_Reports(
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
			array(
				$this->layouts_loader->interactive_fields,
				$this->selections_loader->interactive_fields,
			)
		);

		$this->live_reloader = new Live_Reloader(
			$this->layouts_settings_storage,
			$this->post_selections_settings_storage,
			$this->layouts_loader->shortcode,
			$this->selections_loader->shortcode
		);

		$this->admin_bar = new Admin_Bar(
			$this->layouts_loader->shortcode,
			$this->selections_loader->shortcode,
			$this->live_reloader_component,
			$this->settings
		);

		$this->mount_points = new Mount_Points(
			$this->layouts_settings_storage,
			$this->post_selections_settings_storage,
			$this->layout_cpt,
			$this->post_selection_cpt
		);

		parent::others();
	}

	protected function environment(): void {
		$this->plugin_environment = new Plugin_Environment(
			$this->templates_environment,
			$this->automatic_reports,
			$this->settings,
			$this->plugin,
			$this->file_systems,
			array( $this->layouts_settings_storage, $this->post_selections_settings_storage )
		);

		parent::environment();
	}
}
