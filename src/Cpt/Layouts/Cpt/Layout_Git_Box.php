<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Git_Meta_Box;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;

class Layout_Git_Box extends Git_Meta_Box {
	private Data_Vendors $data_vendors;
	private Layout_Settings_Storage $layouts_settings_storage;

	public function __construct(
		string $cpt_name,
		Settings_Storage $settings,
		Layout_Settings_Storage $layouts_settings_storage,
		Git_Lab_Api $git_lab_api,
		Data_Vendors $data_vendors,
		Plugin $plugin
	) {
		parent::__construct( $cpt_name, $settings, $layouts_settings_storage, $git_lab_api, $plugin );

		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->data_vendors             = $data_vendors;
	}

	/**
	 * @return array<string|int, string>
	 */
	protected function get_export_fs_field_values( Cpt_Settings $cpt_settings, bool $is_with_meta_groups ): array {
		$export_fs_field_values = parent::get_export_fs_field_values( $cpt_settings, $is_with_meta_groups );

		if ( false === ( $cpt_settings instanceof Layout_Settings ) ) {
			return $export_fs_field_values;
		}

		if ( $is_with_meta_groups ) {
			$export_fs_field_values = array_merge(
				$export_fs_field_values,
				$this->data_vendors->get_related_group_export_files( $cpt_settings )
			);
		}

		return $export_fs_field_values;
	}

	protected function push_related_cpt_data_items(
		Cpt_Settings $cpt_settings,
		string $repository_id,
		string $access_token,
		bool $is_with_meta_groups
	): bool {
		if ( false === ( $cpt_settings instanceof Layout_Settings ) ) {
			return false;
		}

		$related_view_unique_ids = $this->data_vendors->get_related_view_unique_ids( $cpt_settings );

		foreach ( $related_view_unique_ids as $related_view_unique_id ) {
			$related_cpt_data = $this->layouts_settings_storage->get( $related_view_unique_id );

			if ( false === $this->push_cpt_data_with_all_related_items(
				$related_cpt_data,
				$repository_id,
				$access_token,
				$is_with_meta_groups
			) ) {
				return false;
			}
		}

		return true;
	}
}
