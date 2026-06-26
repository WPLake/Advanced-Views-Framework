<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Fs_Fields;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;

class Post_Selection_Fs_Fields extends Fs_Fields {
	/**
	 * @return string[]
	 */
	protected function get_template_fs_field_names_without_json(): array {
		return array_merge(
			parent::get_template_fs_field_names_without_json(),
			array(
				'query_preview',
				'extra_query_arguments',
			)
		);
	}

	public function set_fs_field( Cpt_Settings $cpt_settings, string $field_file, string $field_value ): void {
		parent::set_fs_field( $cpt_settings, $field_file, $field_value );

		if ( ! ( $cpt_settings instanceof Post_Selection_Settings ) ) {
			return;
		}

		switch ( $field_file ) {
			case 'query-preview.php':
				$cpt_settings->query_preview = $field_value;
				break;
			case 'controller.php':
				$cpt_settings->extra_query_arguments = $field_value;
				break;
		}
	}

	/**
	 * @return string[]
	 */
	public function get_fs_field_file_names( bool $is_without_auto_generated = false ): array {
		return array_merge(
			parent::get_fs_field_file_names( $is_without_auto_generated ),
			array(
				'query-preview.php',
				'controller.php',
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function get_fs_field_values(
		Cpt_Settings $cpt_settings,
		bool $is_bulk_refresh = false,
		bool $is_skip_auto_generated = false
	): array {
		$fs_field_values = parent::get_fs_field_values( $cpt_settings, $is_bulk_refresh, $is_skip_auto_generated );

		if ( $cpt_settings instanceof Post_Selection_Settings ) {
			$fs_field_values = array_merge(
				$fs_field_values,
				array(
					'query-preview.php' => $cpt_settings->query_preview,
					'controller.php'    => $cpt_settings->extra_query_arguments,
				)
			);
		}

		return $fs_field_values;
	}
}
