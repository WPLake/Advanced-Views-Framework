<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layout_Git_Box;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt\Git_Meta_Box;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;

class Selection_Git_Box extends Git_Meta_Box {

	private Layout_Settings_Storage $layouts_settings_storage;
	private Layout_Git_Box $layouts_git_meta_box;

	public function __construct(
		string $cpt_name,
		Settings $settings,
		Cpt_Settings_Storage $cpt_settings_storage,
		Git_Lab_Api $git_lab_api,
		Layout_Settings_Storage $layouts_settings_storage,
		Layout_Git_Box $layouts_git_meta_box,
		Plugin $plugin
	) {
		parent::__construct( $cpt_name, $settings, $cpt_settings_storage, $git_lab_api, $plugin );

		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layouts_git_meta_box     = $layouts_git_meta_box;
	}

	protected function push_related_cpt_data_items(
		Cpt_Settings $cpt_settings,
		string $repository_id,
		string $access_token,
		bool $is_with_meta_groups
	): bool {
		if ( false === ( $cpt_settings instanceof Post_Selection_Settings ) ) {
			return false;
		}

		$card_data = $cpt_settings;
		$view_data = $this->layouts_settings_storage->get( $card_data->acf_view_id );

		return $this->layouts_git_meta_box->push_cpt_data_with_all_related_items(
			$view_data,
			$repository_id,
			$access_token,
			$is_with_meta_groups
		);
	}
}
