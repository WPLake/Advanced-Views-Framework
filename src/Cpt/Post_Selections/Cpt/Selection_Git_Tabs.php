<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version_Migrator;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Git_Tabs;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Import_Result;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layout_Git_Tabs;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;


class Selection_Git_Tabs extends Git_Tabs {
	private Selection_Settings_Storage $post_selections_settings_storage;
	private Layout_Git_Tabs $layouts_git_cpt_table_tabs;

	public function __construct(
		Cpt_Table $cpt_table,
		Settings_Storage $settings,
		Git_Lab_Api $git_lab_api,
		Cpt_Settings $cpt_settings,
		Selection_Settings_Storage $post_selections_settings_storage,
		Version_Migrator $version_migrator,
		Layout_Git_Tabs $layouts_git_cpt_table_tabs,
		Data_Vendors $data_vendors,
		Logger $logger
	) {
		parent::__construct(
			$cpt_table,
			$settings,
			$git_lab_api,
			$cpt_settings,
			$post_selections_settings_storage,
			$version_migrator,
			$data_vendors,
			$logger
		);

		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layouts_git_cpt_table_tabs       = $layouts_git_cpt_table_tabs;
	}

	protected function get_cpt_data( string $unique_id ): Cpt_Settings {
		return 0 === strpos( $unique_id, Layout_Settings::UNIQUE_ID_PREFIX ) ?
			$this->layouts_git_cpt_table_tabs->get_cpt_data( $unique_id ) :
			$this->post_selections_settings_storage->get( $unique_id );
	}

	protected function import_related_cpt_data_items(
		string $repository_id,
		string $repository_access_token,
		string $unique_id
	): Import_Result {
		$card_data = $this->post_selections_settings_storage->get( $unique_id );

		return $this->layouts_git_cpt_table_tabs->import_items(
			$repository_id,
			$repository_access_token,
			array( $card_data->acf_view_id )
		);
	}
}
